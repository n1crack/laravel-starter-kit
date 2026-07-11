<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
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
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when($roles->isNotEmpty(), fn ($query) => $query->whereHas(
                'roles', fn ($query) => $query->whereIn('name', $roles),
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
}
