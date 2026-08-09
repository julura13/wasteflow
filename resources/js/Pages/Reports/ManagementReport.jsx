import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Download, FileText, ClipboardList } from 'lucide-react';

const MONTHS = [
    { value: 1, label: 'January' }, { value: 2, label: 'February' }, { value: 3, label: 'March' },
    { value: 4, label: 'April' }, { value: 5, label: 'May' }, { value: 6, label: 'June' },
    { value: 7, label: 'July' }, { value: 8, label: 'August' }, { value: 9, label: 'September' },
    { value: 10, label: 'October' }, { value: 11, label: 'November' }, { value: 12, label: 'December' },
];

export default function ManagementReport({ rows, month: monthProp, year: yearProp }) {
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 6 }, (_, i) => currentYear - i);

    const { data, setData, get, processing } = useForm({
        month: monthProp,
        year: yearProp,
    });

    const exportQuery = new URLSearchParams({
        month: String(data.month),
        year: String(data.year),
    }).toString();
    const exportCsvHref = `${route('reports.management-report.export')}?${exportQuery}`;
    const exportPdfHref = `${route('reports.management-report.export-pdf')}?${exportQuery}`;

    const handleApply = (e) => {
        e.preventDefault();
        get(route('reports.management-report'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <DashboardLayout title="Management Report">
            <Head title="Management Report" />

            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link
                        href={route('reports.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-2"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Reports
                    </Link>
                    <div className="flex items-start gap-3">
                        <div className="bg-indigo-600 p-3 rounded-lg shrink-0">
                            <ClipboardList className="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Management Report
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Total waste diverted % and container-type totals for every client, for a single month.
                            </p>
                        </div>
                    </div>
                </div>

                <form
                    onSubmit={handleApply}
                    className="bg-white dark:bg-gray-800 shadow rounded-lg p-4 sm:p-6 mb-6 flex flex-wrap items-end gap-4"
                >
                    <div>
                        <label htmlFor="month" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Month
                        </label>
                        <select
                            id="month"
                            value={data.month}
                            onChange={(e) => setData('month', Number(e.target.value))}
                            className="block w-full min-w-[10rem] border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            {MONTHS.map((m) => (
                                <option key={m.value} value={m.value}>{m.label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="year" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Year
                        </label>
                        <select
                            id="year"
                            value={data.year}
                            onChange={(e) => setData('year', Number(e.target.value))}
                            className="block w-full min-w-[7rem] border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            {years.map((y) => (
                                <option key={y} value={y}>{y}</option>
                            ))}
                        </select>
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                        {processing ? 'Updating…' : 'Apply'}
                    </button>
                    <a
                        href={exportCsvHref}
                        className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <Download className="h-4 w-4 mr-2 shrink-0" />
                        Export CSV
                    </a>
                    <a
                        href={exportPdfHref}
                        className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <FileText className="h-4 w-4 mr-2 shrink-0" />
                        Export PDF
                    </a>
                </form>

                <div className="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th scope="col" className="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Total Waste Diverted %
                                    </th>
                                    <th scope="col" className="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Total Waste Managed (kg)
                                    </th>
                                    <th scope="col" className="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Container Type Totals
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No companies in scope.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((row) => (
                                        <tr key={row.company_id} className="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {row.company_name}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-gray-700 dark:text-gray-300 text-right tabular-nums">
                                                {Number(row.total_waste_diverted_percentage).toFixed(1)}%
                                            </td>
                                            <td className="px-3 py-3 text-sm text-gray-700 dark:text-gray-300 text-right tabular-nums">
                                                {Number(row.total_waste_managed_kg).toFixed(2)}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                {row.container_totals.length === 0 ? (
                                                    <span className="text-gray-400 dark:text-gray-500">—</span>
                                                ) : (
                                                    row.container_totals.map((c) => `${c.name}: ${c.quantity}`).join(', ')
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
