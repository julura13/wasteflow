import { formatDateTimeYyyyMmDd, formatDateYyyyMmDd } from './formatDateYyyyMmDd';

describe('formatDateYyyyMmDd', () => {
    it('formats YYYY-MM-DD as yyyy/mm/dd', () => {
        expect(formatDateYyyyMmDd('2026-04-06')).toBe('2026/04/06');
        expect(formatDateYyyyMmDd('2026-01-05')).toBe('2026/01/05');
    });

    it('returns em dash for empty or invalid', () => {
        expect(formatDateYyyyMmDd('')).toBe('—');
        expect(formatDateYyyyMmDd(null)).toBe('—');
        expect(formatDateYyyyMmDd('not-a-date')).toBe('—');
    });
});

describe('formatDateTimeYyyyMmDd', () => {
    it('returns em dash for empty or invalid', () => {
        expect(formatDateTimeYyyyMmDd('')).toBe('—');
        expect(formatDateTimeYyyyMmDd(null)).toBe('—');
        expect(formatDateTimeYyyyMmDd('not-a-date')).toBe('—');
    });

    it('formats a valid ISO string with local date and time', () => {
        const s = formatDateTimeYyyyMmDd('2026-04-06T14:05:00.000Z');
        expect(s).toMatch(/^2026\/04\/06\b/);
        expect(s.length).toBeGreaterThan(12);
    });
});
