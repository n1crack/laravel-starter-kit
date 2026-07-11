<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-background text-foreground min-h-screen font-sans antialiased">
        <header class="border-border/50 border-b">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="text-lg font-semibold">
                    {{ config('app.name', 'Laravel') }}
                </a>

                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>

                        @if (auth()->user()->hasRole(App\Enums\Role::Admin))
                            <a href="{{ route('admin.dashboard') }}" class="hover:underline">Admin</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="hover:underline">Log in</a>
                        <a
                            href="{{ route('register') }}"
                            class="bg-foreground text-background rounded-md px-4 py-1.5 font-medium hover:opacity-90"
                        >
                            Register
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="border-border/50 text-muted-foreground border-t">
            <div class="mx-auto max-w-6xl px-6 py-6 text-sm">
                &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
            </div>
        </footer>
    </body>
</html>
