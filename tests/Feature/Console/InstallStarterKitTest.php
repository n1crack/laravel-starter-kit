<?php

declare(strict_types=1);

use App\Enums\DatabaseDriver;
use App\Enums\PublicStack;
use Illuminate\Filesystem\Filesystem;

/**
 * The command works on `base_path()`, so each test points the application at a
 * scratch copy of the files it touches. That keeps the real repository out of
 * harm's way while still exercising the command end to end, without mocks —
 * the actions are `final readonly` and cannot be doubled anyway.
 */
beforeEach(function (): void {
    $this->files = new Filesystem;
    $this->originalBasePath = base_path();
    $this->scratch = sys_get_temp_dir().'/starter-kit-'.bin2hex(random_bytes(8));

    $environment = <<<'ENV'
        APP_NAME=Laravel

        DB_CONNECTION=sqlite
        # DB_HOST=127.0.0.1
        # DB_PORT=3306
        # DB_DATABASE=laravel
        # DB_USERNAME=root
        # DB_PASSWORD=
        ENV;

    $this->files->ensureDirectoryExists($this->scratch);
    $this->files->put($this->scratch.'/.env', $environment);
    $this->files->put($this->scratch.'/.env.example', $environment);

    $patched = [
        'app/Models/User.php',
        'config/permission.php',
        'app/Http/Middleware/HandleInertiaRequests.php',
        'bootstrap/providers.php',
        'bootstrap/app.php',
        'composer.json',
        'database/factories/UserFactory.php',
        'tests/Unit/Models/UserTest.php',
        'resources/js/types/index.d.ts',
        'tests/Pest.php',
        'resources/js/components/app-sidebar.tsx',
        'routes/web.php',
        'resources/views/home.blade.php',
        'resources/views/components/layouts/public.blade.php',
        'app/Livewire/ContactForm.php',
        'resources/views/livewire/contact-form.blade.php',
        'tests/Feature/Livewire/ContactFormTest.php',
    ];

    foreach ($patched as $file) {
        $this->files->ensureDirectoryExists($this->scratch.'/'.dirname($file));
        $this->files->copy($this->originalBasePath.'/'.$file, $this->scratch.'/'.$file);
    }

    $this->files->copyDirectory($this->originalBasePath.'/database/migrations', $this->scratch.'/database/migrations');
    $this->files->copyDirectory($this->originalBasePath.'/stubs', $this->scratch.'/stubs');

    $this->app->setBasePath($this->scratch);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    $this->files->deleteDirectory($this->scratch);
});

