import { formatImpactOneDecimal, formatImpactWhole } from './environmentalImpactDisplay';

describe('formatImpactWhole', () => {
    it('rounds and adds grouping', () => {
        expect(formatImpactWhole(388773.06)).toBe('388,773');
        expect(formatImpactWhole(316064.86)).toBe('316,065');
    });

    it('handles non-finite values', () => {
        expect(formatImpactWhole(NaN)).toBe('0');
        expect(formatImpactWhole(null)).toBe('0');
    });
});

describe('formatImpactOneDecimal', () => {
    it('formats with one decimal place', () => {
        expect(formatImpactOneDecimal(84.52)).toBe('84.5');
        expect(formatImpactOneDecimal(10)).toBe('10.0');
    });

    it('handles non-finite values', () => {
        expect(formatImpactOneDecimal(NaN)).toBe('0.0');
    });
});
