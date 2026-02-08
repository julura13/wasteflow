import { Head, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function WasteManagement({ companies, filters }) {
    const [selectedCompany, setSelectedCompany] = useState(filters?.company_id || '');
    const [selectedBranch, setSelectedBranch] = useState(filters?.branch_id || '');
    const [selectedSite, setSelectedSite] = useState(filters?.site_id || '');
    const [month, setMonth] = useState(filters?.month || new Date().getMonth() + 1);
    const [year, setYear] = useState(filters?.year || new Date().getFullYear());
    
    const [branches, setBranches] = useState([]);
    const [sites, setSites] = useState([]);
    const [loadingBranches, setLoadingBranches] = useState(false);
    const [loadingSites, setLoadingSites] = useState(false);

    // Fetch branches when company changes
    useEffect(() => {
        if (selectedCompany) {
            setLoadingBranches(true);
            axios.get(route('reports.waste-management-branches'), {
                params: { company_id: selectedCompany }
            })
                .then(response => {
                    setBranches(response.data);
                    setLoadingBranches(false);
                })
                .catch(() => {
                    setBranches([]);
                    setLoadingBranches(false);
                });
            
            // Reset branch and site when company changes
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

    // Fetch sites when branch changes
    useEffect(() => {
        if (selectedBranch) {
            setLoadingSites(true);
            axios.get(route('reports.waste-management-sites'), {
                params: { branch_id: selectedBranch }
            })
                .then(response => {
                    setSites(response.data);
                    setLoadingSites(false);
                })
                .catch(() => {
                    setSites([]);
                    setLoadingSites(false);
                });
            
            // Reset site when branch changes
            setSelectedSite('');
        } else {
            setSites([]);
            setSelectedSite('');
        }
    }, [selectedBranch]);

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // At least company must be selected
        if (!selectedCompany) {
            alert('Please select at least a company');
            return;
        }

        const params = {
            company_id: selectedCompany || null,
            branch_id: selectedBranch || null,
            site_id: selectedSite || null,
            month: month,
            year: year,
        };

        // Remove null values
        Object.keys(params).forEach(key => {
            if (params[key] === null || params[key] === '') {
                delete params[key];
            }
        });

        router.get(route('reports.waste-management'), params);
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

    return (
        <DashboardLayout title="Waste Management Report">
            <Head title="Waste Management Report" />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Waste Management Report
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Select filters to generate the report
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div className="space-y-6">
                        {/* Company Selection */}
                        <div>
                            <label htmlFor="company_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Company <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="company_id"
                                value={selectedCompany}
                                onChange={(e) => setSelectedCompany(e.target.value)}
                                className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                required
                            >
                                <option value="">Select a company</option>
                                {companies.map((company) => (
                                    <option key={company.id} value={company.id}>
                                        {company.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Branch Selection */}
                        <div>
                            <label htmlFor="branch_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Branch <span className="text-gray-500 text-xs">(Optional)</span>
                            </label>
                            <select
                                id="branch_id"
                                value={selectedBranch}
                                onChange={(e) => setSelectedBranch(e.target.value)}
                                disabled={!selectedCompany || loadingBranches}
                                className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm disabled:bg-gray-100 dark:disabled:bg-gray-600 disabled:cursor-not-allowed"
                            >
                                <option value="">
                                    {!selectedCompany 
                                        ? 'Select a company first' 
                                        : loadingBranches 
                                        ? 'Loading branches...' 
                                        : 'All branches'}
                                </option>
                                {branches.map((branch) => (
                                    <option key={branch.id} value={branch.id}>
                                        {branch.name}
                                    </option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leave empty to include all branches of the selected company
                            </p>
                        </div>

                        {/* Site Selection */}
                        <div>
                            <label htmlFor="site_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Site <span className="text-gray-500 text-xs">(Optional)</span>
                            </label>
                            <select
                                id="site_id"
                                value={selectedSite}
                                onChange={(e) => setSelectedSite(e.target.value)}
                                disabled={!selectedBranch || loadingSites}
                                className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm disabled:bg-gray-100 dark:disabled:bg-gray-600 disabled:cursor-not-allowed"
                            >
                                <option value="">
                                    {!selectedBranch 
                                        ? 'Select a branch first' 
                                        : loadingSites 
                                        ? 'Loading sites...' 
                                        : 'All sites'}
                                </option>
                                {sites.map((site) => (
                                    <option key={site.id} value={site.id}>
                                        {site.name}
                                    </option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leave empty to include all sites of the selected branch
                            </p>
                        </div>

                        {/* Month and Year Selection */}
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="month" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Month <span className="text-red-500">*</span>
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
                                    Year <span className="text-red-500">*</span>
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
                        </div>

                        {/* Submit Button */}
                        <div className="pt-4">
                            <button
                                type="submit"
                                className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Generate Report
                            </button>
                        </div>
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
