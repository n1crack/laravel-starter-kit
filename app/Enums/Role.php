<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The two roles the application itself depends on.
 *
 * Roles are otherwise rows an administrator creates and composes from
 * permissions. These two are seeded and protected from deletion or renaming:
 * `Admin` is what the seeder hands the first account, and `User` is what a
 * registration assigns, so losing either would leave the application unable
 * to make a new administrator or admit a new user.
 */
enum Role: string
{
    case Admin = 'admin';
    case User = 'user';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }

    public static function isProtected(string $name): bool
    {
        return in_array($name, self::names(), true);
    }

    /**
     * Permissions the role is seeded with. An administrator may change them
     * afterwards; only the roles themselves are protected.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::User => [],
        };
    }
}
