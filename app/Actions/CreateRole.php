<?php

declare(strict_types=1);

namespace App\Actions;

use Spatie\Permission\Models\Role;

final readonly class CreateRole
{
    /**
     * @param  list<string>  $permissions
     */
    public function handle(string $name, array $permissions): Role
    {
        $role = Role::findOrCreate($name);
        assert($role instanceof Role);

        $role->syncPermissions($permissions);

        return $role;
    }
}
