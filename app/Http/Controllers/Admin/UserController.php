<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteUser;
use App\Actions\InviteUser;
use App\Actions\MarkUserEmailVerified;
use App\Actions\SyncUserRoles;
use App\Http\Requests\InviteUserRequest;
use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final readonly class UserController
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $roles = $request->collect('roles')->filter()->values();

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when($roles->isNotEmpty(), fn (Builder $query): Builder => $query->whereHas(
                'roles', fn (Builder $query): Builder => $query->whereIn('name', $roles),
            ))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'verified' => $user->email_verified_at !== null,
                'createdAt' => $user->created_at->toDateString(),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'availableRoles' => Role::query()->pluck('name'),
            'filters' => [
                'search' => $search,
                'roles' => $roles,
            ],
        ]);
    }

    public function store(InviteUserRequest $request, InviteUser $action): RedirectResponse
    {
        $action->handle(
            $request->string('name')->value(),
            $request->string('email')->value(),
            $this->roles($request->validated('roles')),
        );

        return to_route('admin.users.index');
    }

    public function update(UpdateUserRolesRequest $request, User $user, SyncUserRoles $action): RedirectResponse
    {
        $action->handle($user, $this->roles($request->validated('roles')));

        return to_route('admin.users.index');
    }

    public function verify(User $user, MarkUserEmailVerified $action): RedirectResponse
    {
        $action->handle($user);

        return to_route('admin.users.index');
    }

    /**
     * An administrator deleting their own account here would lock themselves
     * out mid-session; the profile page is where that belongs. Nobody removes
     * someone holding a permission they lack, and the last administrator
     * stays, or nobody could make another one.
     */
    public function destroy(User $user, #[CurrentUser] User $current, DeleteUser $action): RedirectResponse
    {
        if ($user->is($current)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account from here.',
            ]);
        }

        abort_unless($current->outranks($user), 403);

        if ($user->isLastAdministrator()) {
            throw ValidationException::withMessages([
                'user' => 'The last administrator cannot be deleted.',
            ]);
        }

        $action->handle($user);

        return to_route('admin.users.index');
    }

    /**
     * @return list<string>
     */
    private function roles(mixed $roles): array
    {
        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $name): string => is_string($name) ? $name : '',
            $roles,
        )));
    }
}
