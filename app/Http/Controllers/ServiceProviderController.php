<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $serviceProviders = ServiceProvider::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($scopedQuery) use ($search) {
                    $scopedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($query, $type) {
                $query->whereJsonContains('types', $type);
            })
            ->when($request->status !== null, function ($query) use ($request) {
                $query->where('is_active', $request->status);
            })
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('ServiceProviders/Index', [
            'serviceProviders' => $serviceProviders,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ServiceProviders/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'types' => 'required|array|min:1',
            'types.*' => 'in:waste_collection,recycling,hazardous,general',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'slip_number_prefix' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['slip_number_prefix'] = ! empty(trim((string) ($validated['slip_number_prefix'] ?? '')))
            ? trim((string) $validated['slip_number_prefix'])
            : null;

        ServiceProvider::create($validated);

        return redirect()->route('service-providers.index')
            ->with('success', 'Service provider created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceProvider $serviceProvider)
    {
        $serviceProvider->load(['orders']);

        return Inertia::render('ServiceProviders/Show', [
            'serviceProvider' => $serviceProvider,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceProvider $serviceProvider)
    {
        return Inertia::render('ServiceProviders/Edit', [
            'serviceProvider' => $serviceProvider,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceProvider $serviceProvider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'types' => 'required|array|min:1',
            'types.*' => 'in:waste_collection,recycling,hazardous,general',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'slip_number_prefix' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['slip_number_prefix'] = ! empty(trim((string) ($validated['slip_number_prefix'] ?? '')))
            ? trim((string) $validated['slip_number_prefix'])
            : null;

        $serviceProvider->update($validated);

        return redirect()->route('service-providers.index')
            ->with('success', 'Service provider updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceProvider $serviceProvider)
    {
        $usageCount = $serviceProvider->orders()->count();
        if ($usageCount > 0) {
            return redirect()
                ->route('service-providers.index')
                ->with('error', 'Cannot delete a service provider that has been used on existing orders.');
        }

        $serviceProvider->delete();

        return redirect()->route('service-providers.index')
            ->with('success', 'Service provider deleted successfully.');
    }
}