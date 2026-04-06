import { formatDateSouthAfrica } from './formatDateSouthAfrica';

describe('formatDateSouthAfrica', () => {
    it('formats ISO date strings as dd/mm/yyyy', () => {
        expect(formatDateSouthAfrica('2026-04-06')).toBe('06/04/2026');
        expect(formatDateSouthAfrica('2026-01-05')).toBe('05/01/2026');
    });

    it('returns em dash for empty input', () => {
        expect(formatDateSouthAfrica('')).toBe('—');
        expect(formatDateSouthAfrica(null)).toBe('—');
    });

    it('returns em dash for invalid segments', () => {
        expect(formatDateSouthAfrica('not-a-date')).toBe('—');
    });
});
