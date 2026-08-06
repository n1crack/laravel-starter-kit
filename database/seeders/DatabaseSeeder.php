<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\SyncRolesAndPermissions;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        resolve(SyncRolesAndPermissions::class)->handle();

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $admin->assignRole(RoleEnum::Admin);

        User::factory(25)
            ->create()
            ->each(fn (User $user): User => $user->assignRole(RoleEnum::User));
    }
}
