<?php

declare(strict_types=1);

use App\Actions\ConfigureDatabaseConnection;
use App\Enums\DatabaseDriver;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->basePath = sys_get_temp_dir().'/starter-kit-'.bin2hex(random_bytes(8));

    mkdir($this->basePath);

    $environment = <<<'ENV'
        APP_NAME=Laravel

        DB_CONNECTION=sqlite
        # DB_HOST=127.0.0.1
        # DB_PORT=3306
        # DB_DATABASE=laravel
        # DB_USERNAME=root
        # DB_PASSWORD=
        ENV;

    file_put_contents($this->basePath.'/.env', $environment);
    file_put_contents($this->basePath.'/.env.example', $environment);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->basePath);
});

function configureDatabase(DatabaseDriver $driver, string $basePath, string $name = 'acme'): void
{
    (new ConfigureDatabaseConnection(new Filesystem))->handle($driver, $basePath, $name);
}

it('uncomments the connection settings for a server driver', function (): void {
    configureDatabase(DatabaseDriver::Mysql, $this->basePath);

    $environment = file_get_contents($this->basePath.'/.env');

    expect($environment)
        ->toContain('DB_CONNECTION=mysql')
        ->toContain('DB_HOST=127.0.0.1')
        ->toContain('DB_PORT=3306')
        ->toContain('DB_DATABASE=acme')
        ->toContain('DB_USERNAME=root')
        ->not->toContain('# DB_HOST');
});

it('uses the driver specific default port', function (DatabaseDriver $driver, string $port): void {
    configureDatabase($driver, $this->basePath);

    expect(file_get_contents($this->basePath.'/.env'))->toContain('DB_PORT='.$port);
})->with([
    'postgres' => [DatabaseDriver::Pgsql, '5432'],
    'sql server' => [DatabaseDriver::Sqlsrv, '1433'],
    'mariadb' => [DatabaseDriver::Mariadb, '3306'],
]);

it('comments the connection settings back out for sqlite', function (): void {
    configureDatabase(DatabaseDriver::Mysql, $this->basePath);
    configureDatabase(DatabaseDriver::Sqlite, $this->basePath);

    expect(file_get_contents($this->basePath.'/.env'))
        ->toContain('DB_CONNECTION=sqlite')
        ->toContain('# DB_HOST=127.0.0.1')
        ->toContain('# DB_DATABASE=acme');
});

it('configures both environment files', function (): void {
    configureDatabase(DatabaseDriver::Pgsql, $this->basePath);

    expect(file_get_contents($this->basePath.'/.env.example'))
        ->toContain('DB_CONNECTION=pgsql')
        ->toContain('DB_PORT=5432');
});

it('skips environment files that do not exist', function (): void {
    unlink($this->basePath.'/.env.example');

    configureDatabase(DatabaseDriver::Mysql, $this->basePath);

    expect(file_exists($this->basePath.'/.env.example'))->toBeFalse()
        ->and(file_get_contents($this->basePath.'/.env'))->toContain('DB_CONNECTION=mysql');
});
