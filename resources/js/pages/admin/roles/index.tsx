import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { dashboard } from '@/routes/admin';
import { destroy, store, update } from '@/routes/admin/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';

interface PermissionOption {
    name: string;
    label: string;
}

interface RoleRow {
    id: number;
    name: string;
    users: number;
    permissions: string[];
    protected: boolean;
}

interface Props {
    roles: RoleRow[];
    permissions: PermissionOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: dashboard().url },
    { title: 'Roles', href: '/admin/roles' },
];

function PermissionBoxes({
    permissions,
    selected,
    onToggle,
}: {
    permissions: PermissionOption[];
    selected: string[];
    onToggle: (name: string) => void;
}) {
    return (
        <div className="flex flex-wrap gap-4">
            {permissions.map((permission) => (
                <label
                    key={permission.name}
                    className="flex items-center gap-2 text-sm"
                >
                    <Checkbox
                        checked={selected.includes(permission.name)}
                        onCheckedChange={() => onToggle(permission.name)}
                    />
                    {permission.label}
                </label>
            ))}
        </div>
    );
}

export default function RolesIndex({ roles, permissions }: Props) {
    const [editing, setEditing] = useState<number | null>(null);

    const createForm = useForm<{ name: string; permissions: string[] }>({
        name: '',
        permissions: [],
    });

    const editForm = useForm<{ name: string; permissions: string[] }>({
        name: '',
        permissions: [],
    });

    const toggle = (
        form: typeof createForm,
        current: string[],
        name: string,
    ) => {
        form.setData(
            'permissions',
            current.includes(name)
                ? current.filter((permission) => permission !== name)
                : [...current, name],
        );
    };

    const submitCreate = (event: FormEvent) => {
        event.preventDefault();
        createForm.post(store().url, { onSuccess: () => createForm.reset() });
    };

    const submitEdit = (event: FormEvent, role: RoleRow) => {
        event.preventDefault();
        editForm.patch(update(String(role.id)).url, {
            preserveScroll: true,
            onSuccess: () => setEditing(null),
        });
    };

    const startEditing = (role: RoleRow) => {
        editForm.setData({ name: role.name, permissions: role.permissions });
        setEditing(role.id);
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <form onSubmit={submitCreate} className="space-y-3">
                    <h2 className="text-lg font-medium">Create a role</h2>

                    <div className="flex items-start gap-2">
                        <div className="flex-1 sm:max-w-xs">
                            <Input
                                name="name"
                                value={createForm.data.name}
                                onChange={(event) =>
                                    createForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                                placeholder="Technician"
                            />
                            {createForm.errors.name && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {createForm.errors.name}
                                </p>
                            )}
                        </div>
                        <Button type="submit" disabled={createForm.processing}>
                            Create role
                        </Button>
                    </div>

                    <PermissionBoxes
                        permissions={permissions}
                        selected={createForm.data.permissions}
                        onToggle={(name) =>
                            toggle(
                                createForm,
                                createForm.data.permissions,
                                name,
                            )
                        }
                    />
                </form>

                <ul className="divide-y divide-border rounded-md border">
                    {roles.map((role) => (
                        <li key={role.id} className="px-4 py-3">
                            {editing === role.id ? (
                                <form
                                    onSubmit={(event) =>
                                        submitEdit(event, role)
                                    }
                                    className="space-y-3"
                                >
                                    <div className="flex items-start gap-2">
                                        <Input
                                            value={editForm.data.name}
                                            disabled={role.protected}
                                            onChange={(event) =>
                                                editForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            className="sm:max-w-xs"
                                        />
                                        <Button
                                            type="submit"
                                            disabled={editForm.processing}
                                        >
                                            Save
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => setEditing(null)}
                                        >
                                            Cancel
                                        </Button>
                                    </div>

                                    <PermissionBoxes
                                        permissions={permissions}
                                        selected={editForm.data.permissions}
                                        onToggle={(name) =>
                                            toggle(
                                                editForm,
                                                editForm.data.permissions,
                                                name,
                                            )
                                        }
                                    />
                                </form>
                            ) : (
                                <div className="flex items-center justify-between gap-4">
                                    <div className="min-w-0">
                                        <p className="flex items-center gap-2 font-medium">
                                            {role.name}
                                            {role.protected && (
                                                <Badge variant="outline">
                                                    Required
                                                </Badge>
                                            )}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {role.users} users ·{' '}
                                            {role.permissions.length
                                                ? role.permissions.join(', ')
                                                : 'no permissions'}
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => startEditing(role)}
                                        >
                                            Edit
                                        </Button>
                                        {!role.protected && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.delete(
                                                        destroy(String(role.id))
                                                            .url,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Delete
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            </div>
        </AdminLayout>
    );
}
