import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { dashboard } from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    stats: {
        totalUsers: number;
        verifiedUsers: number;
        newUsersThisWeek: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: dashboard().url,
    },
];

export default function AdminDashboard({ stats }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardDescription>Total users</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.totalUsers}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Verified users</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.verifiedUsers}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>New this week</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.newUsersThisWeek}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
