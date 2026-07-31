<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $page = visit('/');

    $page->assertSee('Laravel')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('renders the guest pages without errors', function (): void {
    $pages = visit(['/login', '/register', '/forgot-password']);

    $pages->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});
