<?php

declare(strict_types=1);

use App\Actions\InstallTeamSupport;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->files = new Filesystem;
    $this->basePath = sys_get_temp_dir().'/starter-kit-'.bin2hex(random_bytes(8));

    // A miniature copy of the files the stubs patch, kept byte-identical to the
    // real ones so the test fails when either side drifts.
    foreach (['app/Models/User.php', 'config/permission.php', 'app/Http/Middleware/HandleInertiaRequests.php'] as $file) {
        $this->files->ensureDirectoryExists($this->basePath.'/'.dirname($file));
        $this->files->copy(base_path($file), $this->basePath.'/'.$file);
    }
});

afterEach(function (): void {
    $this->files->deleteDirectory($this->basePath);
});

function installTeams(string $basePath, ?string $stubPath = null): void
{
    resolve(InstallTeamSupport::class)->handle($basePath, $stubPath ?? base_path('stubs/teams'));
}

it('publishes every stub without the stub suffix', function (): void {
    installTeams($this->basePath);

    expect($this->basePath.'/app/Models/Team.php')->toBeReadableFile()
        ->and($this->basePath.'/app/Models/Concerns/HasTeams.php')->toBeReadableFile()
        ->and($this->basePath.'/app/Enums/TeamRole.php')->toBeReadableFile();
});

it('date prefixes published migrations so they run last', function (): void {
    installTeams($this->basePath);

    $migrations = $this->files->files($this->basePath.'/database/migrations');

    expect($migrations)->toHaveCount(1)
        ->and($migrations[0]->getFilename())->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_create_teams_tables\.php$/');
});

it('adds the teams trait to the user model', function (): void {
    installTeams($this->basePath);

    expect($this->files->get($this->basePath.'/app/Models/User.php'))
        ->toContain('use App\Models\Concerns\HasTeams;')
        ->toContain('use HasFactory, HasRoles, HasTeams, Notifiable, TwoFactorAuthenticatable;');
});

it('turns on team scoped permissions', function (): void {
    installTeams($this->basePath);

    expect($this->files->get($this->basePath.'/config/permission.php'))->toContain("'teams' => true,");
});

it('shares the current team with inertia', function (): void {
    installTeams($this->basePath);

    expect($this->files->get($this->basePath.'/app/Http/Middleware/HandleInertiaRequests.php'))
        ->toContain("'currentTeam' => \$request->user()?->currentTeam,")
        ->toContain("'teams' =>");
});

it('can be run twice without duplicating the patches', function (): void {
    installTeams($this->basePath);
    installTeams($this->basePath);

    $user = $this->files->get($this->basePath.'/app/Models/User.php');

    expect(mb_substr_count($user, 'use App\Models\Concerns\HasTeams;'))->toBeOne()
        ->and(mb_substr_count($user, 'HasTeams, Notifiable'))->toBeOne();
});

it('fails when the stubs are missing', function (): void {
    installTeams($this->basePath, $this->basePath.'/nowhere');
})->throws(RuntimeException::class, 'Stubs are missing from');

it('fails when a patched file is missing', function (): void {
    $this->files->delete($this->basePath.'/config/permission.php');

    installTeams($this->basePath);
})->throws(RuntimeException::class, 'Cannot patch [config/permission.php]');

it('fails when a patched file has drifted from the stubs', function (): void {
    $this->files->put($this->basePath.'/config/permission.php', '<?php return [];');

    installTeams($this->basePath);
})->throws(RuntimeException::class, 'does not match what the stubs expect');

it('re-applies nothing when a deleting patch already ran', function (): void {
    $file = $this->basePath.'/config/permission.php';
    $this->files->put($file, "<?php\n\nreturn ['teams' => false];\n");

    $patch = ['config/permission.php' => [["'teams' => false", '']]];

    resolve(App\Actions\PatchFiles::class)->handle($this->basePath, $patch);
    resolve(App\Actions\PatchFiles::class)->handle($this->basePath, $patch);

    expect($this->files->get($file))->toBe("<?php\n\nreturn [];\n");
});
