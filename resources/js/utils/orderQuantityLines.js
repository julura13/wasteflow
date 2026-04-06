/**
 * Single quantity line label for orders (container options or legacy quantity_type rows).
 *
 * @param {Record<string, unknown>} line
 * @returns {string}
 */
export function formatQuantityLineLabel(line) {
    const qty = line.quantity != null ? line.quantity : '—';
    if (line.container_option_name != null && String(line.container_option_name).trim() !== '') {
        const desc = line.description ? ` (${line.description})` : '';

        return `${qty}× ${line.container_option_name}${desc}`;
    }
    if (line.quantity_type) {
        const label = String(line.quantity_type).replace(/_/g, ' ');

        return line.description ? `${qty}× ${label} (${line.description})` : `${qty}× ${label}`;
    }

    return `${qty}×`;
}

/**
 * Comma-separated summary (e.g. activity log).
 *
 * @param {unknown} lines
 * @returns {string}
 */
export function formatQuantityLinesCommaSeparated(lines) {
    if (!Array.isArray(lines) || lines.length === 0) {
        return '—';
    }

    return lines.map((line) => formatQuantityLineLabel(line)).join(', ');
}
