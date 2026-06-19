import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Calculator } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';

const MATERIALS = [
    { key: 'paper', label: 'Paper' },
    { key: 'plasticPPHD', label: 'Plastic PP / HD' },
    { key: 'plasticPS', label: 'Plastic PS (Polystyrene)' },
    { key: 'plasticLDPE', label: 'Plastic LDPE Film' },
    { key: 'aluminium', label: 'Aluminium' },
    { key: 'steel', label: 'Steel' },
    { key: 'glass', label: 'Glass' },
    { key: 'foodWaste', label: 'Food Waste' },
    { key: 'gardenWaste', label: 'Garden Waste' },
    { key: 'batteries', label: 'Batteries' },
    { key: 'electronics', label: 'Electronics (E-waste)' },
    { key: 'tetrapak', label: 'Tetrapak variants' },
    { key: 'wood', label: 'Wood – Reuse (Pallets & Timber)' },
];

const initialWeights = Object.fromEntries(MATERIALS.map((m) => [m.key, '']));

function formatSummaryKgCo2e(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return '—';
    }
    if (Number.isInteger(n)) {
        return n.toLocaleString('en-US');
    }
    return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

const COLUMN_TOOLTIPS = {
    material: 'Material category used to select the emission factors from docs/Carbon Calculator.xlsx.',
    weight: 'Weight in kg (spreadsheet column B).',
    scope3:
        'Upstream (Scope 3) Emissions Avoided = weight × upstream factor (column C). Same value as spreadsheet column D.',
    landfill:
        'Landfill Emissions Avoided = weight × landfill avoidance factor (column E). Same value as spreadsheet column F.',
    substitution:
        'Recycling substitution factor (column G): fixed kg CO₂e per kg reference value from the workbook. Not multiplied by weight in the sheet and not included in the total (column H).',
    lifecycle:
        'Total Lifecycle Carbon Avoided = column D + column F (spreadsheet column H). Column G is reference only.',
};

