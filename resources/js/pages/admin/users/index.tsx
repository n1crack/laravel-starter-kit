import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DataTable,
    DataTablePagination,
    type PaginationLink,
} from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { MultiSelect } from '@/components/ui/multi-select';
import AdminLayout from '@/layouts/admin-layout';
import { dashboard } from '@/routes/admin';
import { index as usersIndex } from '@/routes/admin/users';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

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

const columns: ColumnDef<UserRow>[] = [
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
];

export default function UsersIndex({ users, availableRoles, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [roles, setRoles] = useState<string[]>(filters.roles);
    const isFirstRender = useRef(true);

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
