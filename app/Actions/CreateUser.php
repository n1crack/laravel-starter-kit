<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

final readonly class CreateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): User
    {
        $user = User::query()->create([
            ...$attributes,
            'password' => Hash::make($password),
        ]);

        $user->assignRole(Role::User);

        event(new Registered($user));

        return $user;
    }
}
