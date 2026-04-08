import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { formatDateYyyyMmDd } from '@/utils/formatDateYyyyMmDd';
import { formatQuantityLinesCommaSeparated } from '@/utils/orderQuantityLines';
import { History, Search, Package, AlertCircle } from 'lucide-react';
import { useState } from 'react';

const EDIT_REASON_LABELS = {
    client_request: 'Client request',
    wrong_quantity: 'Wrong quantity entered',
    wrong_container_type: 'Wrong container type',
    date_correction: 'Date correction',
    data_entry_error: 'Data entry error',
    other: 'Other',
};

const DELETE_REASON_LABELS = {
    incorrect_order: 'Incorrect order',
    duplicate: 'Duplicate order',
    wrong_date: 'Wrong collection date',
    wrong_site: 'Wrong site / collection point',
    cancelled_by_client: 'Cancelled by client',
    other: 'Other',
};

const LOG_LABELS = {
    order_created: 'Order created',
    order_updated: 'Order details updated',
    order_status_changed: 'Status changed',
    order_weights_saved: 'Weights captured',
    order_finalized: 'Order finalized',
    order_collection_date_updated: 'Collection date updated',
    order_consolidated_pdf_scheduled: 'Scheduled via consolidated PDF',
    order_deleted: 'Order deleted',
    duplicate_slip_number: 'Duplicate slip number attempted',
    media_uploaded: 'Document uploaded',
    media_deleted: 'Document deleted',
};

function formatDate(isoString) {
    const d = new Date(isoString);
    if (Number.isNaN(d.getTime())) {
        return { date: '—', time: '' };
    }
    const y = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const date = `${y}/${mo}/${day}`;
    const time = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    return { date, time };
}

/** Show only yyyy/mm/dd for date values (strips time and TZ when value is YYYY-MM-DD…). */
function formatDateOnly(value) {
    if (value == null || value === '') {
        return value;
    }
    return formatDateYyyyMmDd(value);
}

/** Replace date-like substrings in text with yyyy/mm/dd (for descriptions stored with time). */
function descriptionWithShortDates(text) {
    if (!text || typeof text !== 'string') {
        return text;
    }
    const ymdToSlash = (ymd) => {
        const [y, m, d] = ymd.split('-');
        return `${y}/${m}/${d}`;
    };
    return text
        .replace(/(\d{4}-\d{2}-\d{2})\s+\d{2}:\d{2}(?::\d{2})?/g, (_, ymd) => ymdToSlash(ymd))
        .replace(/(\d{4}-\d{2}-\d{2})T[\d.:]+Z?/g, (_, ymd) => ymdToSlash(ymd));
}

