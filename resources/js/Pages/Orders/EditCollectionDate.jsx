import { Head, Link, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import EditReasonModal from '@/Components/EditReasonModal';
import { ArrowLeft, Save } from 'lucide-react';
import { useState } from 'react';

export default function EditCollectionDate({ order }) {
    const currentDate = order.actual_collection_date
        ? new Date(order.actual_collection_date).toISOString().split('T')[0]
        : '';

    const { data, setData, processing, errors } = useForm({
        actual_collection_date: currentDate,
    });
    const [showReasonModal, setShowReasonModal] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setShowReasonModal(true);
    };

    const handleReasonConfirm = ({ reason, reason_details }) => {
        setShowReasonModal(false);
        router.put(route('orders.update-collection-date', order.id), {
            actual_collection_date: data.actual_collection_date,
            reason,
            reason_details,
        }, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Correct collection date">
            <Head title={`Correct collection date • ${order.tracking_number}`} />

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
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-2">
                        Correct actual collection date • {order.tracking_number}
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Use this to fix the date when waste was actually collected. Saving will update the order and recalculate the client monthly material summary so it appears under the correct month.
                    </p>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label
                                htmlFor="actual_collection_date"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2"
                            >
                                Actual collection date <span className="text-red-600">*</span>
                            </label>
                            <input
                                type="date"
                                id="actual_collection_date"
                                value={data.actual_collection_date}
                                onChange={(e) => setData('actual_collection_date', e.target.value)}
                                className="block w-40 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                required
                            />
                            {errors.actual_collection_date && (
                                <p className="mt-1 text-sm text-red-600">{errors.actual_collection_date}</p>
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
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Saving...' : 'Save and recalculate summaries'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <EditReasonModal
                show={showReasonModal}
                onClose={() => setShowReasonModal(false)}
                onConfirm={handleReasonConfirm}
                title="Reason for changing the collection date"
            />
        </DashboardLayout>
    );
}
