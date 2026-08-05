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
            // spatie/laravel-permission also defines `teams()`, meaning the
            // teams a user holds a role in. Membership is the one the UI wants,
            // so the collision is resolved explicitly and theirs kept aliased.
            [
                'use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;',
                "use HasFactory, HasRoles, HasTeams, Notifiable, TwoFactorAuthenticatable {\n"
                    ."        HasTeams::teams insteadof HasRoles;\n"
                    ."        HasRoles::teams as roleTeams;\n"
                    .'    }',
            ],
            [
                ' * @property-read CarbonInterface|null $email_verified_at',
                " * @property-read CarbonInterface|null \$email_verified_at\n * @property-read int|null \$current_team_id",
            ],
            [
                "            'email_verified_at' => 'datetime',",
                "            'email_verified_at' => 'datetime',\n            'current_team_id' => 'integer',",
            ],
        ],

        // Hung off `web.php` rather than `bootstrap/app.php` on purpose: when
        // tenancy is also installed, `web.php` is required from inside the
        // tenant route group, so the team routes follow it into the tenant
        // context without the two installers fighting over the same file.
        'routes/web.php' => [
            [
                "    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');",
                "    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');\n\n"
                    ."    require __DIR__.'/teams.php';",
            ],
        ],

        'resources/js/types/index.d.ts' => [
            [
                "export interface Auth {\n    user: User;\n    roles: string[];\n}",
                "export interface Team {\n    id: number;\n    name: string;\n}\n\n"
                    ."export interface Auth {\n    user: User;\n    roles: string[];\n"
                    ."    currentTeam: Team | null;\n    teams: Team[];\n}",
            ],
        ],

        'resources/js/components/app-sidebar.tsx' => [
            [
                '            <SidebarHeader>',
                "            <SidebarHeader>\n                <TeamSwitcher />",
            ],
            [
                "import { NavMain } from '@/components/nav-main';",
                "import { NavMain } from '@/components/nav-main';\nimport { TeamSwitcher } from '@/components/team-switcher';",
            ],
        ],

        // A factory-made user only carries the attributes the factory set, and
        // the kit runs with `preventAccessingMissingAttributes`, so reading the
        // new column off one would throw without this.
        'database/factories/UserFactory.php' => [
            [
                "            'email_verified_at' => now(),",
                "            'email_verified_at' => now(),\n            'current_team_id' => null,",
            ],
        ],

        'tests/Unit/Models/UserTest.php' => [
            [
                "            'updated_at',\n        ]);",
                "            'updated_at',\n            'current_team_id',\n        ]);",
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
