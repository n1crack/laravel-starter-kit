<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
final class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * @use HasFactory<UserFactory>
     */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Whether this user already holds every permission in the given set, so
     * handing it to someone else cannot grant more than this user has.
     *
     * @param  Collection<int, Permission>  $permissions
     */
    public function holdsEveryPermission(Collection $permissions): bool
    {
        return $permissions->pluck('id')
            ->diff($this->getAllPermissions()->pluck('id'))
            ->isEmpty();
    }

    /**
     * Whether this user may change or remove the other: never someone who
     * holds a permission this user lacks.
     */
    public function outranks(self $other): bool
    {
        return $this->holdsEveryPermission($other->getAllPermissions());
    }

    /**
     * Whether this is the only account left holding the administrator role,
     * which the application needs to be able to make another one.
     */
    public function isLastAdministrator(): bool
    {
        return $this->hasRole(Role::Admin->value)
            && self::query()->role(Role::Admin->value)->count() === 1;
    }
}
