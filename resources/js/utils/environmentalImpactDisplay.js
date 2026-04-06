/**
 * Display formatting for dashboard environmental impact figures (readable, grouped integers).
 */
export function formatImpactWhole(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return '0';
    }

    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(n));
}

export function formatImpactOneDecimal(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return '0.0';
    }

    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n);
}
