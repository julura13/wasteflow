import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import { useMemo, useState, useEffect } from 'react';
import { Plus, Edit, CheckCircle, X, Shield } from 'lucide-react';

function formatLabel(name) {
    return name
        .split('-')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
        .join(' ');
}

export default function RolesIndex({ roles }) {
    const { flash } = usePage().props;
    const [showSuccess, setShowSuccess] = useState(false);

    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    const columns = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: 'Role',
                cell: ({ row }) => {
                    const r = row.original;
                    return (
                        <div className="flex items-start whitespace-normal">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-300 shrink-0">
                                <Shield className="h-4 w-4" />
                            </div>
                            <div className="ml-3">
                                <span className="font-medium text-gray-900 dark:text-gray-100">
                                    {formatLabel(r.name)}
                                </span>
                                {r.description && (
                                    <p className="text-xs text-gray-500 dark:text-gray-400 max-w-xs whitespace-normal">{r.description}</p>
                                )}
                            </div>
                        </div>
                    );
                },
            },
            {
                id: 'permissions',
                header: 'Permissions',
                cell: ({ row }) => {
                    const r = row.original;
                    const perms = r.permissions || [];
                    if (perms.length === 0) {
                        return (
                            <span className="text-gray-400 dark:text-gray-500 text-sm">No permissions</span>
                        );
                    }
                    return (
                        <div className="flex flex-wrap items-center gap-1 max-w-md whitespace-normal">
                            {perms.slice(0, 3).map((name) => (
                                <span
                                    key={name}
                                    className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {formatLabel(name)}
                                </span>
                            ))}
                            {perms.length > 3 && (
                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                    +{perms.length - 3} more
                                </span>
                            )}
                        </div>
                    );
                },
            },
            {
                id: 'count',
                header: 'Permissions count',
                cell: ({ row }) => (
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                        {row.original.permissions_count ?? 0}
                    </span>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const r = row.original;
                    return (
                        <Link
                            href={`/roles/${r.id}/edit`}
                            className="inline-flex items-center text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                            title="Edit role"
                        >
                            <Edit className="h-4 w-4" />
                        </Link>
                    );
                },
            },
        ],
        []
    );

    return (
        <DashboardLayout title="Roles">
            <Head title="Roles" />

            {showSuccess && flash?.success && (
                <div className="mb-6 rounded-lg bg-primary-50 border border-primary-200 p-4 dark:bg-primary-900/20 dark:border-primary-800 animate-fade-in">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <CheckCircle className="h-5 w-5 text-primary-600 mr-3 dark:text-primary-400" />
                            <p className="text-sm font-medium text-primary-800 dark:text-primary-200">
                                {flash.success}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowSuccess(false)}
                            className="text-primary-600 hover:text-primary-800 dark:text-primary-400"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                </div>
            )}

            <div className="mb-6 flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Roles</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Create roles and assign permissions. Then assign roles to users.
                    </p>
                </div>
                <Link
                    href="/roles/create"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Add role
                </Link>
            </div>

            <DataTable
                data={roles}
                columns={columns}
                title="All roles"
                pagination={false}
            />
        </DashboardLayout>
    );
}