export default function CarbonCalculator() {
    const [weights, setWeights] = useState(initialWeights);
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [activeHeaderTooltip, setActiveHeaderTooltip] = useState(null);

    const handleWeightChange = (key, value) => {
        setWeights((prev) => ({ ...prev, [key]: value }));
        setError(null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setError(null);
        const payload = {};
        MATERIALS.forEach(({ key }) => {
            const v = weights[key];
            payload[key] = v === '' || v === null ? 0 : parseFloat(v);
        });
        if (Object.values(payload).every((n) => n === 0)) {
            setError('Enter at least one weight (kg) to calculate.');
            return;
        }
        setLoading(true);
        axios
            .post(route('reports.carbon-calculator.calculate'), { weights: payload })
            .then(({ data }) => {
                setResult(data);
                setLoading(false);
            })
            .catch((err) => {
                setError(err.response?.data?.message || 'Calculation failed.');
                setResult(null);
                setLoading(false);
            });
    };

    const handleClear = () => {
        setWeights(initialWeights);
        setResult(null);
        setError(null);
    };

    return (
        <DashboardLayout title="Carbon Calculator">
            <Head title="Carbon Calculator" />

            <div className="max-w-5xl mx-auto">
                <div className="mb-4">
                    <Link
                        href={route('reports.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-2"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Reports
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Carbon Calculator
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Enter weights (kg) per material to see the same carbon calculations used in
                        the Waste Management Report. No orders or data required — ideal for
                        proofing and customer demos.
                    </p>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-lg shadow mb-4">
                    <div className="px-4 py-4 sm:p-4">
                        <form onSubmit={handleSubmit} className="space-y-2">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center gap-1">
                                                    <span>Material</span>
                                                    <div className="relative inline-flex items-center">
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] leading-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                            aria-label="Material tooltip"
                                                            onClick={() => setActiveHeaderTooltip('material')}
                                                        >
                                                            ?
                                                        </button>
                                                        {activeHeaderTooltip === 'material' && (
                                                            <div className="absolute z-20 left-0 top-full mt-1 w-64 bg-gray-900 text-white text-[11px] leading-snug rounded-md px-2 py-2 shadow">
                                                                <div className="flex items-start justify-between gap-2">
                                                                    <p className="flex-1">{COLUMN_TOOLTIPS.material}</p>
                                                                    <button
                                                                        type="button"
                                                                        className="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-700/60 hover:bg-gray-700 text-white/90"
                                                                        aria-label="Close tooltip"
                                                                        onClick={() => setActiveHeaderTooltip(null)}
                                                                    >
                                                                        x
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    <span>Weight (kg)</span>
                                                    <div className="relative inline-flex items-center">
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] leading-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                            aria-label="Weight tooltip"
                                                            onClick={() => setActiveHeaderTooltip('weight')}
                                                        >
                                                            ?
                                                        </button>
                                                        {activeHeaderTooltip === 'weight' && (
                                                            <div className="absolute z-20 right-0 top-full mt-1 w-64 bg-gray-900 text-white text-[11px] leading-snug rounded-md px-2 py-2 shadow">
                                                                <div className="flex items-start justify-between gap-2">
                                                                    <p className="flex-1">{COLUMN_TOOLTIPS.weight}</p>
                                                                    <button
                                                                        type="button"
                                                                        className="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-700/60 hover:bg-gray-700 text-white/90"
                                                                        aria-label="Close tooltip"
                                                                        onClick={() => setActiveHeaderTooltip(null)}
                                                                    >
                                                                        x
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    <span className="text-right leading-tight">
                                                        Upstream (Scope 3) Emissions Avoided (kg CO₂e)
                                                    </span>
                                                    <div className="relative inline-flex items-center">
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] leading-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                            aria-label="Scope 3 tooltip"
                                                            onClick={() => setActiveHeaderTooltip('scope3')}
                                                        >
                                                            ?
                                                        </button>
                                                        {activeHeaderTooltip === 'scope3' && (
                                                            <div className="absolute z-20 right-0 top-full mt-1 w-64 bg-gray-900 text-white text-[11px] leading-snug rounded-md px-2 py-2 shadow">
                                                                <div className="flex items-start justify-between gap-2">
                                                                    <p className="flex-1">{COLUMN_TOOLTIPS.scope3}</p>
                                                                    <button
                                                                        type="button"
                                                                        className="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-700/60 hover:bg-gray-700 text-white/90"
                                                                        aria-label="Close tooltip"
                                                                        onClick={() => setActiveHeaderTooltip(null)}
                                                                    >
                                                                        x
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    <span className="text-right leading-tight">
                                                        Landfill Emissions Avoided (kg CO₂e)
                                                    </span>
                                                    <div className="relative inline-flex items-center">
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] leading-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                            aria-label="Landfill tooltip"
                                                            onClick={() => setActiveHeaderTooltip('landfill')}
                                                        >
                                                            ?
                                                        </button>
                                                        {activeHeaderTooltip === 'landfill' && (
                                                            <div className="absolute z-20 right-0 top-full mt-1 w-64 bg-gray-900 text-white text-[11px] leading-snug rounded-md px-2 py-2 shadow">
                                                                <div className="flex items-start justify-between gap-2">
                                                                    <p className="flex-1">{COLUMN_TOOLTIPS.landfill}</p>
                                                                    <button
                                                                        type="button"
                                                                        className="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-700/60 hover:bg-gray-700 text-white/90"
                                                                        aria-label="Close tooltip"
                                                                        onClick={() => setActiveHeaderTooltip(null)}
                                                                    >
                                                                        x
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1 max-w-[14rem] ml-auto">
                                                    <span className="text-right leading-tight normal-case">
                                                        Recycling Substitution Factor (Reference Only – Not Included in
                                                        Total)
                                                    </span>
                                                    <div className="relative inline-flex shrink-0 items-center">
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] leading-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                            aria-label="Recycling substitution tooltip"
                                                            onClick={() => setActiveHeaderTooltip('substitution')}
                                                        >
                                                            ?
                                                        </button>
                                                        {activeHeaderTooltip === 'substitution' && (
                                                            <div className="absolute z-20 right-0 top-full mt-1 w-64 bg-gray-900 text-white text-[11px] leading-snug rounded-md px-2 py-2 shadow">
                                                                <div className="flex items-start justify-between gap-2">
                                                                    <p className="flex-1">{COLUMN_TOOLTIPS.substitution}</p>
                                                                    <button
                                                                        type="button"
                                                                        className="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-700/60 hover:bg-gray-700 text-white/90"
                                                                        aria-label="Close tooltip"
                                                                        onClick={() => setActiveHeaderTooltip(null)}
                                                                    >
                                                                        x
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    <span className="text-right leading-tight">
                                                        Total Lifecycle Carbon Avoided (kg CO₂e)
                                                    </span>
                                                    <div className="relative inline-flex items-center">
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] leading-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                            aria-label="Lifecycle tooltip"
                                                            onClick={() => setActiveHeaderTooltip('lifecycle')}
                                                        >
                                                            ?
                                                        </button>
                                                        {activeHeaderTooltip === 'lifecycle' && (
                                                            <div className="absolute z-20 right-0 top-full mt-1 w-64 bg-gray-900 text-white text-[11px] leading-snug rounded-md px-2 py-2 shadow">
                                                                <div className="flex items-start justify-between gap-2">
                                                                    <p className="flex-1">{COLUMN_TOOLTIPS.lifecycle}</p>
                                                                    <button
                                                                        type="button"
                                                                        className="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-700/60 hover:bg-gray-700 text-white/90"
                                                                        aria-label="Close tooltip"
                                                                        onClick={() => setActiveHeaderTooltip(null)}
                                                                    >
                                                                        x
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {MATERIALS.map(({ key, label }, index) => {
                                            const row = result?.materials?.[index];
                                            return (
                                                <tr key={key}>
                                                    <td className="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                                        {label}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                                        <input
                                                            id={`weight-${key}`}
                                                            type="number"
                                                            min="0"
                                                            step="0.1"
                                                            inputMode="decimal"
                                                            value={weights[key]}
                                                            onChange={(e) => handleWeightChange(key, e.target.value)}
                                                            className="w-24 text-right border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm py-1"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                                        {row ? row.scope3EF.toFixed(2) : '—'}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                                        {row ? row.landfillAvoidanceEF.toFixed(2) : '—'}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right text-red-600 dark:text-red-400 tabular-nums">
                                                        {row
                                                            ? Number(row.recyclingSubstitutionFactor).toLocaleString(
                                                                  'en-US',
                                                                  {
                                                                      minimumFractionDigits: 0,
                                                                      maximumFractionDigits: 2,
                                                                  },
                                                              )
                                                            : '—'}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right font-semibold text-gray-900 dark:text-gray-100">
                                                        {row ? row.lifecycleSaving.toFixed(2) : '—'}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                        <tr className="bg-gray-50 dark:bg-gray-700 font-bold">
                                            <td colSpan={2} className="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                                TOTALS
                                            </td>
                                            <td className="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                                {result ? result.totals.scope3EF.toFixed(2) : '—'}
                                            </td>
                                            <td className="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                                {result ? result.totals.landfillAvoidanceEF.toFixed(2) : '—'}
                                            </td>
                                            <td className="px-4 py-2 text-sm text-right font-normal text-gray-400 dark:text-gray-500">
                                                —
                                            </td>
                                            <td className="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                                {result ? result.totals.lifecycleSaving.toFixed(2) : '—'}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}

                            <div className="flex gap-3 pt-0">
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Calculator className="h-4 w-4 mr-2" />
                                    {loading ? 'Calculating…' : 'Calculate'}
                                </button>
                                <button
                                    type="button"
                                    onClick={handleClear}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    Clear
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {result && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-4">
                        <div className="px-4 py-6 sm:px-6">
                            <table className="w-full border-collapse text-sm text-gray-900 dark:text-gray-100">
                                <tbody>
                                    <tr className="border-b border-gray-200 dark:border-gray-600">
                                        <td className="py-2 pr-4 align-middle">
                                            Total Upstream (Scope 3) Avoided
                                        </td>
                                        <td className="py-2 px-2 text-right align-middle tabular-nums font-medium">
                                            {formatSummaryKgCo2e(result.totals.scope3EF)}
                                        </td>
                                        <td className="py-2 pl-2 text-right align-middle whitespace-nowrap">
                                            kg CO<sub className="text-[0.85em]">2</sub>e
                                        </td>
                                    </tr>
                                    <tr className="border-b border-gray-200 dark:border-gray-600">
                                        <td className="py-2 pr-4 align-middle">
                                            Total Landfill Emissions Avoided
                                        </td>
                                        <td className="py-2 px-2 text-right align-middle tabular-nums font-medium">
                                            {formatSummaryKgCo2e(result.totals.landfillAvoidanceEF)}
                                        </td>
                                        <td className="py-2 pl-2 text-right align-middle whitespace-nowrap">
                                            kg CO<sub className="text-[0.85em]">2</sub>e
                                        </td>
                                    </tr>
                                    <tr className="font-bold">
                                        <td className="py-2 pr-4 align-middle">Total Lifecycle Carbon Avoided</td>
                                        <td className="py-2 px-2 text-right align-middle tabular-nums">
                                            {formatSummaryKgCo2e(result.totals.lifecycleSaving)}
                                        </td>
                                        <td className="py-2 pl-2 text-right align-middle whitespace-nowrap">
                                            kg CO<sub className="text-[0.85em]">2</sub>e
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p className="mx-auto mt-6 max-w-4xl text-center text-sm font-bold leading-relaxed text-gray-900 dark:text-gray-100">
                                Carbon emission factors and avoided emission assumptions are based on internationally
                                recognised standards, including DEFRA (UK Government), the EPA WARM model, and
                                peer-reviewed global life cycle assessment (LCA) datasets (e.g. Ecoinvent).
                                Calculations are aligned with best practice under the GHG Protocol, ensuring
                                consistency, transparency, and the avoidance of double counting.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
