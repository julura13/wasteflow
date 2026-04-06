import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { useState, useEffect, useRef } from 'react';
import {
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer,
    Tooltip,
} from 'recharts';
import {
    CarFront,
    Cloud,
    Droplet,
    Fuel,
    LayoutDashboard,
    Table2,
    TreePine,
    Truck,
    X,
    Zap,
} from 'lucide-react';
import axios from 'axios';
import SearchableDropdown from '@/Components/SearchableDropdown';
import { formatImpactOneDecimal, formatImpactWhole } from '@/utils/environmentalImpactDisplay';
import { formatQuantityLineLabel } from '@/utils/orderQuantityLines';

const MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
const MONTH_LABELS = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

const DASHBOARD_FILTERS_KEY = 'wasteflow_dashboard_filters';

function getStoredFilters() {
    try {
        const raw = localStorage.getItem(DASHBOARD_FILTERS_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function storeFilters(filters) {
    try {
        const payload = {
            company_id: filters.company_id || '',
            branch_id: filters.branch_id || '',
            site_id: filters.site_id || '',
            from_date: filters.from_date || '',
            to_date: filters.to_date || '',
        };
        localStorage.setItem(DASHBOARD_FILTERS_KEY, JSON.stringify(payload));
    } catch {
        // ignore
    }
}

/** Tomorrow, today, then seven calendar days back (inclusive), for the dashboard quick orders viewer. */
function getOrdersQuickViewDayOptionsAndValues() {
    const anchor = new Date();
    const opts = [];
    const values = new Set();
    const offsets = [1, 0, -1, -2, -3, -4, -5, -6, -7];
    for (const offset of offsets) {
        const day = new Date(anchor);
        day.setDate(day.getDate() + offset);
        const value = day.toISOString().split('T')[0];
        values.add(value);
        let labelPrefix;
        if (offset === 1) {
            labelPrefix = 'Tomorrow';
        } else if (offset === 0) {
            labelPrefix = 'Today';
        } else if (offset === -1) {
            labelPrefix = 'Yesterday';
        } else {
            labelPrefix = day.toLocaleDateString('en-GB', { weekday: 'long' });
        }
        const datePart = day.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        opts.push({ value, label: `${labelPrefix} (${datePart})` });
    }

    return { options: opts, valueSet: values };
}

export default function Dashboard({ companies = [], dashboardData = null, gradeSummaryByYear = [], ordersNearDates = [], filters = {} }) {
    const [branches, setBranches] = useState([]);
    const [sites, setSites] = useState([]);
    const [loadingBranches, setLoadingBranches] = useState(false);
    const [loadingSites, setLoadingSites] = useState(false);
    const [activeTab, setActiveTab] = useState('dashboard');
    const hasRestoredRef = useRef(false);
    const [selectedOrdersDay, setSelectedOrdersDay] = useState(() => new Date().toISOString().split('T')[0]);

    const { options: orderDayOptions } = getOrdersQuickViewDayOptionsAndValues();

    useEffect(() => {
        const { valueSet } = getOrdersQuickViewDayOptionsAndValues();
        if (!valueSet.has(selectedOrdersDay)) {
            setSelectedOrdersDay(new Date().toISOString().split('T')[0]);
        }
    }, [selectedOrdersDay]);
    // Grade Summary drill-down: single inline panel (daily table + optional orders list)
    const [detailPanel, setDetailPanel] = useState({
        open: false,
        dailyData: null,
        dailyLoading: false,
        wasteStream: null,
        month: null,
        year: null,
        ordersForDate: null,
        ordersLoading: false,
        selectedDate: null,
    });

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

    // Restore saved filters when dashboard loads blank (e.g. after login)
    useEffect(() => {
        const hasSelection = filters.company_id || filters.branch_id || filters.site_id;
        if (hasSelection || hasRestoredRef.current) return;
        const stored = getStoredFilters();
        if (!stored || (!stored.company_id && !stored.branch_id && !stored.site_id)) return;
        hasRestoredRef.current = true;
        const params = {
            company_id: stored.company_id || undefined,
            branch_id: stored.branch_id || undefined,
            site_id: stored.site_id || undefined,
            from_date: stored.from_date || getDefaultFromDate(),
            to_date: stored.to_date || getDefaultToDate(),
        };
        router.get(route('dashboard'), params, { preserveState: false });
    }, []);

    // Persist current filters whenever they change (from URL/props or after Apply)
    useEffect(() => {
        const hasSelection = filters.company_id || filters.branch_id || filters.site_id;
        if (hasSelection || filters.from_date || filters.to_date) {
            storeFilters(filters);
        }
    }, [filters.company_id, filters.branch_id, filters.site_id, filters.from_date, filters.to_date]);

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

    const yearForGradeSummary = filters.from_date ? new Date(filters.from_date).getFullYear() : new Date().getFullYear();

    const isGradeMonthCellSelected = (wasteStreamName, monthIndex) =>
        detailPanel.open &&
        detailPanel.wasteStream === wasteStreamName &&
        detailPanel.month === monthIndex + 1 &&
        detailPanel.year === yearForGradeSummary;

    const isGradeRowHeaderActive = (wasteStreamName) =>
        detailPanel.open && detailPanel.wasteStream === wasteStreamName;

    const handleGradeMonthClick = (wasteStreamName, monthIndex) => {
        const month = monthIndex + 1;
        setDetailPanel({
            open: true,
            dailyData: null,
            dailyLoading: true,
            wasteStream: wasteStreamName,
            month,
            year: yearForGradeSummary,
            ordersForDate: null,
            ordersLoading: false,
            selectedDate: null,
        });
        const params = {
            waste_stream: wasteStreamName,
            month,
            year: yearForGradeSummary,
            company_id: filters.company_id || undefined,
            branch_id: filters.branch_id || undefined,
            site_id: filters.site_id || undefined,
        };
        axios.get(route('dashboard.grade-month-detail'), { params })
            .then((res) => {
                setDetailPanel((prev) => ({ ...prev, dailyData: res.data, dailyLoading: false }));
            })
            .catch(() => {
                setDetailPanel((prev) => ({ ...prev, dailyData: { rows: [], days_in_month: 0 }, dailyLoading: false }));
            });
    };

    const handleDayClick = (date, wasteStreamName) => {
        setDetailPanel((prev) => ({ ...prev, ordersForDate: null, ordersLoading: true, selectedDate: date }));
        const params = {
            date,
            company_id: filters.company_id || undefined,
            branch_id: filters.branch_id || undefined,
            site_id: filters.site_id || undefined,
            waste_stream: wasteStreamName || undefined,
        };
        axios.get(route('dashboard.orders-for-day'), { params })
            .then((res) => {
                setDetailPanel((prev) => ({ ...prev, ordersForDate: res.data.orders || [], ordersLoading: false }));
            })
            .catch(() => {
                setDetailPanel((prev) => ({ ...prev, ordersForDate: [], ordersLoading: false }));
            });
    };

    const closeDetailPanel = () => {
        setDetailPanel((prev) => ({ ...prev, open: false }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        storeFilters({
            company_id: data.company_id,
            branch_id: data.branch_id,
            site_id: data.site_id,
            from_date: data.from_date,
            to_date: data.to_date,
        });
        get(route('dashboard'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const wasteStreamData = dashboardData?.wasteStreamTotals || [];
    const classificationData = {
        avoidance: { total: 0, percentage: 0 },
        recycling: { total: 0, percentage: 0 },
        recovery: { total: 0, percentage: 0 },
        disposal: { total: 0, percentage: 0 },
        diverted: { total: 0, percentage: 0 },
        ...(dashboardData?.classificationTotals || {}),
    };
    const environmentalImpact = dashboardData?.environmentalImpact || {
        treesSaved: 0,
        energySaved: 0,
        waterSaved: 0,
        co2Saved: 0,
        electricityEquivalentKwhSaGrid: 0,
        transportEquivalentKm: 0,
        fuelEquivalentLitresPetrol: 0,
        carsOffRoadAnnualEquivalent: 0,
    };

    // Format number with commas
    const formatNumber = (num) => {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    // Weights in monthly/daily tables: no decimals, round up
    const formatWeight = (num) => {
        if (num == null || num === '' || Number.isNaN(Number(num))) return '0';
        return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.ceil(Number(num)));
    };

    // Client palette: classification dials (avoidance / recycling / recovery / disposal / diverted)
    const classificationColors = {
        avoidance: '#eab308',
        recycling: '#93c5fd',
        recovery: '#2563eb',
        disposal: '#171717',
        diverted: '#ca8a04',
    };

    const classificationDonutHeight = 158;

    const renderClassificationDonut = (title, percentage, fill) => {
        const renderTotalRowBelowChart = () => {
            if (title === 'AVOIDANCE') {
                return (
                    <div className="text-center p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <p className="text-xs text-gray-600 dark:text-gray-400">Total Avoidance</p>
                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{formatNumber(classificationData.avoidance.total)} kg</p>
                    </div>
                );
            }
            if (title === 'RECYCLING') {
                return (
                    <div className="text-center p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <p className="text-xs text-gray-600 dark:text-gray-400">Total Recycling</p>
                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{formatNumber(classificationData.recycling.total)} kg</p>
                    </div>
                );
            }
            if (title === 'RECOVERY') {
                return (
                    <div className="text-center p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <p className="text-xs text-gray-600 dark:text-gray-400">Total Recovery</p>
                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{formatNumber(classificationData.recovery.total)} kg</p>
                    </div>
                );
            }
            if (title === 'DISPOSAL') {
                return (
                    <div className="text-center p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <p className="text-xs text-gray-600 dark:text-gray-400">Total Disposal</p>
                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{formatNumber(classificationData.disposal.total)} kg</p>
                    </div>
                );
            }
            if (title === 'DIVERTED') {
                return (
                    <div className="text-center p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <p className="text-xs text-gray-600 dark:text-gray-400">Total Diverted</p>
                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{formatNumber(classificationData.diverted.total)} kg</p>
                    </div>
                );
            }
            return null;
        };

        return (
            <div className="text-center">
                <h3 className="text-xs font-semibold text-gray-700 dark:text-gray-300">{title}</h3>
                <div
                    className="relative isolate mx-auto w-full overflow-visible"
                    style={{ height: classificationDonutHeight }}
                >
                    <div className="absolute inset-0 z-0 [&_.recharts-surface]:outline-none">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart margin={{ top: 0, right: 0, bottom: 0, left: 0 }}>
                                <Pie
                                    data={[
                                        { name: title, value: percentage, fill },
                                        { name: 'Other', value: Math.max(0, 100 - percentage), fill: '#e5e7eb' },
                                    ]}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius="56%"
                                    outerRadius="76%"
                                    dataKey="value"
                                    startAngle={90}
                                    endAngle={-270}
                                >
                                    <Cell fill={fill} />
                                    <Cell fill="#e5e7eb" />
                                </Pie>
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                    <div className="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
                        <span className="text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                            {percentage}%
                        </span>
                    </div>
                </div>
                {renderTotalRowBelowChart()}
            </div>
        );
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
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-1 border border-gray-200 dark:border-gray-700">
                <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                    <div>
                        <label htmlFor="dashboard_company_id" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Company
                        </label>
                        <SearchableDropdown
                            id="dashboard_company_id"
                            name="company_id"
                            value={data.company_id}
                            onChange={(v) => handleFilterChange('company_id', v)}
                            options={companies}
                            placeholder="Select Company"
                            size="sm"
                            menuMatchTriggerWidth
                            focusWithinClassName="focus-within:border-indigo-500 focus-within:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label htmlFor="dashboard_branch_id" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Branch <span className="text-gray-400 dark:text-gray-500 font-normal">(optional)</span>
                        </label>
                        <SearchableDropdown
                            id="dashboard_branch_id"
                            name="branch_id"
                            value={data.branch_id}
                            onChange={(v) => handleFilterChange('branch_id', v)}
                            options={branches}
                            placeholder={
                                !data.company_id
                                    ? 'Select company first'
                                    : loadingBranches
                                        ? 'Loading…'
                                        : 'All branches'
                            }
                            disabled={!data.company_id || loadingBranches}
                            size="sm"
                            menuMatchTriggerWidth
                            focusWithinClassName="focus-within:border-indigo-500 focus-within:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label htmlFor="dashboard_site_id" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Site <span className="text-gray-400 dark:text-gray-500 font-normal">(optional)</span>
                        </label>
                        <SearchableDropdown
                            id="dashboard_site_id"
                            name="site_id"
                            value={data.site_id}
                            onChange={(v) => handleFilterChange('site_id', v)}
                            options={sites}
                            placeholder={
                                !data.branch_id
                                    ? 'Select branch first'
                                    : loadingSites
                                        ? 'Loading…'
                                        : 'All sites'
                            }
                            disabled={!data.branch_id || loadingSites}
                            size="sm"
                            menuMatchTriggerWidth
                            focusWithinClassName="focus-within:border-indigo-500 focus-within:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            From Date
                        </label>
                        <input
                            type="date"
                            value={data.from_date}
                            onChange={(e) => handleFilterChange('from_date', e.target.value)}
                            className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            To Date
                        </label>
                        <input
                            type="date"
                            value={data.to_date}
                            onChange={(e) => handleFilterChange('to_date', e.target.value)}
                            className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
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

            {/* Tabs */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow mb-1 overflow-hidden border border-gray-200 dark:border-gray-700">
                <div className="border-b border-gray-200 dark:border-gray-700">
                    <nav className="flex -mb-px" aria-label="Tabs">
                        <button
                            type="button"
                            onClick={() => setActiveTab('dashboard')}
                            className={[
                                'flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'dashboard'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/30'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600',
                            ].join(' ')}
                        >
                            <LayoutDashboard className="w-4 h-4" />
                            Dashboard
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('grade-summary')}
                            className={[
                                'flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'grade-summary'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/30'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600',
                            ].join(' ')}
                        >
                            <Table2 className="w-4 h-4" />
                            Grade Summary
                        </button>
                    </nav>
                </div>
            </div>

            {activeTab === 'grade-summary' && (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-3 overflow-x-auto border border-gray-200 dark:border-gray-700">
                    <h2 className="text-sm font-semibold mb-2 text-gray-900 dark:text-gray-100">Waste grade summary by month (kg) – {filters.from_date ? new Date(filters.from_date).getFullYear() : new Date().getFullYear()}</h2>
                    <table className="w-full text-sm border-collapse">
                        <thead>
                            <tr className="border-b border-gray-200 dark:border-gray-600">
                                <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">WASTE GRADE</th>
                                {MONTH_LABELS.map((label) => (
                                    <th key={label} className="text-right py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">{label}</th>
                                ))}
                                <th className="text-right py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            {gradeSummaryByYear.length === 0 ? (
                                <tr>
                                    <td colSpan={14} className="py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data for the selected filters and year. Select company/branch/site and apply.
                                    </td>
                                </tr>
                            ) : (
                                gradeSummaryByYear.map((row) => (
                                    <tr
                                        key={row.name}
                                        className={[
                                            'border-b border-gray-100 dark:border-gray-700',
                                            isGradeRowHeaderActive(row.name)
                                                ? 'bg-indigo-50/40 dark:bg-indigo-950/30'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                                        ].join(' ')}
                                    >
                                        <td
                                            className={[
                                                'py-2 px-2 font-medium border-l-[3px] border-transparent',
                                                isGradeRowHeaderActive(row.name)
                                                    ? 'border-l-indigo-500 text-indigo-900 dark:text-indigo-100'
                                                    : 'text-gray-900 dark:text-gray-100',
                                            ].join(' ')}
                                        >
                                            {row.name}
                                        </td>
                                        {MONTHS.map((m, idx) => {
                                            const selected = isGradeMonthCellSelected(row.name, idx);
                                            return (
                                            <td
                                                key={m}
                                                className={[
                                                    'text-right py-2 px-1 tabular-nums transition-colors',
                                                    selected
                                                        ? 'bg-indigo-100 dark:bg-indigo-900/50 ring-2 ring-inset ring-indigo-500 dark:ring-indigo-400'
                                                        : 'text-gray-600 dark:text-gray-300',
                                                ].join(' ')}
                                            >
                                                <button
                                                    type="button"
                                                    onClick={() => handleGradeMonthClick(row.name, idx)}
                                                    className={[
                                                        'min-w-[2.25rem] inline-block rounded px-1 py-0.5 -mx-0.5',
                                                        row[m]
                                                            ? selected
                                                                ? 'cursor-pointer font-semibold text-indigo-900 dark:text-indigo-100'
                                                                : 'cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-300'
                                                            : 'cursor-default',
                                                    ].join(' ')}
                                                    title={row[m] ? `View daily breakdown for ${row.name} in ${MONTH_LABELS[idx]}` : undefined}
                                                    aria-pressed={selected}
                                                >
                                                    {row[m] ? formatWeight(row[m]) : '–'}
                                                </button>
                                            </td>
                                            );
                                        })}
                                        <td className="text-right py-2 px-2 font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{formatWeight(row.total)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Inline detail panel: daily breakdown + orders for day (only on Grade Summary tab) */}
            {activeTab === 'grade-summary' && detailPanel.open && (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 mb-3 overflow-hidden">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h3 className="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Weight – {detailPanel.wasteStream} – {MONTH_LABELS[(detailPanel.month || 1) - 1]} {detailPanel.year}
                        </h3>
                        <button
                            type="button"
                            onClick={closeDetailPanel}
                            className="inline-flex items-center gap-1 px-2 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                        >
                            <X className="w-4 h-4" /> Close
                        </button>
                    </div>
                    <div className="p-4 overflow-x-auto">
                        {detailPanel.dailyLoading ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400">Loading daily breakdown…</p>
                        ) : detailPanel.dailyData && detailPanel.dailyData.rows && detailPanel.dailyData.rows.length > 0 ? (
                            <table className="w-full text-sm border-collapse border border-gray-300 dark:border-gray-600">
                                <thead>
                                    <tr className="bg-gray-50 dark:bg-gray-700">
                                        <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600">Grade</th>
                                        {Array.from({ length: detailPanel.dailyData.days_in_month || 31 }, (_, i) => i + 1).map((d) => (
                                            <th key={d} className="text-right py-2 px-2 font-semibold text-gray-700 dark:text-gray-300 w-12 border border-gray-300 dark:border-gray-600">{d}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {detailPanel.dailyData.rows.map((r) => (
                                        <tr key={r.name}>
                                            <td className="py-2 px-2 border border-gray-300 dark:border-gray-600 align-top">
                                                <div className="font-medium text-gray-900 dark:text-gray-100">{r.name}</div>
                                                <div className="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5 tabular-nums">{formatWeight(r.total)} kg</div>
                                            </td>
                                            {Array.from({ length: detailPanel.dailyData.days_in_month || 31 }, (_, i) => i + 1).map((day) => {
                                                const dateStr = `${detailPanel.year}-${String(detailPanel.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                                                const val = r['day' + day];
                                                const isEmpty = !val;
                                                return (
                                                    <td
                                                        key={day}
                                                        className={`text-right py-2 px-1 tabular-nums border border-gray-300 dark:border-gray-600 ${isEmpty ? 'bg-gray-50 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500' : ''}`}
                                                    >
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDayClick(dateStr, detailPanel.wasteStream)}
                                                            className={val ? 'cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-300 rounded w-full min-w-[2.5rem]' : 'cursor-default'}
                                                            title={val ? `View orders for ${dateStr}` : undefined}
                                                        >
                                                            {val ? formatWeight(val) : '–'}
                                                        </button>
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <p className="text-sm text-gray-500 dark:text-gray-400">No daily data for this grade and month.</p>
                        )}
                    </div>
                    {detailPanel.selectedDate && (
                        <div className="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-gray-50/70 dark:bg-gray-700/30">
                            <h4 className="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Finalized orders for {detailPanel.selectedDate}
                                {detailPanel.wasteStream ? ` (${detailPanel.wasteStream})` : ''}
                            </h4>
                            {detailPanel.ordersLoading ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Loading orders…</p>
                            ) : !detailPanel.ordersForDate || detailPanel.ordersForDate.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">No finalized orders for this day.</p>
                            ) : (
                                <ul className="divide-y divide-gray-200 dark:divide-gray-600 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700/50 overflow-hidden">
                                    {detailPanel.ordersForDate.map((order) => (
                                        <li key={order.id} className="px-3 py-2 flex items-center justify-between gap-2 hover:bg-gray-50 dark:hover:bg-gray-600/50">
                                            <Link href={route('orders.show', order.id)} className="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                                                {order.tracking_number}
                                            </Link>
                                            <span className="text-xs text-gray-500 dark:text-gray-400 capitalize">{order.status?.replace(/_/g, ' ')}</span>
                                            {order.waste_type && <span className="text-xs text-gray-600 dark:text-gray-400">{order.waste_type}</span>}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}
                </div>
            )}

            {activeTab === 'dashboard' && (
            <>
            {/* All Charts in One Row */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-1 border border-gray-200 dark:border-gray-700">
                <h2 className="text-sm font-semibold mb-2 text-gray-900 dark:text-gray-100">Summary of Waste Treatment Outputs and achievements at a glance (kg per waste category)</h2>
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-3">
                    {/* Main Waste Stream Pie Chart */}
                    {wasteStreamData.length > 0 && (
                        <div className="lg:col-span-4 flex flex-col lg:items-start justify-between">
                            <div className="w-full mx-auto  h-full">
                                <ResponsiveContainer width="100%" height={380}>
                                    <PieChart>
                                        <Pie
                                            data={wasteStreamData}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            outerRadius={180}
                                            fill="#8884d8"
                                            dataKey="value"
                                        >
                                            {wasteStreamData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(value) => `${formatNumber(value)} kg`} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <div
                                className="h-24 min-w-0 w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50/70 dark:bg-gray-800/50 p-3 overflow-y-auto overscroll-contain"
                                role="region"
                                aria-label="Waste stream breakdown by category"
                            >
                                <ul className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2.5">
                                    {wasteStreamData.map((item, index) => (
                                        <li key={index} className="flex items-start gap-2 min-w-0">
                                            <span
                                                className="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-sm ring-1 ring-black/5 dark:ring-white/10"
                                                style={{ backgroundColor: item.color }}
                                                aria-hidden
                                            />
                                            <span className="text-xs text-gray-700 dark:text-gray-300 leading-snug text-left">
                                                <span className="font-medium text-gray-900 dark:text-gray-100">{item.name}</span>
                                                <span className="text-gray-500 dark:text-gray-400"> — {formatNumber(item.value)} kg</span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}

                    {/* Classification donut charts: row 1 avoidance / recycling / recovery; row 2 disposal / diverted */}
                    <div className="lg:col-span-6 flex flex-col gap-4">
                        <div className="grid grid-cols-1 sm:grid-cols-3">
                            {renderClassificationDonut('AVOIDANCE', classificationData.avoidance.percentage, classificationColors.avoidance)}
                            {renderClassificationDonut('RECYCLING', classificationData.recycling.percentage, classificationColors.recycling)}
                            {renderClassificationDonut('RECOVERY', classificationData.recovery.percentage, classificationColors.recovery)}
                        </div>
                        <div className="grid grid-cols-3 gap-4 sm:gap-3 mx-auto w-full">
                            {renderClassificationDonut('DISPOSAL', classificationData.disposal.percentage, classificationColors.disposal)}
                            {renderClassificationDonut('DIVERTED', classificationData.diverted.percentage, classificationColors.diverted)}
                        </div>
                        <p className="text-center text-[10px] text-gray-500 dark:text-gray-400 -mt-1 max-w-xl mx-auto px-2 leading-snug">
                            Diverted = avoidance + recycling + recovery (everything not disposal).
                        </p>
                    </div>
                </div>
            </div>

            {/* Environmental impact & resource savings (metrics aligned with client spec) */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-3 border border-gray-200 dark:border-gray-700">
                <h2 className="text-sm font-semibold mb-2 text-gray-900 dark:text-gray-100">
                    Environmental Impact &amp; Resource Savings
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div className="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800/50">
                        <Cloud className="w-8 h-8 mx-auto mb-1 text-blue-600 dark:text-blue-400" />
                        <p className="text-xs text-gray-600 dark:text-gray-400 mb-1">
                            Total Lifecycle Carbon Avoided (kg CO₂e)
                        </p>
                        <p className="text-lg font-bold text-blue-600 dark:text-blue-400">
                            {formatImpactWhole(environmentalImpact.co2Saved)} kg CO₂e
                        </p>
                    </div>
                    <div className="text-center p-3 bg-cyan-50 dark:bg-cyan-900/30 rounded-lg border border-cyan-100 dark:border-cyan-800/50">
                        <Droplet className="w-8 h-8 mx-auto mb-1 text-cyan-600 dark:text-cyan-400" />
                        <p className="text-xs text-gray-600 dark:text-gray-400 mb-1">Water Saved</p>
                        <p className="text-lg font-bold text-cyan-600 dark:text-cyan-400">
                            {formatImpactWhole(environmentalImpact.waterSaved)} kL
                        </p>
                    </div>
                    <div className="text-center p-3 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-100 dark:border-green-800/50">
                        <TreePine className="w-8 h-8 mx-auto mb-1 text-green-600 dark:text-green-400" />
                        <p className="text-xs text-gray-600 dark:text-gray-400 mb-1">Trees Saved</p>
                        <p className="text-lg font-bold text-green-600 dark:text-green-400">
                            {formatImpactWhole(environmentalImpact.treesSaved)} trees
                        </p>
                    </div>
                    <div className="text-center p-3 bg-yellow-50 dark:bg-amber-900/30 rounded-lg border border-yellow-100 dark:border-amber-800/50">
                        <Zap className="w-8 h-8 mx-auto mb-1 text-yellow-600 dark:text-amber-400" />
                        <p className="text-xs text-gray-600 dark:text-gray-400 mb-1 leading-snug px-0.5">
                            Electricity Equivalent (kWh – SA Grid)
                        </p>
                        <p className="text-lg font-bold text-yellow-600 dark:text-amber-400">
                            {formatImpactWhole(environmentalImpact.electricityEquivalentKwhSaGrid)} kWh
                        </p>
                    </div>
                </div>
                <div className="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300 mb-1">
                        Carbon equivalency indicators
                    </h3>
                    <p className="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed mb-4 max-w-3xl">
                        From lifecycle saving, SA factors — see docs/Dashboard &amp; Reports - Metrics
                    </p>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="text-center p-3.5 bg-indigo-50 dark:bg-indigo-950/40 rounded-lg border border-indigo-100 dark:border-indigo-800/50 shadow-sm">
                            <Truck className="w-7 h-7 mx-auto mb-2 text-indigo-600 dark:text-indigo-400" strokeWidth={1.75} />
                            <p className="text-xs text-gray-600 dark:text-gray-400 mb-1.5 leading-snug px-1">
                                Transport Equivalent (km Avoided)
                            </p>
                            <p className="text-base sm:text-lg font-bold text-indigo-600 dark:text-indigo-400 tabular-nums tracking-tight">
                                {formatImpactWhole(environmentalImpact.transportEquivalentKm)}{' '}
                                <span className="text-sm font-semibold text-indigo-500/90 dark:text-indigo-400/80">km</span>
                            </p>
                        </div>
                        <div className="text-center p-3.5 bg-orange-50 dark:bg-orange-950/35 rounded-lg border border-orange-100 dark:border-orange-900/50 shadow-sm">
                            <Fuel className="w-7 h-7 mx-auto mb-2 text-orange-600 dark:text-orange-400" strokeWidth={1.75} />
                            <p className="text-xs text-gray-600 dark:text-gray-400 mb-1.5 leading-snug px-1">
                                Fuel Equivalent (L Petrol Avoided)
                            </p>
                            <p className="text-base sm:text-lg font-bold text-orange-600 dark:text-orange-400 tabular-nums tracking-tight">
                                {formatImpactWhole(environmentalImpact.fuelEquivalentLitresPetrol)}{' '}
                                <span className="text-sm font-semibold text-orange-500/90 dark:text-orange-400/80">L</span>
                            </p>
                        </div>
                        <div className="text-center p-3.5 bg-violet-50 dark:bg-violet-950/40 rounded-lg border border-violet-100 dark:border-violet-800/50 shadow-sm">
                            <CarFront className="w-7 h-7 mx-auto mb-2 text-violet-600 dark:text-violet-400" strokeWidth={1.75} />
                            <p className="text-xs text-gray-600 dark:text-gray-400 mb-1.5 leading-snug px-1">
                                Cars Off the Road (Annual Equiv.)
                            </p>
                            <p className="text-base sm:text-lg font-bold text-violet-600 dark:text-violet-400 tabular-nums tracking-tight">
                                {formatImpactOneDecimal(environmentalImpact.carsOffRoadAnnualEquivalent)}{' '}
                                <span className="text-sm font-semibold text-violet-500/90 dark:text-violet-400/80">cars</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Orders for selected day (tomorrow, today, and seven days back) */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mt-3 border border-gray-200 dark:border-gray-700">
                <div className="flex flex-wrap items-center gap-2 mb-2">
                    <span className="text-sm font-semibold text-gray-800 dark:text-gray-100">Orders for</span>
                    <select
                        value={selectedOrdersDay}
                        onChange={(e) => setSelectedOrdersDay(e.target.value)}
                        className="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 pr-8"
                    >
                        {orderDayOptions.map((opt) => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                </div>
                <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">Filtered by selected company / branch / site. Change filters above and click Apply to update.</p>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm border-collapse">
                        <thead>
                            <tr className="border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
                                <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">Date</th>
                                <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">Status</th>
                                <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">Tracking No</th>
                                <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">Type</th>
                                <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">Service Provider</th>
                                <th className="text-left py-2 px-2 font-semibold text-gray-700 dark:text-gray-300">Collection quantities</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(!ordersNearDates || ordersNearDates.length === 0) ? (
                                <tr>
                                    <td colSpan={6} className="py-4 text-center text-gray-500 dark:text-gray-400">
                                        No orders from the last 7 days through tomorrow. Select company/branch/site and apply to filter.
                                    </td>
                                </tr>
                            ) : (() => {
                                const filtered = ordersNearDates.filter((o) => o.collection_date === selectedOrdersDay);
                                return filtered.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="py-4 text-center text-gray-500 dark:text-gray-400">
                                            No orders for the selected day.
                                        </td>
                                    </tr>
                                ) : (
                                    filtered.map((order) => (
                                        <tr key={order.id} className="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td className="py-2 px-2 text-gray-600 dark:text-gray-300">{order.collection_date || '–'}</td>
                                            <td className="py-2 px-2">
                                                <span className="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200 capitalize">
                                                    {order.status?.replace(/_/g, ' ') || '–'}
                                                </span>
                                            </td>
                                            <td className="py-2 px-2">
                                                <Link href={route('orders.show', order.id)} className="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                                                    {order.tracking_number}
                                                </Link>
                                            </td>
                                            <td className="py-2 px-2 text-gray-600 dark:text-gray-300 capitalize">{order.order_type || '–'}</td>
                                            <td className="py-2 px-2 text-gray-600 dark:text-gray-300">{order.service_provider || '–'}</td>
                                            <td className="py-2 px-2 font-semibold text-gray-800 dark:text-gray-200">
                                                {Array.isArray(order.quantity_lines) && order.quantity_lines.length > 0 ? (
                                                    <div className="flex flex-col gap-0.5">
                                                        {order.quantity_lines.map((line, index) => (
                                                            <span key={index}>{formatQuantityLineLabel(line)}</span>
                                                        ))}
                                                    </div>
                                                ) : order.quantity != null && order.quantity_type ? (
                                                    formatQuantityLineLabel({
                                                        quantity: order.quantity,
                                                        quantity_type: order.quantity_type,
                                                    })
                                                ) : (
                                                    <span className="font-normal text-gray-400 dark:text-gray-500">–</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                );
                            })()}
                        </tbody>
                    </table>
                </div>
            </div>
            </>
            )}
        </DashboardLayout>
    );
}
