<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\ServiceProvider;
use App\Models\WasteStream;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $materials = Material::query()
            ->with([
                'wasteStream:id,name',
                'grade:id,name',
                'classification:id,name',
                'facility:id,name',
                'serviceProvider:id,name',
            ])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($scoped) use ($search) {
                    $scoped->whereHas('grade', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('wasteStream', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('facility', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->waste_stream_id, fn ($query, $id) => $query->where('waste_stream_id', $id))
            ->when($request->facility_id, fn ($query, $id) => $query->where('facility_id', $id))
            ->when($request->has('rebate'), function ($query) use ($request) {
                $query->where('rebate_offered', filter_var($request->get('rebate'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('is_active', filter_var($request->get('status'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
            })
            ->orderByDesc('updated_at')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'filters' => $request->only(['search', 'waste_stream_id', 'facility_id', 'rebate', 'status']),
            'wasteStreams' => WasteStream::query()->orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Materials/Create', [
            'lookups' => $this->lookupPayload(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        Material::create($validated);

        return redirect()->route('materials.index')
            ->with('success', 'Material created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        $material->load([
            'wasteStream',
            'grade',
            'classification',
            'facility',
            'serviceProvider',
            'orderWasteStreams.order.site',
        ]);

        return Inertia::render('Materials/Show', [
            'material' => $material,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material)
    {
        $material->load([
            'wasteStream:id,name',
            'grade:id,name',
            'classification:id,name',
            'facility:id,name',
            'serviceProvider:id,name',
        ]);

        return Inertia::render('Materials/Edit', [
            'material' => $material,
            'lookups' => $this->lookupPayload(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        $validated = $this->validatePayload($request, $material->id);

        $material->update($validated);

        return redirect()->route('materials.index')
            ->with('success', 'Material updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        $material->delete();

        return redirect()->route('materials.index')
            ->with('success', 'Material deleted successfully.');
    }

    /**
     * Quick update for rebate rate (inline editing).
     */
    public function updateRebateRate(Request $request, Material $material)
    {
        $validated = $request->validate([
            'rebate_rate' => 'required|numeric|min:0|max:999999.99',
        ]);

        $material->update([
            'rebate_rate' => round((float) $validated['rebate_rate'], 2),
        ]);

        // Return JSON for Inertia to handle
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'rebate_rate' => $material->rebate_rate,
            ]);
        }

        // Fallback redirect
        return back()->with('success', 'Rebate rate updated successfully.');
    }

    /**
     * Quick update for client rebate share / rebate percentage (inline editing).
     */
    public function updateRebateShare(Request $request, Material $material)
    {
        $validated = $request->validate([
            'client_rebate_share' => 'required|numeric|min:0|max:100',
        ]);

        $material->update([
            'client_rebate_share' => round((float) $validated['client_rebate_share'], 2),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'client_rebate_share' => $material->client_rebate_share,
            ]);
        }

        return back()->with('success', 'Rebate percentage updated successfully.');
    }

    protected function validatePayload(Request $request, ?int $materialId = null): array
    {
        $baseRules = [
            'waste_stream_id' => 'required|exists:waste_streams,id',
            'grade_id' => 'required|exists:grades,id',
            'classification_id' => 'required|exists:classifications,id',
            'facility_id' => 'required|exists:facilities,id',
            'service_provider_id' => 'nullable|exists:service_providers,id',
            'weight_required' => 'required|string|max:255',
            'rebate_offered' => 'sometimes|boolean',
            'backing_document' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ];

        $rebateRules = $request->boolean('rebate_offered')
            ? [
                'rebate_rate' => 'required|numeric|min:0|max:999999.99',
                'client_rebate_share' => 'required|numeric|min:0|max:100',
            ]
            : [
                'rebate_rate' => 'nullable|numeric|min:0|max:999999.99',
                'client_rebate_share' => 'nullable|numeric|min:0|max:100',
            ];

        $validated = $request->validate(array_merge($baseRules, $rebateRules));

        $rebateOffered = $request->boolean('rebate_offered');
        $validated['rebate_offered'] = $rebateOffered;
        $validated['backing_document'] = $request->boolean('backing_document');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['rebate_rate'] = $rebateOffered ? round((float) $request->input('rebate_rate'), 2) : null;
        $validated['client_rebate_share'] = $rebateOffered ? round((float) $request->input('client_rebate_share'), 2) : null;

        return $validated;
    }

    protected function lookupPayload(): array
    {
        return [
            'wasteStreams' => WasteStream::query()->orderBy('name')->get(['id', 'name']),
            'grades' => Grade::query()->orderBy('name')->get(['id', 'name']),
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::query()->orderBy('name')->get(['id', 'name']),
            'serviceProviders' => ServiceProvider::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}