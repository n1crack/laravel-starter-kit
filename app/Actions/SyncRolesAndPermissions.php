<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class SyncRolesAndPermissions
{
    /**
     * Mirror the permission catalogue into the database and make sure the two
     * roles the application depends on exist.
     *
     * Roles an administrator created are left alone, and so are the
     * permissions they have since granted or revoked on the protected ones —
     * this only fills in what is missing, so it is safe to run on deploy.
     */
    public function handle(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach (RoleEnum::cases() as $case) {
            $role = Role::findOrCreate($case->value);

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions(array_map(
                    fn (PermissionEnum $permission): string => $permission->value,
                    $case->permissions(),
                ));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
