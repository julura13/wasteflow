import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import { useMemo, useState, useEffect } from 'react';
import { Plus, Edit, Search, Filter, CheckCircle, X, UserCog } from 'lucide-react';

function formatRoleName(name) {
    return name
        .split('_')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
        .join(' ');
}

export default function UsersIndex({ users, filters }) {
    const { flash } = usePage().props;
    const [showSuccess, setShowSuccess] = useState(false);

    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    const [search, setSearch] = useState(filters?.search || '');
    const [activeFilter, setActiveFilter] = useState(
        filters?.active !== undefined && filters?.active !== '' ? String(filters.active) : ''
    );

    const columns = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: 'Name',
                cell: ({ row }) => {
                    const u = row.original;
                    return (
                        <div className="flex items-center">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-white text-sm font-medium shrink-0">
                                {u.name.charAt(0).toUpperCase()}
                            </div>
                            <span className="ml-3 font-medium text-gray-900 dark:text-gray-100">{u.name}</span>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'email',
                header: 'Email',
                cell: ({ getValue }) => (
                    <span className="text-gray-600 dark:text-gray-400">{getValue()}</span>
                ),
            },
            {
                id: 'roles',
                header: 'Roles',
                cell: ({ row }) => {
                    const u = row.original;
                    const roleList = u.roles?.length ? u.roles.map((r) => r.name) : [];
                    if (roleList.length === 0) {
                        return (
                            <span className="text-gray-400 dark:text-gray-500 text-sm">No roles</span>
                        );
                    }
                    return (
                        <div className="flex flex-wrap gap-1">
                            {roleList.map((name) => (
                                <span
                                    key={name}
                                    className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-200"
                                >
                                    {formatRoleName(name)}
                                </span>
                            ))}
                        </div>
                    );
                },
            },
            {
                id: 'company',
                header: 'Company',
                cell: ({ row }) => {
                    const u = row.original;
                    const company = u.company;
                    return (
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                            {company?.name || '—'}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'is_active',
                header: 'Status',
                cell: ({ getValue }) => {
                    const isActive = getValue();
                    return (
                        <span
                            className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${
                                isActive
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                            }`}
                        >
                            {isActive ? 'Active' : 'Inactive'}
                        </span>
                    );
                },
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const u = row.original;
                    return (
                        <div onClick={(e) => e.stopPropagation()} className="inline-flex">
                            <Link
                                href={`/users/${u.id}/edit`}
                                className="inline-flex items-center text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                                title="Edit user"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <Edit className="h-4 w-4" />
                            </Link>
                        </div>
                    );
                },
            },
        ],
        []
    );

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/users', { search: search || undefined, active: activeFilter || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <DashboardLayout title="Users">
            <Head title="Users" />

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
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        User & role management
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Add WasteFlow staff and assign roles for orders, documents, weights and finalization.
                    </p>
                </div>
                <Link
                    href="/users/create"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Add user
                </Link>
            </div>

            <div className="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <form onSubmit={handleSearch} className="flex gap-4 items-end flex-wrap">
                    <div className="flex-1 min-w-[200px]">
                        <label
                            htmlFor="search"
                            className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
                        >
                            Search
                        </label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-gray-500" />
                            <input
                                type="text"
                                id="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Name or email..."
                            />
                        </div>
                    </div>
                    <div>
                        <label
                            htmlFor="active"
                            className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
                        >
                            Status
                        </label>
                        <select
                            id="active"
                            value={activeFilter}
                            onChange={(e) => setActiveFilter(e.target.value)}
                            className="block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <button
                        type="submit"
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                    >
                        <Filter className="h-4 w-4 mr-2" />
                        Filter
                    </button>
                </form>
            </div>

            <DataTable
                data={users.data}
                columns={columns}
                title="All users"
                pagination={false}
            />

            {users.links && (
                <div className="mt-6 flex items-center justify-between">
                    <div className="text-sm text-gray-700 dark:text-gray-300">
                        Showing {users.from ?? 0} to {users.to ?? 0} of {users.total ?? 0} results
                    </div>
                    <div className="flex space-x-1">
                        {users.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url || '#'}
                                className={`px-3 py-2 text-sm font-medium rounded-md ${
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : link.url
                                        ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </DashboardLayout>
    );
}
