<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Role;

final readonly class UpdateRole
{
    /**
     * Rename a role and set the permissions it grants.
     *
     * The application depends on the protected roles by name, so those keep
     * theirs; their permissions are still an administrator's to change.
     *
     * @param  list<string>  $permissions
     */
    public function handle(Role $role, string $name, array $permissions): Role
    {
        if (! RoleEnum::isProtected($role->name)) {
            $role->update(['name' => $name]);
        }

        $role->syncPermissions($permissions);

        return $role;
    }
}
