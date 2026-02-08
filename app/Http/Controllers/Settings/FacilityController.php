<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function index(Request $request): Response
    {
        $facilities = Facility::query()
            ->orderBy('name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Settings/Facilities/Index', [
            'facilities' => $facilities,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name',
            'description' => 'nullable|string|max:1000',
            'facility_type' => 'nullable|string|max:100',
            'requires_weight' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['requires_weight'] = $request->has('requires_weight') ? $request->boolean('requires_weight') : true;
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        Facility::create($data);

        return back()->with('success', 'Facility created successfully.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name,' . $facility->id,
            'description' => 'nullable|string|max:1000',
            'facility_type' => 'nullable|string|max:100',
            'requires_weight' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['requires_weight'] = $request->has('requires_weight') ? $request->boolean('requires_weight') : true;
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $facility->update($data);

        return back()->with('success', 'Facility updated successfully.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $facility->delete();

        return back()->with('success', 'Facility deleted successfully.');
    }
}
