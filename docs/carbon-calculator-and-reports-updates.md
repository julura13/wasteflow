# Carbon calculator & report updates

Summary of changes aligned with **`docs/Carbon Calculator.xlsx`** and client feedback (headings, substitution column, summary/disclaimer, monthly report parity).

---

## Source of truth

- **Workbook:** `docs/Carbon Calculator.xlsx`  
  - **D** = B×C (upstream emissions avoided, kg CO₂e)  
  - **F** = B×E (landfill emissions avoided, kg CO₂e)  
  - **G** = fixed recycling substitution **factors** (kg CO₂e per kg), reference only — **not** in **H**  
  - **H** = D + F (total lifecycle carbon avoided)  
  - Totals row sums **D**, **F**, and **H** only; column **G** has **no** total.

---

## Backend

### `app/Services/CarbonCalculator.php`

- Emission factors for columns **C**, **E**, and **G** match the spreadsheet.
- Each material row returns:
  - `scope3EF`, `landfillAvoidanceEF`, `lifecycleSaving` (H = D + F).
  - `recyclingSubstitutionFactor` — fixed value from column **G** (not weight × factor in the API; same numeric reference as the sheet).
- `totals` includes only `scope3EF`, `landfillAvoidanceEF`, `lifecycleSaving` (no sum for substitution).

### `app/Http/Controllers/ReportController.php`

- `getEmptyMaterialsCO2e()` rows/totals no longer include `otherOffsets`.
- Stacked bar chart dataset labels updated to upstream / landfill avoided wording.

---

## Waste Management report (HTML / PDF)

### `resources/views/reports/waste-management.blade.php`  
### `resources/views/reports/waste-management-pdf.blade.php`

- Table headings: **Weight (kg)**, **Upstream (Scope 3) Emissions Avoided (kg CO₂e)**, **Landfill Emissions Avoided (kg CO₂e)**, **Total Lifecycle Carbon Avoided (kg CO₂e)**.
- Removed **Other offsets** column and related summary line from the materials table.
- Page 1 summary: **Total Lifecycle Carbon Avoided (kg CO₂e)** label.
- Summary box under materials table: **Total Upstream (Scope 3) Avoided**, **Total Landfill Emissions Avoided**, **Total Lifecycle Carbon Avoided** (with short spreadsheet column references).

---

## Carbon Calculator (Inertia)

### `resources/js/Pages/Reports/CarbonCalculator.jsx`

- Main table column order matches sheet logic: Material → Weight → D → F → **G (substitution factor)** → H.
- Headers:
  - **Upstream (Scope 3) Emissions Avoided (kg CO₂e)**
  - **Landfill Emissions Avoided (kg CO₂e)**
  - **Recycling Substitution Factor (Reference Only – Not Included in Total)**
  - **Total Lifecycle Carbon Avoided (kg CO₂e)**
- Substitution column values styled **red**; **TOTALS** row shows **—** in substitution column.
- **Bottom summary:** small table with **Total Upstream (Scope 3) Avoided**, **Total Landfill Emissions Avoided**, **Total Lifecycle Carbon Avoided** + **kg CO₂e** (subscript 2); final row bold.
- **Disclaimer (centered, bold):**  
  *Carbon emission factors and avoided emission assumptions are based on internationally recognised standards, including DEFRA (UK Government), the EPA WARM model, and peer-reviewed global life cycle assessment (LCA) datasets (e.g. Ecoinvent). Calculations are aligned with best practice under the GHG Protocol, ensuring consistency, transparency, and the avoidance of double counting.*

---

## Waste Management Report preview (demo data)

### `resources/js/Pages/Reports/WasteManagementReport.jsx`

- Materials CO₂e mock data shaped like the API (kg avoided per row, not “factor × weight” twice).
- Headings and summary wording aligned; stacked bar uses **two** segments (upstream + landfill) only.

---

## Monthly report (email / HTML)

### `resources/views/reports/monthly-waste-management.blade.php`

- Carbon table headers aligned with the workbook / waste report (including **Weight (kg)** and substitution column title).
- Data columns: show **kg** upstream and landfill avoided; substitution as **other_offsets ÷ weight** (reference factor), **red** text; totals row **—** for substitution.
- Summary: **Total Upstream (Scope 3) Avoided**, **Total Landfill Emissions Avoided**, bold **Total Lifecycle Carbon Avoided**; removed standalone “Other Offsets” summary block.
- Same **DEFRA / GHG Protocol** disclaimer paragraph as the calculator (centered, bold).

> **Note:** Monthly figures still come from **`EnvironmentalImpactService`** (`other_offsets` remains the internal kg total used only to derive the displayed substitution **factor** per row).

---

## Tests

### `tests/Feature/CarbonCalculatorTest.php`

- JSON structure includes `recyclingSubstitutionFactor` on each material; asserts e.g. Paper **1.3**.

### `tests/Unit/CarbonFormulasTest.php`

- Asserts `recyclingSubstitutionFactor` for Paper and Tetrapak variants vs spreadsheet.

---

## Other documentation

### `REPORT_CALCULATIONS_SUMMARY.md`

- Materials CO₂e section updated for column D/F/H/G behaviour and calculator-only substitution field.

---

## Files touched (checklist)

| Area | File |
|------|------|
| Service | `app/Services/CarbonCalculator.php` |
| Controller | `app/Http/Controllers/ReportController.php` |
| WM report | `resources/views/reports/waste-management.blade.php`, `waste-management-pdf.blade.php` |
| Calculator UI | `resources/js/Pages/Reports/CarbonCalculator.jsx` |
| WM preview | `resources/js/Pages/Reports/WasteManagementReport.jsx` |
| Monthly report | `resources/views/reports/monthly-waste-management.blade.php` |
| Tests | `tests/Feature/CarbonCalculatorTest.php`, `tests/Unit/CarbonFormulasTest.php` |
| Docs | `REPORT_CALCULATIONS_SUMMARY.md`, **this file** |

---

*Generated to record updates from the carbon calculator / reporting alignment work session.*
