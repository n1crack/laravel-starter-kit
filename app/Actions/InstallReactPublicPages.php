<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class InstallReactPublicPages
{
    /**
     * Edits applied to files the starter kit already ships.
     *
     * @var array<string, list<array{string, string}>>
     */
    private const array PATCHES = [
        'routes/web.php' => [
            [
                'use App\\Http\\Controllers\\SessionController;',
                "use App\\Http\\Controllers\\ContactController;\nuse App\\Http\\Controllers\\SessionController;",
            ],
            [
                "// Public pages (Blade + Livewire)...\nRoute::view('/', 'home')->name('home');",
                "// Public pages (Inertia + React)...\n"
                    ."Route::get('/', fn () => Inertia::render('home'))->name('home');\n"
                    ."Route::post('contact', [ContactController::class, 'store'])\n"
                    ."    ->middleware('throttle:6,1')\n"
                    ."    ->name('contact.store');",
            ],
        ],

        // The public pages were the only thing Livewire was used for.
        'composer.json' => [
            [
                "\"livewire/livewire\": \"^4.3\",\n        ",
                '',
            ],
        ],
    ];

    /**
     * Blade and Livewire files the React public pages replace.
     *
     * @var list<string>
     */
    private const array REPLACED = [
        'app/Livewire',
        'resources/views/home.blade.php',
        'resources/views/livewire',
        'resources/views/components/layouts/public.blade.php',
        'tests/Feature/Livewire',
    ];

    public function __construct(
        private PublishStubs $publishStubs,
        private PatchFiles $patchFiles,
        private DeleteFiles $deleteFiles,
    ) {
        //
    }

    public function handle(string $basePath, string $stubPath): void
    {
        $this->publishStubs->handle($stubPath, $basePath);
        $this->patchFiles->handle($basePath, self::PATCHES);
        $this->deleteFiles->handle($basePath, self::REPLACED);
    }
}
