import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Download, FileText, Users } from 'lucide-react';

function TypeMetricsCells({ metrics, endGroupBorder = false }) {
    return (
        <>
            <td className="px-3 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                {metrics.last_finalized_date
                    ? new Date(metrics.last_finalized_date + 'T12:00:00').toLocaleDateString(undefined, {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric',
                      })
                    : '—'}
            </td>
            <td className="px-3 py-3 text-sm text-gray-700 dark:text-gray-300 tabular-nums">
                {metrics.days_since_last_finalized !== null ? metrics.days_since_last_finalized : '—'}
            </td>
            <td className="px-3 py-3 text-sm text-gray-700 dark:text-gray-300 text-right tabular-nums">
                {metrics.finalized_orders_in_period}
            </td>
            <td
                className={`px-3 py-3 text-sm text-gray-700 dark:text-gray-300 text-right tabular-nums${
                    endGroupBorder ? ' border-r border-gray-200 dark:border-gray-700' : ''
                }`}
            >
                {Number(metrics.average_orders_per_month).toFixed(2)}
            </td>
        </>
    );
}

export default function CustomerOrderFrequencies({ rows, lookback_months: lookbackMonthsProp }) {
    const { data, setData, get, processing } = useForm({
        lookback_months: lookbackMonthsProp ?? 12,
    });

    const exportQuery = new URLSearchParams({
        lookback_months: String(data.lookback_months),
    }).toString();
    const exportCsvHref = `${route('reports.customer-order-frequencies.export')}?${exportQuery}`;
    const exportPdfHref = `${route('reports.customer-order-frequencies.export-pdf')}?${exportQuery}`;

    const handleApply = (e) => {
        e.preventDefault();
        get(route('reports.customer-order-frequencies'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <DashboardLayout title="Customer order frequencies">
            <Head title="Customer order frequencies" />

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
                            <Users className="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Customer order frequencies
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Finalized orders only, split by <strong className="font-medium">waste</strong> vs{' '}
                                <strong className="font-medium">recycling</strong>. &quot;Last finalized&quot; uses actual
                                collection date (or update date if missing). Average per month is finalized orders in the
                                lookback window divided by the number of months.
                            </p>
                        </div>
                    </div>
                </div>

                <form
                    onSubmit={handleApply}
                    className="bg-white dark:bg-gray-800 shadow rounded-lg p-4 sm:p-6 mb-6 flex flex-wrap items-end gap-4"
                >
                    <div>
                        <label
                            htmlFor="lookback_months"
                            className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
                        >
                            Lookback (months)
                        </label>
                        <select
                            id="lookback_months"
                            value={data.lookback_months}
                            onChange={(e) => setData('lookback_months', Number(e.target.value))}
                            className="block w-full min-w-[10rem] border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            {[1, 3, 6, 12, 18, 24, 36, 48, 60].map((m) => (
                                <option key={m} value={m}>
                                    {m === 1 ? '1 month' : `${m} months`}
                                </option>
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
                                    <th
                                        rowSpan={2}
                                        scope="col"
                                        className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-bottom border-r border-gray-200 dark:border-gray-700"
                                    >
                                        Customer
                                    </th>
                                    <th
                                        colSpan={4}
                                        scope="colgroup"
                                        className="px-3 py-2 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-emerald-50/80 dark:bg-emerald-950/30"
                                    >
                                        Waste orders
                                    </th>
                                    <th
                                        colSpan={4}
                                        scope="colgroup"
                                        className="px-3 py-2 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-sky-50/80 dark:bg-sky-950/30"
                                    >
                                        Recycling orders
                                    </th>
                                </tr>
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/20"
                                    >
                                        Last finalized
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/20"
                                    >
                                        Days since
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/20"
                                    >
                                        In period
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/20"
                                    >
                                        Avg / mo
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-sky-50/50 dark:bg-sky-950/20"
                                    >
                                        Last finalized
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-sky-50/50 dark:bg-sky-950/20"
                                    >
                                        Days since
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 bg-sky-50/50 dark:bg-sky-950/20"
                                    >
                                        In period
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-sky-50/50 dark:bg-sky-950/20"
                                    >
                                        Avg / mo
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={9}
                                            className="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            No companies in scope.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((row) => (
                                        <tr key={row.company_id} className="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                                                {row.company_name}
                                            </td>
                                            <TypeMetricsCells metrics={row.waste} endGroupBorder />
                                            <TypeMetricsCells metrics={row.recycling} />
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
