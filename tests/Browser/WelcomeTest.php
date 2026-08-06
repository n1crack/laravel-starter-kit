<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $page = visit('/');

    // Asserted on the call to action rather than the application name: it is
    // present whether the public pages are Blade or React.
    $page->assertSee('Get started')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('renders the guest pages without errors', function (): void {
    $pages = visit(['/login', '/register', '/forgot-password']);

    $pages->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});
