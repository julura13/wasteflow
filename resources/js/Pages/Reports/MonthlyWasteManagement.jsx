import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, FileText, Download } from 'lucide-react';

export default function MonthlyWasteManagement({ companies = [] }) {
    const { data, setData, get } = useForm({
        company_id: '',
        month: new Date().toISOString().slice(0, 7),
    });

    const { auth } = usePage().props;
    const user = auth?.user;
    const userCompanies = user?.is_admin 
        ? companies 
        : companies.filter(c => user?.company_ids?.includes(c.id));

    const handleGenerate = (e) => {
        e.preventDefault();
        if (!data.company_id) {
            alert('Please select a company');
            return;
        }
        
        const url = route('reports.monthly-waste-management', {
            company_id: data.company_id,
            month: data.month,
        });
        
        window.open(url, '_blank');
    };

    return (
        <DashboardLayout title="Monthly Waste Management Report">
            <Head title="Monthly Waste Management Report" />

            <div className="mb-6">
                <Link
                    href={route('reports.index')}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Reports
                </Link>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Monthly Waste Management Report
                </h1>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Generate comprehensive waste management report with environmental impact metrics
                </p>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <form onSubmit={handleGenerate} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="company_id" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    Company <span className="text-red-600">*</span>
                                </label>
                                <select
                                    id="company_id"
                                    value={data.company_id}
                                    onChange={(e) => setData('company_id', e.target.value)}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    required
                                >
                                    <option value="">Select Company</option>
                                    {userCompanies.map((company) => (
                                        <option key={company.id} value={company.id}>
                                            {company.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

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
                        </div>

                        <div className="flex items-end">
                            <button
                                type="submit"
                                className="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                <Download className="h-4 w-4 mr-2" />
                                Generate PDF Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div className="mt-6 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                <h3 className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                    Report Includes:
                </h3>
                <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                    <li>Environmental impact metrics (Trees Saved, Energy Saved, Water Saved)</li>
                    <li>Waste breakdown by type (General, Non-Compactable, Hazardous, Organics)</li>
                    <li>Recycling recovery breakdown by commodity</li>
                    <li>Detailed carbon emissions analysis</li>
                    <li>Cumulative impact dashboard</li>
                    <li>Landfill diversion statistics</li>
                </ul>
                <p className="text-xs text-blue-700 dark:text-blue-300 mt-3">
                    <strong>Note:</strong> Report includes data from finalized orders only for the selected month.
                </p>
            </div>
        </DashboardLayout>
    );
}

