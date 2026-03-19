# Align with Dashboard & Report document

This document describes how the application aligns with the client specification in **`Dashboard & Reports - Metrics (1).docx`** and the related spreadsheet sources for landfill space, water, and carbon.

## Source documents

| Document | Purpose |
|----------|---------|
| `docs/Dashboard & Reports - Metrics (1).docx` | Carbon equivalency formulas (SA grid, transport, fuel, cars), preferred headings/copy |
| `docs/Landfill space saved m3.xlsx` | Landfill airspace avoided: kg ÷ density (kg/m³) per category |
| `docs/Water Calculator.xlsx` | Water saved: weight (kg) × factor (L/kg), total kL = sum(L) ÷ 1000 |
| `docs/Carbon Calculator.xlsx` | Scope 3, landfill avoidance, lifecycle (no “other offsets” in lifecycle total) |

---

## Carbon equivalency indicators (from lifecycle saving)

**Master number:** lifecycle saving in **kg CO₂e** (Scope 3 EF + Landfill avoidance EF only — not substitution / “other offsets”).

Derived metrics (from the client doc):

| Metric | Formula |
|--------|---------|
| **Electricity equivalent (kWh – SA grid)** | CO₂e (kg) ÷ **0.9** |
| **Transport equivalent (km avoided)** | CO₂e (kg) ÷ **0.192** |
| **Fuel equivalent (litres of petrol avoided)** | CO₂e (kg) ÷ **2.31** |
| **Cars off the road (annual equivalent)** | CO₂e (kg) ÷ **4600** |

**Implementation:** `App\Services\LifecycleCarbonEquivalency::fromLifecycleSavingKgCo2e()`

**Where it appears:**

- **Dashboard** — `WasteImpactCalculator::calculateImpactFromCategoryWeights()` adds the four fields to `environmentalImpact` (equivalencies are based on the dashboard’s merged-plastics lifecycle CO₂e).
- **Waste Management Report (Inertia / PDF data)** — `ReportController::getReportData()` recomputes those four metrics from **`materialsCO2eTotals['lifecycleSaving']`** so they match the **materials table** (split plastics).
- **Monthly waste report (Blade)** — `EnvironmentalImpactService::calculateImpact()` attaches the same keys to the `$impact` array (from **split** lifecycle total).
- **Report `carbonEmissionsAvoided`** — transport-style km uses **lifecycle ÷ 0.192** (not the old × 0.17 factor).

**Example (from client doc):** lifecycle saving = **393.84 kg CO₂e** → electricity **437.6 kWh**, transport **2051.25 km**, fuel **170.49 L**, cars **0.0856** (see `tests/Unit/LifecycleCarbonEquivalencyTest.php`).

---

## Headings & copy (client doc)

| Client request | Where updated |
|----------------|----------------|
| Section title: **Environmental Impact & Resource Savings** | `resources/js/Pages/Dashboard.jsx`, `resources/views/reports/monthly-waste-management.blade.php` (cumulative section) |
| **Total Lifecycle Carbon Avoided (kg CO₂e)** (was “Lifecycle Carbon Avoidance” / similar) | Dashboard env cards, monthly summary rows, cumulative metric |
| **Water saved in kL** (not mislabelled as litres where the value was kL) | Waste management Blade/PDF, monthly report, dashboard |
| **Waste Treatment Summary (kg by Category)** (recycling breakdown title) | `monthly-waste-management.blade.php` |
| Carbon page: show **lifecycle total** plus **table of four equivalencies** | `monthly-waste-management.blade.php` (replaces “Total Carbon Emissions Avoided in KM” only) |

---

## Landfill space saved (m³)

**Formula:** per category, **m³ avoided = weight (kg) ÷ density (kg/m³)**; **total** = sum of row contributions (rounded per project rules).

**Densities (from `Landfill space saved m3.xlsx`):** Paper 100, Plastics 65, Aluminium 1300, Steel 300, Glass 400, Tetrapak 150, Organics 500.

**Implementation:** `App\Services\LandfillSpaceCalculator`

**Usage:**

- Waste Management Report — `ReportController::getLandfillSpaceSaved()`
- Monthly report — `EnvironmentalImpactService` via the same category weights as water (stream/summary mapping)
- Demo — `/reports/landfill-space-calculator`

---

## Water saved

**Formula:** per category **litres = weight (kg) × factor (L/kg)**; **total kL = sum(litres) ÷ 1000**.

**Factors (from `Water Calculator.xlsx`):** Paper 1800, Plastics 80, Aluminium 1300, Steel 50, Glass 25, Tetrapak 400, Organics 45.

**Implementation:** `App\Services\WaterCalculator` — used by `WasteImpactCalculator` and the monthly `EnvironmentalImpactService` path (no longer paper × 10 only).

**Demo:** `/reports/water-calculator`

---

## Shared category weights (dashboard, reports, monthly)

Mapping rules live in **`WasteImpactCalculator`**:

- `buildCategoryWeightsFromSummaries()` — `ClientMonthlyMaterialSummary` (dashboard & filtered waste management report)
- `buildCategoryWeightsFromWasteStreams()` — order waste stream lines (monthly Blade report)

**Tetrapak:** counted if **grade** is Tetrapak **or** **waste stream** name is Tetrapak.

**Organics (for water / landfill / recycling energy / trees):** stream **Organic Waste** and grade **Organics Recovered**. If organics only appear elsewhere in data, consider an override similar to report **`organicsRecovered`** from grades.

---

## Carbon lifecycle (materials table)

**Implementation:** `App\Services\CarbonCalculator` (and split-type logic in `EnvironmentalImpactService` for the monthly detailed table).

**Lifecycle total** = Scope 3 + Landfill avoidance only (excludes “other offsets” / substitution column).

---

## Quick file map

| Area | Main code |
|------|-----------|
| Equivalency constants & API | `app/Services/LifecycleCarbonEquivalency.php` |
| Dashboard env metrics + demo CO₂ | `app/Services/WasteImpactCalculator.php`, `app/Http/Controllers/DashboardController.php` |
| Waste management report data | `app/Http/Controllers/ReportController.php` |
| Monthly PDF/HTML impact | `app/Services/EnvironmentalImpactService.php`, `resources/views/reports/monthly-waste-management.blade.php` |
| Landfill / water demos | `app/Http/Controllers/ReportController.php`, `resources/js/Pages/Reports/*Calculator.jsx` |
| Summary of report math | `REPORT_CALCULATIONS_SUMMARY.md` |

---

## Tests

- `tests/Unit/LifecycleCarbonEquivalencyTest.php` — doc example 393.84 kg CO₂e  
- `tests/Unit/WaterCalculatorTest.php` — water xlsx sample + `WasteImpactCalculator` delegation  
- `tests/Unit/CarbonFormulasTest.php` — `EnvironmentalImpactService` lifecycle 578.36 (split plastics)  
- Feature tests for calculator pages (require DB/Sail): `LandfillSpaceCalculatorTest`, `WaterCalculatorTest`, etc.

Run (with Sail):  
`vendor/bin/sail artisan test tests/Unit/LifecycleCarbonEquivalencyTest.php`

---

*Last updated to reflect alignment with **Dashboard & Reports - Metrics (1).docx** and the calculator spreadsheets listed above.*
