<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DatabaseDriver;
use Illuminate\Filesystem\Filesystem;

final readonly class ConfigureDatabaseConnection
{
    /**
     * Ports that differ from the `.env.example` default of 3306.
     *
     * @var array<string, string>
     */
    private const array DEFAULT_PORTS = [
        DatabaseDriver::Pgsql->value => '5432',
        DatabaseDriver::Sqlsrv->value => '1433',
    ];

    /**
     * The commented-out connection settings shipped in `.env.example`.
     *
     * @var list<string>
     */
    private const array CONNECTION_KEYS = [
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
    ];

    public function __construct(private Filesystem $files)
    {
        //
    }

    /**
     * Point both environment files at the given driver.
     *
     * The starter kit ships configured for SQLite, with every other connection
     * setting commented out. Any other driver needs those lines uncommented,
     * which mirrors what the Laravel installer does for its own starter kits.
     */
    public function handle(DatabaseDriver $database, string $basePath, string $databaseName): void
    {
        foreach (['.env', '.env.example'] as $file) {
            $path = $basePath.DIRECTORY_SEPARATOR.$file;

            if (! $this->files->exists($path)) {
                continue;
            }

            $this->files->put($path, $this->configure(
                $this->files->get($path),
                $database,
                $databaseName,
            ));
        }
    }

    private function configure(string $environment, DatabaseDriver $database, string $databaseName): string
    {
        $environment = (string) preg_replace(
            '/^DB_CONNECTION=.*$/m',
            'DB_CONNECTION='.$database->value,
            $environment,
        );

        if ($database === DatabaseDriver::Sqlite) {
            return $this->comment($environment);
        }

        $environment = $this->uncomment($environment);

        $environment = str_replace(
            'DB_PORT=3306',
            'DB_PORT='.(self::DEFAULT_PORTS[$database->value] ?? '3306'),
            $environment,
        );

        return str_replace('DB_DATABASE=laravel', 'DB_DATABASE='.$databaseName, $environment);
    }

    private function comment(string $environment): string
    {
        foreach (self::CONNECTION_KEYS as $key) {
            $environment = (string) preg_replace('/^'.$key.'=/m', '# '.$key.'=', $environment);
        }

        return $environment;
    }

    private function uncomment(string $environment): string
    {
        foreach (self::CONNECTION_KEYS as $key) {
            $environment = (string) preg_replace('/^# ?'.$key.'=/m', $key.'=', $environment);
        }

        return $environment;
    }
}
