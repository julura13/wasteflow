import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import { useMemo, useState, useEffect } from 'react';
import { Plus, Trash2, Eye, Search, Filter, Package, CheckCircle, X, FileDown, FileText, Inbox } from 'lucide-react';
import axios from 'axios';

export default function OrdersIndex({ orders, filters, serviceProviders = [], userCompanyRoles = {} }) {
    const { flash, auth } = usePage().props;
    const user = auth?.user;
    const [showSuccess, setShowSuccess] = useState(false);
    const [showConsolidatedForm, setShowConsolidatedForm] = useState(false);
    const [consolidatedDate, setConsolidatedDate] = useState('');
    const [consolidatedServiceProvider, setConsolidatedServiceProvider] = useState('');
    const [availableServiceProviders, setAvailableServiceProviders] = useState([]);
    const [loadingProviders, setLoadingProviders] = useState(false);

    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    // Fetch service providers when date changes
    useEffect(() => {
        if (consolidatedDate && showConsolidatedForm) {
            setLoadingProviders(true);
            setConsolidatedServiceProvider(''); // Clear selection when date changes

            axios.get('/orders/service-providers-by-date', {
                params: {
                    collection_date: consolidatedDate,
                },
            })
            .then((response) => {
                setAvailableServiceProviders(response.data);
            })
            .catch((error) => {
                console.error('Error fetching service providers:', error);
                setAvailableServiceProviders([]);
            })
            .finally(() => {
                setLoadingProviders(false);
            });
        } else {
            setAvailableServiceProviders([]);
        }
    }, [consolidatedDate, showConsolidatedForm]);
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const columns = useMemo(() => [
        {
            accessorKey: 'tracking_number',
            header: 'Tracking Number',
            cell: ({ getValue, row }) => {
                const trackingNumber = getValue();
                const order = row.original;
                return (
                    <Link
                        href={`/orders/${order.id}`}
                        className="text-primary-600 hover:text-primary-800 font-medium"
                    >
                        {trackingNumber}
                    </Link>
                );
            },
        },
        {
            accessorKey: 'site.name',
            header: 'Company / Branch / Site',
            cell: ({ row }) => {
                const order = row.original;
                const site = order.site;
                const branch = site?.branch ?? order.branch;
                const company = branch?.company ?? order.company;

                return (
                    <div className="flex flex-col text-sm">
                        {company?.name && (
                            <span className="font-semibold text-gray-900 dark:text-gray-100">
                                {company.name}
                            </span>
                        )}
                        {branch?.name && (
                            <span className="text-gray-700 dark:text-gray-300">
                                {branch.name}
                            </span>
                        )}
                        {site?.name && (
                            <span className="text-gray-600 dark:text-gray-400">
                                {site.name}
                            </span>
                        )}
                        {!site && !company?.name && !branch?.name && (
                            <span className="text-gray-400 dark:text-gray-500 italic">N/A</span>
                        )}
                        {!site && (company?.name || branch?.name) && (
                            <span className="text-gray-500 dark:text-gray-400 italic">No collection point</span>
                        )}
                    </div>
                );
            },
        },
        {
            accessorKey: 'service_provider.name',
            header: 'Service Provider',
            cell: ({ row }) => {
                const order = row.original;
                return order.service_provider?.name || order.service_provider || 'N/A';
            },
        },
        {
            id: 'order_info',
            header: 'Type / Status',
            cell: ({ row }) => {
                const order = row.original;
                const type = order.order_type;
                const status = order.status;

                const statusColors = {
                    pending: 'bg-yellow-100 text-yellow-800',
                    scheduled: 'bg-primary-100 text-primary-800',
                    weight_required: 'bg-orange-100 text-orange-800',
                    documents_required: 'bg-blue-100 text-blue-800',
                    finalized: 'bg-green-100 text-green-800',
                };

                return (
                    <div className="flex flex-col gap-1.5">
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                            type === 'waste'
                                ? 'bg-orange-100 text-orange-800'
                                : 'bg-green-100 text-green-800'
                        }`}>
                            {type === 'waste' ? 'Waste Order' : 'Recycling Order'}
                        </span>
                        <span className={`px-2 py-1 text-xs font-medium rounded-full capitalize ${statusColors[status]}`}>
                            {status}
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'collection_dates',
            header: 'Collection Dates',
            cell: ({ row }) => {
                const order = row.original;
                const requestedDate = order.requested_collection_date;
                const actualDate = order.actual_collection_date;

                return (
                    <div className="flex flex-col text-sm">
                        <div className="flex flex-col">
                            <span className="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                Requested:
                            </span>
                            <span className="text-gray-900 dark:text-gray-100">
                                {requestedDate ? new Date(requestedDate).toLocaleDateString() : 'N/A'}
                            </span>
                        </div>
                        <div className="flex flex-col mt-1">
                            <span className="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                Actual:
                            </span>
                            <span className="text-gray-900 dark:text-gray-100">
                                {actualDate ? new Date(actualDate).toLocaleDateString() : 'N/A'}
                            </span>
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => {
                const order = row.original;
                const companyId = order.site?.branch?.company?.id ?? order.company?.id ?? order.company_id;
                const userRole = user?.is_admin ? 'admin' : (companyId ? userCompanyRoles[companyId] : null);
                const canManageOrder = user?.is_admin || userRole === 'manager';
                
                return (
                    <div className="flex space-x-2">
                        <Link
                            href={`/orders/${order.id}`}
                            className="text-primary-600 hover:text-primary-800"
                            title="View Order"
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                        {order.status === 'documents_required' && (
                            <Link
                                href={`/orders/${order.id}/finalize`}
                                className="text-green-600 hover:text-green-800"
                                title="Finalize Order"
                            >
                                <CheckCircle className="h-4 w-4" />
                            </Link>
                        )}
                        <a
                            href={`/orders/${order.id}/download-pdf`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-green-600 hover:text-green-800"
                            title="Download PDF"
                        >
                            <FileDown className="h-4 w-4" />
                        </a>
                        {canManageOrder && (
                            <button
                                onClick={() => handleDelete(order.id)}
                                className="text-red-600 hover:text-red-800"
                                title="Delete Order"
                            >
                                <Trash2 className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                );
            },
        },
    ], [user, userCompanyRoles]);

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this order?')) {
            router.delete(`/orders/${id}`);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/orders', { search, status: statusFilter || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusFilter = (status) => {
        setStatusFilter(status);
        router.get('/orders', { search, status: status !== '' ? status : undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleGenerateConsolidatedPDF = (e) => {
        e.preventDefault();
        if (!consolidatedDate || !consolidatedServiceProvider) {
            alert('Please select both collection date and service provider.');
            return;
        }

        const params = new URLSearchParams({
            collection_date: consolidatedDate,
            service_provider_id: consolidatedServiceProvider,
        });

        const url = `/orders/consolidated-pdf?${params.toString()}`;

        // Open in new window to download PDF
        window.open(url, '_blank');
        setShowConsolidatedForm(false);
        setConsolidatedDate('');
        setConsolidatedServiceProvider('');
        setAvailableServiceProviders([]);
    };

    const ordersContent = orders.data?.length === 0 ? (
        <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
            <Package className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
            <h3 className="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">No orders</h3>
            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new order.</p>
            <Link
                href="/orders/create"
                className="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
            >
                <Plus className="h-4 w-4 mr-2" />
                Create Order
            </Link>
        </div>
    ) : (
        <>
            <DataTable
                data={orders.data || []}
                columns={columns}
                title="All Orders"
                pagination={false}
            />

            {/* Pagination */}
            {orders.links && (
                <div className="mt-6 flex items-center justify-between">
                    <div className="text-sm text-gray-700 dark:text-gray-300">
                        Showing {orders.from || 0} to {orders.to || 0} of {orders.total || 0} results
                    </div>
                    <div className="flex space-x-1">
                        {orders.links.map((link, index) => (
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
        </>
    );

    return (
        <DashboardLayout title="Orders">
            <Head title="Orders" />

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
                    <h2 className="text-2xl font-bold text-gray-900">Order Management</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Track and manage waste collection orders and their processing status.
                    </p>
                </div>
                <div className="flex gap-3">
                    <button
                        onClick={() => setShowConsolidatedForm(true)}
                        className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        <FileText className="h-4 w-4 mr-2" />
                        Schedule Orders
                    </button>
                    <Link
                        href="/orders/create"
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Create Order
                    </Link>
                </div>
            </div>

            {/* Consolidated PDF Modal */}
            {showConsolidatedForm && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                        <div className="mt-3">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Generate Consolidated PDF
                                </h3>
                                <button
                                    onClick={() => {
                                        setShowConsolidatedForm(false);
                                        setConsolidatedDate('');
                                        setConsolidatedServiceProvider('');
                                        setAvailableServiceProviders([]);
                                    }}
                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            <form onSubmit={handleGenerateConsolidatedPDF} className="space-y-4">
                                <div>
                                    <label htmlFor="collection_date" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                        Collection Date *
                                    </label>
                                    <input
                                        type="date"
                                        id="collection_date"
                                        value={consolidatedDate}
                                        onChange={(e) => setConsolidatedDate(e.target.value)}
                                        required
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="service_provider" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                        Service Provider *
                                    </label>
                                    <select
                                        id="service_provider"
                                        value={consolidatedServiceProvider}
                                        onChange={(e) => setConsolidatedServiceProvider(e.target.value)}
                                        required
                                        disabled={!consolidatedDate || loadingProviders}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 disabled:bg-gray-100 disabled:cursor-not-allowed dark:disabled:bg-gray-600"
                                    >
                                        <option value="">
                                            {!consolidatedDate
                                                ? 'Please select a date first'
                                                : loadingProviders
                                                    ? 'Loading...'
                                                    : availableServiceProviders.length === 0
                                                        ? 'No service providers found for this date'
                                                        : 'Select Service Provider'}
                                        </option>
                                        {availableServiceProviders.map((provider) => (
                                            <option key={provider.id} value={provider.id}>
                                                {provider.name}
                                            </option>
                                        ))}
                                    </select>
                                    {consolidatedDate && !loadingProviders && availableServiceProviders.length === 0 && (
                                        <p className="mt-1 text-sm text-amber-600 dark:text-amber-400">
                                            No orders found for the selected date.
                                        </p>
                                    )}
                                </div>
                                <div className="flex justify-end gap-3 pt-4">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowConsolidatedForm(false);
                                            setConsolidatedDate('');
                                            setConsolidatedServiceProvider('');
                                            setAvailableServiceProviders([]);
                                        }}
                                        className="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 dark:hover:bg-gray-600"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        className="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                    >
                                        Generate PDF
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

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
                                placeholder="Search by tracking number, site, or slip number..."
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
                            <option value="pending">Pending</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="weight_required">Weight Required</option>
                            <option value="documents_required">Documents Required</option>
                            <option value="finalized">Finalized</option>
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

            {ordersContent}
        </DashboardLayout>
    );
}
