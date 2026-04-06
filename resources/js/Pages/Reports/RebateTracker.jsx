import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Calendar, Filter, Download } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import SearchableDropdown from '@/Components/SearchableDropdown';

const siteOptionLabel = (site) =>
    `${site.name}${site.branch?.company ? ` (${site.branch.company.name})` : ''}`;

export default function RebateTracker({ rebateData, companies, filters, totalRebate, totalWeight }) {
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
        get(route('reports.rebate-tracker'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const buildPdfUrl = () => {
        const params = new URLSearchParams();
        if (filters.start_date) params.append('start_date', filters.start_date);
        if (filters.end_date) params.append('end_date', filters.end_date);
        if (filters.company_id) params.append('company_id', filters.company_id);
        if (filters.branch_id) params.append('branch_id', filters.branch_id);
        if (filters.site_id) params.append('site_id', filters.site_id);
        return route('reports.rebate-tracker-pdf') + '?' + params.toString();
    };

    return (
        <DashboardLayout title="Waste Collection & Recycling Report">
            <Head title="Waste Collection & Recycling Report" />

            <div className="mb-6">
                <Link
                    href={route('reports.index')}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Reports
                </Link>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Waste Collection & Recycling Report
                </h1>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Track recycling rebates per company, branch, and site
                </p>
            </div>

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
                                <a
                                    href={buildPdfUrl()}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <Download className="h-4 w-4 mr-2" />
                                    PDF
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div className="mb-4 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4">
                <h3 className="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">Filter Information</h3>
                <ul className="text-xs text-blue-800 dark:text-blue-300 space-y-1">
                    <li>• <strong>Company only:</strong> Rebates across all branches and sites of that company</li>
                    <li>• <strong>Company + Branch:</strong> Rebates across all sites of that branch</li>
                    <li>• <strong>Company + Branch + Site:</strong> Rebates for that specific site only</li>
                </ul>
            </div>

            {totalRebate > 0 && (
                <div className="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg mb-6 p-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span className="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Total Weight</span>
                            <p className="text-lg font-semibold text-green-900 dark:text-green-100">
                                {Number(totalWeight).toFixed(2)} kg
                            </p>
                        </div>
                        <div>
                            <span className="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Total Rebate</span>
                            <p className="text-lg font-semibold text-green-900 dark:text-green-100">
                                R {Number(totalRebate).toFixed(2)}
                            </p>
                        </div>
                        <div>
                            <span className="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Average Rate</span>
                            <p className="text-lg font-semibold text-green-900 dark:text-green-100">
                                R {totalWeight > 0 ? Number(totalRebate / totalWeight).toFixed(2) : '0.00'} / kg
                            </p>
                        </div>
                    </div>
                </div>
            )}

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Rebate Details
                    </h3>
                    {rebateData && rebateData.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Company
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Branch
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Site
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Grade
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Weight (kg)
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Rate (R/kg)
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Total (R)
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Invoice
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {rebateData.map((item, index) => (
                                        <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {new Date(item.date).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {item.company_name}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {item.branch_name}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {item.site_name}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {item.grade}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {Number(item.weight).toFixed(2)}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                R {Number(item.rate).toFixed(2)}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                                R {Number(item.total).toFixed(2)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                {item.supporting_documents && item.supporting_documents.length > 0 ? (
                                                    <div className="flex flex-col gap-1">
                                                        {item.supporting_documents.map((doc) => (
                                                            <a
                                                                key={doc.id}
                                                                href={route('media.download', doc.id)}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="inline-flex items-center text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium"
                                                            >
                                                                <FileText className="h-4 w-4 mr-1" />
                                                                <span className="text-xs">{doc.original_name}</span>
                                                            </a>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No rebate data</h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No rebate data found for the selected filters.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
