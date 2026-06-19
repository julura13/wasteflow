import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { TreePine, Battery, Droplets } from 'lucide-react';
import {
    PieChart as RechartsPieChart,
    Pie,
    Cell,
    ResponsiveContainer,
    Legend,
    Tooltip,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid
} from 'recharts';
import { useMemo } from 'react';

export default function WasteManagementReport() {
    // Sample data matching the PDF
    const reportData = useMemo(() => ({
        companyName: 'XXXX',
        reportDate: 'XXXX/XX/XX',
        environmentalImpact: {
            treesSaved: 20,
            energySaved: 26098,
            waterSaved: 6859.80,
        },
        grades: {
            generalWaste: 550,
            nonCompactableWaste: 0,
            hazardousWaste: 0,
            organicsRecovered: 0,
        },
        recyclingCommodities: [
            { name: 'Alu Cans', qty: 36 },
            { name: 'Alu Foil', qty: 0 },
            { name: 'BOPP', qty: 0 },
            { name: 'CMW', qty: 301 },
            { name: 'CMW Rolls', qty: 0 },
            { name: 'EPS', qty: 16 },
            { name: 'FN/SBM', qty: 0 },
            { name: 'Glass', qty: 300 },
            { name: 'Hangers', qty: 0 },
            { name: 'HD', qty: 142 },
            { name: 'HD - Colour', qty: 0 },
            { name: 'HD - PP', qty: 0 },
            { name: 'HD Clear', qty: 0 },
            { name: 'HD Crates', qty: 0 },
            { name: 'HD Dark', qty: 0 },
            { name: 'HD Light', qty: 0 },
            { name: 'HD White', qty: 0 },
            { name: 'Heavy Steel', qty: 0 },
            { name: 'HL 1', qty: 158 },
            { name: 'HL Books', qty: 0 },
            { name: 'HL Dirty', qty: 0 },
        ],
        recyclingCommodities2: [
            { name: 'K4', qty: 303 },
            { name: 'K4 Rolls', qty: 0 },
            { name: 'Label Backing', qty: 0 },
            { name: 'LD Clear', qty: 0 },
            { name: 'LD Consul', qty: 0 },
            { name: 'LD Mix', qty: 190 },
            { name: 'Light Steel', qty: 20 },
            { name: 'Light Steel Cans', qty: 28 },
            { name: 'Light Steel Drums', qty: 0 },
            { name: 'Mixed Bag', qty: 0 },
            { name: 'Oil', qty: 0 },
            { name: 'PET Clear', qty: 0 },
            { name: 'PET Mix', qty: 84 },
            { name: 'Pet Strapping', qty: 0 },
            { name: 'PP', qty: 0 },
            { name: 'PP Caps', qty: 0 },
            { name: 'Tetrapak', qty: 74 },
            { name: 'Tissue Paper', qty: 215 },
            { name: 'Wrapping', qty: 0 },
            { name: 'XPS', qty: 0 },
            { name: '', qty: null },
        ],
        summary: {
            recyclingRecovered: 1867,
            organicsRecovered: 0,
            totalIncomingWaste: 2417,
            divertedFromLandfill: 77.24,
            landfillSpaceSaved: 7.41,
            lifecycleSaving: 1892,
        },
        // Page 2: Materials CO2e data (kg values per row, same shape as CarbonCalculator API)
        materialsCO2e: [
            { material: 'Paper', weight: 89.884, scope3EF: 44.94, landfillAvoidanceEF: 66.31, lifecycleSaving: 111.25 },
            { material: 'Plastic PP / HD', weight: 142, scope3EF: 284.0, landfillAvoidanceEF: 11.36, lifecycleSaving: 295.36 },
            { material: 'Plastic PS (Polystyrene)', weight: 16, scope3EF: 48.0, landfillAvoidanceEF: 0.8, lifecycleSaving: 48.8 },
            { material: 'Plastic LDPE Film', weight: 190, scope3EF: 380.0, landfillAvoidanceEF: 11.4, lifecycleSaving: 391.4 },
            { material: 'Aluminium', weight: 36, scope3EF: 360.0, landfillAvoidanceEF: 324.0, lifecycleSaving: 684.0 },
            { material: 'Steel', weight: 48, scope3EF: 96.0, landfillAvoidanceEF: 96.0, lifecycleSaving: 192.0 },
            { material: 'Glass', weight: 300, scope3EF: 90.0, landfillAvoidanceEF: 9.0, lifecycleSaving: 99.0 },
            { material: 'Food Waste', weight: 0, scope3EF: 0, landfillAvoidanceEF: 0, lifecycleSaving: 0 },
            { material: 'Garden Waste', weight: 0, scope3EF: 0, landfillAvoidanceEF: 0, lifecycleSaving: 0 },
            { material: 'Batteries', weight: 0, scope3EF: 0, landfillAvoidanceEF: 0, lifecycleSaving: 0 },
            { material: 'Electronics (E-waste)', weight: 0, scope3EF: 0, landfillAvoidanceEF: 0, lifecycleSaving: 0 },
            { material: 'Tetrapak variants', weight: 74, scope3EF: 51.8, landfillAvoidanceEF: 18.5, lifecycleSaving: 70.3 },
            { material: 'Wood (Timber / Pallets)', weight: 0, scope3EF: 0, landfillAvoidanceEF: 0, lifecycleSaving: 0 },
        ],
        // Page 3: Chart data
        stackedBarData: [
            {
                name: 'Total',
                scope3EF: 1354.74,
                landfillAvoidanceEF: 537.37,
            },
        ],
        carbonEmissionsAvoided: 16528,
        // Page 4: Donut chart data
        cumulativeImpact: [
            { name: 'Water Saved', value: 19, color: '#3b82f6' },
            { name: 'Energy Saved', value: 73, color: '#a3e635' },
            { name: 'Total Lifecycle Carbon Avoided (kg CO₂e)', value: 8, color: '#6b7280' },
        ],
        recyclingBreakdown: [
            { name: 'Paper', value: 25, color: '#2F80ED' },
            { name: 'Plastics', value: 30, color: '#F2994A' },
            { name: 'Aluminium', value: 10, color: '#BDBDBD' },
            { name: 'Organics', value: 5, color: '#27AE60' },
            { name: 'Tetrapak', value: 8, color: '#2F80ED' },
            { name: 'Steel', value: 7, color: '#4F4F4F' },
            { name: 'Glass', value: 15, color: '#56CCF2' },
            { name: 'Wood', value: 0, color: '#A0522D' },
        ],
        // Page 5: Gauge and pie data
        wasteVsRecovery: [
            { name: 'Waste', value: 15, color: '#1C1C1C' },
            { name: 'Recovery', value: 85, color: '#2D9CDB' },
        ],
    }), []);

    // Pie chart data
    const pieData = useMemo(() => [
        { name: 'General Waste', value: reportData.grades.generalWaste, color: '#1C1C1C' },
        { name: 'Non Compactable Waste', value: reportData.grades.nonCompactableWaste || 1, color: '#4F4F4F' },
        { name: 'Hazardous Waste', value: reportData.grades.hazardousWaste || 1, color: '#EB5757' },
        { name: 'Organics Recovered', value: reportData.grades.organicsRecovered || 1, color: '#27AE60' },
        { name: 'Recycling Recovered', value: reportData.summary.recyclingRecovered, color: '#6FCF97' },
    ], [reportData]);

    const totalWasteProcessed = reportData.grades.generalWaste +
        reportData.grades.nonCompactableWaste +
        reportData.grades.hazardousWaste +
        reportData.grades.organicsRecovered +
        reportData.summary.recyclingRecovered;

    return (
        <DashboardLayout title="Waste Management Report">
            <Head title="Waste Management Report" />

            {/* Main Report Container - matches PDF layout */}
            <div className="max-w-5xl mx-auto bg-white shadow-lg print:shadow-none">
                {/* Header Section */}
                <div className="bg-[#1e3a5f] text-white px-6 py-4">
                    <h1 className="text-2xl font-bold text-center tracking-wide">
                        WASTE MANAGEMENT REPORT
                    </h1>
                    <div className="text-center mt-1">
                        <p className="text-lg font-semibold">{reportData.companyName}</p>
                        <p className="text-sm">{reportData.reportDate}</p>
                    </div>
                </div>

                {/* Environmental Impact Icons */}
                <div className="bg-[#1e3a5f] px-6 pb-6">
                    <div className="flex justify-center gap-12">
                        {/* Trees Saved */}
                        <div className="text-center">
                            <div className="w-20 h-20 rounded-full bg-[#1e3a5f] border-4 border-[#3b82f6] flex items-center justify-center mx-auto">
                                <TreePine className="w-10 h-10 text-white" />
                            </div>
                            <p className="text-xs text-white mt-2 uppercase tracking-wider">Trees Saved</p>
                            <p className="text-2xl font-bold text-white">{reportData.environmentalImpact.treesSaved}</p>
                        </div>

                        {/* Energy Saved */}
                        <div className="text-center">
                            <div className="w-20 h-20 rounded-full bg-[#3b82f6] flex items-center justify-center mx-auto">
                                <Battery className="w-10 h-10 text-white" />
                            </div>
                            <p className="text-xs text-white mt-2 uppercase tracking-wider">Energy Saved</p>
                            <p className="text-2xl font-bold text-white">{reportData.environmentalImpact.energySaved.toLocaleString()}</p>
                        </div>

                        {/* Water Saved */}
                        <div className="text-center">
                            <div className="w-20 h-20 rounded-full bg-[#3b82f6] flex items-center justify-center mx-auto">
                                <Droplets className="w-10 h-10 text-white" />
                            </div>
                            <p className="text-xs text-white mt-2 uppercase tracking-wider">Water Saved (kL)</p>
                            <p className="text-2xl font-bold text-white">{reportData.environmentalImpact.waterSaved.toLocaleString()}</p>
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="flex">
                    {/* Left Column - Tables */}
                    <div className="w-1/2 p-4">
                        {/* Grade Table */}
                        <table className="w-full text-sm border-collapse mb-4">
                            <thead>
                                <tr>
                                    <th className="bg-[#4a7c9b] text-white text-left px-2 py-1 border border-[#2c5a7a] font-semibold">
                                        GRADE
                                    </th>
                                    <th className="bg-[#4a7c9b] text-white text-right px-2 py-1 border border-[#2c5a7a] font-semibold">
                                        WEIGHT KGS
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td className="px-2 py-1 border border-gray-300 bg-gray-50">General Waste</td>
                                    <td className="px-2 py-1 border border-gray-300 bg-gray-50 text-right">{reportData.grades.generalWaste}</td>
                                </tr>
                                <tr>
                                    <td className="px-2 py-1 border border-gray-300">Non Compactable Waste</td>
                                    <td className="px-2 py-1 border border-gray-300 text-right">{reportData.grades.nonCompactableWaste}</td>
                                </tr>
                                <tr>
                                    <td className="px-2 py-1 border border-gray-300 bg-gray-50">Hazardous Waste</td>
                                    <td className="px-2 py-1 border border-gray-300 bg-gray-50 text-right">{reportData.grades.hazardousWaste}</td>
                                </tr>
                                <tr>
                                    <td className="px-2 py-1 border border-gray-300">Organics Recovered</td>
                                    <td className="px-2 py-1 border border-gray-300 text-right">{reportData.grades.organicsRecovered}</td>
                                </tr>
                                <tr>
                                    <td className="px-2 py-1 border border-gray-300 bg-gray-50">Total Recycling</td>
                                    <td className="px-2 py-1 border border-gray-300 bg-gray-50 text-right">{reportData.summary.recyclingRecovered}</td>
                                </tr>
                                <tr className="font-bold">
                                    <td className="px-2 py-1 border border-gray-300 bg-[#c9dde8]">TOTAL WASTE PROCESSED</td>
                                    <td className="px-2 py-1 border border-gray-300 bg-[#c9dde8] text-right">{totalWasteProcessed}</td>
                                </tr>
                            </tbody>
                        </table>

                        {/* Recycling Recovered Table */}
                        <table className="w-full text-sm border-collapse mb-4">
                            <thead>
                                <tr>
                                    <th colSpan="4" className="bg-[#4a7c9b] text-white text-center px-2 py-1 border border-[#2c5a7a] font-semibold">
                                        RECYCLING RECOVERED
                                    </th>
                                </tr>
                                <tr>
                                    <th className="bg-[#6b9ab8] text-white text-left px-2 py-1 border border-[#4a7c9b] text-xs font-semibold">Commodity</th>
                                    <th className="bg-[#6b9ab8] text-white text-right px-2 py-1 border border-[#4a7c9b] text-xs font-semibold">QTY</th>
                                    <th className="bg-[#6b9ab8] text-white text-left px-2 py-1 border border-[#4a7c9b] text-xs font-semibold">Commodity</th>
                                    <th className="bg-[#6b9ab8] text-white text-right px-2 py-1 border border-[#4a7c9b] text-xs font-semibold">QTY</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.recyclingCommodities.map((item, index) => (
                                    <tr key={index} className={index % 2 === 0 ? 'bg-gray-50' : ''}>
                                        <td className="px-2 py-0.5 border border-gray-300 text-xs">{item.name}</td>
                                        <td className="px-2 py-0.5 border border-gray-300 text-xs text-right">{item.qty}</td>
                                        <td className="px-2 py-0.5 border border-gray-300 text-xs">{reportData.recyclingCommodities2[index]?.name || ''}</td>
                                        <td className="px-2 py-0.5 border border-gray-300 text-xs text-right">
                                            {reportData.recyclingCommodities2[index]?.qty !== null ? reportData.recyclingCommodities2[index]?.qty : ''}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Summary Box */}
                        <div className="border-2 border-[#1e3a5f] rounded">
                            <table className="w-full text-sm">
                                <tbody>
                                    <tr className="bg-[#c9dde8]">
                                        <td className="px-3 py-1 font-semibold border-b border-[#1e3a5f]">Recycling Recovered</td>
                                        <td className="px-3 py-1 text-right font-bold border-b border-[#1e3a5f]">{reportData.summary.recyclingRecovered}</td>
                                    </tr>
                                    <tr className="bg-[#a3e635]">
                                        <td className="px-3 py-1 font-semibold border-b border-[#1e3a5f]">Organics Recovered</td>
                                        <td className="px-3 py-1 text-right font-bold border-b border-[#1e3a5f]">{reportData.summary.organicsRecovered}</td>
                                    </tr>
                                    <tr className="bg-[#c9dde8]">
                                        <td className="px-3 py-1 font-semibold border-b border-[#1e3a5f]">Total Incoming Waste</td>
                                        <td className="px-3 py-1 text-right font-bold border-b border-[#1e3a5f]">{reportData.summary.totalIncomingWaste}</td>
                                    </tr>
                                    <tr className="bg-[#3b82f6] text-white">
                                        <td className="px-3 py-1 font-semibold border-b border-[#1e3a5f]">Diverted From Landfill</td>
                                        <td className="px-3 py-1 text-right font-bold border-b border-[#1e3a5f]">{reportData.summary.divertedFromLandfill}%</td>
                                    </tr>
                                    <tr className="bg-[#c9dde8]">
                                        <td className="px-3 py-1 font-semibold border-b border-[#1e3a5f]">Landfill Space Saved M<sup>3</sup></td>
                                        <td className="px-3 py-1 text-right font-bold border-b border-[#1e3a5f]">{reportData.summary.landfillSpaceSaved}</td>
                                    </tr>
                                    <tr className="bg-[#c9dde8]">
                                        <td className="px-3 py-1 font-semibold">Total Lifecycle Carbon Avoided (kg CO<sub>2</sub>e)</td>
                                        <td className="px-3 py-1 text-right font-bold">{reportData.summary.lifecycleSaving}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Right Column - Chart and Image */}
                    <div className="w-1/2 p-4 flex flex-col">
                        {/* Globe with Recycling Symbol */}
                        <div className="flex-shrink-0 mb-4">
                            <div className="w-full h-36 flex items-center justify-center my-4">
                                <img src="/images/wasteflow-logo.png" className="h-44" />
                            </div>
                        </div>

                        {/* Pie Chart */}
                        <div className="flex-grow">
                            <ResponsiveContainer width="100%" height={600}>
                                <RechartsPieChart>
                                    <Pie
                                        data={pieData}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={0}
                                        outerRadius={140}
                                        paddingAngle={2}
                                        dataKey="value"
                                    >
                                        {pieData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip
                                        formatter={(value, name) => [`${value} kg`, name]}
                                    />
                                    <Legend
                                        layout="vertical"
                                        align="bottom"
                                        verticalAlign="bottom"
                                        iconType="square"
                                        iconSize={12}
                                        wrapperStyle={{ fontSize: '11px', paddingLeft: '10px' }}
                                        formatter={(value) => (
                                            <span className="text-gray-700">{value}</span>
                                        )}
                                    />
                                </RechartsPieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <div className="bg-white border-t-2 border-[#3b82f6] px-6 py-4 text-center">
                    <h2 className="text-xl font-bold text-[#3b82f6] tracking-wide">WASTEFLOW</h2>
                    <p className="text-sm text-[#3b82f6] italic">Sustainable Waste Management</p>
                </div>
            </div>

            {/* Page 2: Materials CO2e Table */}
            <div className="max-w-5xl mx-auto bg-white shadow-lg print:shadow-none print:page-break-before-always mt-8">
                {/* Header */}
                <div className="px-6 py-4 border-b">
                    <h1 className="text-2xl font-bold text-center tracking-wide">WASTE MANAGEMENT REPORT</h1>
                    <div className="text-center mt-1">
                        <p className="text-lg font-semibold">{reportData.companyName}</p>
                        <p className="text-sm">{reportData.reportDate}</p>
                    </div>
                </div>

                {/* Materials Table */}
                <div className="p-6">
                    <table className="w-full text-sm border-collapse">
                        <thead>
                            <tr className="bg-gray-800 text-white">
                                <th className="px-3 py-2 text-left border border-gray-600 font-semibold">Material</th>
                                <th className="px-3 py-2 text-right border border-gray-600 font-semibold">Weight (kg)</th>
                                <th className="px-3 py-2 text-right border border-gray-600 font-semibold">Upstream (Scope 3) Emissions Avoided (kg CO₂e)</th>
                                <th className="px-3 py-2 text-right border border-gray-600 font-semibold">Landfill Emissions Avoided (kg CO₂e)</th>
                                <th className="px-3 py-2 text-right border border-gray-600 font-semibold">Total Lifecycle Carbon Avoided (kg CO₂e)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {reportData.materialsCO2e.map((material, index) => (
                                <tr key={index} className={index % 2 === 0 ? 'bg-gray-50' : ''}>
                                    <td className="px-3 py-2 border border-gray-300">{material.material}</td>
                                    <td className="px-3 py-2 border border-gray-300 text-right">{material.weight.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 3 })}</td>
                                    <td className="px-3 py-2 border border-gray-300 text-right">{material.scope3EF.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    <td className="px-3 py-2 border border-gray-300 text-right">{material.landfillAvoidanceEF.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    <td className="px-3 py-2 border border-gray-300 text-right font-semibold">{material.lifecycleSaving.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                </tr>
                            ))}
                            <tr className="bg-gray-200 font-bold">
                                <td colSpan="2" className="px-3 py-2 border border-gray-300">TOTALS</td>
                                <td className="px-3 py-2 border border-gray-300 text-right">
                                    {reportData.materialsCO2e.reduce((sum, m) => sum + m.scope3EF, 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </td>
                                <td className="px-3 py-2 border border-gray-300 text-right">
                                    {reportData.materialsCO2e.reduce((sum, m) => sum + m.landfillAvoidanceEF, 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </td>
                                <td className="px-3 py-2 border border-gray-300 text-right">
                                    {reportData.materialsCO2e.reduce((sum, m) => sum + m.lifecycleSaving, 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {/* Summary Box */}
                    <div className="mt-6 border border-green-300 bg-green-50 rounded p-4 space-y-2 text-sm">
                        <p>
                            <span className="font-semibold">Material Recovery Impact (kg CO₂e): </span>
                            <span className="font-bold text-green-700">
                                {reportData.materialsCO2e.reduce((sum, m) => sum + m.scope3EF, 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>{' '}
                            — (Avoided emissions from reduced virgin material production)
                        </p>
                        <p>
                            <span className="font-semibold">Landfill Diversion Impact (kg CO₂e): </span>
                            <span className="font-bold text-green-700">
                                {reportData.materialsCO2e.reduce((sum, m) => sum + m.landfillAvoidanceEF, 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>{' '}
                            — (Avoided methane emissions from diversion of biodegradable waste from landfill)
                        </p>
                        <p>
                            <span className="font-semibold">Total Environmental Impact (kg CO₂e): </span>
                            <span className="font-bold text-green-700">
                                {reportData.materialsCO2e.reduce((sum, m) => sum + m.lifecycleSaving, 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>
                        </p>
                    </div>

                    {/* Landfill note */}
                    <div className="mt-3 rounded bg-yellow-50 border border-yellow-300 p-4 text-sm font-semibold text-center leading-snug">
                        Landfill emissions primarily reflect the methane generation potential of biodegradable materials. Inert materials such as metals and glass have negligible associated emissions and are therefore assigned low or zero landfill emission factors.
                    </div>

                    {/* Methodology note */}
                    <div className="mt-3 rounded border border-gray-300 p-4 text-sm text-center leading-snug">
                        Carbon emission factors and avoided emission assumptions are based on internationally recognised standards, including DEFRA (UK Government), the EPA WARM model, and peer-reviewed global life cycle assessment (LCA) datasets (e.g. Ecoinvent). Calculations are aligned with best practice under the GHG Protocol, ensuring consistency, transparency, and the avoidance of double counting.
                    </div>
                </div>
            </div>

            {/* Page 3: Charts */}
            <div className="max-w-5xl mx-auto bg-white shadow-lg print:shadow-none print:page-break-before-always mt-8">
                {/* Header */}
                <div className="px-6 py-4 border-b">
                    <h1 className="text-2xl font-bold text-center tracking-wide">WASTE MANAGEMENT REPORT</h1>
                    <div className="text-center mt-1">
                        <p className="text-lg font-semibold">{reportData.companyName}</p>
                        <p className="text-sm">{reportData.reportDate}</p>
                    </div>
                </div>

                <div className="p-6 space-y-8">
                    {/* Stacked Bar Chart */}
                    <div>
                        <p className="text-center text-sm mb-2">(kg CO₂e)</p>
                        <ResponsiveContainer width="100%" height={400}>
                            <BarChart data={reportData.stackedBarData} layout="vertical">
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis type="number" domain={[0, 3000]} ticks={[0, 500, 1000, 1500, 2000, 2500, 3000]} />
                                <YAxis dataKey="name" type="category" width={80} />
                                <Tooltip />
                                <Legend />
                                <Bar dataKey="scope3EF" stackId="a" fill="#60a5fa" name="Upstream (Scope 3) Emissions Avoided (kg CO₂e)" />
                                <Bar dataKey="landfillAvoidanceEF" stackId="a" fill="#9ca3af" name="Landfill Emissions Avoided (kg CO₂e)" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Single Bar Chart */}
                    <div>
                        <h3 className="text-center font-bold text-lg mb-4">Total Carbon Emissions Avoided in KM</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={[{ name: '1', value: reportData.carbonEmissionsAvoided }]}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" />
                                <YAxis domain={[0, 18000]} ticks={[0, 2000, 4000, 6000, 8000, 10000, 12000, 14000, 16000, 18000]} />
                                <Tooltip />
                                <Bar dataKey="value" fill="#60a5fa" radius={[4, 4, 0, 0]}>
                                    <Cell fill="#60a5fa" />
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                        <div className="text-center mt-2">
                            <span className="text-3xl font-bold text-blue-600">{reportData.carbonEmissionsAvoided.toLocaleString()}</span>
                        </div>
                    </div>

                    {/* Footer Text */}
                    <div className="mt-6 text-sm space-y-2">
                        <p>
                            The CO₂e saved by your recycling and organics recovery is equivalent to a car driving roughly{' '}
                            <span className="font-bold">{reportData.carbonEmissionsAvoided.toLocaleString()}</span> km.
                        </p>
                        <p>
                            By diverting waste from landfill and recycling efficiently, your operations are actively preventing CO₂e from entering the atmosphere.
                        </p>
                    </div>
                </div>
            </div>

            {/* Page 4: Donut Charts */}
            <div className="max-w-5xl mx-auto bg-white shadow-lg print:shadow-none print:page-break-before-always mt-8">
                {/* Header */}
                <div className="px-6 py-4 border-b">
                    <h1 className="text-2xl font-bold text-center tracking-wide">WASTE MANAGEMENT REPORT</h1>
                    <div className="text-center mt-1">
                        <p className="text-lg font-semibold">{reportData.companyName}</p>
                        <p className="text-sm">{reportData.reportDate}</p>
                    </div>
                </div>

                <div className="p-6 space-y-12">
                    {/* Cumulative Impact Dashboard */}
                    <div>
                        <h3 className="text-center font-bold text-xl text-gray-700 mb-4">CUMULATIVE IMPACT DASHBOARD</h3>
                        <div className="flex justify-center gap-4 mb-4">
                            {reportData.cumulativeImpact.map((item, index) => (
                                <div key={index} className="flex items-center gap-2">
                                    <div className="w-4 h-4 rounded" style={{ backgroundColor: item.color }}></div>
                                    <span className="text-sm">{item.name}</span>
                                </div>
                            ))}
                        </div>
                        <ResponsiveContainer width="100%" height={400}>
                            <RechartsPieChart>
                                <Pie
                                    data={reportData.cumulativeImpact}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={80}
                                    outerRadius={150}
                                    paddingAngle={2}
                                    dataKey="value"
                                    label={({ percent }) => `${(percent * 100).toFixed(0)}%`}
                                >
                                    {reportData.cumulativeImpact.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </RechartsPieChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Recycling Breakdown */}
                    <div>
                        <h3 className="text-center font-bold text-xl text-gray-700 mb-4">RECYCLING BREAKDOWN</h3>
                        <ResponsiveContainer width="100%" height={400}>
                            <RechartsPieChart>
                                <Pie
                                    data={reportData.recyclingBreakdown}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={80}
                                    outerRadius={150}
                                    paddingAngle={2}
                                    dataKey="value"
                                >
                                    {reportData.recyclingBreakdown.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </RechartsPieChart>
                        </ResponsiveContainer>
                        <div className="flex justify-center gap-4 mt-4 flex-wrap">
                            {reportData.recyclingBreakdown.map((item, index) => (
                                <div key={index} className="flex items-center gap-2">
                                    <div className="w-4 h-4 rounded" style={{ backgroundColor: item.color }}></div>
                                    <span className="text-sm">{item.name}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {/* Page 5: Gauge and Waste vs Recovery */}
            <div className="max-w-5xl mx-auto bg-white shadow-lg print:shadow-none print:page-break-before-always mt-8">
                {/* Header */}
                <div className="px-6 py-4 border-b">
                    <h1 className="text-2xl font-bold text-center tracking-wide">WASTE MANAGEMENT REPORT</h1>
                    <div className="text-center mt-1">
                        <p className="text-lg font-semibold">{reportData.companyName}</p>
                        <p className="text-sm">{reportData.reportDate}</p>
                    </div>
                </div>

                <div className="p-6 space-y-12">
                    {/* Diverted from Landfill Gauge */}
                    <div>
                        <h3 className="text-center font-bold text-xl text-gray-700 mb-6">DIVERTED FROM LANDFILL</h3>
                        <div className="flex justify-center">
                            <div className="relative w-96 h-48">
                                {/* Gauge background */}
                                <svg viewBox="0 0 200 120" className="w-full h-full">
                                    <defs>
                                        <linearGradient id="gaugeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stopColor="#dc2626" />
                                            <stop offset="50%" stopColor="#fbbf24" />
                                            <stop offset="100%" stopColor="#a3e635" />
                                        </linearGradient>
                                    </defs>
                                    {/* Gauge arc */}
                                    <path
                                        d="M 20 100 A 80 80 0 0 1 180 100"
                                        fill="none"
                                        stroke="url(#gaugeGradient)"
                                        strokeWidth="20"
                                        strokeLinecap="round"
                                    />
                                    {/* Needle */}
                                    <line
                                        x1="100"
                                        y1="100"
                                        x2={100 + 70 * Math.cos((reportData.summary.divertedFromLandfill / 100) * Math.PI - Math.PI / 2)}
                                        y2={100 + 70 * Math.sin((reportData.summary.divertedFromLandfill / 100) * Math.PI - Math.PI / 2)}
                                        stroke="#1e3a5f"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                    />
                                    {/* Center dot */}
                                    <circle cx="100" cy="100" r="5" fill="#1e3a5f" />
                                </svg>
                                {/* Percentage display */}
                                <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 text-center">
                                    <div className="text-4xl font-bold text-gray-800">{reportData.summary.divertedFromLandfill.toFixed(1)}%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Waste vs Recovery Pie Chart */}
                    <div>
                        <h3 className="text-center font-bold text-xl mb-6">WASTE vs RECOVERY</h3>
                        <ResponsiveContainer width="100%" height={400}>
                            <RechartsPieChart>
                                <Pie
                                    data={reportData.wasteVsRecovery}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={0}
                                    outerRadius={150}
                                    paddingAngle={2}
                                    dataKey="value"
                                    label={({ percent }) => `${(percent * 100).toFixed(0)}%`}
                                >
                                    {reportData.wasteVsRecovery.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                    ))}
                                </Pie>
                                <Tooltip />
                                <Legend
                                    layout="horizontal"
                                    align="center"
                                    verticalAlign="bottom"
                                    iconType="square"
                                    iconSize={12}
                                />
                            </RechartsPieChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            {/* Print Button */}
            <div className="max-w-5xl mx-auto mt-6 flex justify-end print:hidden">
                <button
                    onClick={() => window.print()}
                    className="bg-[#3b82f6] hover:bg-[#2563eb] text-white font-semibold py-2 px-6 rounded-lg shadow transition-colors"
                >
                    Print Report
                </button>
            </div>
        </DashboardLayout>
    );
}
