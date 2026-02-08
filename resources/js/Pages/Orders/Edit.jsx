import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Save } from 'lucide-react';

export default function Edit({ order, sites, wasteTypes }) {
    const { data, setData, put, processing, errors } = useForm({
        site_id: order.site_id || '',
        order_type: order.order_type || 'waste',
        status: order.status || 'pending',
        requested_collection_date: order.requested_collection_date || '',
        actual_collection_date: order.actual_collection_date || '',
        service_provider: order.service_provider || '',
        slip_number: order.slip_number || '',
        estimated_quantity: order.estimated_quantity || '',
        actual_quantity: order.actual_quantity || '',
        notes: order.notes || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/orders/${order.id}`);
    };

    const statusOptions = [
        { value: 'pending', label: 'Pending' },
        { value: 'scheduled', label: 'Scheduled' },
        { value: 'collected', label: 'Collected' },
        { value: 'sorted', label: 'Sorted' },
        { value: 'finalized', label: 'Finalized' },
    ];

    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        scheduled: 'bg-primary-100 text-primary-800',
        collected: 'bg-purple-100 text-purple-800',
        sorted: 'bg-indigo-100 text-indigo-800',
        finalized: 'bg-green-100 text-green-800',
    };

    return (
        <DashboardLayout title="Edit Order">
            <Head title={`Edit Order • ${order.tracking_number}`} />

            <div className="mb-6">
                <Link
                    href={`/orders/${order.id}`}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
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
                        <span className={`px-3 py-1 text-sm font-medium rounded-full capitalize ${statusColors[order.status] || 'bg-gray-100 text-gray-800'}`}>
                            {order.status}
                        </span>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Order Type */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Order Type
                            </label>
                            <div className="flex space-x-4">
                                <label className="inline-flex items-center">
                                    <input
                                        type="radio"
                                        value="waste"
                                        checked={data.order_type === 'waste'}
                                        onChange={(e) => setData('order_type', e.target.value)}
                                        className="form-radio h-4 w-4 text-primary-600"
                                    />
                                    <span className="ml-2 text-gray-700 dark:text-gray-200">Waste Order</span>
                                </label>
                                <label className="inline-flex items-center">
                                    <input
                                        type="radio"
                                        value="recycling"
                                        checked={data.order_type === 'recycling'}
                                        onChange={(e) => setData('order_type', e.target.value)}
                                        className="form-radio h-4 w-4 text-primary-600"
                                    />
                                    <span className="ml-2 text-gray-700 dark:text-gray-200">Recycling Order</span>
                                </label>
                            </div>
                            {errors.order_type && (
                                <p className="mt-1 text-sm text-red-600">{errors.order_type}</p>
                            )}
                        </div>

                        {/* Site Selection */}
                        <div>
                            <label htmlFor="site_id" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Site *
                            </label>
                            <select
                                id="site_id"
                                value={data.site_id}
                                onChange={(e) => setData('site_id', e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                required
                            >
                                <option value="">Select a collection site</option>
                                {sites.map((site) => {
                                    const companyName = site.company_name || 'Unknown Company';
                                    return (
                                        <option key={site.id} value={site.id}>
                                            {site.name} - {companyName}
                                            {site.branch && ` (${site.branch.name})`}
                                        </option>
                                    );
                                })}
                            </select>
                            {errors.site_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.site_id}</p>
                            )}
                        </div>

                        {/* Status */}
                        <div>
                            <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Status *
                            </label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                required
                            >
                                {statusOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            {errors.status && (
                                <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                            )}
                        </div>

                        {/* Dates */}
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label htmlFor="requested_collection_date" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Requested Collection Date *
                                </label>
                                <input
                                    type="date"
                                    id="requested_collection_date"
                                    value={data.requested_collection_date ? new Date(data.requested_collection_date).toISOString().split('T')[0] : ''}
                                    onChange={(e) => setData('requested_collection_date', e.target.value)}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    required
                                />
                                {errors.requested_collection_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.requested_collection_date}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="actual_collection_date" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Actual Collection Date
                                </label>
                                <input
                                    type="date"
                                    id="actual_collection_date"
                                    value={data.actual_collection_date ? new Date(data.actual_collection_date).toISOString().split('T')[0] : ''}
                                    onChange={(e) => setData('actual_collection_date', e.target.value)}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.actual_collection_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.actual_collection_date}</p>
                                )}
                            </div>
                        </div>

                        {/* Service Provider */}
                        <div>
                            <label htmlFor="service_provider" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Service Provider
                            </label>
                            <input
                                type="text"
                                id="service_provider"
                                value={data.service_provider}
                                onChange={(e) => setData('service_provider', e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Service provider name"
                            />
                            {errors.service_provider && (
                                <p className="mt-1 text-sm text-red-600">{errors.service_provider}</p>
                            )}
                        </div>

                        {/* Slip Number */}
                        <div>
                            <label htmlFor="slip_number" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Slip Number
                            </label>
                            <input
                                type="text"
                                id="slip_number"
                                value={data.slip_number}
                                onChange={(e) => setData('slip_number', e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Slip number"
                            />
                            {errors.slip_number && (
                                <p className="mt-1 text-sm text-red-600">{errors.slip_number}</p>
                            )}
                        </div>

                        {/* Quantities */}
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label htmlFor="estimated_quantity" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Estimated Quantity
                                </label>
                                <input
                                    type="number"
                                    id="estimated_quantity"
                                    value={data.estimated_quantity}
                                    onChange={(e) => setData('estimated_quantity', e.target.value)}
                                    min="1"
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.estimated_quantity && (
                                    <p className="mt-1 text-sm text-red-600">{errors.estimated_quantity}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="actual_quantity" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Actual Quantity
                                </label>
                                <input
                                    type="number"
                                    id="actual_quantity"
                                    value={data.actual_quantity}
                                    onChange={(e) => setData('actual_quantity', e.target.value)}
                                    min="0"
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.actual_quantity && (
                                    <p className="mt-1 text-sm text-red-600">{errors.actual_quantity}</p>
                                )}
                            </div>
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
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Special instructions or additional notes..."
                            />
                            {errors.notes && (
                                <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                            )}
                        </div>

                        {/* Actions */}
                        <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Link
                                href={`/orders/${order.id}`}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Saving...' : 'Save Changes'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}

