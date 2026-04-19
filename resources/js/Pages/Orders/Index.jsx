import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import { formatQuantityLineLabel } from '@/utils/orderQuantityLines';
import { formatDateYyyyMmDd } from '@/utils/formatDateYyyyMmDd';
import { useMemo, useState, useEffect, useCallback } from 'react';
import { Plus, Trash2, Eye, Search, Filter, Package, CheckCircle, X, FileDown, FileText, FileSpreadsheet, Pencil, ChevronDown, Loader2, Download } from 'lucide-react';
import axios from 'axios';

export default function OrdersIndex({ orders, filters, serviceProviders = [], userCompanyRoles = {} }) {
    const { flash, auth, errors: pageErrors = {} } = usePage().props;
    const user = auth?.user;
    const [showSuccess, setShowSuccess] = useState(false);
    const [showConsolidatedForm, setShowConsolidatedForm] = useState(false);
    const [consolidatedDate, setConsolidatedDate] = useState('');
    const [consolidatedServiceProvider, setConsolidatedServiceProvider] = useState('');
    const [availableServiceProviders, setAvailableServiceProviders] = useState([]);
    const [loadingProviders, setLoadingProviders] = useState(false);
    const [orderToDelete, setOrderToDelete] = useState(null);
    const [deleteReason, setDeleteReason] = useState('');
    const [deleteReasonDetails, setDeleteReasonDetails] = useState('');
    const [exportOpen, setExportOpen] = useState(false);
    const [exportHideServiceProvider, setExportHideServiceProvider] = useState(false);
    const [orderExportUuid, setOrderExportUuid] = useState(null);
    const [orderExportFormat, setOrderExportFormat] = useState(null);
    const [orderExportStatus, setOrderExportStatus] = useState(null);

    useEffect(() => {
        if (flash?.success && !flash?.order_export_uuid) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    useEffect(() => {
        if (flash?.order_export_uuid) {
            setOrderExportUuid(flash.order_export_uuid);
            setOrderExportFormat(flash.order_export_format ?? null);
            setOrderExportStatus(null);
        }
    }, [flash?.order_export_uuid, flash?.order_export_format]);

    useEffect(() => {
        if (!orderExportUuid) {
            return undefined;
        }

        let intervalId;
        const poll = async () => {
            try {
                const { data } = await axios.get(route('orders.export.status', orderExportUuid));
                setOrderExportStatus(data);
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(intervalId);
                }
            } catch {
                clearInterval(intervalId);
                setOrderExportStatus({
                    status: 'failed',
                    error_message: 'Could not check export status. Please refresh the page.',
                });
            }
        };

        poll();
        intervalId = setInterval(poll, 2500);

        return () => clearInterval(intervalId);
    }, [orderExportUuid]);

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
    const [statusFilter, setStatusFilter] = useState(filters.status ?? '');
    const [requestedCollectionFrom, setRequestedCollectionFrom] = useState(
        filters.requested_collection_from || ''
    );
    const [requestedCollectionTo, setRequestedCollectionTo] = useState(filters.requested_collection_to || '');
    const [orderTypeWaste, setOrderTypeWaste] = useState(
        () => !filters.order_types?.length || filters.order_types.includes('waste')
    );
    const [orderTypeRecycling, setOrderTypeRecycling] = useState(
        () => !filters.order_types?.length || filters.order_types.includes('recycling')
    );

    useEffect(() => {
        setRequestedCollectionFrom(filters.requested_collection_from || '');
        setRequestedCollectionTo(filters.requested_collection_to || '');
    }, [filters.requested_collection_from, filters.requested_collection_to]);

    useEffect(() => {
        setStatusFilter(filters.status ?? '');
    }, [filters.status]);

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
            accessorKey: 'slip_number',
            header: 'Slip number',
            cell: ({ getValue }) => {
                const slip = getValue();
                return slip ? (
                    <span className="text-sm text-gray-900 dark:text-gray-100 font-mono">{slip}</span>
                ) : (
                    <span className="text-sm text-gray-400 dark:text-gray-500">—</span>
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
                                {requestedDate ? formatDateYyyyMmDd(requestedDate) : 'N/A'}
                            </span>
                        </div>
                        <div className="flex flex-col mt-1">
                            <span className="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                Actual:
                            </span>
                            <span className="text-gray-900 dark:text-gray-100">
                                {actualDate ? formatDateYyyyMmDd(actualDate) : 'N/A'}
                            </span>
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'collection_quantities',
            header: 'Collection quantities',
            cell: ({ row }) => {
                const order = row.original;
                const lines = order.quantity_lines;

                if (Array.isArray(lines) && lines.length > 0) {
                    return (
                        <div className="flex flex-col gap-0.5 text-sm">
                            {lines.map((line, index) => (
                                <span
                                    key={index}
                                    className="text-gray-900 dark:text-gray-100"
                                >
                                    {formatQuantityLineLabel(line)}
                                </span>
                            ))}
                        </div>
                    );
                }

                if (order.quantity != null && order.quantity_type) {
                    return (
                        <div className="flex flex-col text-sm">
                            <span className="text-gray-900 dark:text-gray-100">
                                {formatQuantityLineLabel({
                                    quantity: order.quantity,
                                    quantity_type: order.quantity_type,
                                })}
                            </span>
                        </div>
                    );
                }

                return (
                    <span className="text-sm text-gray-400 dark:text-gray-500">—</span>
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
                        {canManageOrder && (order.status === 'pending' || order.status === 'scheduled') && (
                            <Link
                                href={`/orders/${order.id}/edit`}
                                className="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                title="Edit order (quantity or grade)"
                            >
                                <Pencil className="h-4 w-4" />
                            </Link>
                        )}
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
                                onClick={() => {
                                    setOrderToDelete(order);
                                    setDeleteReason('');
                                    setDeleteReasonDetails('');
                                }}
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

    const deleteReasonOptions = [
        { value: 'incorrect_order', label: 'Incorrect order' },
        { value: 'duplicate', label: 'Duplicate order' },
        { value: 'wrong_date', label: 'Wrong collection date' },
        { value: 'wrong_site', label: 'Wrong site / collection point' },
        { value: 'cancelled_by_client', label: 'Cancelled by client' },
        { value: 'other', label: 'Other' },
    ];

    const handleDeleteOrder = (e) => {
        e.preventDefault();
        if (!orderToDelete || !deleteReason) return;
        if (deleteReason === 'other' && !deleteReasonDetails.trim()) return;
        router.post(`/orders/${orderToDelete.id}/delete`, {
            reason: deleteReason,
            reason_details: deleteReason === 'other' ? deleteReasonDetails.trim() : '',
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setOrderToDelete(null);
                setDeleteReason('');
                setDeleteReasonDetails('');
            },
        });
    };

    const closeDeleteModal = () => {
        setOrderToDelete(null);
        setDeleteReason('');
        setDeleteReasonDetails('');
    };

    const getOrderTypesParam = () => {
        const types = [];
        if (orderTypeWaste) types.push('waste');
        if (orderTypeRecycling) types.push('recycling');
        return types;
    };

    const buildFilterParams = (orderTypesOverride) => {
        const params = {
            search: search || undefined,
            status: statusFilter,
            requested_collection_from: requestedCollectionFrom || undefined,
            requested_collection_to: requestedCollectionTo || undefined,
        };
        const types = orderTypesOverride ?? getOrderTypesParam();
        if (types.length === 1) {
            params.order_types = types;
        } else if (types.length === 2) {
            params.order_types = ['waste', 'recycling'];
        }
        return params;
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/orders', buildFilterParams(), {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusFilter = (newStatus) => {
        setStatusFilter(newStatus);
        const types = getOrderTypesParam();
        const params = {
            search: search || undefined,
            status: newStatus,
            requested_collection_from: requestedCollectionFrom || undefined,
            requested_collection_to: requestedCollectionTo || undefined,
        };
        if (types.length === 1) {
            params.order_types = types;
        } else if (types.length === 2) {
            params.order_types = ['waste', 'recycling'];
        }
        router.get('/orders', params, {
            preserveState: true,
            replace: true,
        });
    };

    const handleOrderTypeChange = (waste, recycling) => {
        setOrderTypeWaste(waste);
        setOrderTypeRecycling(recycling);
        const types = [];
        if (waste) types.push('waste');
        if (recycling) types.push('recycling');
        router.get('/orders', buildFilterParams(types), { preserveState: true, replace: true });
    };

    const buildExportPostBody = useCallback(
        (format) => {
            const data = { format };
            if (search) {
                data.search = search;
            }
            if (statusFilter !== '') {
                data.status = statusFilter;
            }
            if (requestedCollectionFrom) {
                data.requested_collection_from = requestedCollectionFrom;
            }
            if (requestedCollectionTo) {
                data.requested_collection_to = requestedCollectionTo;
            }
            const types = [];
            if (orderTypeWaste) {
                types.push('waste');
            }
            if (orderTypeRecycling) {
                types.push('recycling');
            }
            if (types.length === 1) {
                data.order_types = types;
            } else if (types.length === 2) {
                data.order_types = ['waste', 'recycling'];
            }
            if (exportHideServiceProvider) {
                data.hide_service_provider = true;
            }
            return data;
        },
        [
            search,
            statusFilter,
            requestedCollectionFrom,
            requestedCollectionTo,
            orderTypeWaste,
            orderTypeRecycling,
            exportHideServiceProvider,
        ],
    );

    const requestOrderExport = useCallback(
        (format) => {
            setOrderExportStatus(null);
            router.post(route('orders.export.request'), buildExportPostBody(format), { preserveScroll: true });
        },
        [buildExportPostBody],
    );

    const orderExportIsProcessing =
        orderExportUuid &&
        (!orderExportStatus || orderExportStatus.status === 'pending' || orderExportStatus.status === 'processing');

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

            {/* Error Message (e.g. delete validation or status) */}
            {(flash?.error || pageErrors?.reason || pageErrors?.reason_details) && (
                <div className="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                    <p className="text-sm font-medium text-red-800 dark:text-red-200">
                        {flash?.error || pageErrors?.reason || pageErrors?.reason_details}
                    </p>
                </div>
            )}

            {(orderExportUuid || flash?.order_export_uuid) && (
                <div className="mb-6 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/25 p-4 space-y-3">
                    <p className="text-sm text-green-900 dark:text-green-100">
                        Export queued using your current filters. Large exports run in the background—download below when
                        the button appears.
                    </p>
                    {orderExportIsProcessing && (
                        <div className="flex items-center gap-2 text-sm text-green-800 dark:text-green-200">
                            <Loader2 className="h-4 w-4 animate-spin shrink-0" />
                            <span>
                                Generating {orderExportFormat === 'csv' ? 'CSV' : 'PDF'} in the background… This
                                section will update when the file is ready.
                            </span>
                        </div>
                    )}
                    {orderExportStatus?.status === 'completed' && orderExportStatus.download_url && (
                        <div>
                            <a
                                href={orderExportStatus.download_url}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <Download className="h-4 w-4 mr-2" />
                                Download {orderExportFormat === 'csv' ? 'CSV' : 'PDF'}
                            </a>
                        </div>
                    )}
                    {orderExportStatus?.status === 'failed' && (
                        <p className="text-sm text-red-700 dark:text-red-300">
                            {orderExportStatus.error_message ||
                                'Export failed. Please try again.'}
                        </p>
                    )}
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

            {/* Delete Order Modal */}
            {orderToDelete && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
                        <div className="mt-3">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Delete order {orderToDelete.tracking_number}
                                </h3>
                                <button
                                    type="button"
                                    onClick={closeDeleteModal}
                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                This action cannot be undone. Please select a reason for deleting this order.
                            </p>
                            <form onSubmit={handleDeleteOrder} className="space-y-4">
                                <div>
                                    <label htmlFor="delete_reason" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                        Reason *
                                    </label>
                                    <select
                                        id="delete_reason"
                                        value={deleteReason}
                                        onChange={(e) => setDeleteReason(e.target.value)}
                                        required
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    >
                                        <option value="">Select a reason</option>
                                        {deleteReasonOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                    {pageErrors.reason && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{pageErrors.reason}</p>
                                    )}
                                </div>
                                {deleteReason === 'other' && (
                                    <div>
                                        <label htmlFor="delete_reason_details" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                            Details *
                                        </label>
                                        <textarea
                                            id="delete_reason_details"
                                            rows={3}
                                            value={deleteReasonDetails}
                                            onChange={(e) => setDeleteReasonDetails(e.target.value)}
                                            placeholder="Please provide details..."
                                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        />
                                        {pageErrors.reason_details && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{pageErrors.reason_details}</p>
                                        )}
                                    </div>
                                )}
                                <div className="flex justify-end gap-3 pt-4">
                                    <button
                                        type="button"
                                        onClick={closeDeleteModal}
                                        className="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 dark:hover:bg-gray-600"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={!deleteReason || (deleteReason === 'other' && !deleteReasonDetails.trim())}
                                        className="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Delete order
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Filters */}
            <div className="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <form onSubmit={handleSearch} className="space-y-4">
                    <div className="flex flex-wrap gap-4 items-end">
                        <div className="flex-1 min-w-[200px]">
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
                                    placeholder="Tracking number, slip number, company, branch, site, service provider…"
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
                        <div>
                            <span className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Order Type</span>
                            <div className="flex gap-4">
                                <label className="inline-flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={orderTypeWaste}
                                        onChange={(e) => handleOrderTypeChange(e.target.checked, orderTypeRecycling)}
                                        className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                    <span className="text-sm text-gray-700 dark:text-gray-300">Waste Order</span>
                                </label>
                                <label className="inline-flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={orderTypeRecycling}
                                        onChange={(e) => handleOrderTypeChange(orderTypeWaste, e.target.checked)}
                                        className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                    <span className="text-sm text-gray-700 dark:text-gray-300">Recycling Order</span>
                                </label>
                            </div>
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Both selected = show all</p>
                        </div>
                        <button
                            type="submit"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            <Filter className="h-4 w-4 mr-2" />
                            Filter
                        </button>
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setExportOpen((o) => !o)}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Export
                                <ChevronDown className="ml-2 h-4 w-4" />
                            </button>
                            {exportOpen && (
                                <>
                                    <div className="fixed inset-0 z-10" onClick={() => setExportOpen(false)} aria-hidden="true" />
                                    <div className="absolute right-0 mt-1 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-20">
                                        <div className="px-3 py-2 border-b border-gray-200 dark:border-gray-600">
                                            <label className="flex items-start gap-2 cursor-pointer text-left">
                                                <input
                                                    type="checkbox"
                                                    checked={exportHideServiceProvider}
                                                    onChange={(e) => setExportHideServiceProvider(e.target.checked)}
                                                    className="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <span className="text-xs text-gray-700 dark:text-gray-200 leading-snug">
                                                    Hide service provider column
                                                </span>
                                            </label>
                                        </div>
                                        <div className="py-1">
                                            <button
                                                type="button"
                                                className="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-left"
                                                onClick={() => {
                                                    requestOrderExport('pdf');
                                                    setExportOpen(false);
                                                }}
                                            >
                                                <FileText className="h-4 w-4 mr-2 text-red-600 shrink-0" />
                                                Export to PDF
                                            </button>
                                            <button
                                                type="button"
                                                className="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-left"
                                                onClick={() => {
                                                    requestOrderExport('csv');
                                                    setExportOpen(false);
                                                }}
                                            >
                                                <FileSpreadsheet className="h-4 w-4 mr-2 text-green-600 shrink-0" />
                                                Export to CSV
                                            </button>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-4 items-end pt-4 border-t border-gray-200 dark:border-gray-600">
                        <div className="w-full sm:w-auto min-w-[160px]">
                            <label
                                htmlFor="requested_collection_from"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
                            >
                                Requested collection from
                            </label>
                            <input
                                type="date"
                                id="requested_collection_from"
                                value={requestedCollectionFrom}
                                onChange={(e) => setRequestedCollectionFrom(e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <div className="w-full sm:w-auto min-w-[160px]">
                            <label
                                htmlFor="requested_collection_to"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
                            >
                                Requested collection to
                            </label>
                            <input
                                type="date"
                                id="requested_collection_to"
                                value={requestedCollectionTo}
                                onChange={(e) => setRequestedCollectionTo(e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400 max-w-md pb-1 sm:pb-0 sm:self-end">
                            Filters by each order&apos;s <span className="font-medium text-gray-600 dark:text-gray-300">requested</span> collection date (inclusive). Leave both blank for no date limit, then click Filter.
                        </p>
                    </div>
                </form>
            </div>

            {ordersContent}
        </DashboardLayout>
    );
}
