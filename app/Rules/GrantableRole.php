<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Spatie\Permission\Models\Role;

/**
 * A role may only be handed out by someone who already holds every
 * permission it carries; otherwise managing users would be a way to grant
 * yourself, or an accomplice, more than you have.
 */
final readonly class GrantableRole implements ValidationRule
{
    public function __construct(private ?User $actor) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $role = is_string($value) ? Role::query()->where('name', $value)->first() : null;

        if (! $role instanceof Role) {
            return;
        }

        if (! $this->actor instanceof User || ! $this->actor->holdsEveryPermission($role->permissions)) {
            $fail('You cannot grant a role with permissions you do not hold.');
        }
    }
}