function PropertySummary({ properties, logName }) {
    if (!properties || typeof properties !== 'object') return null;
    const entries = [];
    if (properties.old_status != null && properties.new_status != null) {
        entries.push({ label: 'Status', value: `${properties.old_status} → ${properties.new_status}` });
    }
    if (properties.old_quantity_lines != null && properties.new_quantity_lines != null) {
        const oldStr = formatQuantityLinesCommaSeparated(properties.old_quantity_lines);
        const newStr = formatQuantityLinesCommaSeparated(properties.new_quantity_lines);
        entries.push({ label: 'Quantity lines', value: `${oldStr} → ${newStr}` });
    }
    if (properties.old_estimated_quantity != null && properties.new_estimated_quantity != null) {
        entries.push({
            label: 'Estimated quantity',
            value: `${properties.old_estimated_quantity} → ${properties.new_estimated_quantity}`,
        });
    }
    if (properties.old_date != null && properties.new_date != null) {
        entries.push({
            label: 'Collection date',
            value: `${formatDateOnly(properties.old_date)} → ${formatDateOnly(properties.new_date)}`,
        });
    }
    if (properties.slip_number) {
        entries.push({ label: 'Slip number', value: properties.slip_number });
    }
    if (properties.actual_collection_date != null) {
        entries.push({
            label: 'Collection date',
            value: formatDateOnly(properties.actual_collection_date),
        });
    }
    if (properties.actual_quantity != null) {
        entries.push({ label: 'Actual quantity', value: String(properties.actual_quantity) });
    }
    if (properties.weight_lines && Array.isArray(properties.weight_lines) && properties.weight_lines.length > 0) {
        const lines = properties.weight_lines;
        const hasWeights = lines.some((l) => l.weight != null || l.material_id != null);
        if (hasWeights) {
            entries.push({
                label: 'Weight lines',
                value: null,
                weightLines: lines.map((l) => ({
                    name: l.material_name ?? `Material #${l.material_id ?? '?'}`,
                    weight: l.weight != null ? Number(l.weight) : null,
                })),
            });
        } else {
            entries.push({
                label: 'Weight lines',
                value: `${lines.length} line(s) captured`,
            });
        }
    }
    if (properties.reason) {
        const labels = logName === 'order_deleted' ? DELETE_REASON_LABELS : EDIT_REASON_LABELS;
        const label = labels[properties.reason] ?? EDIT_REASON_LABELS[properties.reason] ?? properties.reason;
        const value = properties.reason_details ? `${label}: ${properties.reason_details}` : label;
        entries.push({ label: 'Reason', value });
    }
    if (entries.length === 0) return null;
    return (
        <ul className="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
            {entries.map((entry) => {
                const { label, value, weightLines } = entry;
                if (weightLines && Array.isArray(weightLines) && weightLines.length > 0) {
                    return (
                        <li key={label} className="flex gap-2">
                            <span className="font-medium text-gray-500 dark:text-gray-500 shrink-0">{label}:</span>
                            <ul className="list-disc list-inside space-y-0.5">
                                {weightLines.map((line, i) => (
                                    <li key={i}>
                                        {line.name}
                                        {line.weight != null && (
                                            <span className="font-medium text-gray-800 dark:text-gray-200">
                                                {' '}
                                                — {Number(line.weight).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 3 })} kg
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </li>
                    );
                }
                const isChange = typeof value === 'string' && value.includes(' → ');
                const [before, after] = isChange ? value.split(' → ') : [null, null];
                return (
                    <li key={label} className="flex gap-2">
                        <span className="font-medium text-gray-500 dark:text-gray-500 shrink-0">{label}:</span>
                        <span className="break-words">
                            {isChange ? (
                                <>
                                    <strong className="font-semibold text-gray-800 dark:text-gray-200">
                                        {before}
                                    </strong>
                                    {' → '}
                                    <strong className="font-semibold text-gray-800 dark:text-gray-200">
                                        {after}
                                    </strong>
                                </>
                            ) : (
                                value
                            )}
                        </span>
                    </li>
                );
            })}
        </ul>
    );
}

export default function Index({ filterOrder = '', order, entries }) {
    const [orderInput, setOrderInput] = useState(filterOrder);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('activity-log.index'), { order: orderInput || undefined }, { preserveState: true });
    };

    const hasSearched = filterOrder !== '';
    const noOrderFound = hasSearched && !order && filterOrder.length > 0;
    const hasEntries = entries && entries.length > 0;

    return (
        <DashboardLayout>
            <Head title="Activity Log" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <History className="h-7 w-7" />
                    Activity Log
                </h1>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    View the audit timeline for an order by entering its tracking number.
                </p>
            </div>

            <form onSubmit={handleSearch} className="mb-10">
                <div className="flex flex-col sm:flex-row gap-3 max-w-xl">
                    <div className="flex-1">
                        <label htmlFor="order" className="sr-only">
                            Order tracking number
                        </label>
                        <input
                            id="order"
                            type="text"
                            value={orderInput}
                            onChange={(e) => setOrderInput(e.target.value)}
                            placeholder="e.g. WO-2501-30001"
                            className="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-4 pr-10 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                    </div>
                    <button
                        type="submit"
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    >
                        <Search className="h-4 w-4" />
                        View timeline
                    </button>
                </div>
            </form>

            {noOrderFound && (
                <div className="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 flex items-start gap-3">
                    <AlertCircle className="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div>
                        <p className="font-medium text-amber-800 dark:text-amber-200">No order found</p>
                        <p className="text-sm text-amber-700 dark:text-amber-300 mt-1">
                            No order with tracking number &quot;{filterOrder}&quot; exists. Check the number and try again.
                        </p>
                    </div>
                </div>
            )}

            {hasSearched && order && !hasEntries && (
                <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 text-center">
                    <Package className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                    <p className="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">No activity yet</p>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        No activity log entries for order {order.tracking_number}.
                    </p>
                    {!order.deleted_at && (
                        <Link
                            href={route('orders.show', order.id)}
                            className="mt-4 inline-flex text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                        >
                            View order →
                        </Link>
                    )}
                </div>
            )}

            {hasEntries && (
                <div className="flow-root">
                    <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <span className="font-medium text-gray-900 dark:text-gray-100">{entries.length}</span> event
                        {entries.length !== 1 ? 's' : ''} for{' '}
                        {order.deleted_at ? (
                            <>
                                <span className="font-medium text-gray-900 dark:text-gray-100">{order.tracking_number}</span>
                                <span className="ml-1.5 text-xs text-amber-600 dark:text-amber-400 font-medium">(deleted)</span>
                            </>
                        ) : (
                            <Link
                                href={route('orders.show', order.id)}
                                className="text-primary-600 hover:text-primary-500 dark:text-primary-400 font-medium"
                            >
                                {order.tracking_number}
                            </Link>
                        )}
                        <span className="ml-1">(oldest first)</span>
                    </p>

                    <ul className="relative space-y-0">
                        {/* Vertical line */}
                        <div
                            className="absolute left-4 top-2 bottom-2 w-px bg-gray-200 dark:bg-gray-600"
                            aria-hidden="true"
                        />

                        {entries.map((entry, index) => {
                            const { date, time } = formatDate(entry.created_at);
                            const label = LOG_LABELS[entry.log_name] || entry.log_name.replace(/_/g, ' ');
                            const isLatest = index === entries.length - 1;
                            return (
                                <li key={entry.id} className="relative flex gap-6 pb-8 last:pb-0">
                                    {/* Dot */}
                                    <div
                                        className={`relative z-10 mt-1.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 ${
                                            isLatest
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30'
                                                : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800'
                                        }`}
                                    >
                                        <span
                                            className={`h-2 w-2 rounded-full ${
                                                isLatest ? 'bg-primary-500' : 'bg-gray-400 dark:bg-gray-500'
                                            }`}
                                        />
                                    </div>

                                    <div className="flex-1 min-w-0 pt-0.5">
                                        <div className="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                            <p className="font-medium text-gray-900 dark:text-gray-100 capitalize">
                                                {label}
                                            </p>
                                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                                {date} at {time}
                                            </span>
                                            {entry.causer && (
                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                    by {entry.causer.name}
                                                </span>
                                            )}
                                        </div>
                                        {entry.description && (
                                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {descriptionWithShortDates(entry.description)}
                                            </p>
                                        )}
                                        <PropertySummary properties={entry.properties} logName={entry.log_name} />
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            )}

            {!hasSearched && (
                <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
                    <History className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                    <p className="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                        Enter an order number to view its activity timeline
                    </p>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                        Use the tracking number (e.g. WO-2501-30001 or RO-2501-30002) to see all events logged for
                        that order.
                    </p>
                </div>
            )}
        </DashboardLayout>
    );
}
