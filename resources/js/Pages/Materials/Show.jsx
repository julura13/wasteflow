import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Edit } from 'lucide-react';

export default function Show({ material }) {
    const detailRow = (label, value) => (
        <div className="flex flex-col">
            <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</span>
            <span className="text-sm text-gray-900 dark:text-gray-100">{value ?? '—'}</span>
        </div>
    );

    return (
        <DashboardLayout title={`Material • ${material.grade?.name ?? material.id}` }>
            <Head title={`Material • ${material.grade?.name ?? material.id}`} />

            <div className="mb-6 flex items-center justify-between">
                <Link
                    href="/materials"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Materials
                </Link>
                <Link
                    href={`/materials/${material.id}/edit`}
                    className="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                >
                    <Edit className="h-4 w-4 mr-2" />
                    Edit Material
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {detailRow('Waste Stream', material.waste_stream?.name)}
                        {detailRow('Grade', material.grade?.name)}
                        {detailRow('Classification', material.classification?.name)}
                        {detailRow('Facility', material.facility?.name)}
                        {detailRow('Service Provider', material.service_provider?.name)}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {detailRow('Weight Requirement', material.weight_required)}
                        {detailRow('Rebate Offered', material.rebate_offered ? 'Yes' : 'No')}
                        {detailRow('Backing Document', material.backing_document ? 'Required' : 'Optional')}
                        {detailRow('Rebate Rate (R/kg)', material.rebate_offered && material.rebate_rate !== null ? `R ${Number(material.rebate_rate).toFixed(2)}` : '—')}
                        {detailRow('Client Rebate Share (%)', material.rebate_offered && material.client_rebate_share !== null ? `${Number(material.client_rebate_share).toFixed(2)}%` : '—')}
                        {detailRow('Status', material.is_active ? 'Active' : 'Inactive')}
                    </div>

                    <div>
                        <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 block">Notes</span>
                        <p className="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">
                            {material.notes ?? 'No additional notes.'}
                        </p>
                    </div>

                    {Array.isArray(material.order_waste_streams) && material.order_waste_streams.length > 0 && (
                        <div>
                            <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 block mb-2">Usage History</span>
                            <div className="space-y-2">
                                {material.order_waste_streams.map((stream) => (
                                    <div key={stream.id} className="border border-gray-200 dark:border-gray-700 rounded-md p-3 text-sm text-gray-700 dark:text-gray-300">
                                        <div className="flex justify-between">
                                            <span>Order #{stream.order?.tracking_number ?? stream.order_id}</span>
                                            <div className="text-right">
                                                <span>{stream.order?.site?.name ?? stream.order?.branch?.name ?? stream.order?.company?.name ?? 'Unknown'}</span>
                                                <span className="block text-[10px] text-gray-400 dark:text-gray-500">
                                                    {stream.order?.site?.name ? 'Site' : stream.order?.branch?.name ? 'Branch' : stream.order?.company?.name ? 'Company' : ''}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Nett Weight: {stream.nett_weight ?? '—'} | Quantity: {stream.quantity ?? '—'}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
