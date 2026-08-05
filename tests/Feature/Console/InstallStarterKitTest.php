<?php

declare(strict_types=1);

use App\Enums\DatabaseDriver;
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

    foreach (['app/Models/User.php', 'config/permission.php', 'app/Http/Middleware/HandleInertiaRequests.php'] as $file) {
        $this->files->ensureDirectoryExists($this->scratch.'/'.dirname($file));
        $this->files->copy($this->originalBasePath.'/'.$file, $this->scratch.'/'.$file);
    }

    $this->files->copyDirectory($this->originalBasePath.'/stubs', $this->scratch.'/stubs');

    $this->app->setBasePath($this->scratch);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    $this->files->deleteDirectory($this->scratch);
});

it('asks for teams support and a database driver', function (): void {
    $this->artisan('starter-kit:install')
        ->expectsQuestion('Would you like to add teams support to your application?', true)
        ->expectsQuestion('Which database will your application use?', DatabaseDriver::Pgsql->value)
        ->assertSuccessful();

    expect($this->scratch.'/app/Models/Team.php')->toBeReadableFile()
        ->and($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=pgsql');
});

it('leaves teams out when declined', function (): void {
    $this->artisan('starter-kit:install')
        ->expectsQuestion('Would you like to add teams support to your application?', false)
        ->expectsQuestion('Which database will your application use?', DatabaseDriver::Sqlite->value)
        ->assertSuccessful();

    expect($this->files->exists($this->scratch.'/app/Models/Team.php'))->toBeFalse()
        ->and($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=sqlite');
});

it('skips both prompts when the options are given', function (): void {
    $this->artisan('starter-kit:install', ['--teams' => true, '--database' => 'mysql'])
        ->assertSuccessful();

    expect($this->scratch.'/app/Models/Team.php')->toBeReadableFile()
        ->and($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=mysql');
});

it('falls back to sqlite without teams when not interactive', function (): void {
    $this->artisan('starter-kit:install', ['--no-interaction' => true])->assertSuccessful();

    expect($this->files->exists($this->scratch.'/app/Models/Team.php'))->toBeFalse()
        ->and($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=sqlite');
});

it('rejects an unknown database driver', function (): void {
    $this->artisan('starter-kit:install', ['--database' => 'oracle'])
        ->expectsOutputToContain('Invalid database driver [oracle].')
        ->assertFailed();

    expect($this->files->get($this->scratch.'/.env'))->toContain('DB_CONNECTION=sqlite');
});

it('names the database after the application', function (): void {
    config(['app.name' => 'Acme-App']);

    $this->artisan('starter-kit:install', ['--database' => 'mysql', '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->files->get($this->scratch.'/.env'))->toContain('DB_DATABASE=acme_app');
});
