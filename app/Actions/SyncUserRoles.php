<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class SyncUserRoles
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user;
    }
}
