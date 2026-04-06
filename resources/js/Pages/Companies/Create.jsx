import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { useState } from 'react';
import { ArrowLeft, Save } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function Create({ serviceProviders = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        address: '',
        contact_person: '',
        registration_number: '',
        rebate_percentage: '',
        default_waste_service_provider_id: '',
        default_recycling_service_provider_id: '',
        is_active: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/companies');
    };

    return (
        <DashboardLayout title="Create Company">
            <Head title="Create Company" />

            <div className="mb-6">
                <Link
                    href="/companies"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Companies
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-6">
                        Create New Company
                    </h3>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Company Name *
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    required
                                />
                                {errors.name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.email && (
                                    <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Phone
                                </label>
                                <input
                                    type="tel"
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.phone && (
                                    <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="registration_number" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Registration Number
                                </label>
                                <input
                                    type="text"
                                    id="registration_number"
                                    value={data.registration_number}
                                    onChange={(e) => setData('registration_number', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.registration_number && (
                                    <p className="mt-1 text-sm text-red-600">{errors.registration_number}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="rebate_percentage" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Rebate Percentage (%)
                                </label>
                                <input
                                    type="number"
                                    id="rebate_percentage"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={data.rebate_percentage}
                                    onChange={(e) => setData('rebate_percentage', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    placeholder="e.g., 90.00"
                                />
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Optional: Company-specific rebate percentage. If set, overrides material rebate share for all orders from this company.
                                </p>
                                {errors.rebate_percentage && (
                                    <p className="mt-1 text-sm text-red-600">{errors.rebate_percentage}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="default_waste_service_provider_id" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Default service provider (waste orders)
                                </label>
                                <select
                                    id="default_waste_service_provider_id"
                                    value={data.default_waste_service_provider_id}
                                    onChange={(e) => setData('default_waste_service_provider_id', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <option value="">None</option>
                                    {serviceProviders.map((sp) => (
                                        <option key={sp.id} value={sp.id}>
                                            {sp.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.default_waste_service_provider_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.default_waste_service_provider_id}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="default_recycling_service_provider_id" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Default service provider (recycling orders)
                                </label>
                                <select
                                    id="default_recycling_service_provider_id"
                                    value={data.default_recycling_service_provider_id}
                                    onChange={(e) => setData('default_recycling_service_provider_id', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <option value="">None</option>
                                    {serviceProviders.map((sp) => (
                                        <option key={sp.id} value={sp.id}>
                                            {sp.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.default_recycling_service_provider_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.default_recycling_service_provider_id}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="contact_person" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Contact Person
                                </label>
                                <input
                                    type="text"
                                    id="contact_person"
                                    value={data.contact_person}
                                    onChange={(e) => setData('contact_person', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.contact_person && (
                                    <p className="mt-1 text-sm text-red-600">{errors.contact_person}</p>
                                )}
                            </div>

                            <div className="sm:col-span-2">
                                <label htmlFor="address" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Address
                                </label>
                                <textarea
                                    id="address"
                                    rows={3}
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.address && (
                                    <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                )}
                            </div>

                            <div className="flex items-center">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div className="flex justify-end space-x-3">
                            <Link
                                href="/companies"
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Creating...' : 'Create Company'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