it('asks for the public stack, teams, tenancy and a database driver', function (): void {
    $this->artisan('starter-kit:install')
        ->expectsQuestion('How should the public pages be rendered?', PublicStack::React->value)
        ->expectsQuestion('Would you like to add teams support to your application?', true)
        ->expectsQuestion('Would you like to add multi-tenancy to your application?', true)
        ->expectsQuestion('Which domains serve the application itself?', 'app.test, localhost')
        ->expectsQuestion('What should tenant database names be prefixed with?', 'acme')
        ->expectsQuestion('Which database will your application use?', DatabaseDriver::Pgsql->value)
        ->assertSuccessful();

    expect($this->scratch.'/app/Models/Team.php')->toBeReadableFile()
        ->and($this->scratch.'/config/tenancy.php')->toBeReadableFile()
        ->and($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=pgsql');
});

it('does not ask the tenancy follow ups when tenancy is declined', function (): void {
    $this->artisan('starter-kit:install')
        ->expectsQuestion('How should the public pages be rendered?', PublicStack::Livewire->value)
        ->expectsQuestion('Would you like to add teams support to your application?', false)
        ->expectsQuestion('Would you like to add multi-tenancy to your application?', false)
        ->expectsQuestion('Which database will your application use?', DatabaseDriver::Sqlite->value)
        ->assertSuccessful();

    expect($this->files->exists($this->scratch.'/config/tenancy.php'))->toBeFalse()
        ->and($this->files->exists($this->scratch.'/app/Models/Team.php'))->toBeFalse();
});

it('writes the central domains and tenant prefix into the config', function (): void {
    $this->artisan('starter-kit:install', [
        '--tenancy' => true,
        '--central-domain' => ['app.test', 'admin.app.test'],
        '--tenant-prefix' => 'acme',
        '--public' => PublicStack::Livewire->value,
        '--database' => 'mysql',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $config = $this->files->get($this->scratch.'/config/tenancy.php');

    expect($config)
        ->toContain("'app.test',")
        ->toContain("'admin.app.test',")
        ->toContain("'prefix' => 'acme'")
        ->not->toContain("'127.0.0.1',");
});

it('moves the kit migrations into the tenant directory', function (): void {
    $this->artisan('starter-kit:install', ['--tenancy' => true, '--no-interaction' => true])
        ->assertSuccessful();

    $central = collect($this->files->files($this->scratch.'/database/migrations'))
        ->map(fn ($file): string => $file->getFilename());

    expect($this->scratch.'/database/migrations/tenant/0001_01_01_000000_create_users_table.php')->toBeReadableFile()
        ->and($central)->each->toMatch('/tenants_table|domains_table|impersonation_tokens_table/');
});

it('registers the tenancy service provider', function (): void {
    $this->artisan('starter-kit:install', ['--tenancy' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->files->get($this->scratch.'/bootstrap/providers.php'))
        ->toContain('App\Providers\TenancyServiceProvider::class,');
});

it('swaps the public pages over to react', function (): void {
    $this->artisan('starter-kit:install', ['--public' => 'react', '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->scratch.'/resources/js/pages/home.tsx')->toBeReadableFile()
        ->and($this->scratch.'/resources/js/layouts/public-layout.tsx')->toBeReadableFile()
        ->and($this->scratch.'/app/Http/Controllers/ContactController.php')->toBeReadableFile()
        ->and($this->files->get($this->scratch.'/routes/web.php'))
        ->toContain("Inertia::render('home')")
        ->toContain("'contact.store'")
        ->and($this->files->get($this->scratch.'/composer.json'))->not->toContain('livewire/livewire');
});

it('removes the blade and livewire public pages it replaces', function (): void {
    $this->artisan('starter-kit:install', ['--public' => 'react', '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->files->exists($this->scratch.'/resources/views/home.blade.php'))->toBeFalse()
        ->and($this->files->exists($this->scratch.'/app/Livewire'))->toBeFalse()
        ->and($this->files->exists($this->scratch.'/resources/views/livewire'))->toBeFalse()
        ->and($this->files->exists($this->scratch.'/tests/Feature/Livewire'))->toBeFalse();
});

it('leaves the blade public pages alone for the livewire stack', function (): void {
    $this->artisan('starter-kit:install', ['--public' => 'livewire', '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->scratch.'/resources/views/home.blade.php')->toBeReadableFile()
        ->and($this->scratch.'/app/Livewire/ContactForm.php')->toBeReadableFile()
        ->and($this->files->exists($this->scratch.'/resources/js/pages/home.tsx'))->toBeFalse()
        ->and($this->files->get($this->scratch.'/composer.json'))->toContain('livewire/livewire');
});

it('requires the tenancy package and says composer still has to run', function (): void {
    $this->artisan('starter-kit:install', ['--tenancy' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Run "composer update" to install stancl/tenancy.')
        ->assertSuccessful();

    expect($this->files->get($this->scratch.'/composer.json'))->toContain('"stancl/tenancy": "^3.10"');
});

it('leaves the tenancy package out when tenancy is declined', function (): void {
    $this->artisan('starter-kit:install', ['--no-interaction' => true])->assertSuccessful();

    expect($this->files->get($this->scratch.'/composer.json'))->not->toContain('stancl/tenancy');
});

it('runs the generated tests inside a tenant', function (): void {
    $this->artisan('starter-kit:install', ['--tenancy' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->files->get($this->scratch.'/tests/Pest.php'))
        ->toContain('use App\Models\Tenant;')
        ->toContain('tenancy()->initialize($tenant);')
        ->toContain("URL::forceRootUrl('http://test.localhost');")
        ->toContain('tenancy()->end();');
});

it('hands the kit routes over to the tenant context', function (): void {
    $this->artisan('starter-kit:install', ['--tenancy' => true, '--no-interaction' => true])
        ->assertSuccessful();

    $bootstrap = $this->files->get($this->scratch.'/bootstrap/app.php');

    // The central domains keep serving only central.php; web.php and admin.php
    // are required from inside the tenant group instead of being rewritten.
    expect($bootstrap)
        ->toContain("web: __DIR__.'/../routes/central.php'")
        ->not->toContain('routes/web.php')
        ->not->toContain('routes/admin.php')
        ->and($this->files->get($this->scratch.'/routes/tenant.php'))
        ->toContain("require base_path('routes/web.php');")
        ->toContain("->group(base_path('routes/admin.php'));")
        ->toContain('InitializeTenancyByDomainOrSubdomain::class')
        ->and($this->scratch.'/routes/central.php')->toBeReadableFile()
        ->and($this->scratch.'/app/Models/Tenant.php')->toBeReadableFile();
});

it('skips the prompts it was given options for', function (): void {
    // There is no `--no-tenancy`, matching how the Laravel installer treats its
    // own boolean flags, so declining tenancy is still a prompt here.
    $this->artisan('starter-kit:install', [
        '--public' => PublicStack::React->value,
        '--teams' => true,
        '--database' => 'mysql',
    ])
        ->expectsQuestion('Would you like to add multi-tenancy to your application?', false)
        ->assertSuccessful();

    expect($this->scratch.'/app/Models/Team.php')->toBeReadableFile()
        ->and($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=mysql');
});

it('falls back to livewire, sqlite and no extras when not interactive', function (): void {
    $this->artisan('starter-kit:install', ['--no-interaction' => true])->assertSuccessful();

    expect($this->files->exists($this->scratch.'/app/Models/Team.php'))->toBeFalse()
        ->and($this->files->exists($this->scratch.'/config/tenancy.php'))->toBeFalse()
        ->and($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=sqlite');
});

it('rejects an unknown database driver', function (): void {
    $this->artisan('starter-kit:install', ['--database' => 'oracle'])
        ->expectsOutputToContain('Invalid database driver [oracle].')
        ->assertFailed();

    expect($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=sqlite');
});

it('rejects an unknown public stack', function (): void {
    $this->artisan('starter-kit:install', ['--public' => 'vue'])
        ->expectsOutputToContain('Invalid public stack [vue].')
        ->assertFailed();
});

it('names the database after the application', function (): void {
    config(['app.name' => 'Acme-App']);

    $this->artisan('starter-kit:install', ['--database' => 'mysql', '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->files->get($this->scratch.'/.env'))->toContain('DB_DATABASE=acme_app');
});
