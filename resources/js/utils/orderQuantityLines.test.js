import { formatQuantityLineLabel, formatQuantityLinesCommaSeparated } from './orderQuantityLines';

describe('formatQuantityLineLabel', () => {
    it('formats container option lines', () => {
        expect(
            formatQuantityLineLabel({
                container_option_name: 'Wheelie bin',
                quantity: 3,
            }),
        ).toBe('3× Wheelie bin');
    });

    it('appends description for container option lines when present', () => {
        expect(
            formatQuantityLineLabel({
                container_option_name: 'Skip',
                quantity: 1,
                description: 'Rear access',
            }),
        ).toBe('1× Skip (Rear access)');
    });

    it('formats legacy quantity_type lines', () => {
        expect(
            formatQuantityLineLabel({
                quantity_type: 'wheelie_bins',
                quantity: 2,
            }),
        ).toBe('2× wheelie bins');
    });

    it('prefers container_option_name over quantity_type', () => {
        expect(
            formatQuantityLineLabel({
                container_option_name: 'Bin',
                quantity_type: 'wheelie_bins',
                quantity: 1,
            }),
        ).toBe('1× Bin');
    });
});

describe('formatQuantityLinesCommaSeparated', () => {
    it('returns em dash for empty input', () => {
        expect(formatQuantityLinesCommaSeparated([])).toBe('—');
        expect(formatQuantityLinesCommaSeparated(null)).toBe('—');
    });

    it('joins multiple lines', () => {
        expect(
            formatQuantityLinesCommaSeparated([
                { container_option_name: 'A', quantity: 1 },
                { container_option_name: 'B', quantity: 2 },
            ]),
        ).toBe('1× A, 2× B');
    });
});
