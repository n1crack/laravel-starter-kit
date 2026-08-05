<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final readonly class PatchFiles
{
    public function __construct(private Filesystem $files)
    {
        //
    }

    /**
     * Apply search/replace edits to files the starter kit already ships.
     *
     * An edit whose replacement is already present is skipped, so installing
     * twice is harmless. A search string that does not appear exactly once
     * means the file drifted from what the stubs were written against, and is
     * refused rather than corrupted.
     *
     * @param  array<string, list<array{string, string}>>  $patches
     */
    public function handle(string $basePath, array $patches): void
    {
        foreach ($patches as $file => $edits) {
            $path = $basePath.DIRECTORY_SEPARATOR.$file;

            throw_unless(
                $this->files->exists($path),
                RuntimeException::class,
                "Cannot patch [{$file}]: the file is missing.",
            );

            $contents = $this->files->get($path);

            foreach ($edits as [$search, $replace]) {
                // How an edit shows that it already ran depends on its shape.
                // An edit that inserts around what it matched leaves the search
                // string in place, so only the replacement's presence can say.
                // Every other edit — one that deletes, or rewrites what it
                // matched — is done exactly when the search string is gone.
                // Looking for the replacement in those cases silently skips the
                // edit whenever the replacement is a substring of the original,
                // which is what a deletion always looks like.
                $applied = str_contains($replace, $search)
                    ? str_contains($contents, $replace)
                    : ! str_contains($contents, $search);

                if ($applied) {
                    continue;
                }

                throw_if(
                    mb_substr_count($contents, $search) !== 1,
                    RuntimeException::class,
                    "Cannot patch [{$file}]: it does not match what the stubs expect.",
                );

                $contents = str_replace($search, $replace, $contents);
            }

            $this->files->put($path, $contents);
        }
    }
}
