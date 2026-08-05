<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Filesystem\Filesystem;

final readonly class DeleteFiles
{
    public function __construct(private Filesystem $files)
    {
        //
    }

    /**
     * Remove files an installed feature replaces.
     *
     * Missing files are ignored, so an install can be re-run and a kit that
     * never had the file in the first place is not an error.
     *
     * @param  list<string>  $paths
     */
    public function handle(string $basePath, array $paths): void
    {
        foreach ($paths as $path) {
            $target = $basePath.DIRECTORY_SEPARATOR.$path;

            if ($this->files->isDirectory($target)) {
                $this->files->deleteDirectory($target);

                continue;
            }

            $this->files->delete($target);
        }
    }
}
