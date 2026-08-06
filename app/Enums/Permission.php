<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What the application is able to check.
 *
 * Roles are rows an administrator composes; permissions are not. A permission
 * only means something because code gates on it, so inventing one at runtime
 * would produce a name nothing ever asks about. This enum is the catalogue,
 * and the seeder mirrors it into the database.
 */
enum Permission: string
{
    case AccessAdmin = 'access-admin';
    case ManageUsers = 'manage-users';
    case ManageRoles = 'manage-roles';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::AccessAdmin => 'Access the admin area',
            self::ManageUsers => 'Manage users',
            self::ManageRoles => 'Manage roles',
        };
    }
}
