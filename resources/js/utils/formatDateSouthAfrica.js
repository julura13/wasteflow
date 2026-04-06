/**
 * South African-style calendar date: dd/mm/yyyy.
 * Parses YYYY-MM-DD without timezone shift.
 */
export function formatDateSouthAfrica(value) {
    if (!value) {
        return '—';
    }
    const part = String(value).slice(0, 10);
    const [y, m, d] = part.split('-').map((n) => Number.parseInt(n, 10));
    if (!y || !m || !d) {
        return '—';
    }
    return `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${y}`;
}
