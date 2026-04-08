import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Filter } from 'lucide-react';
import SearchableDropdown from '@/Components/SearchableDropdown';

export default function WasteManagementSummary({ reportData, companies, filters }) {
    const { data, setData, get } = useForm({
        company_id: filters?.company_id || '',
        month: filters?.month || new Date().getMonth() + 1,
        year: filters?.year || new Date().getFullYear(),
    });

    const handleFilter = (e) => {
        e.preventDefault();
        get(route('reports.waste-management-summary'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Generate month options
    const months = [
        { value: 1, label: 'January' },
        { value: 2, label: 'February' },
        { value: 3, label: 'March' },
        { value: 4, label: 'April' },
        { value: 5, label: 'May' },
        { value: 6, label: 'June' },
        { value: 7, label: 'July' },
        { value: 8, label: 'August' },
        { value: 9, label: 'September' },
        { value: 10, label: 'October' },
        { value: 11, label: 'November' },
        { value: 12, label: 'December' },
    ];

    // Generate year options (current year and 2 years back)
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 3 }, (_, i) => currentYear - i);

    return (
        <DashboardLayout title="Resource Intelligence Report - Summary">
            <Head title="Resource Intelligence Report - Summary" />

            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link
                        href={route('reports.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Reports
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Resource Intelligence Report — data summary
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        JSON data structure for testing
                    </p>
                </div>

                {/* Filter Form */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <form onSubmit={handleFilter} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <SearchableDropdown
                                        id="company_id"
                                        name="company_id"
                                        label={(
                                            <>
                                                Company <span className="text-red-500">*</span>
                                            </>
                                        )}
                                        value={data.company_id}
                                        onChange={(v) => setData('company_id', v)}
                                        options={companies}
                                        placeholder="Select a company"
                                        required
                                    />
                                </div>

                                <div>
                                    <label htmlFor="month" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Month <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="month"
                                        value={data.month}
                                        onChange={(e) => setData('month', e.target.value)}
                                        className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                        required
                                    >
                                        {months.map((month) => (
                                            <option key={month.value} value={month.value}>
                                                {month.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="year" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Year <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="year"
                                        value={data.year}
                                        onChange={(e) => setData('year', e.target.value)}
                                        className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                        required
                                    >
                                        {years.map((year) => (
                                            <option key={year} value={year}>
                                                {year}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    <Filter className="h-4 w-4 mr-2" />
                                    Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {/* JSON Display */}
                {reportData && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Report Data (JSON)
                            </h2>
                        </div>
                        <pre className="p-6 overflow-auto text-xs bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 max-h-[600px]">
                            {JSON.stringify(reportData, null, 2)}
                        </pre>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
