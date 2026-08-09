import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Calendar, Filter, Download, Loader2 } from 'lucide-react';
import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import SearchableDropdown from '@/Components/SearchableDropdown';
import { formatDateYyyyMmDd } from '@/utils/formatDateYyyyMmDd';

const siteOptionLabel = (site) =>
    `${site.name}${site.branch?.company ? ` (${site.branch.company.name})` : ''}`;

export default function WasteStreamCollectionReport({ wasteStreamBreakdown, companies, filters, totalWeight }) {
    const { flash } = usePage().props;
    const [pdfExportUuid, setPdfExportUuid] = useState(null);
    const [pdfStatus, setPdfStatus] = useState(null);

    const { data, setData, get } = useForm({
        start_date: filters.start_date || '',
        end_date: filters.end_date || '',
        company_id: filters.company_id || '',
        branch_id: filters.branch_id || '',
        site_id: filters.site_id || '',
    });

    const [branches, setBranches] = useState([]);
    const [sites, setSites] = useState([]);
    const [loadingBranches, setLoadingBranches] = useState(false);
    const [loadingSites, setLoadingSites] = useState(false);

    // Fetch branches when company changes
    useEffect(() => {
        if (data.company_id) {
            setLoadingBranches(true);
            axios.get(route('reports.waste-management-branches'), {
                params: { company_id: data.company_id },
            })
                .then((response) => {
                    setBranches(response.data);
                    setLoadingBranches(false);
                })
                .catch(() => {
                    setBranches([]);
                    setLoadingBranches(false);
                });
        } else {
            setBranches([]);
        }
    }, [data.company_id]);

    // Fetch sites when branch changes
    useEffect(() => {
        if (data.branch_id) {
            setLoadingSites(true);
            axios.get(route('reports.waste-management-sites'), {
                params: { branch_id: data.branch_id },
            })
                .then((response) => {
                    setSites(response.data);
                    setLoadingSites(false);
                })
                .catch(() => {
                    setSites([]);
                    setLoadingSites(false);
                });
        } else {
            setSites([]);
        }
    }, [data.branch_id]);

    const handleCompanyChange = (value) => {
        setData('company_id', value);
        setData('branch_id', '');
        setData('site_id', '');
    };

    const handleBranchChange = (value) => {
        setData('branch_id', value);
        setData('site_id', '');
    };

    const handleFilter = (e) => {
        e.preventDefault();
        get(route('reports.waste-stream-collection'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    useEffect(() => {
        if (flash?.waste_stream_collection_pdf_export_uuid) {
            setPdfExportUuid(flash.waste_stream_collection_pdf_export_uuid);
            setPdfStatus(null);
        }
    }, [flash?.waste_stream_collection_pdf_export_uuid]);

    useEffect(() => {
        if (!pdfExportUuid) {
            return undefined;
        }

        let intervalId;
        const poll = async () => {
            try {
                const { data } = await axios.get(route('reports.waste-stream-collection-pdf.status', pdfExportUuid));
                setPdfStatus(data);
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(intervalId);
                }
            } catch {
                clearInterval(intervalId);
                setPdfStatus({ status: 'failed', error_message: 'Could not check report status. Please refresh the page.' });
            }
        };

        poll();
        intervalId = setInterval(poll, 2500);

        return () => clearInterval(intervalId);
    }, [pdfExportUuid]);

    const requestPdfExport = useCallback(() => {
        setPdfStatus(null);
        router.post(
            route('reports.waste-stream-collection-pdf.request'),
            {
                start_date: filters.start_date,
                end_date: filters.end_date,
                company_id: filters.company_id ?? '',
                branch_id: filters.branch_id ?? '',
                site_id: filters.site_id ?? '',
            },
            { preserveScroll: true },
        );
    }, [filters.start_date, filters.end_date, filters.company_id, filters.branch_id, filters.site_id]);

    const pdfIsProcessing =
        pdfExportUuid &&
        (!pdfStatus || pdfStatus.status === 'pending' || pdfStatus.status === 'processing');

    return (
        <DashboardLayout title="Waste Stream Collection Report">
            <Head title="Waste Stream Collection Report" />

            <div className="mx-auto max-w-[1600px] px-2 py-4 sm:px-4 sm:py-6 lg:px-6">
                <div className="mb-6">
                    <Link
                        href={route('reports.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Reports
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        Waste Stream Collection Report
                    </h1>
                    <p className="mt-1 text-base text-gray-600 dark:text-gray-400">
                        Collections grouped by waste stream and grade, with order number, slip number, and quantity per collection
                    </p>
                </div>

                {(pdfExportUuid || flash?.waste_stream_collection_pdf_export_uuid) ? (
                    <div className="mb-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/25 p-4 space-y-3">
                        {flash?.success && flash?.waste_stream_collection_pdf_export_uuid && (
                            <p className="text-sm text-green-900 dark:text-green-100">{flash.success}</p>
                        )}
                        {pdfIsProcessing && (
                            <div className="flex items-center gap-2 text-sm text-green-800 dark:text-green-200">
                                <Loader2 className="h-4 w-4 animate-spin shrink-0" />
                                <span>Generating PDF in the background… This page will update when it is ready.</span>
                            </div>
                        )}
                        {pdfStatus?.status === 'completed' && pdfStatus.download_url && (
                            <div>
                                <a
                                    href={pdfStatus.download_url}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <Download className="h-4 w-4 mr-2" />
                                    Download PDF
                                </a>
                            </div>
                        )}
                        {pdfStatus?.status === 'failed' && (
                            <p className="text-sm text-red-700 dark:text-red-300">
                                {pdfStatus.error_message || 'Report generation failed. Please try again.'}
                            </p>
                        )}
                    </div>
                ) : null}

                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <form onSubmit={handleFilter} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                            <div>
                                <label htmlFor="start_date" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Start Date
                                </label>
                                <input
                                    type="date"
                                    id="start_date"
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>

                            <div>
                                <label htmlFor="end_date" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    End Date
                                </label>
                                <input
                                    type="date"
                                    id="end_date"
                                    value={data.end_date}
                                    onChange={(e) => setData('end_date', e.target.value)}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>

                            <div>
                                <SearchableDropdown
                                    id="company_id"
                                    name="company_id"
                                    label="Company (Optional)"
                                    value={data.company_id}
                                    onChange={handleCompanyChange}
                                    options={companies}
                                    placeholder="All Companies"
                                />
                            </div>

                            <div>
                                <SearchableDropdown
                                    id="branch_id"
                                    name="branch_id"
                                    label="Branch (Optional)"
                                    value={data.branch_id}
                                    onChange={handleBranchChange}
                                    options={branches}
                                    placeholder={
                                        !data.company_id
                                            ? 'Select company first'
                                            : loadingBranches
                                                ? 'Loading…'
                                                : 'All Branches'
                                    }
                                    disabled={!data.company_id || loadingBranches}
                                />
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
                                    placeholder={
                                        !data.branch_id
                                            ? 'Select branch first'
                                            : loadingSites
                                                ? 'Loading…'
                                                : 'All Sites'
                                    }
                                    disabled={!data.branch_id || loadingSites}
                                />
                            </div>

                            <div className="flex items-end gap-2">
                                <button
                                    type="submit"
                                    className="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    <Filter className="h-4 w-4 mr-2" />
                                    Filter
                                </button>
                                <button
                                    type="button"
                                    onClick={requestPdfExport}
                                    className="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-60"
                                    disabled={pdfIsProcessing}
                                >
                                    {pdfIsProcessing ? (
                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    ) : (
                                        <Download className="h-4 w-4 mr-2" />
                                    )}
                                    {pdfIsProcessing ? 'Preparing…' : 'PDF'}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {totalWeight > 0 && (
                <div className="bg-green-50 dark:bg-green-900 border-2 border-green-600 dark:border-green-500 rounded-lg mb-6 p-4">
                    <h3 className="text-sm font-bold text-green-700 dark:text-green-300 mb-3">Summary</h3>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Total Weight</span>
                        <p className="text-lg font-semibold text-green-900 dark:text-green-100">
                            {Number(totalWeight).toFixed(2)} kg
                        </p>
                    </div>
                </div>
            )}

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-xl font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Waste Stream / Grade Breakdown
                    </h3>
                    {wasteStreamBreakdown && wasteStreamBreakdown.length > 0 ? (
                        <div className="space-y-6">
                            {wasteStreamBreakdown.map((group) => (
                                <div key={group.heading} className="overflow-x-auto">
                                    <div className="bg-[#1e3a5f] text-white font-semibold text-sm px-3 py-2 rounded-t-md">
                                        {group.heading}
                                    </div>
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-t-0 border-gray-200 dark:border-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Order No
                                                </th>
                                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Slip No
                                                </th>
                                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Qty
                                                </th>
                                                <th className="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Weight (kg)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {group.rows.map((row, index) => (
                                                <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <td className="px-4 py-3 whitespace-nowrap text-base tabular-nums text-gray-900 dark:text-gray-100">
                                                        {formatDateYyyyMmDd(row.date)}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-base text-gray-900 dark:text-gray-100">
                                                        {row.tracking_number}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-base text-gray-900 dark:text-gray-100">
                                                        {row.slip_number}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-pre-line text-base text-gray-900 dark:text-gray-100">
                                                        {row.quantity}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-right text-base text-gray-900 dark:text-gray-100">
                                                        {Number(row.weight).toFixed(2)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot className="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <td colSpan={4} className="px-4 py-3 text-base font-bold text-gray-900 dark:text-gray-100">
                                                    Subtotal
                                                </td>
                                                <td className="px-4 py-3 text-right text-base font-bold text-gray-900 dark:text-gray-100">
                                                    {Number(group.subtotal_weight).toFixed(2)}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No collection data</h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No waste stream collection data found for the selected filters.
                            </p>
                        </div>
                    )}
                </div>
            </div>

            <div className="mt-8 flex flex-col items-center justify-center border-t border-gray-200 dark:border-gray-600 pt-8">
                <img
                    src="/images/logo.png"
                    alt="WasteFlow"
                    className="h-9 w-auto object-contain sm:h-10"
                />
            </div>
            </div>
        </DashboardLayout>
    );
}
