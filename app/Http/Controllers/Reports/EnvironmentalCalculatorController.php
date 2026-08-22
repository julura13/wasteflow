<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\CarbonCalculator;
use App\Services\LandfillSpaceCalculator;
use App\Services\WaterCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnvironmentalCalculatorController extends Controller
{
    public function __construct(
        protected CarbonCalculator $carbonCalculator,
        protected LandfillSpaceCalculator $landfillSpaceCalculator,
        protected WaterCalculator $waterCalculator,
    ) {}

    /**
     * Display the carbon calculator proofing page (manual weights, same formulas as reports).
     */
    public function carbonCalculator()
    {
        return Inertia::render('Reports/CarbonCalculator');
    }

    /**
     * Run carbon calculation from manually entered weights (same logic as reports).
     */
    public function carbonCalculatorCalculate(Request $request)
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.paper' => ['nullable', 'numeric', 'min:0'],
            'weights.plasticPPHD' => ['nullable', 'numeric', 'min:0'],
            'weights.plasticPS' => ['nullable', 'numeric', 'min:0'],
            'weights.plasticLDPE' => ['nullable', 'numeric', 'min:0'],
            'weights.aluminium' => ['nullable', 'numeric', 'min:0'],
            'weights.steel' => ['nullable', 'numeric', 'min:0'],
            'weights.glass' => ['nullable', 'numeric', 'min:0'],
            'weights.foodWaste' => ['nullable', 'numeric', 'min:0'],
            'weights.gardenWaste' => ['nullable', 'numeric', 'min:0'],
            'weights.batteries' => ['nullable', 'numeric', 'min:0'],
            'weights.electronics' => ['nullable', 'numeric', 'min:0'],
            'weights.tetrapak' => ['nullable', 'numeric', 'min:0'],
            'weights.wood' => ['nullable', 'numeric', 'min:0'],
        ]);

        $weights = array_map(fn ($v) => (float) ($v ?? 0), $validated['weights'] ?? []);

        $result = $this->carbonCalculator->calculateMaterialsCO2e($weights);

        return response()->json([
            'materials' => $result['materials'],
            'totals' => $result['totals'],
        ]);
    }

    /**
     * Display landfill space saved calculator (manual weights, same formulas as reports).
     */
    public function landfillSpaceCalculator()
    {
        return Inertia::render('Reports/LandfillSpaceCalculator');
    }

    /**
     * Run landfill space calculation from manually entered weights (same logic as reports).
     */
    public function landfillSpaceCalculatorCalculate(Request $request)
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.paper' => ['nullable', 'numeric', 'min:0'],
            'weights.plastics' => ['nullable', 'numeric', 'min:0'],
            'weights.aluminium' => ['nullable', 'numeric', 'min:0'],
            'weights.steel' => ['nullable', 'numeric', 'min:0'],
            'weights.glass' => ['nullable', 'numeric', 'min:0'],
            'weights.tetrapak' => ['nullable', 'numeric', 'min:0'],
            'weights.organics' => ['nullable', 'numeric', 'min:0'],
            'weights.wood' => ['nullable', 'numeric', 'min:0'],
        ]);

        $weights = array_map(fn ($v) => (float) ($v ?? 0), $validated['weights'] ?? []);

        $breakdown = $this->landfillSpaceCalculator->calculate($weights);

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Display water saved calculator (manual weights, same formulas as reports).
     */
    public function waterCalculator()
    {
        return Inertia::render('Reports/WaterCalculator');
    }

    /**
     * Run water calculation from manually entered weights (same logic as reports).
     */
    public function waterCalculatorCalculate(Request $request)
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.paper' => ['nullable', 'numeric', 'min:0'],
            'weights.plastics' => ['nullable', 'numeric', 'min:0'],
            'weights.aluminium' => ['nullable', 'numeric', 'min:0'],
            'weights.steel' => ['nullable', 'numeric', 'min:0'],
            'weights.glass' => ['nullable', 'numeric', 'min:0'],
            'weights.tetrapak' => ['nullable', 'numeric', 'min:0'],
            'weights.organics' => ['nullable', 'numeric', 'min:0'],
            'weights.wood' => ['nullable', 'numeric', 'min:0'],
        ]);

        $weights = array_map(fn ($v) => (float) ($v ?? 0), $validated['weights'] ?? []);

        $breakdown = $this->waterCalculator->calculate($weights);

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }
}
