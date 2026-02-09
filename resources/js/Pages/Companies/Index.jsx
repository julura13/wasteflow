import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import { useMemo, useState, useEffect } from 'react';
import { Plus, Edit, Trash2, Eye, Search, Filter, CheckCircle, X } from 'lucide-react';

export default function CompaniesIndex({ companies, filters }) {
    const { flash } = usePage().props;
    const [showSuccess, setShowSuccess] = useState(false);

    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status !== undefined ? filters.status : '');

    const columns = useMemo(() => [
        {
            accessorKey: 'name',
            header: 'Company Name',
            cell: ({ row }) => {
                const company = row.original;
                return (
                    <Link
                        href={`/companies/${company.id}`}
                        className="text-primary-600 hover:text-primary-800 font-medium"
                    >
                        {company.name}
                    </Link>
                );
            },
        },
        {
            accessorKey: 'email',
            header: 'Email',
        },
        {
            id: 'contact',
            header: 'Contact',
            cell: ({ row }) => {
                const company = row.original;
                return (
                    <div className="text-sm">
                        <div className="font-medium text-gray-900 dark:text-gray-100">{company.contact_person || 'N/A'}</div>
                        <div className="text-gray-500 dark:text-gray-400">{company.phone || 'No phone'}</div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'branches_count',
            header: 'Branches',
            cell: ({ row }) => {
                const company = row.original;
                const count = company.branches ? company.branches.length : 0;
                return (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        {count} {count === 1 ? 'branch' : 'branches'}
                    </span>
                );
            },
        },
        {
            id: 'rebate_percentage',
            header: 'Rebate %',
            cell: ({ row }) => {
                const company = row.original;
                const pct = company.rebate_percentage;
                if (pct === null || pct === undefined || pct === '') return <span className="text-gray-400 dark:text-gray-500">—</span>;
                return <span className="text-sm text-gray-900 dark:text-gray-100">{Number(pct).toFixed(2)}%</span>;
            },
        },
        {
            accessorKey: 'is_active',
            header: 'Status',
            cell: ({ getValue }) => {
                const isActive = getValue();
                return (
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                        isActive 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                    }`}>
                        {isActive ? 'Active' : 'Inactive'}
                    </span>
                );
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => {
                const company = row.original;
                return (
                    <div className="flex space-x-2">
                        <Link
                            href={`/companies/${company.id}`}
                            className="text-primary-600 hover:text-primary-800"
                            title="View"
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                        <Link
                            href={`/companies/${company.id}/edit`}
                            className="text-amber-600 hover:text-amber-800"
                            title="Edit"
                        >
                            <Edit className="h-4 w-4" />
                        </Link>
                        <button
                            onClick={() => handleDelete(company.id)}
                            className="text-red-600 hover:text-red-800"
                            title="Delete"
                        >
                            <Trash2 className="h-4 w-4" />
                        </button>
                    </div>
                );
            },
        },
    ], []);

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this company?')) {
            router.delete(`/companies/${id}`);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/companies', { search, status: statusFilter || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusFilter = (status) => {
        setStatusFilter(status);
        router.get('/companies', { search, status: status !== '' ? status : undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <DashboardLayout title="Companies">
            <Head title="Companies" />

            {/* Success Message */}
            {showSuccess && flash?.success && (
                <div className="mb-6 rounded-lg bg-primary-50 border border-primary-200 p-4 animate-fade-in">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <CheckCircle className="h-5 w-5 text-primary-600 mr-3" />
                            <p className="text-sm font-medium text-primary-800">{flash.success}</p>
                        </div>
                        <button
                            onClick={() => setShowSuccess(false)}
                            className="text-primary-600 hover:text-primary-800"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                </div>
            )}

            <div className="mb-6 flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Company Management</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage your waste management clients and their information.
                    </p>
                </div>
                <Link
                    href="/companies/create"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Add Company
                </Link>
            </div>

            {/* Filters */}
            <div className="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <form onSubmit={handleSearch} className="flex gap-4 items-end">
                    <div className="flex-1">
                        <label htmlFor="search" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Search
                        </label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-gray-500" />
                            <input
                                type="text"
                                id="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-400"
                                placeholder="Search companies..."
                            />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Status
                        </label>
                        <select
                            id="status"
                            value={statusFilter}
                            onChange={(e) => handleStatusFilter(e.target.value)}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <button
                        type="submit"
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <Filter className="h-4 w-4 mr-2" />
                        Filter
                    </button>
                </form>
            </div>

            <DataTable
                data={companies.data}
                columns={columns}
                title="All Companies"
            />

            {/* Pagination */}
            {companies.links && (
                <div className="mt-6 flex items-center justify-between">
                    <div className="text-sm text-gray-700 dark:text-gray-300">
                        Showing {companies.from || 0} to {companies.to || 0} of {companies.total || 0} results
                    </div>
                    <div className="flex space-x-1">
                        {companies.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url || '#'}
                                className={`px-3 py-2 text-sm font-medium rounded-md ${
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : link.url
                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 dark:hover:bg-gray-600'
                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-500'
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
