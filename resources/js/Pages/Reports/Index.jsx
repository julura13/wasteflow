import { Head, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { TrendingUp, Leaf, Calculator, Box, Droplets } from 'lucide-react';

export default function Index() {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const hasPermission = (permissionName) => permissions.includes(permissionName);

    const reports = [
        {
            title: 'Waste Management Report',
            description: 'Comprehensive waste management summary: Environmental impact, waste grades, recycling commodities, and diversion metrics.',
            href: '/reports/waste-management',
            icon: Leaf,
            color: 'bg-emerald-600',
            details: 'Shows: Trees saved, energy saved, water saved, recycling breakdown, and landfill diversion rate',
        },
        {
            title: 'Carbon Calculator',
            description: 'Enter weights per material to see the same carbon calculations as the report—no orders or data required. Ideal for proofing and customer demos.',
            href: '/reports/carbon-calculator',
            icon: Calculator,
            color: 'bg-teal-600',
            details: 'Manual weight inputs; uses report formulas for Scope 3, landfill avoidance, and lifecycle saving',
            requiredPermission: 'view-carbon-calculator',
        },
        {
            title: 'Landfill space calculator',
            description:
                'Enter weights (kg) per category to see landfill airspace avoided (m³)—same formula as the Waste Management Report. Matches docs/Landfill space saved m3.xlsx.',
            href: '/reports/landfill-space-calculator',
            icon: Box,
            color: 'bg-slate-600',
            details: 'Weight ÷ density (kg/m³) per row, then sum — no orders required',
            requiredPermission: 'view-landfill-space-calculator',
        },
        {
            title: 'Water calculator',
            description:
                'Enter weights (kg) per category to see water saved (L and kL)—same factors as the Waste Management Report. Matches docs/Water Calculator.xlsx.',
            href: '/reports/water-calculator',
            icon: Droplets,
            color: 'bg-sky-600',
            details: 'Weight × factor (L/kg) per row; total kL = sum of litres ÷ 1000',
            requiredPermission: 'view-water-calculator',
        },
        {
            title: 'Waste Collection & Recycling Report',
            description: 'Financial tracking: View rebate earnings per grade and site. Shows how much money was earned from recycling orders.',
            href: '/reports/rebate-tracker',
            icon: TrendingUp,
            color: 'bg-green-500',
            details: 'Requires: Finalized recycling orders with rebate materials and weights captured',
        },
    ];

    const visibleReports = reports.filter(
        (report) => !report.requiredPermission || hasPermission(report.requiredPermission)
    );

    return (
        <DashboardLayout title="Reports">
            <Head title="Reports" />

            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Reports</h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        View and analyze order data, rebates, and performance metrics
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {visibleReports.map((report) => {
                        const Icon = report.icon;
                        return (
                            <div
                                key={report.href}
                                onClick={() => router.visit(report.href)}
                                className="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow p-6 border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 cursor-pointer"
                            >
                                <div className="flex items-start">
                                    <div className={`${report.color} p-3 rounded-lg`}>
                                        <Icon className="h-6 w-6 text-white" />
                                    </div>
                                    <div className="ml-4 flex-1">
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            {report.title}
                                        </h3>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            {report.description}
                                        </p>
                                        {report.details && (
                                            <p className="text-xs text-gray-500 dark:text-gray-500 italic">
                                                {report.details}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </DashboardLayout>
    );
}

