import { Head, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { useState, useEffect, useCallback } from 'react';
import {
    PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, Tooltip,
    ResponsiveContainer, CartesianGrid, LineChart, Line, Legend,
} from 'recharts';
import axios from 'axios';
import LandfillSpaceAvoidedIcon from '@/Components/LandfillSpaceAvoidedIcon';
import SearchableDropdown from '@/Components/SearchableDropdown';
import {
    Cloud, Droplet, TreePine, Zap,
    Truck, Fuel, CarFront, Eye, ChevronDown, ChevronUp,
    Layers, Percent, Recycle, Flame, Home, Gauge,
} from 'lucide-react';

const HEADER_BG = '#9AD993';
const NAVY = '#1F3A5F';
const BRAND_BLUE = '#2563eb';

const MONTHS = [
    { value: 1, label: 'January' }, { value: 2, label: 'February' }, { value: 3, label: 'March' },
    { value: 4, label: 'April' }, { value: 5, label: 'May' }, { value: 6, label: 'June' },
    { value: 7, label: 'July' }, { value: 8, label: 'August' }, { value: 9, label: 'September' },
    { value: 10, label: 'October' }, { value: 11, label: 'November' }, { value: 12, label: 'December' },
];

const CLASS_COLORS = {
    avoidance: '#BDBDBD',
    recycling: '#6FCF97',
    recovery:  '#2D9CDB',
    disposal:  '#1C1C1C',
    diverted:  '#C69200',
};

const MONTH_KEYS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
const MONTH_LABELS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const TREND_COLORS = {
    'Total Waste Diverted': '#2D9CDB',
    'Waste to Landfill': '#EB5757',
    'Total Waste Managed': '#4F4F4F',
};

/**
 * Converts the backend's "one row per series, jan..dec keys" shape (shared by
 * gradeSummaryByYear and wasteManagementTrendByYear) into Recharts' "one point per
 * month, one field per series" shape.
 */
function seriesRowsToMonthlyPoints(rows) {
    return MONTH_KEYS.map((key, i) => {
        const point = { month: MONTH_LABELS_SHORT[i] };
        rows.forEach((row) => {
            point[row.name] = row[key] ?? 0;
        });
        return point;
    });
}

function fmtN(v, dec = 2) {
    const n = parseFloat(v) || 0;
    return n.toLocaleString('en-ZA', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

function fmtWhole(v) {
    return Math.round(parseFloat(v) || 0).toLocaleString('en-ZA');
}

function fmtOneDecimal(v) {
    return (parseFloat(v) || 0).toFixed(1);
}

function useIsPrintMedia() {
    const [printing, setPrinting] = useState(false);
    useEffect(() => {
        const before = () => setPrinting(true);
        const after = () => setPrinting(false);
        window.addEventListener('beforeprint', before);
        window.addEventListener('afterprint', after);
        return () => {
            window.removeEventListener('beforeprint', before);
            window.removeEventListener('afterprint', after);
        };
    }, []);
    return printing;
}

/** Donut using ResponsiveContainer – matches dashboard pattern exactly. */
function ClassificationDonut({ title, totalLabel, percentage, fill, totalKg, printMode = false }) {
    const pct = Math.min(100, Math.max(0, parseFloat(percentage) || 0));
    const pieData = [
        { value: pct, fill },
        { value: Math.max(0, 100 - pct), fill: '#e5e7eb' },
    ];
    const pieProps = {
        cx: '50%', cy: '50%',
        innerRadius: '56%', outerRadius: '76%',
        dataKey: 'value',
        startAngle: 90, endAngle: -270,
        isAnimationActive: false,
        labelLine: false,
        legendType: 'none',
    };
    return (
        <div className="text-center">
            <h3 className="text-xs font-semibold text-gray-700">{title}</h3>
            <div className="relative isolate mx-auto w-full min-h-[158px] shrink-0 overflow-visible" style={{ height: 158 }}>
                <div className="absolute inset-0 z-0 flex items-center justify-center [&_.recharts-legend-wrapper]:hidden [&_.recharts-surface]:outline-none">
                    {printMode ? (
                        <PieChart width={158} height={158} margin={{ top: 0, right: 0, bottom: 0, left: 0 }} accessibilityLayer={false}>
                            <Pie data={pieData} {...pieProps}>
                                <Cell fill={pct > 0 ? fill : '#e5e7eb'} />
                                <Cell fill="#e5e7eb" />
                            </Pie>
                        </PieChart>
                    ) : (
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart margin={{ top: 0, right: 0, bottom: 0, left: 0 }} accessibilityLayer={false}>
                                <Pie data={pieData} {...pieProps}>
                                    <Cell fill={pct > 0 ? fill : '#e5e7eb'} />
                                    <Cell fill="#e5e7eb" />
                                </Pie>
                            </PieChart>
                        </ResponsiveContainer>
                    )}
                </div>
                <div className="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
                    <span className="text-sm font-semibold tabular-nums text-gray-900">
                        {fmtOneDecimal(pct)}%
                    </span>
                </div>
            </div>
            <div className="text-center p-1.5 bg-gray-50 rounded">
                <p className="text-xs text-gray-500">{totalLabel ?? `Total ${title.charAt(0) + title.slice(1).toLowerCase()}`}</p>
                <p className="text-sm font-semibold text-gray-900">{fmtN(totalKg)} kg</p>
            </div>
        </div>
    );
}

/** Landfill saved panel – client icon, mirrors dashboard. */
function LandfillSavedPanel({ m3 }) {
    return (
        <div className="text-center">
            <h3 className="text-xs font-semibold text-gray-700">LANDFILL AIRSPACE SAVED</h3>
            <div className="relative isolate mx-auto mt-0.5 flex w-full max-w-[200px] flex-col items-center justify-center rounded-xl border-0 bg-white px-2"
                style={{ height: 158 }}>
                <LandfillSpaceAvoidedIcon className="h-[5.5rem] w-auto max-w-[5.5rem] shrink-0 object-contain" />
                <p className="mt-1.5 text-xs font-medium text-teal-800">Landfill airspace avoided</p>
            </div>
            <div className="mt-0 text-center p-1.5 bg-gray-50 rounded">
                <p className="text-xs text-gray-500">Total Landfill Airspace Saved</p>
                <p className="text-sm font-semibold text-gray-900 tabular-nums">{fmtN(m3)} m³</p>
            </div>
        </div>
    );
}

function SectionHeader({ children }) {
    return (
        <div className="text-center font-bold text-sm py-2 px-4 text-white" style={{ backgroundColor: NAVY }}>
            {children}
        </div>
    );
}

function ReportHeader({ scopeDisplayName, reportingPeriodLabel }) {
    return (
        <div className="text-center py-4 px-6" style={{ backgroundColor: HEADER_BG }}>
            <div className="text-2xl font-bold text-gray-900">{scopeDisplayName || 'Site Name'}</div>
            <div className="text-base font-semibold text-gray-800">WasteFlow Resource Intelligence Report</div>
            <div className="text-sm text-gray-700">{reportingPeriodLabel}</div>
        </div>
    );
}

export default function ResourceIntelligence({ reportData, companies, filters, isPrint = false }) {
    const { flash } = usePage().props;

    const [filtersOpen, setFiltersOpen] = useState(!filters?.company_id);
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
        if (selectedCompany) {
            setLoadingBranches(true);
            axios.get(route('reports.waste-management-branches'), { params: { company_id: selectedCompany } })
                .then((r) => { setBranches(r.data); setLoadingBranches(false); })
                .catch(() => { setBranches([]); setLoadingBranches(false); });
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
            axios.get(route('reports.waste-management-sites'), { params: { branch_id: selectedBranch } })
                .then((r) => { setSites(r.data); setLoadingSites(false); })
                .catch(() => { setSites([]); setLoadingSites(false); });
            setSelectedSite('');
        } else {
            setSites([]);
            setSelectedSite('');
        }
    }, [selectedBranch]);

    const loadReport = useCallback((e) => {
        e?.preventDefault();
        if (!selectedCompany) return;
        // If the "to" period is before the "from" period, snap it forward instead of submitting an empty range.
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
    }, [selectedCompany, selectedBranch, selectedSite, month, year, toMonth, toYear]);

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 10 }, (_, i) => currentYear - i);

    const rd = reportData || {};
    const summary = rd.summary || {};
    const ct = rd.classificationTotals || {};
    const ei = rd.environmentalImpact || {};
    const grades = rd.grades || {};
    const wasteStreams = rd.wasteStreamTotals || [];
    const materialsCO2e = rd.materialsCO2e || [];
    const materialsCO2eTotals = rd.materialsCO2eTotals || {};
    const cumulativeImpact = rd.cumulativeImpact || [];
    const gradeSummaryByYear = rd.gradeSummaryByYear || [];
    const wasteManagementTrendByYear = rd.wasteManagementTrendByYear || [];
    const gradeSummaryMonthlyPoints = seriesRowsToMonthlyPoints(gradeSummaryByYear);
    const wasteManagementTrendMonthlyPoints = seriesRowsToMonthlyPoints(wasteManagementTrendByYear);

    // Only show commodities with non-zero qty
    const allCommodities = [
        ...(rd.recyclingCommodities || []),
        ...(rd.recyclingCommodities2 || []),
    ].filter((c) => c.name && parseFloat(c.qty) > 0);

    const hasData = !!filters?.company_id;
    const printMedia = useIsPrintMedia();
    const printMode = isPrint || printMedia;

    return (
        <DashboardLayout title="Resource Intelligence Report">
            <Head title="WasteFlow Resource Intelligence Report" />
            <style>{`
                @media print {
                    @page { size: A4 portrait; margin: 10mm 8mm; }
                    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    .resource-intelligence-report { box-shadow: none !important; border: none !important; border-radius: 0 !important; }
                    .print-page-break { page-break-before: always; break-before: page; border-top: none !important; }
                }
            `}</style>

            <div className="w-full max-w-[90rem] mx-auto space-y-4 px-3 sm:px-4 print:max-w-none print:px-0 print:mx-0">

                {/* ── FILTER PANEL ──────────────────────────────────── */}
                {!isPrint && <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 print:hidden">
                    <button type="button" onClick={() => setFiltersOpen((v) => !v)}
                        className="w-full flex items-center justify-between px-5 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750 rounded-lg">
                        <span>
                            {hasData
                                ? (() => {
                                    const fromLabel = `${MONTHS.find((m) => m.value === filters?.month)?.label || ''} ${filters?.year || ''}`;
                                    const isRange = filters?.to_month && filters?.to_year
                                        && (filters.to_month !== filters.month || filters.to_year !== filters.year);
                                    const toLabel = isRange
                                        ? ` – ${MONTHS.find((m) => m.value === filters?.to_month)?.label || ''} ${filters?.to_year || ''}`
                                        : '';
                                    return `${rd.scopeDisplayName || ''} — ${fromLabel}${toLabel}`;
                                })()
                                : 'Select filters to load the report'}
                        </span>
                        {filtersOpen ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                    </button>

                    {filtersOpen && (
                        <form onSubmit={loadReport} className="px-5 pb-5 pt-1 border-t border-gray-100 dark:border-gray-700">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                <SearchableDropdown id="company_id" label="Company" value={selectedCompany}
                                    onChange={setSelectedCompany} options={companies}
                                    placeholder="Select a company" menuMatchTriggerWidth required />
                                <SearchableDropdown id="branch_id"
                                    label={<>Branch <span className="text-gray-400 text-xs font-normal">(optional)</span></>}
                                    value={selectedBranch} onChange={setSelectedBranch} options={branches}
                                    menuMatchTriggerWidth disabled={!selectedCompany || loadingBranches}
                                    placeholder={!selectedCompany ? 'Select company first' : loadingBranches ? 'Loading…' : 'All branches'} />
                                <SearchableDropdown id="site_id"
                                    label={<>Site <span className="text-gray-400 text-xs font-normal">(optional)</span></>}
                                    value={selectedSite} onChange={setSelectedSite} options={sites}
                                    menuMatchTriggerWidth disabled={!selectedBranch || loadingSites}
                                    placeholder={!selectedBranch ? 'Select branch first' : loadingSites ? 'Loading…' : 'All sites'} />
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Month</label>
                                    <select value={month} onChange={(e) => setMonth(parseInt(e.target.value))}
                                        className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm sm:text-sm dark:bg-gray-700 dark:text-gray-100">
                                        {MONTHS.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Year</label>
                                    <select value={year} onChange={(e) => setYear(parseInt(e.target.value))}
                                        className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm sm:text-sm dark:bg-gray-700 dark:text-gray-100">
                                        {years.map((y) => <option key={y} value={y}>{y}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        To Month <span className="text-gray-400 text-xs font-normal">(optional, for a custom range)</span>
                                    </label>
                                    <select value={toMonth} onChange={(e) => setToMonth(parseInt(e.target.value))}
                                        className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm sm:text-sm dark:bg-gray-700 dark:text-gray-100">
                                        {MONTHS.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Year</label>
                                    <select value={toYear} onChange={(e) => setToYear(parseInt(e.target.value))}
                                        className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm sm:text-sm dark:bg-gray-700 dark:text-gray-100">
                                        {years.map((y) => <option key={y} value={y}>{y}</option>)}
                                    </select>
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <button type="submit" disabled={!selectedCompany}
                                    className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-md disabled:opacity-50"
                                    style={{ backgroundColor: NAVY }}>
                                    <Eye size={14} /> Load Report
                                </button>
                            </div>
                        </form>
                    )}
                </div>}

                {!hasData && !isPrint ? (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center text-gray-500">
                        Select a company and period above to load the report.
                    </div>
                ) : (
                    <div className="resource-intelligence-report bg-white rounded-lg shadow border border-gray-200 print:shadow-none print:border-0 print:rounded-none">

                        {/* ── PAGE 1: WASTE TREATMENT SUMMARY ──────────── */}
                        <ReportHeader scopeDisplayName={rd.scopeDisplayName} reportingPeriodLabel={rd.reportingPeriodLabel} />

                        <div className="p-4 space-y-4">
                            <div className="text-xs font-semibold text-gray-600 border border-gray-200 rounded px-3 py-2 bg-gray-50">
                                Summary of Waste Treatment Outputs and achievements at a glance (kg per waste category)
                            </div>

                            {/* Main pie 2/3 + legend 1/3 — matches PDF */}
                            <div className="grid w-full grid-cols-1 gap-4 md:grid-cols-5 md:items-center md:gap-1">
                                <div className="min-w-0 md:col-span-3">
                                    <div className="w-full max-w-2xl">
                                        {wasteStreams.length > 0 ? (
                                            <div className="h-[min(18.75rem,55vw)] w-full min-h-[200px] max-h-[18.75rem] print:h-[280px] [&_.recharts-legend-wrapper]:hidden">
                                                {printMode ? (
                                                    <PieChart width={500} height={280} margin={{ top: 4, right: 4, bottom: 4, left: 4 }} accessibilityLayer={false}>
                                                        <Pie data={wasteStreams} cx="50%" cy="50%"
                                                            outerRadius="88%" dataKey="value"
                                                            isAnimationActive={false} strokeWidth={1} stroke="#fff"
                                                            labelLine={false}
                                                            legendType="none">
                                                            {wasteStreams.map((entry, i) => (
                                                                <Cell key={i} fill={entry.color || '#9AD993'} />
                                                            ))}
                                                        </Pie>
                                                    </PieChart>
                                                ) : (
                                                    <ResponsiveContainer width="100%" height="100%">
                                                        <PieChart margin={{ top: 4, right: 4, bottom: 4, left: 4 }} accessibilityLayer={false}>
                                                            <Pie data={wasteStreams} cx="50%" cy="50%"
                                                                outerRadius="88%" dataKey="value"
                                                                isAnimationActive={false} strokeWidth={1} stroke="#fff"
                                                                labelLine={false}
                                                                legendType="none">
                                                                {wasteStreams.map((entry, i) => (
                                                                    <Cell key={i} fill={entry.color || '#9AD993'} />
                                                                ))}
                                                            </Pie>
                                                            <Tooltip formatter={(v) => `${fmtN(v)} kg`} />
                                                        </PieChart>
                                                    </ResponsiveContainer>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="flex h-64 w-full max-w-md items-center justify-center text-gray-400 text-xs">No data</div>
                                        )}
                                    </div>
                                </div>
                                <div className="min-w-0 md:col-span-2">
                                    <div className="flex h-full min-h-[12rem] flex-col justify-center gap-1 border border-gray-100 bg-gray-50/80 px-3 py-3 text-xs leading-snug text-gray-800 md:border-0 md:bg-transparent md:px-0 md:py-0">
                                        {wasteStreams.filter((s) => parseFloat(s.value) > 0).map((s, i) => (
                                            <div key={i} className="flex items-start gap-1.5">
                                                <span className="mt-0.5 h-3 w-3 shrink-0 rounded-sm" style={{ backgroundColor: s.color }} />
                                                <span>
                                                    <span className="font-medium">{s.name}</span>
                                                    <span className="text-gray-500"> — {fmtN(s.value)} kg</span>
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {/* Classification doughnuts + landfill (bottom of page 1) — matches PDF */}
                            <div className="space-y-8 pt-1">
                                <div className="grid grid-cols-1 items-start gap-2 sm:grid-cols-3 sm:gap-3 sm:items-start">
                                    <ClassificationDonut title="CIRCULARITY (REUSE)"
                                        totalLabel="Circularity (Reuse)"
                                        percentage={ct.avoidance?.percentage ?? 0}
                                        fill={CLASS_COLORS.avoidance}
                                        totalKg={ct.avoidance?.total ?? 0}
                                        printMode={printMode} />
                                    <ClassificationDonut title="RECYCLING"
                                        percentage={ct.recycling?.percentage ?? 0}
                                        fill={CLASS_COLORS.recycling}
                                        totalKg={ct.recycling?.total ?? 0}
                                        printMode={printMode} />
                                    <ClassificationDonut title="RECOVERY"
                                        percentage={ct.recovery?.percentage ?? 0}
                                        fill={CLASS_COLORS.recovery}
                                        totalKg={ct.recovery?.total ?? 0}
                                        printMode={printMode} />
                                </div>
                                <div className="grid grid-cols-1 items-start gap-2 sm:grid-cols-3 sm:gap-3 sm:items-start">
                                    <ClassificationDonut title="DISPOSAL"
                                        percentage={ct.disposal?.percentage ?? 0}
                                        fill={CLASS_COLORS.disposal}
                                        totalKg={ct.disposal?.total ?? 0}
                                        printMode={printMode} />
                                    <ClassificationDonut title="DIVERTED"
                                        percentage={ct.diverted?.percentage ?? 0}
                                        fill={CLASS_COLORS.diverted}
                                        totalKg={ct.diverted?.total ?? 0}
                                        printMode={printMode} />
                                    <LandfillSavedPanel m3={summary.landfillSpaceSaved ?? 0} />
                                </div>
                            </div>
                        </div>

                        {/* ── PAGE 2: GRADE (full width), RECYCLING RECOVERED, CARBON (matches PDF) ── */}
                        <div className="border-t-4 border-gray-100 print-page-break">
                            <ReportHeader scopeDisplayName={rd.scopeDisplayName} reportingPeriodLabel={rd.reportingPeriodLabel} />
                            <div className="p-4 space-y-4">
                                <div className="w-full">
                                    <table className="w-full text-xs border-collapse">
                                        <thead>
                                            <tr style={{ backgroundColor: NAVY, color: 'white' }}>
                                                <th className="text-left px-3 py-2">Grade</th>
                                                <th className="text-right px-3 py-2">Weight KGS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {/* WASTE TO LANDFILL */}
                                            <tr>
                                                <td colSpan={2} className="px-3 py-1 border border-gray-200 text-center font-bold" style={{ backgroundColor: NAVY, color: 'white' }}>WASTE TO LANDFILL</td>
                                            </tr>
                                            {[
                                                { label: 'General Waste', val: grades.generalWaste },
                                                { label: 'Non Compactable Waste', val: grades.nonCompactableWaste },
                                                { label: 'Hazardous Waste', val: grades.hazardousWaste },
                                            ].map((r, i) => (
                                                <tr key={i} className={i % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                    <td className="px-3 py-1 border border-gray-200">{r.label}</td>
                                                    <td className="px-3 py-1 border border-gray-200 text-right">{fmtN(r.val)}</td>
                                                </tr>
                                            ))}
                                            <tr style={{ fontWeight: 'bold' }} className="bg-white">
                                                <td className="px-3 py-1 border border-gray-200">Total Waste to Landfill</td>
                                                <td className="px-3 py-1 border border-gray-200 text-right">{fmtN(ct.disposal?.total ?? 0)}</td>
                                            </tr>

                                            {/* WASTE DIVERTED */}
                                            <tr>
                                                <td colSpan={2} className="px-3 py-1 border border-gray-200 text-center font-bold" style={{ backgroundColor: NAVY, color: 'white' }}>WASTE DIVERTED (All Streams)</td>
                                            </tr>
                                            {[
                                                { label: 'Organics Recovery', val: grades.organicsRecovered ?? 0 },
                                                { label: 'Material Recovery', val: grades.materialRecovery ?? 0 },
                                                { label: 'Circularity (Reuse)', val: ct.avoidance?.total ?? 0 },
                                                { label: 'Total Recycling', val: ct.recycling?.total ?? 0 },
                                            ].map((r, i) => (
                                                <tr key={i} className={i % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                    <td className="px-3 py-1 border border-gray-200">{r.label}</td>
                                                    <td className="px-3 py-1 border border-gray-200 text-right">{fmtN(r.val)}</td>
                                                </tr>
                                            ))}
                                            <tr style={{ fontWeight: 'bold' }} className="bg-white">
                                                <td className="px-3 py-1 border border-gray-200">Total Waste Diverted</td>
                                                <td className="px-3 py-1 border border-gray-200 text-right">{fmtN(ct.diverted?.total ?? 0)}</td>
                                            </tr>

                                            <tr style={{ backgroundColor: '#c9dde8', fontWeight: 'bold' }}>
                                                <td className="px-3 py-1 border border-gray-300">TOTAL WASTE MANAGED</td>
                                                <td className="px-3 py-1 border border-gray-300 text-right">
                                                    {fmtN(ct.total ?? 0)}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p className="text-gray-400 mt-1" style={{ fontSize: '10px', lineHeight: '1.4' }}>
                                        All diversion streams are mutually exclusive and represent distinct treatment pathways (recycling, organics, recovery, and reuse).
                                    </p>
                                </div>
                                <div>
                                    <SectionHeader>RECYCLING RECOVERED</SectionHeader>
                                    {allCommodities.length === 0 ? (
                                        <p className="text-xs text-gray-400 py-4 text-center border border-t-0 border-gray-200 rounded-b">
                                            No recycling data for this period.
                                        </p>
                                    ) : (
                                        <div className="grid grid-cols-1 gap-0 overflow-hidden border border-t-0 border-gray-200 rounded-b sm:grid-cols-2">
                                            {[
                                                allCommodities.slice(0, Math.ceil(allCommodities.length / 2)),
                                                allCommodities.slice(Math.ceil(allCommodities.length / 2)),
                                            ].map((col, ci) => (
                                                <table key={ci} className={`w-full text-xs border-collapse ${ci === 0 ? 'sm:border-r sm:border-gray-200' : ''}`}>
                                                    <thead>
                                                        <tr style={{ backgroundColor: '#4a7c9b', color: 'white' }}>
                                                            <th className="px-3 py-2 text-left">Commodity</th>
                                                            <th className="px-3 py-2 text-right">QTY (kg)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {col.map((c, i) => (
                                                            <tr key={i} className={i % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                                <td className="border-b border-gray-100 px-3 py-1">{c.name}</td>
                                                                <td className="border-b border-gray-100 px-3 py-1 text-right font-semibold tabular-nums">{fmtN(c.qty)}</td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            ))}
                                        </div>
                                    )}
                                </div>

                            </div>
                        </div>

                        {/* ── PAGE 3: CARBON ACCOUNTING (print: own page to avoid table split) ── */}
                        <div className={`border-t-4 border-gray-100 ${printMode ? 'print-page-break' : ''}`}>
                            {printMode && (
                                <ReportHeader
                                    scopeDisplayName={rd.scopeDisplayName}
                                    reportingPeriodLabel={rd.reportingPeriodLabel}
                                />
                            )}
                            <div className="p-4 space-y-4">
                                <div className="text-sm font-bold text-center py-1" style={{ color: NAVY }}>Carbon Accounting Report</div>

                                <table className={`w-full border-collapse ${printMode ? 'text-[11px]' : 'text-xs'}`}>
                                    <thead>
                                        <tr style={{ backgroundColor: NAVY, color: 'white' }}>
                                            <th className={`${printMode ? 'px-2 py-1.5' : 'px-3 py-2'} text-left`}>Material</th>
                                            <th className={`${printMode ? 'px-2 py-1.5' : 'px-3 py-2'} text-right`}>Weight (kg)</th>
                                            <th className={`${printMode ? 'px-2 py-1.5' : 'px-3 py-2'} text-right`}>Upstream (Scope 3) Avoided (kg CO₂e)</th>
                                            <th className={`${printMode ? 'px-2 py-1.5' : 'px-3 py-2'} text-right`}>Landfill Avoided (kg CO₂e)</th>
                                            <th className={`${printMode ? 'px-2 py-1.5' : 'px-3 py-2'} text-right`}>Total Lifecycle Avoided (kg CO₂e)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {materialsCO2e.map((row, i) => (
                                            <tr key={i} className={i % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-200`}>{row.material}</td>
                                                <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-200 text-right tabular-nums`}>{fmtN(row.weight)}</td>
                                                <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-200 text-right tabular-nums`}>{fmtN(row.scope3EF)}</td>
                                                <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-200 text-right tabular-nums`}>{fmtN(row.landfillAvoidanceEF)}</td>
                                                <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-200 text-right font-semibold tabular-nums`}>{fmtN(row.lifecycleSaving)}</td>
                                            </tr>
                                        ))}
                                        <tr style={{ backgroundColor: '#c9dde8', fontWeight: 'bold' }}>
                                            <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-300`}>TOTALS</td>
                                            <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-300 text-right`} />
                                            <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-300 text-right tabular-nums`}>{fmtN(materialsCO2eTotals.scope3EF)}</td>
                                            <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-300 text-right tabular-nums`}>{fmtN(materialsCO2eTotals.landfillAvoidanceEF)}</td>
                                            <td className={`${printMode ? 'px-2 py-0.5' : 'px-3 py-1'} border border-gray-300 text-right tabular-nums`}>{fmtN(materialsCO2eTotals.lifecycleSaving)}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                {/* Carbon summary box */}
                                <div className={`border border-green-300 rounded bg-green-50 space-y-2 leading-relaxed ${printMode ? 'p-3 text-[11px]' : 'p-4 text-xs'}`}>
                                    <p>
                                        <strong>Material Recovery Impact (kg CO₂e): </strong>
                                        <span className="text-green-700 font-bold">{fmtN(materialsCO2eTotals.scope3EF)}</span>
                                        {' '}— (Avoided emissions from reduced virgin material production)
                                    </p>
                                    <p>
                                        <strong>Landfill Diversion Impact (kg CO₂e): </strong>
                                        <span className="text-green-700 font-bold">{fmtN(materialsCO2eTotals.landfillAvoidanceEF)}</span>
                                        {' '}— (Avoided methane emissions from diversion of biodegradable waste from landfill)
                                    </p>
                                    <p>
                                        <strong>Total Environmental Impact (kg CO₂e): </strong>
                                        <span className="text-green-700 font-bold">{fmtN(materialsCO2eTotals.lifecycleSaving)}</span>
                                    </p>
                                </div>

                                {/* Landfill note */}
                                <div className={`rounded bg-yellow-50 border border-yellow-300 text-center font-semibold leading-snug ${printMode ? 'p-3 text-[10px]' : 'p-4 text-xs'}`}>
                                    Landfill emissions primarily reflect the methane generation potential of biodegradable materials. Inert materials such as metals and glass have negligible associated emissions and are therefore assigned low or zero landfill emission factors.
                                </div>

                                {/* Methodology note */}
                                <div className={`rounded border border-gray-300 text-center leading-snug ${printMode ? 'p-3 text-[10px]' : 'p-4 text-xs'}`}>
                                    Carbon emission factors and avoided emission assumptions are based on internationally recognised standards, including DEFRA (UK Government), the EPA WARM model, and peer-reviewed global life cycle assessment (LCA) datasets (e.g. Ecoinvent). Calculations are aligned with best practice under the GHG Protocol, ensuring consistency, transparency, and the avoidance of double counting.
                                </div>
                            </div>
                        </div>

                        {/* ── PAGE 3: ENVIRONMENTAL IMPACT ─────────────── */}
                        <div className="border-t-4 border-gray-100 print-page-break">
                            <div className="text-center py-4 px-6" style={{ backgroundColor: HEADER_BG }}>
                                <div className="text-2xl font-bold text-gray-900">{rd.scopeDisplayName}</div>
                                <div className="text-base font-semibold text-gray-800">WasteFlow Resource Intelligence Report</div>
                                <div className="text-sm text-gray-700">{rd.reportingPeriodLabel}</div>
                            </div>

                            <div className="p-4 space-y-4">
                                <h2 className="text-sm font-semibold text-gray-900">
                                    Environmental Impact &amp; Resource Savings
                                </h2>

                                {/* 4 primary KPI tiles – matches dashboard exactly */}
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <div className="text-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                                        <Cloud className="w-8 h-8 mx-auto mb-1 text-blue-600" />
                                        <p className="text-xs text-gray-600 mb-1">Total Lifecycle Carbon Avoided (kg CO₂e)</p>
                                        <p className="text-lg font-bold text-blue-600">
                                            {fmtWhole(ei.co2Saved)} kg CO₂e
                                        </p>
                                    </div>
                                    <div className="text-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
                                        <Droplet className="w-8 h-8 mx-auto mb-1 text-cyan-600" />
                                        <p className="text-xs text-gray-600 mb-1">Water Saved</p>
                                        <p className="text-lg font-bold text-cyan-600">
                                            {fmtWhole(ei.waterSaved)} kL
                                        </p>
                                        <p className="text-[10px] text-gray-500 leading-snug px-0.5 mb-2">
                                            Water Savings (kL – Recycling vs Virgin Production)
                                        </p>
                                    </div>
                                    <div className="text-center p-3 bg-green-50 rounded-lg border border-green-100">
                                        <TreePine className="w-8 h-8 mx-auto mb-1 text-green-600" />
                                        <p className="text-xs text-gray-600 mb-1">Trees Preserved</p>
                                        <p className="text-lg font-bold text-green-600">
                                            {fmtWhole(ei.treesSaved)} trees
                                        </p>
                                        <p className="text-[10px] text-gray-500 leading-snug px-0.5 mb-2">
                                            Trees preserved – Paper based tree conversion
                                        </p>
                                    </div>
                                    <div className="text-center p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                                        <Zap className="w-8 h-8 mx-auto mb-1 text-yellow-600" />
                                        <p className="text-xs text-gray-600 mb-1 leading-snug px-0.5">
                                            Electricity Equivalent (kWh – SA Grid)
                                        </p>
                                        <p className="text-lg font-bold text-yellow-600">
                                            {fmtWhole(ei.electricityEquivalentKwhSaGrid)} kWh
                                        </p>
                                    </div>
                                </div>

                                {/* Circular economy & additional savings – matches dashboard */}
                                <div className="mt-4 pt-4 border-t border-gray-100">
                                    <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-700 mb-1">
                                        Circular Economy &amp; Additional Savings
                                    </h3>
                                    <p className="text-[11px] text-gray-500 leading-relaxed mb-4">
                                        Circular economy rate reflects reuse, recycling, and organics recovery as a share of total waste managed.
                                    </p>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div className="text-center p-3.5 bg-emerald-50 rounded-lg border border-emerald-100 shadow-sm">
                                            <Recycle className="w-7 h-7 mx-auto mb-2 text-emerald-600" strokeWidth={1.75} />
                                            <p className="text-xs text-gray-600 mb-1.5 leading-snug px-1">Circular Economy Rate</p>
                                            <p className="text-base font-bold text-emerald-600 tabular-nums">
                                                {fmtOneDecimal(summary.divertedFromLandfill ?? 0)}
                                                <span className="text-sm font-semibold text-emerald-500/90">%</span>
                                            </p>
                                        </div>
                                        <div className="text-center p-3.5 bg-amber-50 rounded-lg border border-amber-100 shadow-sm">
                                            <Flame className="w-7 h-7 mx-auto mb-2 text-amber-600" strokeWidth={1.75} />
                                            <p className="text-xs text-gray-600 mb-1.5 leading-snug px-1">Barrels of Oil Saved</p>
                                            <p className="text-base font-bold text-amber-600 tabular-nums">
                                                {fmtWhole(ei.barrelsOfOilSaved)}
                                            </p>
                                        </div>
                                        <div className="text-center p-3.5 bg-rose-50 rounded-lg border border-rose-100 shadow-sm">
                                            <Home className="w-7 h-7 mx-auto mb-2 text-rose-600" strokeWidth={1.75} />
                                            <p className="text-xs text-gray-600 mb-1.5 leading-snug px-1">Homes Powered (1 Month)</p>
                                            <p className="text-base font-bold text-rose-600 tabular-nums">
                                                {fmtWhole(ei.homesPoweredOneMonth)}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Carbon equivalency – matches dashboard */}
                                <div className="mt-4 pt-4 border-t border-gray-100">
                                    <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-700 mb-1">
                                        Carbon Equivalency Indicators
                                    </h3>
                                    <p className="text-[11px] text-gray-500 leading-relaxed mb-4">
                                        From lifecycle saving, SA factors — see docs/Dashboard &amp; Reports - Metrics
                                    </p>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div className="text-center p-3.5 bg-indigo-50 rounded-lg border border-indigo-100 shadow-sm">
                                            <Truck className="w-7 h-7 mx-auto mb-2 text-indigo-600" strokeWidth={1.75} />
                                            <p className="text-xs text-gray-600 mb-1.5 leading-snug px-1">Transport Equivalent (km Avoided)</p>
                                            <p className="text-base font-bold text-indigo-600 tabular-nums">
                                                {fmtWhole(ei.transportEquivalentKm)}{' '}
                                                <span className="text-sm font-semibold text-indigo-500/90">km</span>
                                            </p>
                                        </div>
                                        <div className="text-center p-3.5 bg-orange-50 rounded-lg border border-orange-100 shadow-sm">
                                            <Fuel className="w-7 h-7 mx-auto mb-2 text-orange-600" strokeWidth={1.75} />
                                            <p className="text-xs text-gray-600 mb-1.5 leading-snug px-1">Fuel Equivalent (L Petrol Avoided)</p>
                                            <p className="text-base font-bold text-orange-600 tabular-nums">
                                                {fmtWhole(ei.fuelEquivalentLitresPetrol)}{' '}
                                                <span className="text-sm font-semibold text-orange-500/90">L</span>
                                            </p>
                                        </div>
                                        <div className="text-center p-3.5 bg-violet-50 rounded-lg border border-violet-100 shadow-sm">
                                            <CarFront className="w-7 h-7 mx-auto mb-2 text-violet-600" strokeWidth={1.75} />
                                            <p className="text-xs text-gray-600 mb-1.5 leading-snug px-1">Cars Off the Road (Annual Equiv.)</p>
                                            <p className="text-base font-bold text-violet-600 tabular-nums">
                                                {fmtOneDecimal(ei.carsOffRoadAnnualEquivalent)}{' '}
                                                <span className="text-sm font-semibold text-violet-500/90">cars</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Monthly material recovery by waste grade (Jan-Dec line graph) */}
                                {gradeSummaryByYear.length > 0 && (
                                    <div className="mt-4 pt-4 border-t border-gray-100">
                                        <SectionHeader>MONTHLY MATERIAL RECOVERY BY WASTE GRADE (KG)</SectionHeader>
                                        <div className="pt-4">
                                            {printMode ? (
                                                <LineChart width={700} height={260} data={gradeSummaryMonthlyPoints} margin={{ left: 10, right: 20, top: 5, bottom: 5 }}>
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis dataKey="month" tick={{ fontSize: 10 }} />
                                                    <YAxis tick={{ fontSize: 10 }} />
                                                    <Legend wrapperStyle={{ fontSize: 10 }} />
                                                    {gradeSummaryByYear.map((row) => (
                                                        <Line key={row.name} type="monotone" dataKey={row.name} stroke={row.color} dot={false} isAnimationActive={false} />
                                                    ))}
                                                </LineChart>
                                            ) : (
                                                <ResponsiveContainer width="100%" height={260}>
                                                    <LineChart data={gradeSummaryMonthlyPoints} margin={{ left: 10, right: 20, top: 5, bottom: 5 }}>
                                                        <CartesianGrid strokeDasharray="3 3" />
                                                        <XAxis dataKey="month" tick={{ fontSize: 10 }} />
                                                        <YAxis tick={{ fontSize: 10 }} />
                                                        <Tooltip formatter={(v) => `${fmtN(v)} kg`} />
                                                        <Legend wrapperStyle={{ fontSize: 10 }} />
                                                        {gradeSummaryByYear.map((row) => (
                                                            <Line key={row.name} type="monotone" dataKey={row.name} stroke={row.color} dot={false} isAnimationActive={false} />
                                                        ))}
                                                    </LineChart>
                                                </ResponsiveContainer>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Waste management performance trends (Jan-Dec line graph) */}
                                {wasteManagementTrendByYear.length > 0 && (
                                    <div className="mt-4 pt-4 border-t border-gray-100">
                                        <SectionHeader>WASTE MANAGEMENT PERFORMANCE TRENDS</SectionHeader>
                                        <div className="pt-4">
                                            {printMode ? (
                                                <LineChart width={700} height={260} data={wasteManagementTrendMonthlyPoints} margin={{ left: 10, right: 20, top: 5, bottom: 5 }}>
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis dataKey="month" tick={{ fontSize: 10 }} />
                                                    <YAxis tick={{ fontSize: 10 }} />
                                                    <Legend wrapperStyle={{ fontSize: 10 }} />
                                                    {wasteManagementTrendByYear.map((row) => (
                                                        <Line key={row.name} type="monotone" dataKey={row.name} stroke={TREND_COLORS[row.name] ?? '#828282'} dot={false} isAnimationActive={false} />
                                                    ))}
                                                </LineChart>
                                            ) : (
                                                <ResponsiveContainer width="100%" height={260}>
                                                    <LineChart data={wasteManagementTrendMonthlyPoints} margin={{ left: 10, right: 20, top: 5, bottom: 5 }}>
                                                        <CartesianGrid strokeDasharray="3 3" />
                                                        <XAxis dataKey="month" tick={{ fontSize: 10 }} />
                                                        <YAxis tick={{ fontSize: 10 }} />
                                                        <Tooltip formatter={(v) => `${fmtN(v)} kg`} />
                                                        <Legend wrapperStyle={{ fontSize: 10 }} />
                                                        {wasteManagementTrendByYear.map((row) => (
                                                            <Line key={row.name} type="monotone" dataKey={row.name} stroke={TREND_COLORS[row.name] ?? '#828282'} dot={false} isAnimationActive={false} />
                                                        ))}
                                                    </LineChart>
                                                </ResponsiveContainer>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Cumulative impact bar chart */}
                                {cumulativeImpact.length > 0 && (
                                    <div className="mt-4 pt-4 border-t border-gray-100">
                                        <SectionHeader>CUMULATIVE IMPACT DASHBOARD</SectionHeader>
                                        <div className="pt-4">
                                            {printMode ? (
                                                <BarChart width={700} height={200} data={cumulativeImpact}
                                                    layout="vertical" margin={{ left: 20, right: 40, top: 5, bottom: 5 }}>
                                                    <CartesianGrid strokeDasharray="3 3" horizontal={false} />
                                                    <XAxis type="number" tick={{ fontSize: 10 }} />
                                                    <YAxis type="category" dataKey="name" width={240} tick={{ fontSize: 10 }} />
                                                    <Bar dataKey="value" isAnimationActive={false}>
                                                        {cumulativeImpact.map((entry, i) => (
                                                            <Cell key={i} fill={entry.color} />
                                                        ))}
                                                    </Bar>
                                                </BarChart>
                                            ) : (
                                                <ResponsiveContainer width="100%" height={200}>
                                                    <BarChart data={cumulativeImpact}
                                                        layout="vertical" margin={{ left: 20, right: 40, top: 5, bottom: 5 }}>
                                                        <CartesianGrid strokeDasharray="3 3" horizontal={false} />
                                                        <XAxis type="number" tick={{ fontSize: 10 }} />
                                                        <YAxis type="category" dataKey="name" width={240} tick={{ fontSize: 10 }} />
                                                        <Tooltip formatter={(v) => fmtN(v)} />
                                                        <Bar dataKey="value" isAnimationActive={false}>
                                                            {cumulativeImpact.map((entry, i) => (
                                                                <Cell key={i} fill={entry.color} />
                                                            ))}
                                                        </Bar>
                                                    </BarChart>
                                                </ResponsiveContainer>
                                            )}
                                        </div>

                                        {/* Landfill Airspace Saved + Diversion Rate + Recovery Rate + Carbon Avoidance Intensity tiles */}
                                        <div className="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                            <div className="text-center p-3 bg-teal-50 rounded-lg border border-teal-100">
                                                <Layers className="w-8 h-8 mx-auto mb-1 text-teal-600" />
                                                <p className="text-xs text-gray-600 mb-1">Landfill Airspace Saved</p>
                                                <p className="text-lg font-bold text-teal-600">
                                                    {fmtN(summary.landfillSpaceSaved ?? 0)} m³
                                                </p>
                                            </div>
                                            <div className="text-center p-3 bg-purple-50 rounded-lg border border-purple-100">
                                                <Percent className="w-8 h-8 mx-auto mb-1 text-purple-600" />
                                                <p className="text-xs text-gray-600 mb-1">Diversion Rate</p>
                                                <p className="text-lg font-bold text-purple-600">
                                                    {fmtOneDecimal(summary.divertedFromLandfill ?? 0)}%
                                                </p>
                                            </div>
                                            <div className="text-center p-3 bg-sky-50 rounded-lg border border-sky-100">
                                                <Recycle className="w-8 h-8 mx-auto mb-1 text-sky-600" />
                                                <p className="text-xs text-gray-600 mb-1">Recovery Rate</p>
                                                <p className="text-lg font-bold text-sky-600">
                                                    {fmtOneDecimal(ct.recovery?.percentage ?? 0)}%
                                                </p>
                                            </div>
                                            <div className="text-center p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                                                <Gauge className="w-8 h-8 mx-auto mb-1 text-emerald-600" />
                                                <p className="text-xs text-gray-600 mb-1">Carbon Avoidance Intensity</p>
                                                <p className="text-lg font-bold text-emerald-600">
                                                    {fmtN(summary.carbonAvoidanceIntensity ?? 0)} kg CO₂e/kg
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* ── PAGE 4: METHODOLOGY — client: keep content; add logo at end; readable copy ── */}
                        <div className="border-t-4 border-gray-100 p-4 sm:p-5 bg-white print-page-break">
                            <div className="font-bold text-sm mb-3" style={{ color: NAVY }}>Methodology &amp; Data Sources</div>
                            <div className="space-y-2.5 text-sm text-gray-600 leading-relaxed max-w-4xl">
                                <p>This report has been prepared using verified waste data collected on-site and processed through the WasteFlow Resource Intelligence Portal.</p>
                                <p>Carbon emission factors and environmental impact calculations are aligned with internationally recognised methodologies, including the UK Department for Environment, Food &amp; Rural Affairs (DEFRA) greenhouse gas conversion factors and industry-standard lifecycle datasets.</p>
                                <p>All carbon calculations (CO₂e) are aligned with the Greenhouse Gas (GHG) Protocol, with a focus on Scope 3 emissions avoided through recycling, material recovery, and landfill diversion.</p>
                                <p>Data is supported by operational records, collection data and verified waste streams ensuring a consistent and transparent reporting framework.</p>
                                <div className="border-t border-gray-300 pt-3 font-medium text-gray-800 leading-relaxed">
                                    All reported environmental metrics have been calculated using DEFRA-aligned emission factors in accordance with GHG Protocol best practice, international standards and applicable South African sustainability and reporting frameworks.
                                </div>
                            </div>
                            <div className="mt-8 flex flex-col items-center border-t border-gray-200 dark:border-gray-600 pt-8">
                                <img
                                    src="/images/logo.png"
                                    alt="WasteFlow"
                                    className="h-20 w-auto object-contain sm:h-24"
                                />
                                <div
                                    className="mt-4 h-0 w-[85%] max-w-md border-t-2"
                                    style={{ borderColor: BRAND_BLUE }}
                                    aria-hidden="true"
                                />
                                <p
                                    className="mt-4 max-w-2xl px-2 text-center text-base sm:text-lg leading-snug"
                                    style={{ color: BRAND_BLUE }}
                                >
                                    Sustainable Waste Management
                                </p>
                            </div>
                        </div>

                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
