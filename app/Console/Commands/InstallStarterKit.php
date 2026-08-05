<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ConfigureDatabaseConnection;
use App\Actions\InstallTeamSupport;
use App\Enums\DatabaseDriver;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

#[\Illuminate\Console\Attributes\Description('Configure a freshly installed copy of the starter kit')]
#[\Illuminate\Console\Attributes\Signature('starter-kit:install
        {--teams : Add team support without prompting}
        {--database= : The database driver to use, skipping the prompt}')]
final class InstallStarterKit extends Command
{
    public function handle(ConfigureDatabaseConnection $configureDatabase, InstallTeamSupport $installTeams): int
    {
        $option = $this->option('database');

        if (is_string($option) && $option !== '' && DatabaseDriver::tryFrom($option) === null) {
            $this->components->error('Invalid database driver ['.$option.'].');

            return self::FAILURE;
        }

        if ($this->teams()) {
            $installTeams->handle(base_path(), base_path('stubs/teams'));

            $this->components->info('Team support added.');
        }

        $driver = $this->database();

        $configureDatabase->handle($driver, base_path(), $this->databaseName());

        $this->components->info('Using '.$driver->label().'.');

        return self::SUCCESS;
    }

    private function database(): DatabaseDriver
    {
        $option = $this->option('database');

        if (is_string($option) && $option !== '') {
            return DatabaseDriver::from($option);
        }

        if (! $this->input->isInteractive()) {
            return DatabaseDriver::Sqlite;
        }

        $selected = select(
            label: 'Which database will your application use?',
            options: DatabaseDriver::options(),
            default: DatabaseDriver::Sqlite->value,
        );

        return DatabaseDriver::from($selected);
    }

    private function teams(): bool
    {
        if ($this->option('teams') === true) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm(
            label: 'Would you like to add teams support to your application?',
            default: false,
        );
    }

    /**
     * The database name to write into the environment files, derived from the
     * application name the way the Laravel installer derives it.
     */
    private function databaseName(): string
    {
        $name = config('app.name');

        return str_replace('-', '_', mb_strtolower(is_string($name) ? $name : 'laravel'));
    }
}
