/**
 * Calendar date as yyyy/mm/dd.
 * Parses YYYY-MM-DD without timezone shift when the value starts with a date part.
 */
export function formatDateYyyyMmDd(value) {
    if (!value) {
        return '—';
    }
    const part = String(value).slice(0, 10);
    const [y, m, d] = part.split('-').map((n) => Number.parseInt(n, 10));
    if (!y || !m || !d) {
        return '—';
    }
    return `${y}/${String(m).padStart(2, '0')}/${String(d).padStart(2, '0')}`;
}

/**
 * Local calendar date and time for ISO timestamps (yyyy/mm/dd h:mm am/pm).
 */
export function formatDateTimeYyyyMmDd(isoString) {
    if (!isoString) {
        return '—';
    }
    const d = new Date(isoString);
    if (Number.isNaN(d.getTime())) {
        return '—';
    }
    const y = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const date = `${y}/${mo}/${day}`;
    const time = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    return `${date} ${time}`;
}
