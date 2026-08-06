<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final readonly class InviteUser
{
    /**
     * Create an account on someone's behalf and let them choose their own
     * password through the ordinary reset flow.
     *
     * The address is left unverified: the reset link proves they can read the
     * mailbox, and clicking it is what confirms the address.
     */
    public function __construct(private SyncUserRoles $syncRoles)
    {
        //
    }

    /**
     * @param  list<string>  $roles
     */
    public function handle(string $name, string $email, array $roles): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Str::password(32),
        ]);

        $this->syncRoles->handle($user, $roles);

        Password::sendResetLink(['email' => $email]);

        return $user;
    }
}
