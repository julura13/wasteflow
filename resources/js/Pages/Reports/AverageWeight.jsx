import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { formatDateYyyyMmDd } from '@/utils/formatDateYyyyMmDd';
import { ArrowLeft, Calendar, Filter, BarChart3 } from 'lucide-react';
import SearchableDropdown from '@/Components/SearchableDropdown';

const siteOptionLabel = (site) =>
    `${site.name}${site.branch?.company ? ` (${site.branch.company.name})` : ''}`;

export default function AverageWeight({ averageWeightData, sites, filters, containerTypes = {} }) {
    const { data, setData, get } = useForm({
        month: filters.month || new Date().toISOString().slice(0, 7),
        site_id: filters.site_id || '',
        container_type: filters.container_type || 'wheelie_bins',
    });

    const handleFilter = (e) => {
        e.preventDefault();
        get(route('reports.average-weight-wheelie-bins'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const containerTypeLabel = containerTypes[data.container_type] || 'Container';
    const dynamicTitle = `Average Weight for ${containerTypeLabel}`;

    return (
        <DashboardLayout title={dynamicTitle}>
            <Head title={dynamicTitle} />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <Link
                        href={route('reports.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Reports
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Average Weight for Containers
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Calculate average weight per container for operational planning
                    </p>
                </div>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div className="px-4 py-5 sm:p-6">
                    <form onSubmit={handleFilter} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label htmlFor="month" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Month <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="month"
                                    id="month"
                                    value={data.month}
                                    onChange={(e) => setData('month', e.target.value)}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    required
                                />
                            </div>

                            <div>
                                <label htmlFor="container_type" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Container Type <span className="text-red-600">*</span>
                                </label>
                                <select
                                    id="container_type"
                                    value={data.container_type}
                                    onChange={(e) => setData('container_type', e.target.value)}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    required
                                >
                                    {Object.entries(containerTypes).map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <SearchableDropdown
                                    id="site_id"
                                    name="site_id"
                                    label="Site (Optional)"
                                    value={data.site_id}
                                    onChange={(v) => setData('site_id', v)}
                                    options={sites}
                                    getOptionLabel={siteOptionLabel}
                                    placeholder="All Sites"
                                />
                            </div>

                            <div className="flex items-end">
                                <button
                                    type="submit"
                                    className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    <Filter className="h-4 w-4 mr-2" />
                                    Calculate
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {averageWeightData && averageWeightData.total_containers > 0 ? (
                <>
                    <div className="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg mb-6 p-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <span className="text-xs uppercase tracking-wide text-blue-700 dark:text-blue-300">Total Weight</span>
                                <p className="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                                    {Number(averageWeightData.total_weight).toFixed(2)} kg
                                </p>
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wide text-blue-700 dark:text-blue-300">Total {averageWeightData.container_type_label || 'Containers'}</span>
                                <p className="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                                    {averageWeightData.total_containers}
                                </p>
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wide text-blue-700 dark:text-blue-300">Average Weight per {averageWeightData.container_type_label || 'Container'}</span>
                                <p className="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                                    {Number(averageWeightData.average_weight_per_container).toFixed(2)} kg
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <div className="flex items-center mb-4">
                            <BarChart3 className="h-5 w-5 text-blue-600 mr-2" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Analysis for {formatDateYyyyMmDd(`${averageWeightData.month}-01`)}
                            </h3>
                        </div>
                        <div className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <p>
                                Based on <strong>{averageWeightData.total_containers}</strong> {averageWeightData.container_type_label?.toLowerCase() || 'containers'} collected, 
                                the average weight per {averageWeightData.container_type_label?.toLowerCase() || 'container'} is <strong>{Number(averageWeightData.average_weight_per_container).toFixed(2)} kg</strong>.
                            </p>
                            <p>
                                This information helps optimize collection routes and understand typical container weights for planning purposes.
                            </p>
                        </div>
                    </div>
                </>
            ) : (
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="text-center py-12">
                            <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No data available</h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No {containerTypeLabel.toLowerCase()} data found for the selected month and site.
                            </p>
                            <div className="mt-3 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                <p><strong>To see data, ensure:</strong></p>
                                <ul className="list-disc list-inside ml-2 space-y-1">
                                    <li>Order is <strong>finalized</strong> (not pending/scheduled)</li>
                                    <li>Order has <strong>{data.container_type}</strong> in quantity lines</li>
                                    <li>Order has <strong>waste streams with weights</strong> captured</li>
                                    <li>Collection date matches selected <strong>month</strong></li>
                                    <li>Site filter matches (if filtering by site)</li>
                                </ul>
                                <p className="mt-2"><strong>Note:</strong> These container types are typically used in Waste orders, not Recycling orders.</p>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </DashboardLayout>
    );
}

