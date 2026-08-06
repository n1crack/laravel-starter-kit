import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DataTable,
    type DataTableFeatures,
    DataTablePagination,
    type PaginationLink,
} from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { MultiSelect } from '@/components/ui/multi-select';
import AdminLayout from '@/layouts/admin-layout';
import { dashboard } from '@/routes/admin';
import {
    destroy as destroyUser,
    index as usersIndex,
    store as inviteUser,
    update as updateUser,
    verify as verifyUser,
} from '@/routes/admin/users';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown } from 'lucide-react';
import { type FormEvent, useEffect, useRef, useState } from 'react';

interface UserRow {
    id: number;
    name: string;
    email: string;
    roles: string[];
    verified: boolean;
    createdAt: string;
}

interface Props {
    users: {
        data: UserRow[];
        links: PaginationLink[];
        total: number;
    };
    availableRoles: string[];
    filters: {
        search: string;
        roles: string[];
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: dashboard().url,
    },
    {
        title: 'Users',
        href: usersIndex().url,
    },
];

const buildColumns = (
    availableRoles: string[],
): ColumnDef<DataTableFeatures, UserRow>[] => [
    {
        accessorKey: 'name',
        header: ({ column }) => (
            <Button
                variant="ghost"
                size="sm"
                className="-ml-2"
                onClick={() =>
                    column.toggleSorting(column.getIsSorted() === 'asc')
                }
            >
                Name
                <ArrowUpDown className="ml-1 size-3.5" />
            </Button>
        ),
    },
    {
        accessorKey: 'email',
        header: 'Email',
    },
    {
        accessorKey: 'roles',
        header: 'Roles',
        cell: ({ row }) => (
            <div className="flex gap-1">
                {row.original.roles.map((role) => (
                    <Badge
                        key={role}
                        variant={role === 'admin' ? 'default' : 'secondary'}
                    >
                        {role}
                    </Badge>
                ))}
            </div>
        ),
    },
    {
        accessorKey: 'verified',
        header: 'Verified',
        cell: ({ row }) =>
            row.original.verified ? (
                <Badge variant="outline">Verified</Badge>
            ) : (
                <Badge variant="destructive">Pending</Badge>
            ),
    },
    {
        accessorKey: 'createdAt',
        header: ({ column }) => (
            <Button
                variant="ghost"
                size="sm"
                className="-ml-2"
                onClick={() =>
                    column.toggleSorting(column.getIsSorted() === 'asc')
                }
            >
                Joined
                <ArrowUpDown className="ml-1 size-3.5" />
            </Button>
        ),
    },
    {
        id: 'actions',
        header: 'Manage',
        cell: ({ row }) => (
            <div className="flex items-center justify-end gap-1">
                <MultiSelect
                    options={availableRoles.map((role) => ({
                        value: role,
                        label: role,
                    }))}
                    values={row.original.roles}
                    onValuesChange={(next) =>
                        router.patch(
                            updateUser(row.original.id).url,
                            { roles: next },
                            { preserveScroll: true },
                        )
                    }
                    placeholder="Roles"
                    className="w-40"
                />

                {!row.original.verified && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() =>
                            router.put(
                                verifyUser(row.original.id).url,
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Verify
                    </Button>
                )}

                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() =>
                        router.delete(destroyUser(row.original.id).url, {
                            preserveScroll: true,
                        })
                    }
                >
                    Delete
                </Button>
            </div>
        ),
    },
];

export default function UsersIndex({ users, availableRoles, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [roles, setRoles] = useState<string[]>(filters.roles);
    const isFirstRender = useRef(true);

    const columns = buildColumns(availableRoles);

    const invite = useForm<{ name: string; email: string; roles: string[] }>({
        name: '',
        email: '',
        roles: [],
    });

    const submitInvite = (event: FormEvent) => {
        event.preventDefault();
        invite.post(inviteUser().url, { onSuccess: () => invite.reset() });
    };

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                usersIndex().url,
                {
                    search: search || undefined,
                    roles: roles.length ? roles : undefined,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, roles]);

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search by name or email..."
                        className="sm:max-w-xs"
                    />
                    <MultiSelect
                        options={availableRoles.map((role) => ({
                            value: role,
                            label: role,
                        }))}
                        values={roles}
                        onValuesChange={setRoles}
                        placeholder="Filter by role..."
                        className="sm:max-w-xs"
                    />
                    <p className="text-sm text-muted-foreground sm:ml-auto">
                        {users.total} users
                    </p>
                </div>

                <form
                    onSubmit={submitInvite}
                    className="flex flex-col gap-2 sm:flex-row sm:items-start"
                >
                    <div className="sm:max-w-xs">
                        <Input
                            name="name"
                            value={invite.data.name}
                            onChange={(event) =>
                                invite.setData('name', event.target.value)
                            }
                            placeholder="Name"
                        />
                        {invite.errors.name && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                {invite.errors.name}
                            </p>
                        )}
                    </div>

                    <div className="sm:max-w-xs">
                        <Input
                            name="email"
                            type="email"
                            value={invite.data.email}
                            onChange={(event) =>
                                invite.setData('email', event.target.value)
                            }
                            placeholder="teammate@example.com"
                        />
                        {invite.errors.email && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                {invite.errors.email}
                            </p>
                        )}
                    </div>

                    <MultiSelect
                        options={availableRoles.map((role) => ({
                            value: role,
                            label: role,
                        }))}
                        values={invite.data.roles}
                        onValuesChange={(next) => invite.setData('roles', next)}
                        placeholder="Roles"
                        className="sm:max-w-xs"
                    />

                    <Button type="submit" disabled={invite.processing}>
                        Invite
                    </Button>
                </form>

                <DataTable
                    columns={columns}
                    data={users.data}
                    emptyText="No users found."
                />

                <DataTablePagination links={users.links} />
            </div>
        </AdminLayout>
    );
}
