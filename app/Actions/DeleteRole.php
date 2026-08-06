<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Role;

final readonly class DeleteRole
{
    /**
     * @return bool whether the role could be deleted
     */
    public function handle(Role $role): bool
    {
        if (RoleEnum::isProtected($role->name)) {
            return false;
        }

        $role->delete();

        return true;
    }
}
