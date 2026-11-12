<?php

declare(strict_types=1);

use App\Models\User;

it('opens the sidebar user menu and navigates through it', function (): void {
    $this->actingAs(User::factory()->create(['name' => 'Ada Lovelace']));

    $page = visit(route('dashboard', absolute: false));

    $page->click('[data-test="sidebar-menu-button"]')
        ->assertSee('Settings')
        ->assertVisible('[data-test="logout-button"]')
        ->click('Settings')
        ->assertPathIs('/settings/profile')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('opens and dismisses the delete account dialog', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit(route('user-profile.edit', absolute: false));

    $page->click('[data-test="delete-user-button"]')
        ->assertSee('Are you sure you want to delete your account?')
        ->click('Cancel')
        ->assertDontSee('Are you sure you want to delete your account?')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('renders the authenticated pages without errors', function (string $route): void {
    $this->actingAs(User::factory()->create());

    visit(route($route, absolute: false))
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
})->with([
    'dashboard',
    'user-profile.edit',
    'password.edit',
    'appearance.edit',
    'two-factor.show',
]);
