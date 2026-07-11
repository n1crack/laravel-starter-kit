<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::Admin);
});

it('lists users for admins', function (): void {
    User::factory(3)->create()->each(fn (User $user) => $user->assignRole(RoleEnum::User));

    $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/users/index')
            ->has('users.data', 4)
            ->has('availableRoles'),
    );
});

it('filters users by search term', function (): void {
    User::factory()->create(['name' => 'Ayşe Yılmaz']);
    User::factory()->create(['name' => 'Mehmet Demir']);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['search' => 'Ayşe']));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/users/index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Ayşe Yılmaz'),
    );
});

it('filters users by role', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::User);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['roles' => ['admin']]));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/users/index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->admin->id),
    );
});

it('forbids regular users from the users list', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::User);

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertForbidden();
});
