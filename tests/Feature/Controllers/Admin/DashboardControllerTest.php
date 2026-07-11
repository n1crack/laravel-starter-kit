<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Models\User;

it('renders the admin dashboard for admins', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::Admin);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

it('forbids regular users from the admin dashboard', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::User);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

it('redirects guests to the login page', function (): void {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});
