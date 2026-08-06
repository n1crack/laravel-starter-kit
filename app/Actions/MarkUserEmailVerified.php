<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class MarkUserEmailVerified
{
    /**
     * Mark an address verified without the user clicking the link.
     *
     * Support needs this when mail never arrives, so it is deliberately not
     * reversible here: unverifying an account is not a thing an administrator
     * should be able to do by accident.
     *
     * @return bool whether the address needed verifying
     */
    public function handle(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        return $user->markEmailAsVerified();
    }
}
