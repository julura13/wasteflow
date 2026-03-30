import { Head, Link, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import EditReasonModal from '@/Components/EditReasonModal';
import { ArrowLeft, Save, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const LEGACY_RECYCLING_TYPE_LABELS = {
    scrap_load: 'Scrap Load',
    loose_bags: 'Loose Bags',
    cage_8m3: '8m³ Cage',
    cage_20m3: '20m³ Cage',
    other: 'Other',
};

function buildInitialQuantityLines(order, containerOptions) {
    const lines = order.quantity_lines || [];
    if (lines.length === 0) {
        return [{ id: 1, quantity_type: '', quantity: '', description: '' }];
    }
    const opts = containerOptions || [];
    return lines.map((line, index) => {
        let qtyType = '';
        if (line.container_option_id != null && line.container_option_id !== '') {
            qtyType = String(line.container_option_id);
        } else if (order.order_type === 'recycling' && line.quantity_type) {
            const name = LEGACY_RECYCLING_TYPE_LABELS[line.quantity_type];
            const match = name ? opts.find((o) => o.name === name) : null;
            if (match) {
                qtyType = String(match.id);
            }
        }
        return {
            id: index + 1,
            quantity_type: qtyType,
            quantity: line.quantity ?? '',
            description: line.description ?? '',
        };
    });
}

export default function Edit({ order, containerOptions = [] }) {
    const [quantityLines, setQuantityLines] = useState(() =>
        buildInitialQuantityLines(order, containerOptions)
    );

    const isWaste = order.order_type === 'waste';
    const quantityTypes = (containerOptions || []).map((opt) => ({ value: String(opt.id), label: opt.name }));

    const [submitting, setSubmitting] = useState(false);
    const [showReasonModal, setShowReasonModal] = useState(false);
    const [pendingPayload, setPendingPayload] = useState(null);
    const { data, setData, errors } = useForm({
        notes: order.notes || '',
    });

    const addQuantityLine = () => {
        const nextId = Math.max(...quantityLines.map((l) => l.id), 0) + 1;
        setQuantityLines([...quantityLines, { id: nextId, quantity_type: '', quantity: '', description: '' }]);
    };

    const removeQuantityLine = (id) => {
        if (quantityLines.length <= 1) return;
        setQuantityLines(quantityLines.filter((l) => l.id !== id));
    };

    const updateQuantityLine = (id, field, value) => {
        setQuantityLines(
            quantityLines.map((l) => (l.id === id ? { ...l, [field]: value } : l))
        );
    };

    const getTotalContainers = () => {
        return quantityLines.reduce((sum, l) => sum + (parseInt(l.quantity, 10) || 0), 0);
    };

    const buildPayload = () => {
        const validLines = quantityLines.filter((line) => {
            const hasType = line.quantity_type && String(line.quantity_type).trim() !== '';
            const hasQty = line.quantity != null && parseInt(line.quantity, 10) > 0;
            return hasType && hasQty;
        });
        if (validLines.length === 0) return null;
        return {
            notes: data.notes || null,
            quantity_lines: validLines.map((line) => ({
                container_option_id: parseInt(line.quantity_type, 10),
                quantity: parseInt(line.quantity, 10),
                ...(!isWaste && line.description?.trim()
                    ? { description: String(line.description).trim() }
                    : {}),
            })),
        };
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const payload = buildPayload();
        if (!payload) return;
        setPendingPayload(payload);
        setShowReasonModal(true);
    };

    const handleReasonConfirm = ({ reason, reason_details }) => {
        if (!pendingPayload) return;
        setSubmitting(true);
        router.put(route('orders.update', order.id), { ...pendingPayload, reason, reason_details }, {
            preserveScroll: true,
            onFinish: () => {
                setSubmitting(false);
                setPendingPayload(null);
            },
        });
    };

    const siteName = order.site?.name ?? (order.site_id ? `Site #${order.site_id}` : '—');
    const serviceProviderName = order.service_provider?.name ?? order.service_provider ?? '—';
    const requestedDate = order.requested_collection_date
        ? new Date(order.requested_collection_date).toLocaleDateString()
        : '—';

    return (
        <DashboardLayout title="Edit Order">
            <Head title={`Edit Order • ${order.tracking_number}`} />

            <div className="mb-6">
                <Link
                    href={route('orders.show', order.id)}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Order
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex items-center justify-between mb-6">
                        <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                            Edit Order • {order.tracking_number}
                        </h3>
                        <span className="px-3 py-1 text-sm font-medium rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-200 capitalize">
                            {order.status}
                        </span>
                    </div>

                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        You can only change quantity. Service provider and collection type cannot be changed.
                    </p>

                    {/* Read-only order details */}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Order type</span>
                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize">{order.order_type}</p>
                        </div>
                        <div>
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Site</span>
                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{siteName}</p>
                        </div>
                        <div>
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Service provider</span>
                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{serviceProviderName}</p>
                        </div>
                        <div>
                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Requested collection date</span>
                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{requestedDate}</p>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Quantity lines */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-md font-medium text-gray-900 dark:text-gray-100">Quantities</h4>
                                <button
                                    type="button"
                                    onClick={addQuantityLine}
                                    className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    <Plus className="h-4 w-4 mr-2" />
                                    Add Line
                                </button>
                            </div>
                            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table className="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Container Type
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Quantity
                                            </th>
                                            {!isWaste && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Notes (optional)
                                                </th>
                                            )}
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                                                {' '}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {quantityLines.map((line) => (
                                            <tr key={line.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <select
                                                        value={line.quantity_type}
                                                        onChange={(e) => updateQuantityLine(line.id, 'quantity_type', e.target.value)}
                                                        className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                                        required
                                                    >
                                                        <option value="">Select type</option>
                                                        {quantityTypes.map((type) => (
                                                            <option key={type.value} value={type.value}>
                                                                {type.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <input
                                                        type="number"
                                                        value={line.quantity}
                                                        onChange={(e) => updateQuantityLine(line.id, 'quantity', e.target.value)}
                                                        min="1"
                                                        className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                                        required
                                                    />
                                                </td>
                                                {!isWaste && (
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <input
                                                            type="text"
                                                            value={line.description || ''}
                                                            onChange={(e) => updateQuantityLine(line.id, 'description', e.target.value)}
                                                            className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                                            placeholder="Optional notes"
                                                        />
                                                    </td>
                                                )}
                                                <td className="px-6 py-4 whitespace-nowrap text-right">
                                                    {quantityLines.length > 1 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => removeQuantityLine(line.id)}
                                                            className="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                                                            title="Remove line"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {quantityLines.length > 0 && (
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Total containers: <span className="font-semibold">{getTotalContainers()}</span>
                                </p>
                            )}
                            {errors.quantity_lines && (
                                <p className="mt-1 text-sm text-red-600">{errors.quantity_lines}</p>
                            )}
                        </div>

                        {/* Notes */}
                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                rows={3}
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Special instructions or additional notes..."
                            />
                            {errors.notes && (
                                <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                            )}
                        </div>

                        <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Link
                                href={route('orders.show', order.id)}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={submitting}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {submitting ? 'Saving...' : 'Save changes'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <EditReasonModal
                show={showReasonModal}
                onClose={() => {
                    setShowReasonModal(false);
                    setPendingPayload(null);
                }}
                onConfirm={handleReasonConfirm}
                title="Reason for this change"
            />
        </DashboardLayout>
    );
}
