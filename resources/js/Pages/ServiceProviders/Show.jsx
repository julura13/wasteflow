import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Edit } from 'lucide-react';

export default function Show({ serviceProvider }) {
    const Row = ({ label, value }) => (
        <div className="flex flex-col">
            <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</span>
            <span className="text-sm text-gray-900 dark:text-gray-100">{value ?? '—'}</span>
        </div>
    );

    return (
        <DashboardLayout title={`Service Provider • ${serviceProvider.name}`}>
            <Head title={`Service Provider • ${serviceProvider.name}`} />

            <div className="mb-6 flex items-center justify-between">
                <Link href="/service-providers" className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Service Providers
                </Link>
                <Link
                    href={`/service-providers/${serviceProvider.id}/edit`}
                    className="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                >
                    <Edit className="h-4 w-4 mr-2" />
                    Edit Provider
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Row label="Name" value={serviceProvider.name} />
                        <Row label="Contact Person" value={serviceProvider.contact_person} />
                        <Row label="Phone" value={serviceProvider.phone} />
                        <Row label="Email" value={serviceProvider.email} />
                        <Row label="Registration #" value={serviceProvider.registration_number} />
                        <Row label="Status" value={serviceProvider.is_active ? 'Active' : 'Inactive'} />
                    </div>

                    <div>
                        <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 block">Address</span>
                        <p className="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">
                            {serviceProvider.address ?? '—'}
                        </p>
                    </div>

                    <div>
                        <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 block">Services</span>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {(serviceProvider.types ?? []).map((type) => (
                                <span key={type} className="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {type.replace('_', ' ')}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}

