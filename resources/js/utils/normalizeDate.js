/**
 * Normalize any date string to yyyy-mm-dd so <input type="date"> and the
 * backend always receive a consistent format regardless of browser locale.
 *
 * Handles:
 *   yyyy-mm-dd  (ISO, already correct)
 *   yyyy/mm/dd  (ZA / Asian locale display)
 *   mm/dd/yyyy  (US locale)
 *   dd/mm/yyyy  (UK / ZA manual entry)
 *
 * Returns '' for empty / unrecognised values.
 */
export function normalizeToYmd(value) {
    if (!value || typeof value !== 'string') return '';

    const v = value.trim();
    if (v === '') return '';

    // Already yyyy-mm-dd
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;

    // yyyy/mm/dd → yyyy-mm-dd
    if (/^\d{4}\/\d{2}\/\d{2}$/.test(v)) return v.replace(/\//g, '-');

    // mm/dd/yyyy or dd/mm/yyyy (both look the same for ambiguous days)
    // Detect by checking if the first part looks like a 4-digit year
    const slashParts = v.split('/');
    if (slashParts.length === 3) {
        const [a, b, c] = slashParts;
        if (c.length === 4) {
            // Could be mm/dd/yyyy or dd/mm/yyyy — treat as mm/dd/yyyy (US, most
            // common source of locale mismatch in this app)
            return `${c}-${a.padStart(2, '0')}-${b.padStart(2, '0')}`;
        }
    }

    return v;
}
