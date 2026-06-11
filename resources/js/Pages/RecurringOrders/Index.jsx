import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { formatQuantityLinesCommaSeparated } from '@/utils/orderQuantityLines';
import { CheckCircle, Pencil, Plus, RefreshCw, Trash2, X } from 'lucide-react';
import { useState, useEffect } from 'react';

const DAY_ABBREV = { monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun' };

export default function RecurringOrdersIndex({ recurringOrders = [] }) {
    const { flash } = usePage().props;
    const [showSuccess, setShowSuccess] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState(null);

    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const t = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(t);
        }
    }, [flash?.success]);

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(route('recurring-orders.destroy', deleteTarget.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    };

    const handleRestore = (id) => {
        router.post(route('recurring-orders.restore', id), {}, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Recurring Orders">
            <Head title="Recurring Orders" />

            {showSuccess && flash?.success && (
                <div className="mb-4 rounded-lg bg-primary-50 border border-primary-200 p-4 flex items-center justify-between">
                    <div className="flex items-center gap-2 text-primary-800">
                        <CheckCircle className="h-5 w-5 text-primary-600" />
                        <span className="text-sm font-medium">{flash.success}</span>
                    </div>
                    <button onClick={() => setShowSuccess(false)}><X className="h-4 w-4 text-primary-600" /></button>
                </div>
            )}

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Recurring Orders</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Order templates that automatically generate a new pending order each day at 4 am.
                    </p>
                </div>
                <Link
                    href={route('recurring-orders.create')}
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Add Recurring Order
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-gray-200 dark:border-gray-700">
                {recurringOrders.length === 0 ? (
                    <div className="p-12 text-center">
                        <RefreshCw className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                        <h3 className="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">No recurring orders</h3>
                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Create one to automatically generate orders on a schedule.
                        </p>
                    </div>
                ) : (
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead className="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                {['Customer', 'Type', 'Service Provider', 'Days', 'Containers', 'Status', 'Actions'].map((h) => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {recurringOrders.map((r) => (
                                <tr key={r.id} className={`${r.deleted_at ? 'opacity-50' : ''} hover:bg-gray-50 dark:hover:bg-gray-700/50`}>
                                    <td className="px-4 py-3">
                                        <div className="font-medium text-gray-900 dark:text-gray-100">{r.company}</div>
                                        {r.branch && <div className="text-xs text-gray-500 dark:text-gray-400">{r.branch}</div>}
                                        {r.site && <div className="text-xs text-gray-400 dark:text-gray-500">{r.site}</div>}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${r.order_type === 'waste' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'}`}>
                                            {r.order_type === 'waste' ? 'Waste' : 'Recycling'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-gray-700 dark:text-gray-300">{r.service_provider ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            {(r.days_of_week ?? []).map((d) => (
                                                <span key={d} className="px-1.5 py-0.5 text-xs rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 font-medium">
                                                    {DAY_ABBREV[d] ?? d}
                                                </span>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs">
                                        <span className="line-clamp-2">{formatQuantityLinesCommaSeparated(r.quantity_lines)}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        {r.deleted_at ? (
                                            <span className="text-xs text-red-600 dark:text-red-400 font-medium">Deleted</span>
                                        ) : r.is_active ? (
                                            <span className="text-xs text-green-600 dark:text-green-400 font-medium">Active</span>
                                        ) : (
                                            <span className="text-xs text-gray-500 dark:text-gray-400 font-medium">Inactive</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            {r.deleted_at ? (
                                                <button
                                                    onClick={() => handleRestore(r.id)}
                                                    className="text-primary-600 hover:text-primary-800 text-xs font-medium"
                                                >
                                                    Restore
                                                </button>
                                            ) : (
                                                <>
                                                    <Link href={route('recurring-orders.edit', r.id)} className="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" title="Edit">
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                    <button onClick={() => setDeleteTarget(r)} className="text-red-600 hover:text-red-800" title="Delete">
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            {/* Delete confirmation modal */}
            {deleteTarget && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-sm">
                        <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Delete recurring order?</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            This will stop new orders from being created from this template. Existing orders are not affected.
                        </p>
                        <div className="flex justify-end gap-3">
                            <button onClick={() => setDeleteTarget(null)} className="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600">
                                Cancel
                            </button>
                            <button onClick={handleDelete} className="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </DashboardLayout>
    );
}
