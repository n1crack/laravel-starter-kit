<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class InstallTeamSupport
{
    /**
     * Edits applied to files the starter kit already ships.
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

    public function __construct(
        private PublishStubs $publishStubs,
        private PatchFiles $patchFiles,
    ) {
        //
    }

    public function handle(string $basePath, string $stubPath): void
    {
        $this->publishStubs->handle($stubPath, $basePath);
        $this->patchFiles->handle($basePath, self::PATCHES);
    }
}
