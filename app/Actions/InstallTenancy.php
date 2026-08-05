<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Filesystem\Filesystem;

final readonly class InstallTenancy
{
    /**
     * Edits applied to files the starter kit already ships.
     *
     * @var array<string, list<array{string, string}>>
     */
    /**
     * The tenancy package version the stubs were written against.
     */
    public const string PACKAGE = 'stancl/tenancy';

    private const array PATCHES = [
        // Tenancy is opt-in, so the package is only required by installs that
        // asked for it. Composer still has to be run afterwards.
        'composer.json' => [
            [
                '"spatie/laravel-permission": "^8.3"',
                "\"spatie/laravel-permission\": \"^8.3\",\n        \"stancl/tenancy\": \"^3.10\"",
            ],
        ],

        'bootstrap/providers.php' => [
            [
                '    App\\Providers\\FortifyServiceProvider::class,',
                "    App\\Providers\\FortifyServiceProvider::class,\n    App\\Providers\\TenancyServiceProvider::class,",
            ],
        ],

        // The kit's own routes move behind tenant identification, so the
        // central domains are left serving `routes/central.php`. The tenant
        // route file requires web.php and admin.php from inside the tenancy
        // middleware group, which is why neither is rewritten here.
        'bootstrap/app.php' => [
            [
                "use Illuminate\\Http\\Middleware\\AddLinkHeadersForPreloadedAssets;\nuse Illuminate\\Support\\Facades\\Route;",
                'use Illuminate\\Http\\Middleware\\AddLinkHeadersForPreloadedAssets;',
            ],
            [
                "        web: __DIR__.'/../routes/web.php',\n"
                    ."        commands: __DIR__.'/../routes/console.php',\n"
                    ."        then: function (): void {\n"
                    ."            Route::middleware(['web', 'auth', 'verified', 'role:admin'])\n"
                    ."                ->prefix('admin')\n"
                    ."                ->name('admin.')\n"
                    ."                ->group(base_path('routes/admin.php'));\n"
                    ."        },\n",
                "        web: __DIR__.'/../routes/central.php',\n"
                    ."        commands: __DIR__.'/../routes/console.php',\n",
            ],
        ],
    ];

    public function __construct(
        private PublishStubs $publishStubs,
        private PatchFiles $patchFiles,
        private Filesystem $files,
    ) {
        //
    }

    /**
     * Turn the central starter kit into a multi-tenant one.
     *
     * Everything the kit already had becomes tenant-scoped: its migrations move
     * under `database/migrations/tenant`, and its web and admin routes move
     * into `routes/tenant.php` behind domain-or-subdomain identification. The
     * central database is left holding only tenants and domains.
     *
     * @param  list<string>  $centralDomains
     */
    public function handle(string $basePath, string $stubPath, array $centralDomains, string $databasePrefix): void
    {
        $this->publishStubs->handle($stubPath, $basePath);
        $this->patchFiles->handle($basePath, self::PATCHES);

        $this->moveMigrationsToTenant($basePath);
        $this->configure($basePath, $centralDomains, $databasePrefix);
    }

    /**
     * Every migration the kit ships describes tenant-owned data, so all of them
     * move. The tenants and domains migrations the stubs publish stay central.
     */
    private function moveMigrationsToTenant(string $basePath): void
    {
        $central = $basePath.'/database/migrations';
        $tenant = $central.'/tenant';

        $this->files->ensureDirectoryExists($tenant);

        foreach ($this->files->files($central) as $migration) {
            if (str_contains($migration->getFilename(), 'create_tenants_table')) {
                continue;
            }
            if (str_contains($migration->getFilename(), 'create_domains_table')) {
                continue;
            }
            if (str_contains($migration->getFilename(), 'impersonation_tokens_table')) {
                continue;
            }
            $this->files->move(
                $migration->getPathname(),
                $tenant.DIRECTORY_SEPARATOR.$migration->getFilename(),
            );
        }
    }

    /**
     * @param  list<string>  $centralDomains
     */
    private function configure(string $basePath, array $centralDomains, string $databasePrefix): void
    {
        $path = $basePath.'/config/tenancy.php';

        if (! $this->files->exists($path)) {
            return;
        }

        $domains = collect($centralDomains)
            ->map(fn (string $domain): string => "        '".mb_trim($domain)."',")
            ->implode("\n");

        $config = $this->files->get($path);

        $config = (string) preg_replace(
            "/'central_domains' => \[.*?\]/s",
            "'central_domains' => [\n".$domains."\n    ]",
            $config,
            1,
        );

        $config = (string) preg_replace(
            "/'prefix' => '[^']*'/",
            "'prefix' => '".$databasePrefix."'",
            $config,
            1,
        );

        $this->files->put($path, $config);
    }
}
