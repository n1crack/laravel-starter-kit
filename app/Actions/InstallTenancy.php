<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DatabaseDriver;
use Illuminate\Filesystem\Filesystem;

final readonly class InstallTenancy
{
    /**
     * The tenancy package the stubs were written against.
     */
    public const string PACKAGE = 'stancl/tenancy';

    /**
     * Edits applied to files the starter kit already ships.
     *
     * @var array<string, list<array{string, string}>>
     */
    private const array PATCHES = [
        // Tenancy is opt-in, so the package is only required by installs that
        // asked for it. Composer still has to be run afterwards.
        'composer.json' => [
            [
                '"spatie/laravel-permission": "^8.3"',
                "\"spatie/laravel-permission\": \"^8.3\",\n        \"stancl/tenancy\": \"^3.10\"",
            ],
        ],

        // Every table and route the kit ships now lives inside a tenant, so
        // the tests have to run inside one too. Creating the tenant provisions
        // its database and runs the tenant migrations; the domain and forced
        // root URL are what keep requests off the central domains, where
        // `PreventAccessFromCentralDomains` would refuse them.
        'tests/Pest.php' => [
            [
                'use App\\Enums\\Role as RoleEnum;',
                "use App\\Enums\\Role as RoleEnum;\nuse App\\Models\\Tenant;",
            ],
            [
                'use Illuminate\\Support\\Facades\\Process;',
                "use Illuminate\\Support\\Facades\\Process;\nuse Illuminate\\Support\\Facades\\URL;",
            ],
            [
                "        \$this->freezeTime();\n\n        foreach (RoleEnum::cases() as \$role) {\n"
                    ."            Role::findOrCreate(\$role->value);\n        }\n    })",
                "        \$this->freezeTime();\n\n"
                    ."        \$tenant = Tenant::query()->create();\n"
                    ."        \$tenant->domains()->create(['domain' => 'test']);\n"
                    ."        tenancy()->initialize(\$tenant);\n\n"
                    ."        URL::forceRootUrl('http://test.localhost');\n\n"
                    ."        foreach (RoleEnum::cases() as \$role) {\n"
                    ."            Role::findOrCreate(\$role->value);\n        }\n    })\n"
                    ."    ->afterEach(function (): void {\n"
                    ."        \$tenant = tenant();\n\n"
                    ."        tenancy()->end();\n\n"
                    ."        \$tenant?->delete();\n    })",
            ],
        ],

        // Everything this seeder creates — roles, users — now lives in a tenant
        // database, so running it centrally would fail on missing tables. It
        // stays the tenant seeder, which is what `Jobs\SeedDatabase` runs when
        // a tenant is created; centrally it says where to go instead.
        'database/seeders/DatabaseSeeder.php' => [
            [
                "    public function run(): void\n    {\n",
                "    public function run(): void\n    {\n"
                    ."        if (! tenancy()->initialized) {\n"
                    ."            \$this->command->warn('Nothing is seeded centrally. Run tenants:create or tenants:seed instead.');\n\n"
                    ."            return;\n        }\n\n",
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
                "        web: __DIR__.'/../routes/web.php',\n"
                    ."        commands: __DIR__.'/../routes/console.php',\n"
                    ."        then: function (): void {\n"
                    ."            Route::middleware(['web', 'auth', 'verified', 'role:admin'])\n"
                    ."                ->prefix('admin')\n"
                    ."                ->name('admin.')\n"
                    ."                ->group(base_path('routes/admin.php'));\n"
                    ."        },\n",
                "        commands: __DIR__.'/../routes/console.php',\n"
                    ."        then: function (): void {\n"
                    ."            // Central routes are stateless: the sessions table lives in the\n"
                    ."            // tenant databases, so the central domains must not start one.\n"
                    ."            Route::group([], base_path('routes/central.php'));\n"
                    ."        },\n",
            ],
        ],
    ];

    /**
     * Migrations that describe something the whole installation shares, rather
     * than something one tenant owns.
     *
     * Queue tables stay central so a single `queue:work` serves every tenant —
     * `QueueTenancyBootstrapper` puts each job back in its tenant's context
     * when it runs. Per-tenant queue tables would need a worker per tenant.
     *
     * @var list<string>
     */
    private const array CENTRAL_MIGRATIONS = [
        'create_tenants_table',
        'create_domains_table',
        'impersonation_tokens_table',
        'create_jobs_table',
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
    public function handle(
        string $basePath,
        string $stubPath,
        array $centralDomains,
        string $databasePrefix,
        DatabaseDriver $driver,
    ): void {
        $this->publishStubs->handle($stubPath, $basePath);
        $this->patchFiles->handle($basePath, self::PATCHES);

        $this->moveMigrationsToTenant($basePath);
        $this->configure($basePath, $centralDomains, $databasePrefix, $driver);
    }

    /**
     * Everything else the kit ships describes tenant-owned data — users,
     * sessions, cache, permissions — so it moves into the tenant database.
     */
    private function moveMigrationsToTenant(string $basePath): void
    {
        $central = $basePath.'/database/migrations';
        $tenant = $central.'/tenant';

        $this->files->ensureDirectoryExists($tenant);

        foreach ($this->files->files($central) as $migration) {
            $stays = array_filter(
                self::CENTRAL_MIGRATIONS,
                fn (string $name): bool => str_contains($migration->getFilename(), $name),
            );

            if ($stays !== []) {
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
    private function configure(
        string $basePath,
        array $centralDomains,
        string $databasePrefix,
        DatabaseDriver $driver,
    ): void {
        // Published by `handle()` moments ago, so it is always there.
        $path = $basePath.'/config/tenancy.php';

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

        // A SQLite tenant database is a file named after the database, so
        // without this it lands in `database/` with no extension and slips
        // past the `*.sqlite*` ignore rule.
        $config = (string) preg_replace(
            "/'suffix' => '[^']*'/",
            "'suffix' => '".($driver === DatabaseDriver::Sqlite ? '.sqlite' : '')."'",
            $config,
            1,
        );

        $this->files->put($path, $config);
    }
}
