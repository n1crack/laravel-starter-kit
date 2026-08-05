<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ConfigureDatabaseConnection;
use App\Actions\InstallTeamSupport;
use App\Actions\InstallTenancy;
use App\Enums\DatabaseDriver;
use App\Enums\PublicStack;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Description('Configure a freshly installed copy of the starter kit')]
#[Signature('starter-kit:install
        {--public= : How public pages are rendered: livewire or react}
        {--teams : Add team support without prompting}
        {--tenancy : Add multi-tenancy without prompting}
        {--central-domain=* : Domains that serve the application itself}
        {--tenant-prefix=tenant : Prefix for tenant database names}
        {--database= : The database driver to use, skipping the prompt}')]
final class InstallStarterKit extends Command
{
    public function handle(
        ConfigureDatabaseConnection $configureDatabase,
        InstallTeamSupport $installTeams,
        InstallTenancy $installTenancy,
    ): int {
        $option = $this->option('database');

        if (is_string($option) && $option !== '' && DatabaseDriver::tryFrom($option) === null) {
            $this->components->error('Invalid database driver ['.$option.'].');

            return self::FAILURE;
        }

        $public = $this->publicStack();

        if (! $public instanceof PublicStack) {
            $this->components->error('Invalid public stack ['.$this->option('public').'].');

            return self::FAILURE;
        }

        $this->components->info('Public pages: '.$public->label().'.');

        if ($this->teams()) {
            $installTeams->handle(base_path(), base_path('stubs/teams'));

            $this->components->info('Team support added.');
        }

        if ($this->tenancy()) {
            $installTenancy->handle(
                base_path(),
                base_path('stubs/tenancy'),
                $this->centralDomains(),
                $this->tenantPrefix(),
            );

            $this->components->info('Multi-tenancy added.');
            $this->components->warn('Run "composer update" to install '.InstallTenancy::PACKAGE.'.');
        }

        $driver = $this->database();

        $configureDatabase->handle($driver, base_path(), $this->databaseName());

        $this->components->info('Using '.$driver->label().'.');

        return self::SUCCESS;
    }

    /**
     * The chosen public stack, or null when an invalid one was passed.
     */
    private function publicStack(): ?PublicStack
    {
        $option = $this->option('public');

        if (is_string($option) && $option !== '') {
            return PublicStack::tryFrom($option);
        }

        if (! $this->input->isInteractive()) {
            return PublicStack::Livewire;
        }

        return PublicStack::from(select(
            label: 'How should the public pages be rendered?',
            options: PublicStack::options(),
            default: PublicStack::Livewire->value,
            hint: 'The admin and user panels are always React.',
        ));
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

    private function tenancy(): bool
    {
        if ($this->option('tenancy') === true) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm(
            label: 'Would you like to add multi-tenancy to your application?',
            default: false,
            hint: 'Each tenant gets its own database, resolved from the request domain or subdomain.',
        );
    }

    /**
     * @return list<string>
     */
    private function centralDomains(): array
    {
        /** @var array<int, string|null> $option */
        $option = $this->option('central-domain');

        $given = $this->domainList($option);

        if ($given !== []) {
            return $given;
        }

        if (! $this->input->isInteractive()) {
            return ['localhost', '127.0.0.1'];
        }

        return $this->domainList(explode(',', text(
            label: 'Which domains serve the application itself?',
            placeholder: 'localhost, 127.0.0.1',
            default: 'localhost, 127.0.0.1',
            hint: 'Comma separated. Requests to these are never resolved to a tenant.',
        )));
    }

    /**
     * @param  array<int, string|null>  $domains
     * @return list<string>
     */
    private function domainList(array $domains): array
    {
        return array_values(array_filter(array_map(
            fn (?string $domain): string => mb_trim($domain ?? ''),
            $domains,
        )));
    }

    private function tenantPrefix(): string
    {
        $option = $this->option('tenant-prefix');
        $default = is_string($option) && $option !== '' ? $option : 'tenant';

        if (! $this->input->isInteractive()) {
            return $default;
        }

        return text(
            label: 'What should tenant database names be prefixed with?',
            default: $default,
            hint: 'A tenant then lives in, for example, '.$default.'_3f9a1c.',
        );
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

        return DatabaseDriver::from(select(
            label: 'Which database will your application use?',
            options: DatabaseDriver::options(),
            default: DatabaseDriver::Sqlite->value,
        ));
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
