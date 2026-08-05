<?php

declare(strict_types=1);

namespace App\Enums;

enum DatabaseDriver: string
{
    case Sqlite = 'sqlite';
    case Mysql = 'mysql';
    case Mariadb = 'mariadb';
    case Pgsql = 'pgsql';
    case Sqlsrv = 'sqlsrv';

    /**
     * Options for a `select` prompt, with unavailable drivers sorted last.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->sortBy(fn (self $driver): int => $driver->isAvailable() ? 0 : 1)
            ->mapWithKeys(fn (self $driver): array => [
                $driver->value => $driver->label().($driver->isAvailable() ? '' : ' (Missing PDO extension)'),
            ])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Sqlite => 'SQLite',
            self::Mysql => 'MySQL',
            self::Mariadb => 'MariaDB',
            self::Pgsql => 'PostgreSQL',
            self::Sqlsrv => 'SQL Server',
        };
    }

    /**
     * The PDO extension the driver needs, so unavailable drivers can be flagged.
     */
    public function extension(): string
    {
        return match ($this) {
            self::Sqlite => 'pdo_sqlite',
            self::Mysql, self::Mariadb => 'pdo_mysql',
            self::Pgsql => 'pdo_pgsql',
            self::Sqlsrv => 'pdo_sqlsrv',
        };
    }

    public function isAvailable(): bool
    {
        return extension_loaded($this->extension());
    }
}
