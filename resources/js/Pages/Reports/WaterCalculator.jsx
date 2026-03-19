import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Droplets } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';

const ROWS = [
    { key: 'paper', label: 'Paper' },
    { key: 'plastics', label: 'Plastics' },
    { key: 'aluminium', label: 'Aluminium' },
    { key: 'steel', label: 'Steel' },
    { key: 'glass', label: 'Glass' },
    { key: 'tetrapak', label: 'Tetrapak' },
    { key: 'organics', label: 'Organics' },
];

const initialWeights = Object.fromEntries(ROWS.map((r) => [r.key, '']));

const COLUMN_TOOLTIPS = {
    material: 'Category labels match docs/Water Calculator.xlsx column A.',
    weight: 'Weight in kilograms (column B).',
    factor: 'Water saving factor in litres per kg (column C).',
    litres: 'Total water saved for this row (L) = Weight × Factor (column D).',
};

export default function WaterCalculator() {
    const [weights, setWeights] = useState(initialWeights);
    const [breakdown, setBreakdown] = useState(null);
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
        ROWS.forEach(({ key }) => {
            const v = weights[key];
            payload[key] = v === '' || v === null ? 0 : parseFloat(v);
        });
        if (Object.values(payload).every((n) => n === 0)) {
            setError('Enter at least one weight (kg) to calculate.');
            return;
        }
        setLoading(true);
        axios
            .post(route('reports.water-calculator.calculate'), { weights: payload })
            .then(({ data }) => {
                setBreakdown(data.breakdown);
                setLoading(false);
            })
            .catch((err) => {
                setError(err.response?.data?.message || 'Calculation failed.');
                setBreakdown(null);
                setLoading(false);
            });
    };

    const handleClear = () => {
        setWeights(initialWeights);
        setBreakdown(null);
        setError(null);
    };

    return (
        <DashboardLayout title="Water calculator">
            <Head title="Water calculator" />

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
                        Water saved (kL)
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Same calculation as the Waste Management Report environmental impact. Per row:{' '}
                        <span className="font-medium text-gray-800 dark:text-gray-200">
                            water saved (L) = weight (kg) × factor (L/kg)
                        </span>
                        ; total kL = sum of litres ÷ 1000. Source:{' '}
                        <code className="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">
                            docs/Water Calculator.xlsx
                        </code>
                        .
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
                                                    <TooltipButton
                                                        id="material"
                                                        active={activeHeaderTooltip}
                                                        setActive={setActiveHeaderTooltip}
                                                        text={COLUMN_TOOLTIPS.material}
                                                    />
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    <span>Weight (kg)</span>
                                                    <TooltipButton
                                                        id="weight"
                                                        active={activeHeaderTooltip}
                                                        setActive={setActiveHeaderTooltip}
                                                        text={COLUMN_TOOLTIPS.weight}
                                                        alignRight
                                                    />
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    <span>Factor (L/kg)</span>
                                                    <TooltipButton
                                                        id="factor"
                                                        active={activeHeaderTooltip}
                                                        setActive={setActiveHeaderTooltip}
                                                        text={COLUMN_TOOLTIPS.factor}
                                                        alignRight
                                                    />
                                                </div>
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    <span>Water saved (L)</span>
                                                    <TooltipButton
                                                        id="litres"
                                                        active={activeHeaderTooltip}
                                                        setActive={setActiveHeaderTooltip}
                                                        text={COLUMN_TOOLTIPS.litres}
                                                        alignRight
                                                    />
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {ROWS.map(({ key, label }) => {
                                            const row = breakdown?.[key];
                                            return (
                                                <tr key={key}>
                                                    <td className="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                                        {label}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right">
                                                        <input
                                                            id={`weight-${key}`}
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            inputMode="decimal"
                                                            value={weights[key]}
                                                            onChange={(e) =>
                                                                handleWeightChange(key, e.target.value)
                                                            }
                                                            className="w-28 text-right border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm py-1"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right text-gray-700 dark:text-gray-300">
                                                        {row
                                                            ? row.factorLPerKg.toLocaleString('en-US', {
                                                                  maximumFractionDigits: 0,
                                                              })
                                                            : '—'}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                                                        {row
                                                            ? row.waterSavedLitres.toLocaleString('en-US', {
                                                                  maximumFractionDigits: 2,
                                                              })
                                                            : '—'}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                        <tr className="bg-gray-50 dark:bg-gray-700 font-bold">
                                            <td
                                                colSpan={3}
                                                className="px-4 py-2 text-sm text-gray-900 dark:text-gray-100"
                                            >
                                                Total water saved (L)
                                            </td>
                                            <td className="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">
                                                {breakdown
                                                    ? breakdown.totalLitres.toLocaleString('en-US', {
                                                          maximumFractionDigits: 2,
                                                      })
                                                    : '—'}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}

                            <div className="flex gap-3 pt-2">
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Droplets className="h-4 w-4 mr-2" />
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

                {breakdown && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-4">
                        <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Summary (report unit)
                            </h2>
                        </div>
                        <div className="px-4 py-4 text-sm text-gray-700 dark:text-gray-300 space-y-2">
                            <p>
                                <strong>Total (kL):</strong>{' '}
                                <span className="text-primary-600 dark:text-primary-400 font-semibold text-lg">
                                    {breakdown.totalKilolitres.toFixed(2)}
                                </span>
                            </p>
                            <p className="text-gray-500 dark:text-gray-400 text-xs">
                                The Waste Management report displays litres of water saved; dashboard uses kL.
                                Total kL = total litres ÷ 1000 (matches spreadsheet row B12).
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}

function TooltipButton({ id, active, setActive, text, alignRight }) {
    return (
        <div className="relative inline-flex items-center">
            <button
                type="button"
                className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 text-[10px] leading-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                aria-label={`${id} help`}
                onClick={() => setActive(active === id ? null : id)}
            >
                ?
            </button>
            {active === id && (
                <div
                    className={`absolute z-20 top-full mt-1 w-64 bg-gray-900 text-white text-[11px] leading-snug rounded-md px-2 py-2 shadow ${alignRight ? 'right-0' : 'left-0'}`}
                >
                    <div className="flex items-start justify-between gap-2">
                        <p className="flex-1">{text}</p>
                        <button
                            type="button"
                            className="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-700/60 hover:bg-gray-700 text-white/90 shrink-0"
                            aria-label="Close tooltip"
                            onClick={() => setActive(null)}
                        >
                            ×
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
