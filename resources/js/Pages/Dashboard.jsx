import { Head, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { useState, useEffect } from 'react';
import {
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer,
    Tooltip,
    Legend,
} from 'recharts';
import { Cloud, Droplet, TreePine, Zap } from 'lucide-react';
import axios from 'axios';

export default function Dashboard({ companies = [], dashboardData = null, filters = {} }) {
    const [branches, setBranches] = useState([]);
    const [sites, setSites] = useState([]);
    const [loadingBranches, setLoadingBranches] = useState(false);
    const [loadingSites, setLoadingSites] = useState(false);

    // Default date range: 1st of current month to today
    const getDefaultFromDate = () => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    };

    const getDefaultToDate = () => {
        return new Date().toISOString().split('T')[0];
    };

    const { data, setData, get } = useForm({
        company_id: filters.company_id || '',
        branch_id: filters.branch_id || '',
        site_id: filters.site_id || '',
        from_date: filters.from_date || getDefaultFromDate(),
        to_date: filters.to_date || getDefaultToDate(),
    });

    // Load branches when company changes
    useEffect(() => {
        if (data.company_id) {
            setLoadingBranches(true);
            axios.get(route('dashboard.branches'), { params: { company_id: data.company_id } })
                .then(response => {
                    setBranches(response.data);
                    setLoadingBranches(false);
                })
                .catch(() => {
                    setBranches([]);
                    setLoadingBranches(false);
                });
        } else {
            setBranches([]);
            setSites([]);
            setData('branch_id', '');
            setData('site_id', '');
        }
    }, [data.company_id]);

    // Load sites when branch changes
    useEffect(() => {
        if (data.branch_id) {
            setLoadingSites(true);
            axios.get(route('dashboard.sites'), { params: { branch_id: data.branch_id } })
                .then(response => {
                    setSites(response.data);
                    setLoadingSites(false);
                })
                .catch(() => {
                    setSites([]);
                    setLoadingSites(false);
                });
        } else {
            setSites([]);
            setData('site_id', '');
        }
    }, [data.branch_id]);

    const handleFilterChange = (field, value) => {
        setData(field, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('dashboard'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const wasteStreamData = dashboardData?.wasteStreamTotals || [];
    const classificationData = dashboardData?.classificationTotals || {
        avoidance: { total: 0, percentage: 0 },
        recycling: { total: 0, percentage: 0 },
        recovery: { total: 0, percentage: 0 },
        disposal: { total: 0, percentage: 0 },
    };
    const environmentalImpact = dashboardData?.environmentalImpact || {
        treesSaved: 0,
        energySaved: 0,
        waterSaved: 0,
        co2Saved: 0,
    };

    // Format number with commas
    const formatNumber = (num) => {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    // Classification colors
    const classificationColors = {
        avoidance: '#9ca3af',
        recycling: '#3b82f6',
        recovery: '#3b82f6',
        disposal: '#1e3a5f',
    };

    // Create pie chart data for classifications
    const createClassificationChartData = (total, percentage) => {
        return [
            { name: 'Total', value: total, fill: classificationColors[Object.keys(classificationColors)[0]] },
            { name: 'Other', value: Math.max(0, 100 - percentage), fill: '#e5e7eb' },
        ];
    };

    return (
        <DashboardLayout title="Dashboard">
            <Head title="Dashboard" />

            {/* Filters */}
            <div className="bg-white rounded-lg shadow p-3 mb-3">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                            Company
                        </label>
                        <select
                            value={data.company_id}
                            onChange={(e) => handleFilterChange('company_id', e.target.value)}
                            className="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                        >
                            <option value="">Select Company</option>
                            {companies.map((company) => (
                                <option key={company.id} value={company.id}>
                                    {company.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                            Branch
                        </label>
                        <select
                            value={data.branch_id}
                            onChange={(e) => handleFilterChange('branch_id', e.target.value)}
                            disabled={!data.company_id || loadingBranches}
                            className="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 py-1.5"
                        >
                            <option value="">Select Branch</option>
                            {branches.map((branch) => (
                                <option key={branch.id} value={branch.id}>
                                    {branch.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                            Site
                        </label>
                        <select
                            value={data.site_id}
                            onChange={(e) => handleFilterChange('site_id', e.target.value)}
                            disabled={!data.branch_id || loadingSites}
                            className="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 py-1.5"
                        >
                            <option value="">Select Site</option>
                            {sites.map((site) => (
                                <option key={site.id} value={site.id}>
                                    {site.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                            From Date
                        </label>
                        <input
                            type="date"
                            value={data.from_date}
                            onChange={(e) => handleFilterChange('from_date', e.target.value)}
                            className="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                            To Date
                        </label>
                        <input
                            type="date"
                            value={data.to_date}
                            onChange={(e) => handleFilterChange('to_date', e.target.value)}
                            className="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                        />
                    </div>

                    <div>
                        <button
                            type="submit"
                            className="w-full px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            Apply
                        </button>
                    </div>
                </form>
            </div>

            {/* All Charts in One Row */}
            <div className="bg-white rounded-lg shadow p-3 mb-3">
                <h2 className="text-sm font-semibold mb-2">Summary of Waste Treatment Outputs and achievements at a glance (kg per waste category)</h2>
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-3">
                    {/* Main Waste Stream Pie Chart */}
                    {wasteStreamData.length > 0 && (
                        <div className="lg:col-span-3">
                            <ResponsiveContainer width="100%" height={180}>
                                <PieChart>
                                    <Pie
                                        data={wasteStreamData}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        // label={({ name, value }) => `${name}: ${formatNumber(value)} kg`}
                                        outerRadius={90}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {wasteStreamData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value) => `${formatNumber(value)} kg`} />
                                    {/* <Legend wrapperStyle={{ fontSize: '10px' }} /> */}
                                </PieChart>
                            </ResponsiveContainer>
                            <div className="mt-1 space-y-0.5 flex items-center space-x-2">
                                {wasteStreamData.map((item, index) => (
                                    <div key={index} className="flex items-center space-x-1">
                                        <div
                                            className="w-2 h-2 rounded"
                                            style={{ backgroundColor: item.color }}
                                        />
                                        <span className="text-xs">{item.name}: {formatNumber(item.value)} kg</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Classification Pie Charts */}
                    <div className="lg:col-span-7">
                        <div className="grid grid-cols-4 gap-2 mb-2">
                            {/* Avoidance */}
                            <div className="text-center">
                                <h3 className="text-xs font-medium mb-1">AVOIDANCE</h3>
                                <ResponsiveContainer width="100%" height={100}>
                                    <PieChart>
                                        <Pie
                                            data={[
                                                { name: 'Avoidance', value: classificationData.avoidance.percentage, fill: classificationColors.avoidance },
                                                { name: 'Other', value: 100 - classificationData.avoidance.percentage, fill: '#e5e7eb' },
                                            ]}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={25}
                                            outerRadius={40}
                                            dataKey="value"
                                            startAngle={90}
                                            endAngle={-270}
                                        >
                                            <Cell fill={classificationColors.avoidance} />
                                            <Cell fill="#e5e7eb" />
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                                <p className="text-xs text-gray-500 mt-1">{classificationData.avoidance.percentage}%</p>
                                <p className="text-xs text-gray-500">kg</p>
                            </div>

                            {/* Recycling */}
                            <div className="text-center">
                                <h3 className="text-xs font-medium mb-1">RECYCLING</h3>
                                <ResponsiveContainer width="100%" height={100}>
                                    <PieChart>
                                        <Pie
                                            data={[
                                                { name: 'Recycling', value: classificationData.recycling.percentage, fill: classificationColors.recycling },
                                                { name: 'Other', value: 100 - classificationData.recycling.percentage, fill: '#e5e7eb' },
                                            ]}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={25}
                                            outerRadius={40}
                                            dataKey="value"
                                            startAngle={90}
                                            endAngle={-270}
                                        >
                                            <Cell fill={classificationColors.recycling} />
                                            <Cell fill="#e5e7eb" />
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                                <p className="text-xs text-gray-500 mt-1">{classificationData.recycling.percentage}%</p>
                                <p className="text-xs text-gray-500">kg</p>
                            </div>

                            {/* Recovery */}
                            <div className="text-center">
                                <h3 className="text-xs font-medium mb-1">RECOVERY</h3>
                                <ResponsiveContainer width="100%" height={100}>
                                    <PieChart>
                                        <Pie
                                            data={[
                                                { name: 'Recovery', value: classificationData.recovery.percentage, fill: classificationColors.recovery },
                                                { name: 'Other', value: 100 - classificationData.recovery.percentage, fill: '#e5e7eb' },
                                            ]}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={25}
                                            outerRadius={40}
                                            dataKey="value"
                                            startAngle={90}
                                            endAngle={-270}
                                        >
                                            <Cell fill={classificationColors.recovery} />
                                            <Cell fill="#e5e7eb" />
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                                <p className="text-xs text-gray-500 mt-1">{classificationData.recovery.percentage}%</p>
                                <p className="text-xs text-gray-500">kg</p>
                            </div>

                            {/* Disposal */}
                            <div className="text-center">
                                <h3 className="text-xs font-medium mb-1">DISPOSAL</h3>
                                <ResponsiveContainer width="100%" height={100}>
                                    <PieChart>
                                        <Pie
                                            data={[
                                                { name: 'Disposal', value: classificationData.disposal.percentage, fill: classificationColors.disposal },
                                                { name: 'Other', value: 100 - classificationData.disposal.percentage, fill: '#e5e7eb' },
                                            ]}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={25}
                                            outerRadius={40}
                                            dataKey="value"
                                            startAngle={90}
                                            endAngle={-270}
                                        >
                                            <Cell fill={classificationColors.disposal} />
                                            <Cell fill="#e5e7eb" />
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                                <p className="text-xs text-gray-500 mt-1">{classificationData.disposal.percentage}%</p>
                                <p className="text-xs text-gray-500">kg</p>
                            </div>
                        </div>

                        {/* Classification Totals */}
                        <div className="grid grid-cols-4 gap-2">
                            <div className="text-center p-1.5 bg-gray-50 rounded">
                                <p className="text-xs text-gray-600">Total Avoidance</p>
                                <p className="text-sm font-semibold">{formatNumber(classificationData.avoidance.total)} kg</p>
                            </div>
                            <div className="text-center p-1.5 bg-gray-50 rounded">
                                <p className="text-xs text-gray-600">Total Recycling</p>
                                <p className="text-sm font-semibold">{formatNumber(classificationData.recycling.total)} kg</p>
                            </div>
                            <div className="text-center p-1.5 bg-gray-50 rounded">
                                <p className="text-xs text-gray-600">Total Recovery</p>
                                <p className="text-sm font-semibold">{formatNumber(classificationData.recovery.total)} kg</p>
                            </div>
                            <div className="text-center p-1.5 bg-gray-50 rounded">
                                <p className="text-xs text-gray-600">Total Disposal</p>
                                <p className="text-sm font-semibold">{formatNumber(classificationData.disposal.total)} kg</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Environmental Achievements */}
            <div className="bg-white rounded-lg shadow p-3">
                <h2 className="text-sm font-semibold mb-2">Environmental achievements through implementation of waste hierarchy options</h2>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div className="text-center p-3 bg-blue-50 rounded-lg">
                        <Cloud className="w-8 h-8 mx-auto mb-1 text-blue-600" />
                        <p className="text-xs text-gray-600 mb-1">Carbon Dioxide Saved</p>
                        <p className="text-lg font-bold text-blue-600">
                            {formatNumber(environmentalImpact.co2Saved)} kg CO₂e
                        </p>
                    </div>
                    <div className="text-center p-3 bg-cyan-50 rounded-lg">
                        <Droplet className="w-8 h-8 mx-auto mb-1 text-cyan-600" />
                        <p className="text-xs text-gray-600 mb-1">Water Saved</p>
                        <p className="text-lg font-bold text-cyan-600">
                            {formatNumber(environmentalImpact.waterSaved)} kL
                        </p>
                    </div>
                    <div className="text-center p-3 bg-green-50 rounded-lg">
                        <TreePine className="w-8 h-8 mx-auto mb-1 text-green-600" />
                        <p className="text-xs text-gray-600 mb-1">Trees Saved</p>
                        <p className="text-lg font-bold text-green-600">
                            {formatNumber(environmentalImpact.treesSaved)} trees
                        </p>
                    </div>
                    <div className="text-center p-3 bg-yellow-50 rounded-lg">
                        <Zap className="w-8 h-8 mx-auto mb-1 text-yellow-600" />
                        <p className="text-xs text-gray-600 mb-1">Energy Saved</p>
                        <p className="text-lg font-bold text-yellow-600">
                            {formatNumber(environmentalImpact.energySaved)} kWh
                        </p>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
