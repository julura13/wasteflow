<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceProviderRequest;
use App\Http\Requests\UpdateServiceProviderRequest;
use App\Models\ActivityLog;
use App\Models\ServiceProvider;
use Illuminate\Foundation\Http\FormRequest;
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
    public function store(StoreServiceProviderRequest $request)
    {
        $validated = $this->applyServiceProviderDefaults($request);

        $serviceProvider = ServiceProvider::create($validated);

        ActivityLog::log('service_provider_created', "Service provider {$serviceProvider->name} created", $serviceProvider, ['name' => $serviceProvider->name]);

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
    public function update(UpdateServiceProviderRequest $request, ServiceProvider $serviceProvider)
    {
        $validated = $this->applyServiceProviderDefaults($request);

        $serviceProvider->update($validated);

        ActivityLog::log('service_provider_updated', "Service provider {$serviceProvider->name} updated", $serviceProvider, ['name' => $serviceProvider->name]);

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

        ActivityLog::log('service_provider_deleted', "Service provider {$serviceProvider->name} deleted", $serviceProvider, ['name' => $serviceProvider->name]);
        $serviceProvider->delete();

        return redirect()->route('service-providers.index')
            ->with('success', 'Service provider deleted successfully.');
    }

    protected function applyServiceProviderDefaults(FormRequest $request): array
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['slip_number_prefix'] = ! empty(trim((string) ($validated['slip_number_prefix'] ?? '')))
            ? trim((string) $validated['slip_number_prefix'])
            : null;

        return $validated;
    }
}
