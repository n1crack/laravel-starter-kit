<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::Admin);
});

it('lists roles with their permissions and user counts', function (): void {
    $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/roles/index')
            ->has('roles', 2)
            ->has('permissions', count(PermissionEnum::cases())),
    );
});

it('creates a role with the permissions it was given', function (): void {
    $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'technician',
        'permissions' => [PermissionEnum::AccessAdmin->value],
    ])->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'technician')->sole();

    expect($role->hasPermissionTo(PermissionEnum::AccessAdmin->value))->toBeTrue()
        ->and($role->hasPermissionTo(PermissionEnum::ManageRoles->value))->toBeFalse();
});

it('refuses a permission the application does not define', function (): void {
    $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'technician',
        'permissions' => ['invent-a-permission'],
    ])->assertSessionHasErrors('permissions.0');
});

it('refuses a duplicate role name', function (): void {
    $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => RoleEnum::Admin->value,
    ])->assertSessionHasErrors('name');
});

it('renames a role and resets its permissions', function (): void {
    $role = Role::findOrCreate('technician');

    $this->actingAs($this->admin)->patch(route('admin.roles.update', $role), [
        'name' => 'field-technician',
        'permissions' => [PermissionEnum::ManageUsers->value],
    ])->assertRedirect(route('admin.roles.index'));

    expect($role->refresh()->name)->toBe('field-technician')
        ->and($role->hasPermissionTo(PermissionEnum::ManageUsers->value))->toBeTrue();
});

it('keeps the name of a role the application depends on', function (): void {
    $role = Role::query()->where('name', RoleEnum::Admin->value)->sole();

    $this->actingAs($this->admin)->patch(route('admin.roles.update', $role), [
        'name' => 'renamed',
        'permissions' => PermissionEnum::names(),
    ])->assertRedirect(route('admin.roles.index'));

    expect($role->refresh()->name)->toBe(RoleEnum::Admin->value);
});

it('still lets its permissions be changed', function (): void {
    $role = Role::query()->where('name', RoleEnum::User->value)->sole();

    $this->actingAs($this->admin)->patch(route('admin.roles.update', $role), [
        'name' => RoleEnum::User->value,
        'permissions' => [PermissionEnum::AccessAdmin->value],
    ]);

    expect($role->hasPermissionTo(PermissionEnum::AccessAdmin->value))->toBeTrue();
});

it('deletes a role an administrator made', function (): void {
    $role = Role::findOrCreate('technician');

    $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});

it('refuses to delete a role the application depends on', function (string $name): void {
    $role = Role::query()->where('name', $name)->sole();

    $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))
        ->assertSessionHasErrors('role');

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue();
})->with(RoleEnum::names());

it('keeps someone without the permission out', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionEnum::AccessAdmin->value);

    $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
});

it('lets a custom role into the admin area', function (): void {
    $role = Role::findOrCreate('operations');
    $role->syncPermissions([PermissionEnum::AccessAdmin->value, PermissionEnum::ManageRoles->value]);

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get(route('admin.roles.index'))->assertOk();
});
