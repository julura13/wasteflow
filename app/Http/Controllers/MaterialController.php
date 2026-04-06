<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Classification;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\ServiceProvider;
use App\Models\WasteStream;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $materials = $this->materialsFilteredQuery($request)
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
     * Export material definitions as PDF (same filters as the index listing; all matching rows, no pagination).
     */
    public function exportPdf(Request $request)
    {
        $materials = $this->materialsFilteredQuery($request)
            ->join('waste_streams', 'materials.waste_stream_id', '=', 'waste_streams.id')
            ->join('grades', 'materials.grade_id', '=', 'grades.id')
            ->orderBy('waste_streams.name')
            ->orderBy('grades.name')
            ->orderBy('materials.id')
            ->select('materials.*')
            ->get();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('materials.export-pdf', [
            'materials' => $materials,
            'filterSummary' => $this->materialsExportFilterSummary($request),
            'generatedAt' => Carbon::now(),
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'material_definitions_'.now()->format('Y-m-d_His').'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
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

        $material = Material::create($validated);
        $material->load(['grade:id,name', 'wasteStream:id,name']);
        $label = ($material->grade->name ?? '').' / '.($material->wasteStream->name ?? '');
        ActivityLog::log('material_created', "Material created: {$label}", $material, ['grade_id' => $material->grade_id, 'waste_stream_id' => $material->waste_stream_id]);

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
        $material->load(['grade:id,name', 'wasteStream:id,name']);
        $label = ($material->grade->name ?? '').' / '.($material->wasteStream->name ?? '');
        ActivityLog::log('material_updated', "Material updated: {$label}", $material, ['material_id' => $material->id]);

        return redirect()->route('materials.index')
            ->with('success', 'Material updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        $material->load(['grade:id,name', 'wasteStream:id,name']);
        $label = ($material->grade->name ?? '').' / '.($material->wasteStream->name ?? '');
        ActivityLog::log('material_deleted', "Material deleted: {$label}", $material, ['material_id' => $material->id]);
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

        $oldRate = $material->rebate_rate;
        $material->update([
            'rebate_rate' => round((float) $validated['rebate_rate'], 2),
        ]);

        ActivityLog::log('material_rebate_rate_updated', "Material id {$material->id} rebate rate changed from {$oldRate} to {$material->rebate_rate}", $material, ['material_id' => $material->id, 'old_rate' => $oldRate, 'new_rate' => $material->rebate_rate]);

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

        $oldShare = $material->client_rebate_share;
        $material->update([
            'client_rebate_share' => round((float) $validated['client_rebate_share'], 2),
        ]);

        ActivityLog::log('material_rebate_share_updated', "Material id {$material->id} client rebate share changed from {$oldShare}% to {$material->client_rebate_share}%", $material, ['material_id' => $material->id, 'old_share' => $oldShare, 'new_share' => $material->client_rebate_share]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'client_rebate_share' => $material->client_rebate_share,
            ]);
        }

        return back()->with('success', 'Rebate percentage updated successfully.');
    }

    protected function materialsFilteredQuery(Request $request): Builder
    {
        return Material::query()
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
            });
    }

    protected function materialsExportFilterSummary(Request $request): string
    {
        $parts = [];

        if ($request->filled('search')) {
            $parts[] = 'Search: '.Str::limit((string) $request->input('search'), 80);
        }

        if ($request->filled('waste_stream_id')) {
            $name = WasteStream::query()->whereKey($request->input('waste_stream_id'))->value('name');
            $parts[] = 'Waste stream: '.($name ?? ('#'.$request->input('waste_stream_id')));
        }

        if ($request->filled('facility_id')) {
            $name = Facility::query()->whereKey($request->input('facility_id'))->value('name');
            $parts[] = 'Facility: '.($name ?? ('#'.$request->input('facility_id')));
        }

        if ($request->has('rebate') && $request->input('rebate') !== '' && $request->input('rebate') !== null) {
            $flag = filter_var($request->get('rebate'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $parts[] = 'Rebate: '.($flag ? 'Offered' : 'Not offered');
        }

        if ($request->has('status') && $request->input('status') !== '' && $request->input('status') !== null) {
            $flag = filter_var($request->get('status'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $parts[] = 'Status: '.($flag ? 'Active' : 'Inactive');
        }

        return $parts === [] ? 'Filters: none (all materials)' : 'Filters: '.implode(' — ', $parts);
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
