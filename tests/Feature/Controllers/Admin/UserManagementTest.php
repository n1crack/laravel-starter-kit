<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::Admin);
});

it('changes the roles a user holds', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::User);

    $technician = Role::findOrCreate('technician');

    $this->actingAs($this->admin)
        ->patch(route('admin.users.update', $user), ['roles' => [$technician->name]])
        ->assertRedirect(route('admin.users.index'));

    expect($user->refresh()->hasRole('technician'))->toBeTrue()
        ->and($user->hasRole(RoleEnum::User->value))->toBeFalse();
});

it('refuses a role that does not exist', function (): void {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.users.update', $user), ['roles' => ['not-a-role']])
        ->assertSessionHasErrors('roles.0');
});

it('marks an address verified when the mail never arrived', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.users.verify', $user))
        ->assertRedirect(route('admin.users.index'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('leaves an already verified address alone', function (): void {
    $user = User::factory()->create();
    $verifiedAt = $user->email_verified_at;

    $this->actingAs($this->admin)->put(route('admin.users.verify', $user));

    expect($user->refresh()->email_verified_at?->toString())->toBe($verifiedAt?->toString());
});

it('invites someone and lets them set their own password', function (): void {
    Notification::fake();

    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Mehmet Demir',
        'email' => 'mehmet@example.com',
        'roles' => [RoleEnum::User->value],
    ])->assertRedirect(route('admin.users.index'));

    $invited = User::query()->where('email', 'mehmet@example.com')->sole();

    expect($invited->hasRole(RoleEnum::User->value))->toBeTrue()
        ->and($invited->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($invited, ResetPassword::class);
});

it('refuses to invite an address that already has an account', function (): void {
    User::factory()->create(['email' => 'mehmet@example.com']);

    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Mehmet Demir',
        'email' => 'mehmet@example.com',
    ])->assertSessionHasErrors('email');
});

it('deletes a user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('refuses to let an administrator delete themselves from here', function (): void {
    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $this->admin))
        ->assertSessionHasErrors('user');

    expect(User::query()->whereKey($this->admin->id)->exists())->toBeTrue();
});

it('keeps someone without the permission out of every write', function (string $method, string $route): void {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(PermissionEnum::AccessAdmin->value);

    $target = User::factory()->create();

    $this->actingAs($viewer)
        ->{$method}(route($route, $target))
        ->assertForbidden();
})->with([
    'update' => ['patch', 'admin.users.update'],
    'verify' => ['put', 'admin.users.verify'],
    'destroy' => ['delete', 'admin.users.destroy'],
]);

it('still lets a viewer read the list', function (): void {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(PermissionEnum::AccessAdmin->value);

    $this->actingAs($viewer)->get(route('admin.users.index'))->assertOk();
});
