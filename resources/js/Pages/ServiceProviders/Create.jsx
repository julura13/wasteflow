import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Save, Truck, Recycle, AlertTriangle } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        types: [],
        email: '',
        phone: '',
        address: '',
        contact_person: '',
        registration_number: '',
        slip_number_prefix: '',
        notes: '',
        is_active: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/service-providers');
    };

    const serviceTypes = [
        { value: 'waste_collection', label: 'Waste Collection', icon: Truck, color: 'orange' },
        { value: 'recycling', label: 'Recycling', icon: Recycle, color: 'green' },
        { value: 'hazardous', label: 'Hazardous Waste', icon: AlertTriangle, color: 'red' },
        { value: 'general', label: 'General Services', icon: Truck, color: 'gray' },
    ];

    const toggleType = (value) => {
        if (data.types.includes(value)) {
            setData('types', data.types.filter((type) => type !== value));
        } else {
            setData('types', [...data.types, value]);
        }
    };

    return (
        <DashboardLayout title="Create Service Provider">
            <Head title="Create Service Provider" />

            <div className="mb-6">
                <Link
                    href="/service-providers"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Service Providers
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-6">
                        Create New Service Provider
                    </h3>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Provider Name *
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    placeholder="e.g., Green Recycling Solutions"
                                    required
                                />
                                {errors.name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            {/* Service Type Selection */}
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">
                                    Service Types *
                                </label>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    {serviceTypes.map((type) => {
                                        const Icon = type.icon;
                                        const selected = data.types.includes(type.value);
                                        return (
                                            <button
                                                key={type.value}
                                                type="button"
                                                onClick={() => toggleType(type.value)}
                                                className={`relative flex flex-col items-center p-4 border-2 rounded-lg transition-all ${
                                                    selected
                                                        ? 'border-primary-600 bg-primary-50 shadow-md dark:bg-primary-900/20'
                                                        : 'border-gray-300 hover:border-primary-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                                                }`}
                                            >
                                                <Icon className={`w-8 h-8 mb-2 ${selected ? 'text-primary-600' : 'text-gray-400 dark:text-gray-500'}`} />
                                                <span className={`text-sm font-medium text-center ${selected ? 'text-primary-700' : 'text-gray-700 dark:text-gray-200'}`}>
                                                    {type.label}
                                                </span>
                                                {selected && (
                                                    <div className="absolute top-2 right-2">
                                                        <svg className="w-5 h-5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </div>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                                {errors.types && <p className="mt-2 text-sm text-red-600">{errors.types}</p>}
                                {errors['types.0'] && <p className="mt-1 text-sm text-red-600">{errors['types.0']}</p>}
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
                                <label htmlFor="slip_number_prefix" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Slip Number Prefix
                                </label>
                                <input
                                    type="text"
                                    id="slip_number_prefix"
                                    value={data.slip_number_prefix}
                                    onChange={(e) => setData('slip_number_prefix', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    placeholder="e.g. WK"
                                    maxLength={20}
                                />
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Added to slip numbers when finalizing orders (e.g. WK → WK-12345)
                                </p>
                                {errors.slip_number_prefix && (
                                    <p className="mt-1 text-sm text-red-600">{errors.slip_number_prefix}</p>
                                )}
                            </div>

                            <div className="sm:col-span-2">
                                <label htmlFor="address" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Address
                                </label>
                                <textarea
                                    id="address"
                                    rows={2}
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                {errors.address && (
                                    <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                )}
                            </div>

                            <div className="sm:col-span-2">
                                <label htmlFor="notes" className="block text	sm font-medium text-gray-700 dark:text-gray-200">
                                    Notes
                                </label>
                                <textarea
                                    id="notes"
                                    rows={3}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    placeholder="Additional information about this service provider..."
                                />
                                {errors.notes && (
                                    <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
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
                                href="/service-providers"
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
                                {processing ? 'Creating...' : 'Create Service Provider'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
