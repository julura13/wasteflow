import { Head, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import SearchableDropdown from '@/Components/SearchableDropdown';
import { Download, Loader2, Eye, Award } from 'lucide-react';

export default function WasteManagement({ companies, filters }) {
    const { flash } = usePage().props;
    const [pdfExportUuid, setPdfExportUuid] = useState(null);
    const [pdfStatus, setPdfStatus] = useState(null);

    const [selectedCompany, setSelectedCompany] = useState(filters?.company_id || '');
    const [selectedBranch, setSelectedBranch] = useState(filters?.branch_id || '');
    const [selectedSite, setSelectedSite] = useState(filters?.site_id || '');
    const [month, setMonth] = useState(filters?.month || new Date().getMonth() + 1);
    const [year, setYear] = useState(filters?.year || new Date().getFullYear());
    const [toMonth, setToMonth] = useState(filters?.to_month || filters?.month || new Date().getMonth() + 1);
    const [toYear, setToYear] = useState(filters?.to_year || filters?.year || new Date().getFullYear());

    const [branches, setBranches] = useState([]);
    const [sites, setSites] = useState([]);
    const [loadingBranches, setLoadingBranches] = useState(false);
    const [loadingSites, setLoadingSites] = useState(false);

    useEffect(() => {
        if (flash?.waste_management_pdf_export_uuid) {
            setPdfExportUuid(flash.waste_management_pdf_export_uuid);
            setPdfStatus(null);
        }
    }, [flash?.waste_management_pdf_export_uuid]);

    useEffect(() => {
        if (!pdfExportUuid) {
            return undefined;
        }

        let intervalId;
        const poll = async () => {
            try {
                const { data } = await axios.get(route('reports.waste-management-pdf.status', pdfExportUuid));
                setPdfStatus(data);
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(intervalId);
                }
            } catch {
                clearInterval(intervalId);
                setPdfStatus({
                    status: 'failed',
                    error_message: 'Could not check report status. Please refresh the page.',
                });
            }
        };

        poll();
        intervalId = setInterval(poll, 2500);

        return () => clearInterval(intervalId);
    }, [pdfExportUuid]);

    useEffect(() => {
        if (selectedCompany) {
            setLoadingBranches(true);
            axios.get(route('reports.waste-management-branches'), {
                params: { company_id: selectedCompany },
            })
                .then((response) => {
                    setBranches(response.data);
                    setLoadingBranches(false);
                })
                .catch(() => {
                    setBranches([]);
                    setLoadingBranches(false);
                });

            setSelectedBranch('');
            setSelectedSite('');
            setSites([]);
        } else {
            setBranches([]);
            setSelectedBranch('');
            setSelectedSite('');
            setSites([]);
        }
    }, [selectedCompany]);

    useEffect(() => {
        if (selectedBranch) {
            setLoadingSites(true);
            axios.get(route('reports.waste-management-sites'), {
                params: { branch_id: selectedBranch },
            })
                .then((response) => {
                    setSites(response.data);
                    setLoadingSites(false);
                })
                .catch(() => {
                    setSites([]);
                    setLoadingSites(false);
                });

            setSelectedSite('');
        } else {
            setSites([]);
            setSelectedSite('');
        }
    }, [selectedBranch]);

    const requestPdfExport = useCallback(() => {
        if (!selectedCompany) {
            alert('Please select at least a company');
            return;
        }

        setPdfStatus(null);
        const rangeInverted = toYear < year || (toYear === year && toMonth < month);
        router.post(
            route('reports.waste-management-pdf.request'),
            {
                company_id: selectedCompany,
                branch_id: selectedBranch || '',
                site_id: selectedSite || '',
                month,
                year,
                to_month: rangeInverted ? month : toMonth,
                to_year: rangeInverted ? year : toYear,
            },
            { preserveScroll: true },
        );
    }, [selectedCompany, selectedBranch, selectedSite, month, year, toMonth, toYear]);

    const handleSubmit = (e) => {
        e.preventDefault();
        requestPdfExport();
    };

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

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 10 }, (_, i) => currentYear - i);

    const pdfIsProcessing =
        pdfExportUuid &&
        (!pdfStatus || pdfStatus.status === 'pending' || pdfStatus.status === 'processing');

    return (
        <DashboardLayout title="WasteFlow Resource Intelligence Report">
            <Head title="WasteFlow Resource Intelligence Report" />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        WasteFlow Resource Intelligence Report
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Choose scope and period. The PDF is generated in the background; when it is ready, use the download button below.
                    </p>
                </div>

                {(pdfExportUuid || flash?.waste_management_pdf_export_uuid) ? (
                    <div className="mb-4 rounded-lg border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/25 p-4 space-y-3">
                        {flash?.success && flash?.waste_management_pdf_export_uuid && (
                            <p className="text-sm text-primary-900 dark:text-primary-100">{flash.success}</p>
                        )}
                        {pdfIsProcessing && (
                            <div className="flex items-center gap-2 text-sm text-primary-800 dark:text-primary-200">
                                <Loader2 className="h-4 w-4 animate-spin shrink-0" />
                                <span>Generating PDF in the background… This panel updates when it is ready.</span>
                            </div>
                        )}
                        {pdfStatus?.status === 'completed' && pdfStatus.download_url && (
                            <div>
                                <a
                                    href={pdfStatus.download_url}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
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

                <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div className="space-y-6">
                        <div>
                            <SearchableDropdown
                                id="company_id"
                                name="company_id"
                                label={(
                                    <>
                                        Company
                                    </>
                                )}
                                value={selectedCompany}
                                onChange={(v) => setSelectedCompany(v)}
                                options={companies}
                                placeholder="Select a company"
                                menuMatchTriggerWidth
                                required
                            />
                        </div>

                        <div>
                            <SearchableDropdown
                                id="branch_id"
                                name="branch_id"
                                label={(
                                    <>
                                        Branch <span className="text-gray-500 text-xs font-normal">(Optional)</span>
                                    </>
                                )}
                                value={selectedBranch}
                                onChange={(v) => setSelectedBranch(v)}
                                options={branches}
                                menuMatchTriggerWidth
                                placeholder={
                                    !selectedCompany
                                        ? 'Select a company first'
                                        : loadingBranches
                                            ? 'Loading branches…'
                                            : 'All branches'
                                }
                                disabled={!selectedCompany || loadingBranches}
                            />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leave empty to include all branches of the selected company
                            </p>
                        </div>

                        <div>
                            <SearchableDropdown
                                id="site_id"
                                name="site_id"
                                label={(
                                    <>
                                        Site <span className="text-gray-500 text-xs font-normal">(Optional)</span>
                                    </>
                                )}
                                value={selectedSite}
                                onChange={(v) => setSelectedSite(v)}
                                options={sites}
                                menuMatchTriggerWidth
                                placeholder={
                                    !selectedBranch
                                        ? 'Select a branch first'
                                        : loadingSites
                                            ? 'Loading sites…'
                                            : 'All sites'
                                }
                                disabled={!selectedBranch || loadingSites}
                            />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leave empty to include all sites of the selected branch
                            </p>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="month" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    From Month <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="month"
                                    value={month}
                                    onChange={(e) => setMonth(parseInt(e.target.value))}
                                    className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    required
                                >
                                    {months.map((m) => (
                                        <option key={m.value} value={m.value}>
                                            {m.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label htmlFor="year" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    From Year <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="year"
                                    value={year}
                                    onChange={(e) => setYear(parseInt(e.target.value))}
                                    className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    required
                                >
                                    {years.map((y) => (
                                        <option key={y} value={y}>
                                            {y}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label htmlFor="to_month" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    To Month <span className="text-gray-400 text-xs font-normal">(optional, for a custom range)</span>
                                </label>
                                <select
                                    id="to_month"
                                    value={toMonth}
                                    onChange={(e) => setToMonth(parseInt(e.target.value))}
                                    className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                >
                                    {months.map((m) => (
                                        <option key={m.value} value={m.value}>
                                            {m.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label htmlFor="to_year" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    To Year
                                </label>
                                <select
                                    id="to_year"
                                    value={toYear}
                                    onChange={(e) => setToYear(parseInt(e.target.value))}
                                    className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                >
                                    {years.map((y) => (
                                        <option key={y} value={y}>
                                            {y}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="pt-4 flex gap-3">
                            <button
                                type="button"
                                onClick={() => {
                                    if (!selectedCompany) return;
                                    const rangeInverted = toYear < year || (toYear === year && toMonth < month);
                                    router.get(route('reports.resource-intelligence'), {
                                        company_id: selectedCompany,
                                        branch_id: selectedBranch || '',
                                        site_id: selectedSite || '',
                                        month,
                                        year,
                                        to_month: rangeInverted ? month : toMonth,
                                        to_year: rangeInverted ? year : toYear,
                                    });
                                }}
                                disabled={!selectedCompany}
                                className="flex-1 inline-flex justify-center items-center gap-2 px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                            >
                                <Eye className="h-4 w-4" />
                                View Report
                            </button>
                            {selectedCompany ? (
                                <a
                                    href={route('reports.waste-management-certificate.download', {
                                        company_id: selectedCompany,
                                        month,
                                        year,
                                    })}
                                    className="flex-1 inline-flex justify-center items-center gap-2 px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                                >
                                    <Award className="h-4 w-4" />
                                    Download Certificate
                                </a>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="flex-1 inline-flex justify-center items-center gap-2 px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-amber-600 opacity-50"
                                >
                                    <Award className="h-4 w-4" />
                                    Download Certificate
                                </button>
                            )}
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Download Certificate uses the "From" month/year above (single month, not the date range) and the company's landfill diversion rate for that month.
                        </p>
                    </div>
                </form>

                <div className="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h3 className="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
                        Filter Information
                    </h3>
                    <ul className="text-xs text-blue-800 dark:text-blue-300 space-y-1">
                        <li>• <strong>Company only:</strong> Includes all branches and sites of that company</li>
                        <li>• <strong>Company + Branch:</strong> Includes all sites of that branch</li>
                        <li>• <strong>Company + Branch + Site:</strong> Includes only data for that specific site</li>
                    </ul>
                </div>
            </div>
        </DashboardLayout>
    );
}
