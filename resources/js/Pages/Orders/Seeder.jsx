import { Head, Link, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Database, AlertCircle } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function Seeder({ companies }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        company_id: '',
        recycling_order_count: 10,
        waste_order_count: 10,
        month: new Date().toISOString().slice(0, 7), // Current month in YYYY-MM format
        status: 'pending',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('orders.seeder.generate'), {
            onSuccess: () => {
                // Optionally reset form or keep values
            },
        });
    };

    // Generate month options (current month and 12 months before)
    const generateMonthOptions = () => {
        const options = [];
        const currentDate = new Date();
        
        for (let i = 0; i < 13; i++) {
            const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const monthName = date.toLocaleString('default', { month: 'long', year: 'numeric' });
            options.push({
                value: `${year}-${month}`,
                label: monthName,
            });
        }
        
        return options;
    };

    const monthOptions = generateMonthOptions();

    // Show success message
    const [showSuccess, setShowSuccess] = useState(false);
    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash?.success]);

    return (
        <DashboardLayout title="Order Seeder">
            <Head title="Order Seeder" />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link
                        href={route('orders.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Orders
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Order Seeder
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Generate test orders for a selected company and calendar month
                    </p>
                </div>

                {/* Success Message */}
                {showSuccess && flash?.success && (
                    <div className="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm font-medium text-green-800 dark:text-green-200">
                                    {flash.success}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Error Messages */}
                {Object.keys(errors).length > 0 && (
                    <div className="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <AlertCircle className="h-5 w-5 text-red-400" />
                            </div>
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-red-800 dark:text-red-200 mb-2">
                                    Please correct the following errors:
                                </h3>
                                <ul className="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message}</li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                )}

                {/* Form */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div className="px-4 py-5 sm:p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Company Selection */}
                            <div>
                                <label htmlFor="company_id" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Company <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="company_id"
                                    value={data.company_id}
                                    onChange={(e) => setData('company_id', e.target.value)}
                                    className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    required
                                >
                                    <option value="">Select a company</option>
                                    {companies.map((company) => (
                                        <option key={company.id} value={company.id}>
                                            {company.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.company_id && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.company_id}</p>
                                )}
                            </div>

                            {/* Calendar Month Selection */}
                            <div>
                                <label htmlFor="month" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Calendar Month <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="month"
                                    value={data.month}
                                    onChange={(e) => setData('month', e.target.value)}
                                    className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    required
                                >
                                    {monthOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Orders will be generated with requested collection dates falling within this month
                                </p>
                                {errors.month && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.month}</p>
                                )}
                            </div>

                            {/* Recycling Orders Count */}
                            <div>
                                <label htmlFor="recycling_order_count" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Number of Recycling Orders <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="recycling_order_count"
                                    min="0"
                                    max="1000"
                                    value={data.recycling_order_count}
                                    onChange={(e) => setData('recycling_order_count', parseInt(e.target.value) || 0)}
                                    className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    required
                                />
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Number of recycling orders to generate (0-1000)
                                </p>
                                {errors.recycling_order_count && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.recycling_order_count}</p>
                                )}
                            </div>

                            {/* Waste Orders Count */}
                            <div>
                                <label htmlFor="waste_order_count" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Number of Waste Orders <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="waste_order_count"
                                    min="0"
                                    max="1000"
                                    value={data.waste_order_count}
                                    onChange={(e) => setData('waste_order_count', parseInt(e.target.value) || 0)}
                                    className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    required
                                />
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Number of waste orders to generate (0-1000)
                                </p>
                                {errors.waste_order_count && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.waste_order_count}</p>
                                )}
                            </div>

                            {/* Order Status Selection */}
                            <div>
                                <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Order Status <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    required
                                >
                                    <option value="pending">Pending</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="weight_required">Weight Required</option>
                                    <option value="documents_required">Documents Required</option>
                                    <option value="finalized">Finalized</option>
                                </select>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    All generated orders will have this status
                                </p>
                                {errors.status && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.status}</p>
                                )}
                            </div>

                            {/* Info Box */}
                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <Database className="h-5 w-5 text-blue-400" />
                                    </div>
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                            How it works
                                        </h3>
                                        <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1 list-disc list-inside">
                                            <li>Orders will be randomly distributed across the company's active sites</li>
                                            <li>Collection dates will be randomly assigned to weekdays within the selected month</li>
                                            <li>Service providers will be randomly assigned from active service providers</li>
                                            <li>All orders will have the selected status</li>
                                            <li>Quantity types and amounts will be randomly generated</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            {/* Submit Button */}
                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Database className="h-4 w-4 mr-2" />
                                    {processing ? 'Generating Orders...' : 'Generate Orders'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
