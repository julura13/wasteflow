# Waste Management Report - Calculations Summary

## ✅ Fully Implemented Calculations

### Summary Section
- ✅ **recyclingRecovered**: Sum of all recycling commodity weights
- ✅ **organicsRecovered**: From grades calculation (Organic Waste stream, Organics Recovered grade)
- ✅ **totalIncomingWaste**: generalWaste + organicsRecovered + recyclingRecovered
- ✅ **divertedFromLandfill**: (recyclingRecovered + organicsRecovered) / totalIncomingWaste * 100
- ✅ **landfillSpaceSaved**: Sum of rounded per-row airspace: weight (kg) ÷ density (kg/m³) per category (`LandfillSpaceCalculator`), matching `docs/Landfill space saved m3.xlsx`. Densities: Paper 100, Plastics 65, Aluminium 1300, Steel 300, Glass 400, Tetrapak 150, Organics 500.
- ✅ **lifecycleSaving**: From materialsCO2eTotals

### Materials CO2e Section (docs/Carbon Calculator.xlsx)
- ✅ **Weights**: Calculated from actual waste stream data (column B)
- ✅ **scope3EF**: Upstream (Scope 3) Emissions Avoided — weight × upstream factor (column D)
- ✅ **landfillAvoidanceEF**: Landfill Emissions Avoided — weight × landfill factor (column F)
- ✅ **recyclingSubstitutionFactor** (carbon calculator API only): Fixed reference factor per material (column G); not summed in workbook row 14 and not in `materialsCO2eTotals`
- ✅ **lifecycleSaving**: Total Lifecycle Carbon Avoided — column D + column F (column H)
- ✅ **Totals**: `scope3EF`, `landfillAvoidanceEF`, `lifecycleSaving` in `materialsCO2eTotals`

### Environmental Impact Section
- ✅ **treesSaved**: totalPaperWeight × (20 / 1000)
- ✅ **energySaved**: Sum of (weight × energy factor) for each category
- ✅ **waterSaved**: Sum of (weight kg × water factor L/kg) for each category (`WaterCalculator`, docs/Water Calculator.xlsx), then ÷ 1000 → **kL**. Factors: Paper 1800, Plastics 80, Aluminium 1300, Steel 50, Glass 25, Tetrapak 400, Organics 45. Report label: **Water Saved (kL)** (value was always kL; not litres).

### Landfill Space Saved Breakdown
- ✅ All category calculations with totals and space saved per category

### Additional Calculations
- ✅ **carbonEmissionsAvoided**: Calculated from lifecycleSaving (conversion factor may need adjustment)
- ✅ **cumulativeImpact**: Calculated from environmental impact and materialsCO2eTotals
- ✅ **recyclingBreakdown**: Percentage breakdown by material category

## ⚠️ Values That May Need Adjustment

### 1. carbonEmissionsAvoided (transport equivalent km)
**Implementation**: `lifecycleSaving (kg CO₂e) ÷ 0.192` — matches docs/Dashboard & Reports - Metrics (1).docx (transport equivalent). Same basis as `LifecycleCarbonEquivalency`.

### 2. cumulativeImpact
**Current Implementation**: Returns actual values (not percentages)
**Location**: `calculateCumulativeImpact()` method
**Action Required**: Confirm if these should be:
- Actual values (current implementation)
- Percentages
- Normalized values
- Something else

### 3. Chart Scaling
**Current Implementation**: Dynamic scaling based on data
**Location**: `generateCharts()` method
**Action Required**: Review chart max values and step sizes to ensure they display well

## 📋 Code Organization

### Methods Structure
1. **Public Methods**:
   - `wasteManagement()` - HTML view
   - `wasteManagementPdf()` - PDF generation
   - `wasteManagementSummary()` - JSON data for testing

2. **Private Calculation Methods**:
   - `getReportData()` - Main data aggregation
   - `getGrades()` - Calculate waste grades
   - `getMaterialWeights()` - Get material weights by grade
   - `getRecyclingCommodities()` - Format recycling commodities
   - `getLandfillSpaceSaved()` - Calculate landfill space saved breakdown
   - `getMaterialsCO2e()` - Calculate materials CO2e with all factors
   - `getEnvironmentalImpact()` - Calculate trees/energy/water saved
   - `calculateCarbonEmissionsAvoided()` - Calculate carbon emissions avoided
   - `calculateCumulativeImpact()` - Calculate cumulative impact dashboard
   - `calculateRecyclingBreakdown()` - Calculate recycling breakdown percentages
   - `generateCharts()` - Generate all chart images

### Code Cleanup Completed
- ✅ Removed debug code from `wasteManagementSummary()`
- ✅ Removed hardcoded values
- ✅ Updated all calculations to use actual data
- ✅ Formatted code with proper comments
- ✅ Organized methods logically

## 🔍 Potential Issues to Review

1. **wasteManagement() and wasteManagementPdf() methods**
   - Currently call `getReportData()` without parameters
   - May need to accept company/month/year filters like `wasteManagementSummary()`

2. **Date Range Logic**
   - Used consistently across all methods
   - Uses `actual_collection_date` if available, falls back to `requested_collection_date`

3. **Material Categorization**
   - Paper excludes Tetrapak (handled correctly)
   - Plastics includes all Plastic waste stream materials
   - Landfill space: Aluminium stream → aluminium density; Metal stream with steel grades → steel density (split per spreadsheet)
   - Organics uses organicsRecovered from grades

## 📊 Data Structure

The report data structure includes:
```php
[
    'companyName' => string,
    'reportDate' => string (e.g., "Aug-25"),
    'environmentalImpact' => [
        'treesSaved' => float,
        'energySaved' => float,
        'waterSaved' => float,
    ],
    'grades' => [
        'generalWaste' => float,
        'nonCompactableWaste' => float,
        'hazardousWaste' => float,
        'organicsRecovered' => float,
    ],
    'recyclingCommodities' => array,
    'recyclingCommodities2' => array,
    'summary' => [
        'recyclingRecovered' => float,
        'organicsRecovered' => float,
        'totalIncomingWaste' => float,
        'divertedFromLandfill' => float,
        'landfillSpaceSaved' => float,
        'lifecycleSaving' => float,
    ],
    'landfillSpaceSavedBreakdown' => [
        'paper' => ['total' => float, 'densityKgPerM3' => float, 'spaceSaved' => float],
        'plastics' => ['total' => float, 'densityKgPerM3' => float, 'spaceSaved' => float],
        'aluminium' => ['total' => float, 'densityKgPerM3' => float, 'spaceSaved' => float],
        'steel' => ['total' => float, 'densityKgPerM3' => float, 'spaceSaved' => float],
        'glass' => ['total' => float, 'densityKgPerM3' => float, 'spaceSaved' => float],
        'tetrapak' => ['total' => float, 'densityKgPerM3' => float, 'spaceSaved' => float],
        'organics' => ['total' => float, 'densityKgPerM3' => float, 'spaceSaved' => float],
        'total' => float,
    ],
    'materialsCO2e' => array of material objects,
    'materialsCO2eTotals' => [
        'scope3EF' => float,
        'landfillAvoidanceEF' => float,
        'lifecycleSaving' => float,
    ],
    'carbonEmissionsAvoided' => float,
    'cumulativeImpact' => array,
    'recyclingBreakdown' => array,
]
```

## ✅ All Calculations Complete

All required calculations have been implemented. The code is clean, organized, and ready for use. Only minor adjustments may be needed for:
- Carbon emissions conversion factor
- Cumulative impact format (if percentages are needed)
- Chart scaling preferences
