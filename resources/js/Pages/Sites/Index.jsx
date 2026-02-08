import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import { useMemo, useState, useEffect } from 'react';
import { Plus, Edit, Trash2, Eye, Search, Filter, CheckCircle, X, MapPin } from 'lucide-react';

export default function SitesIndex({ sites, companies, branches, filters }) {
    const { flash } = usePage().props;
    const [showSuccess, setShowSuccess] = useState(false);
    const [search, setSearch] = useState(filters.search || '');
    const [companyFilter, setCompanyFilter] = useState(filters.company_id || '');
    const [branchFilter, setBranchFilter] = useState(filters.branch_id || '');

    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    // Filter branches based on selected company
    const filteredBranches = useMemo(() => {
        if (!companyFilter) return branches;
        return branches.filter(b => b.company_id === parseInt(companyFilter));
    }, [companyFilter, branches]);

    const columns = useMemo(() => [
        {
            accessorKey: 'name',
            header: 'Site Name',
        },
        {
            id: 'company',
            header: 'Company / Branch',
            cell: ({ row }) => {
                const site = row.original;
                return (
                    <div className="text-sm">
                        <div className="font-medium text-gray-900">{site.branch?.company?.name || 'N/A'}</div>
                        <div className="text-gray-500">{site.branch?.name || 'Direct site'}</div>
                    </div>
                );
            },
        },
        {
            id: 'contact',
            header: 'Contact',
            cell: ({ row }) => {
                const site = row.original;
                return (
                    <div className="text-sm">
                        <div className="font-medium text-gray-900">{site.contact_person || 'N/A'}</div>
                        <div className="text-gray-500">{site.phone || 'No phone'}</div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'address',
            header: 'Location',
            cell: ({ getValue }) => {
                const address = getValue();
                return address ? (
                    <div className="flex items-start text-sm text-gray-700">
                        <MapPin className="h-4 w-4 mr-1 text-gray-400 mt-0.5 flex-shrink-0" />
                        <span className="line-clamp-2">{address}</span>
                    </div>
                ) : (
                    <span className="text-gray-400">No address</span>
                );
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
                const site = row.original;
                return (
                    <div className="flex space-x-2">
                        <Link
                            href={`/sites/${site.id}`}
                            className="text-primary-600 hover:text-primary-800"
                            title="View"
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                        <Link
                            href={`/sites/${site.id}/edit`}
                            className="text-amber-600 hover:text-amber-800"
                            title="Edit"
                        >
                            <Edit className="h-4 w-4" />
                        </Link>
                        <button
                            onClick={() => handleDelete(site.id)}
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
        if (confirm('Are you sure you want to delete this site? All associated orders will also be deleted.')) {
            router.delete(`/sites/${id}`);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/sites', { 
            search, 
            company_id: companyFilter || undefined,
            branch_id: branchFilter || undefined 
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleCompanyFilter = (companyId) => {
        setCompanyFilter(companyId);
        setBranchFilter(''); // Reset branch filter when company changes
        router.get('/sites', { 
            search, 
            company_id: companyId !== '' ? companyId : undefined 
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleBranchFilter = (branchId) => {
        setBranchFilter(branchId);
        router.get('/sites', { 
            search, 
            company_id: companyFilter || undefined,
            branch_id: branchId !== '' ? branchId : undefined 
        }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <DashboardLayout title="Sites">
            <Head title="Sites" />

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
                    <h2 className="text-2xl font-bold text-gray-900">Site Management</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Manage individual collection sites and their details.
                    </p>
                </div>
                <Link
                    href="/sites/create"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Add Site
                </Link>
            </div>

            {/* Filters */}
            <div className="mb-6 bg-white p-4 rounded-lg shadow">
                <form onSubmit={handleSearch} className="flex gap-4 items-end flex-wrap">
                    <div className="flex-1 min-w-[200px]">
                        <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                id="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="Search sites..."
                            />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="company" className="block text-sm font-medium text-gray-700 mb-1">
                            Company
                        </label>
                        <select
                            id="company"
                            value={companyFilter}
                            onChange={(e) => handleCompanyFilter(e.target.value)}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        >
                            <option value="">All Companies</option>
                            {companies.map((company) => (
                                <option key={company.id} value={company.id}>
                                    {company.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="branch" className="block text-sm font-medium text-gray-700 mb-1">
                            Branch
                        </label>
                        <select
                            id="branch"
                            value={branchFilter}
                            onChange={(e) => handleBranchFilter(e.target.value)}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            disabled={!companyFilter}
                        >
                            <option value="">All Branches</option>
                            {filteredBranches.map((branch) => (
                                <option key={branch.id} value={branch.id}>
                                    {branch.name}
                                </option>
                            ))}
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
                data={sites.data}
                columns={columns}
                title="All Sites"
            />

            {/* Pagination */}
            {sites.links && (
                <div className="mt-6 flex items-center justify-between">
                    <div className="text-sm text-gray-700">
                        Showing {sites.from || 0} to {sites.to || 0} of {sites.total || 0} results
                    </div>
                    <div className="flex space-x-1">
                        {sites.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url || '#'}
                                className={`px-3 py-2 text-sm font-medium rounded-md ${
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : link.url
                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
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
