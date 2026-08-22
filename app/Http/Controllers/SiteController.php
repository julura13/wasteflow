<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sites = Site::with(['branch.company'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('branch.company', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->company_id, function ($query, $companyId) {
                $query->whereHas('branch', function ($branchQuery) use ($companyId) {
                    $branchQuery->where('company_id', $companyId);
                });
            })
            ->when($request->branch_id, function ($query, $branchId) {
                $query->where('branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_id']);

        return Inertia::render('CollectionPoints/Index', [
            'sites' => $sites,
            'companies' => $companies,
            'branches' => $branches,
            'filters' => $request->only(['search', 'company_id', 'branch_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::with('branches')->where('is_active', true)->get();

        return Inertia::render('CollectionPoints/Create', [
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiteRequest $request)
    {
        $validated = $request->validated();

        $site = Site::create($validated);

        ActivityLog::log('collection_point_created', "Collection point {$site->name} created", $site, ['name' => $site->name, 'branch_id' => $site->branch_id]);

        $branch = Branch::find($validated['branch_id']);
        $companyId = $branch ? $branch->company_id : null;

        if ($request->header('X-Inertia') && $companyId) {
            return redirect()->route('companies.show', $companyId)
                ->with('success', 'Collection point created successfully.');
        }

        return redirect()->route('collection-points.index')
            ->with('success', 'Collection point created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Site $site)
    {
        $site->load(['branch.company', 'orders']);

        return Inertia::render('CollectionPoints/Show', [
            'site' => $site,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        $site->load(['branch.company']);
        $companies = Company::with('branches')->where('is_active', true)->get();

        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'site' => $site,
                'companies' => $companies,
            ]);
        }

        return Inertia::render('CollectionPoints/Edit', [
            'site' => $site,
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiteRequest $request, Site $site)
    {
        $validated = $request->validated();

        $site->update($validated);

        ActivityLog::log('collection_point_updated', "Collection point {$site->name} updated", $site, ['name' => $site->name]);

        $branch = Branch::find($validated['branch_id']);
        $companyId = $branch ? $branch->company_id : null;

        if ($request->header('X-Inertia') && $companyId) {
            return redirect()->route('companies.show', $companyId)
                ->with('success', 'Collection point updated successfully.');
        }

        return redirect()->route('collection-points.index')
            ->with('success', 'Collection point updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        if ($site->orders()->exists()) {
            return redirect()->back()
                ->with('error', 'This collection point cannot be deleted because it has associated orders.');
        }

        $branch = $site->branch;
        $companyId = $branch ? $branch->company_id : null;

        ActivityLog::log('collection_point_deleted', "Collection point {$site->name} deleted", $site, ['name' => $site->name]);
        $site->delete();

        if (request()->header('X-Inertia') && $companyId) {
            return redirect()->route('companies.show', $companyId)
                ->with('success', 'Collection point deleted successfully.');
        }

        if (request()->header('X-Inertia') && $branch) {
            return redirect()->route('branches.show', $branch->id)
                ->with('success', 'Collection point deleted successfully.');
        }

        return redirect()->route('collection-points.index')
            ->with('success', 'Collection point deleted successfully.');
    }
}
