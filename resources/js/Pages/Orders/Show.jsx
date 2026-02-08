import { Head, Link, usePage, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import Modal from '@/Components/Modal';
import { ArrowLeft, CheckCircle, Download, File, FileDown, Clock, PlayCircle, Package, FileText, AlertTriangle } from 'lucide-react';
import { useState } from 'react';

export default function Show({ order, canManageOrder = true }) {
    const { flash, errors } = usePage().props;
    const [updatingStatus, setUpdatingStatus] = useState(false);
    const [showStatusModal, setShowStatusModal] = useState(false);

    const getNextStatus = () => {
        const statusMap = {
            'pending': 'scheduled',
            'scheduled': 'weight_required',
            'weight_required': 'documents_required',
        };
        return statusMap[order.status];
    };

    const getStatusButtonLabel = () => {
        const labels = {
            'pending': 'Schedule Order',
            'scheduled': 'Mark as Weight Required',
            'weight_required': 'Mark as Documents Required',
        };
        return labels[order.status] || 'Update Status';
    };

    const handleStatusTransitionClick = () => {
        const nextStatus = getNextStatus();
        if (!nextStatus) return;
        setShowStatusModal(true);
    };

    const handleStatusTransitionConfirm = () => {
        const nextStatus = getNextStatus();
        if (!nextStatus) return;

        setUpdatingStatus(true);
        router.post(route('orders.update-status', order.id), {
            status: nextStatus,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setUpdatingStatus(false);
                setShowStatusModal(false);
            },
        });
    };

    const detailRow = (label, value) => (
        <div className="flex flex-col">
            <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</span>
            <span className="text-sm text-gray-900 dark:text-gray-100 font-medium">{value ?? '—'}</span>
        </div>
    );

    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        scheduled: 'bg-primary-100 text-primary-800',
        weight_required: 'bg-orange-100 text-orange-800',
        documents_required: 'bg-blue-100 text-blue-800',
        finalized: 'bg-green-100 text-green-800',
    };

    return (
        <DashboardLayout title={`Order • ${order.tracking_number}`}>
            <Head title={`Order • ${order.tracking_number}`} />

            <div className="mb-6 flex items-center justify-between">
                <Link
                    href="/orders"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Orders
                </Link>
                <div className="flex space-x-3">
                    <a
                        href={`/orders/${order.id}/download-pdf`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                    >
                        <FileDown className="h-4 w-4 mr-2" />
                        Download PDF
                    </a>
                    {canManageOrder && order.status !== 'finalized' && order.status !== 'documents_required' && getNextStatus() && (
                        <button
                            onClick={handleStatusTransitionClick}
                            disabled={updatingStatus}
                            className="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                        >
                            <PlayCircle className="h-4 w-4 mr-2" />
                            {updatingStatus ? 'Updating...' : getStatusButtonLabel()}
                        </button>
                    )}
                    {canManageOrder && order.status === 'documents_required' && (
                        <Link
                            href={route('orders.finalize', order.id)}
                            className={`inline-flex items-center px-3 py-2 text-sm font-medium rounded-md ${
                                order.can_be_finalized
                                    ? 'text-white bg-green-600 hover:bg-green-700'
                                    : 'text-white bg-yellow-600 hover:bg-yellow-700'
                            }`}
                        >
                            <CheckCircle className="h-4 w-4 mr-2" />
                            {order.can_be_finalized ? 'Finalize Order' : 'Go to Finalize Page'}
                        </Link>
                    )}
                    {order.status === 'finalized' && (
                        <span className="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-green-700">
                            <CheckCircle className="h-4 w-4 mr-2" />
                            Finalized
                        </span>
                    )}
                </div>
            </div>

            {flash?.success && (
                <div className="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {flash.success}
                </div>
            )}

            {flash?.error && (
                <div className="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {flash.error}
                </div>
            )}

            {errors?.documents && (
                <div className="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {errors.documents}
                </div>
            )}

            {errors?.status && (
                <div className="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {errors.status}
                </div>
            )}

            {/* Workflow Guidance */}
            {order.status !== 'finalized' && (
                <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 className="text-sm font-semibold text-blue-900 mb-2">Order Workflow:</h4>
                    <div className="text-sm text-blue-800 space-y-1">
                        {order.status === 'pending' && (
                            <p>• <strong>Current:</strong> Order created. Click "Schedule Order" to mark it as scheduled for collection.</p>
                        )}
                        {order.status === 'scheduled' && (
                            <p>• <strong>Current:</strong> Order scheduled. After collection, click "Mark as Weight Required" to capture weights.</p>
                        )}
                        {order.status === 'weight_required' && (
                            <p>• <strong>Current:</strong> Weights need to be captured. Go to Finalize page to add weights, then upload documents.</p>
                        )}
                        {order.status === 'documents_required' && (
                            <>
                                <p>• <strong>Current:</strong> Documents Required status. To finalize, you need:</p>
                                <ul className="list-disc list-inside ml-2 mt-1 space-y-1">
                                    <li>{order.waste_streams && order.waste_streams.length > 0 ? '✅' : <span className="text-red-600 text-xs">✗</span>} Weights captured</li>
                                    <li>{order.has_required_supporting_documents ? '✅' : <span className="text-red-600 text-xs">✗</span>} At least one document uploaded</li>
                                    <li>{order.slip_number ? '✅' : <span className="text-red-600 text-xs">✗</span>} Slip number entered</li>
                                </ul>
                                {order.can_be_finalized ? (
                                    <p className="mt-2 font-semibold text-green-700">✅ All requirements met! Click "Finalize Order" to complete.</p>
                                ) : (
                                    <p className="mt-2 text-yellow-700">⚠️ Click "Go to Finalize Page" to complete missing requirements, then finalize.</p>
                                )}
                            </>
                        )}
                        <p className="mt-2 text-xs text-blue-700">
                            Workflow: Pending → Scheduled → Weight Required → Documents Required → Finalized
                        </p>
                    </div>
                </div>
            )}

            {/* Order Details */}
            <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Order Details
                        </h3>
                        <span className={`px-3 py-1 text-sm font-medium rounded-full capitalize ${statusColors[order.status] || 'bg-gray-100 text-gray-800'}`}>
                            {order.status}
                        </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {detailRow('Tracking Number', order.tracking_number)}
                        {detailRow('Order Type', order.order_type === 'waste' ? 'Waste Order' : 'Recycling Order')}
                        {detailRow('Site', order.site?.name || '—')}
                        {detailRow('Company', order.site?.branch?.company?.name || '—')}
                        {detailRow('Branch', order.site?.branch?.name || '—')}
                        {detailRow('Service Provider', order.service_provider?.name || order.service_provider || '—')}
                        {detailRow('Requested Collection Date', order.requested_collection_date ? new Date(order.requested_collection_date).toLocaleDateString() : '—')}
                        {detailRow('Actual Collection Date', order.actual_collection_date ? new Date(order.actual_collection_date).toLocaleDateString() : '—')}
                        {detailRow('Slip Number', order.slip_number || '—')}
                        {detailRow('Estimated Quantity', order.estimated_quantity || '—')}
                        {detailRow('Actual Quantity', order.actual_quantity || '—')}
                        {detailRow('Created By', order.creator?.name || '—')}
                        {order.order_type === 'recycling' && order.total_rebate && detailRow('Total Rebate', `R ${Number(order.total_rebate).toFixed(2)}`)}
                    </div>

                    {order.waste_type && (
                        <div className="mt-4">
                            {detailRow('Material Type', order.waste_type)}
                        </div>
                    )}

                    {order.notes && (
                        <div className="mt-6">
                            <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 block">Notes</span>
                            <p className="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">
                                {order.notes}
                            </p>
                        </div>
                    )}
                </div>
            </div>

            {order.waste_streams && order.waste_streams.length > 0 && (
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Waste Streams & Weights
                        </h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Material</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Weight (kg)</th>
                                        {order.order_type === 'recycling' && (
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rebate</th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {order.waste_streams.map((stream) => (
                                        <tr key={stream.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {stream.material?.grade?.name || '—'} - {stream.material?.waste_stream?.name || '—'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {stream.nett_weight || stream.gross_weight || '—'}
                                            </td>
                                            {order.order_type === 'recycling' && (
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {stream.material?.rebate_offered && stream.material?.rebate_rate ? (() => {
                                                        let companyRebatePercentage = null;
                                                        
                                                        if (order.status === 'finalized' && order.company_rebate_percentage !== null && order.company_rebate_percentage !== undefined) {
                                                            companyRebatePercentage = order.company_rebate_percentage;
                                                        } else {
                                                            companyRebatePercentage = order.site?.branch?.company?.rebate_percentage;
                                                        }
                                                        
                                                        const clientShare = companyRebatePercentage !== null && companyRebatePercentage !== undefined 
                                                            ? companyRebatePercentage 
                                                            : (stream.material.client_rebate_share || 100);
                                                        return (
                                                            <span className="text-green-600 font-medium">
                                                                R{Number((stream.nett_weight || 0) * stream.material.rebate_rate * clientShare / 100).toFixed(2)}
                                                            </span>
                                                        );
                                                    })() : '—'}
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}

            {order.status_history && order.status_history.length > 0 && (
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <Clock className="h-5 w-5 mr-2" />
                            Status History
                        </h3>
                        <div className="space-y-3">
                            {order.status_history.map((history, index) => (
                                <div key={history.id} className="flex items-start space-x-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                    <div className="flex-shrink-0">
                                        <div className={`h-2 w-2 rounded-full mt-2 ${
                                            history.status === 'finalized' ? 'bg-green-500' :
                                            history.status === 'documents_required' ? 'bg-blue-500' :
                                            history.status === 'weight_required' ? 'bg-orange-500' :
                                            history.status === 'scheduled' ? 'bg-primary-500' :
                                            'bg-yellow-500'
                                        }`}></div>
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize">
                                            {history.status.replace('_', ' ')}
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Changed by {history.changed_by?.name || 'System'} • {new Date(history.created_at).toLocaleString()}
                                        </p>
                                        {history.notes && (
                                            <p className="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                {history.notes}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {order.supporting_documents && order.supporting_documents.length > 0 && (
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Supporting Documents ({order.supporting_documents.length})
                        </h3>
                        <div className="space-y-2">
                            {order.supporting_documents.map((doc) => (
                                <div
                                    key={doc.id}
                                    className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                                >
                                    <div className="flex items-center space-x-3">
                                        <File className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {doc.original_name}
                                            </p>
                                            {doc.description && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {doc.description}
                                                </p>
                                            )}
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {doc.human_readable_size} • {doc.mime_type}
                                            </p>
                                        </div>
                                    </div>
                                    <a
                                        href={route('media.download', doc.id)}
                                        className="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-primary-700 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900 dark:text-primary-300 dark:hover:bg-primary-800"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Download className="h-4 w-4 mr-1" />
                                        Download
                                    </a>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            <Modal show={showStatusModal} onClose={() => setShowStatusModal(false)} maxWidth="md">
                <div className="p-6">
                    <div className="flex items-center mb-4">
                        <AlertTriangle className="h-6 w-6 text-green-600 mr-3" />
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Confirm Status Change
                        </h3>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Are you sure you want to change the order status from <strong>{order.status.replace('_', ' ')}</strong> to <strong>{getNextStatus()?.replace('_', ' ')}</strong>?
                    </p>
                    <div className="flex justify-end space-x-3">
                        <button
                            onClick={() => setShowStatusModal(false)}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleStatusTransitionConfirm}
                            disabled={updatingStatus}
                            className="px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {updatingStatus ? 'Updating...' : 'Confirm'}
                        </button>
                    </div>
                </div>
            </Modal>
        </DashboardLayout>
    );
}

