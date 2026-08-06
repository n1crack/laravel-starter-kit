<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\CreateRole;
use App\Actions\DeleteRole;
use App\Actions\UpdateRole;
use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final readonly class RoleController
{
    public function index(): Response
    {
        return Inertia::render('admin/roles/index', [
            'roles' => Role::query()
                ->withCount('users')
                ->with('permissions:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name'),
                    'protected' => RoleEnum::isProtected($role->name),
                ]),

            'permissions' => collect(PermissionEnum::cases())
                ->map(fn (PermissionEnum $permission): array => [
                    'name' => $permission->value,
                    'label' => $permission->label(),
                ]),
        ]);
    }

    public function store(CreateRoleRequest $request, CreateRole $action): RedirectResponse
    {
        $action->handle(
            $request->string('name')->value(),
            $this->permissions($request->validated('permissions')),
        );

        return to_route('admin.roles.index');
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $action): RedirectResponse
    {
        $action->handle(
            $role,
            $request->string('name')->value(),
            $this->permissions($request->validated('permissions')),
        );

        return to_route('admin.roles.index');
    }

    public function destroy(Role $role, DeleteRole $action): RedirectResponse
    {
        if (! $action->handle($role)) {
            throw ValidationException::withMessages([
                'role' => "The {$role->name} role is required by the application and cannot be deleted.",
            ]);
        }

        return to_route('admin.roles.index');
    }

    /**
     * @return list<string>
     */
    private function permissions(mixed $permissions): array
    {
        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $name): string => is_string($name) ? $name : '',
            $permissions,
        )));
    }
}
