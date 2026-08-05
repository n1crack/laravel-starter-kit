<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final readonly class PublishStubs
{
    public function __construct(private Filesystem $files)
    {
        //
    }

    /**
     * Mirror a stub tree into the application, dropping the `.stub` suffix.
     *
     * Migrations are date-prefixed on the way out so they run after the ones
     * the starter kit already ships.
     */
    public function handle(string $stubPath, string $basePath): void
    {
        throw_unless(
            $this->files->isDirectory($stubPath),
            RuntimeException::class,
            "Stubs are missing from [{$stubPath}].",
        );

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

        if (str_contains($relative, 'database/migrations/')) {
            return str_replace(
                'database/migrations/',
                'database/migrations/'.now()->format('Y_m_d_His').'_',
                $relative,
            );
        }

        return $relative;
    }
}
