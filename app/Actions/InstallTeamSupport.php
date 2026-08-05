<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final readonly class InstallTeamSupport
{
    /**
     * Edits applied to files the starter kit already ships, keyed by path.
     *
     * Each entry is a [search, replace] pair. The search string must appear
     * exactly once; anything else means the file drifted from what the stubs
     * were written against, and installing would corrupt it.
     *
     * @var array<string, list<array{string, string}>>
     */
    private const array PATCHES = [
        'app/Models/User.php' => [
            [
                'use Carbon\\CarbonInterface;',
                "use App\\Models\\Concerns\\HasTeams;\nuse Carbon\\CarbonInterface;",
            ],
            [
                'use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;',
                'use HasFactory, HasRoles, HasTeams, Notifiable, TwoFactorAuthenticatable;',
            ],
        ],

        'config/permission.php' => [
            [
                "'teams' => false,",
                "'teams' => true,",
            ],
        ],

        'app/Http/Middleware/HandleInertiaRequests.php' => [
            [
                "                'roles' => \$request->user()?->getRoleNames() ?? [],",
                "                'roles' => \$request->user()?->getRoleNames() ?? [],\n"
                    ."                'currentTeam' => \$request->user()?->currentTeam,\n"
                    ."                'teams' => \$request->user()?->teams()->get(['teams.id', 'teams.name']) ?? [],",
            ],
        ],
    ];

    public function __construct(private Filesystem $files)
    {
        //
    }

    /**
     * Copy every team stub into the application and wire it into the files
     * the starter kit already ships.
     */
    public function handle(string $basePath, string $stubPath): void
    {
        $this->publishStubs($basePath, $stubPath);
        $this->patch($basePath);
    }

    /**
     * Mirror `stubs/teams` into the application, dropping the `.stub` suffix.
     *
     * Migrations are date-prefixed on the way out so they run after the
     * migrations the starter kit already ships.
     */
    private function publishStubs(string $basePath, string $stubPath): void
    {
        if (! $this->files->isDirectory($stubPath)) {
            throw new RuntimeException("Team stubs are missing from [{$stubPath}].");
        }

        foreach ($this->files->allFiles($stubPath) as $file) {
            $relative = mb_substr($file->getPathname(), mb_strlen($stubPath) + 1);
            $target = $basePath.DIRECTORY_SEPARATOR.$this->targetPath($relative);

            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->put($target, $file->getContents());
        }
    }

    private function targetPath(string $relative): string
    {
        $relative = (string) preg_replace('/\.stub$/', '', $relative);

        if (str_starts_with($relative, 'database/migrations/')) {
            $relative = str_replace(
                'database/migrations/',
                'database/migrations/'.now()->format('Y_m_d_His').'_',
                $relative,
            );
        }

        return $relative;
    }

    private function patch(string $basePath): void
    {
        foreach (self::PATCHES as $file => $edits) {
            $path = $basePath.DIRECTORY_SEPARATOR.$file;

            if (! $this->files->exists($path)) {
                throw new RuntimeException("Cannot add team support: [{$file}] is missing.");
            }

            $contents = $this->files->get($path);

            foreach ($edits as [$search, $replace]) {
                if (str_contains($contents, $replace)) {
                    continue;
                }

                if (mb_substr_count($contents, $search) !== 1) {
                    throw new RuntimeException(
                        "Cannot add team support: [{$file}] does not match what the stubs expect.",
                    );
                }

                $contents = str_replace($search, $replace, $contents);
            }

            $this->files->put($path, $contents);
        }
    }
}
