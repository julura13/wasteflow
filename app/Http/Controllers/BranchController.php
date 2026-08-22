<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branches = Branch::with(['company', 'sites'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->company_id, function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'companies' => $companies,
            'filters' => $request->only(['search', 'company_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::where('is_active', true)->get();

        return Inertia::render('Branches/Create', [
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBranchRequest $request)
    {
        $validated = $request->validated();

        $branch = Branch::create($validated);

        ActivityLog::log('branch_created', "Branch {$branch->name} created", $branch, ['name' => $branch->name, 'company_id' => $branch->company_id]);

        if ($request->header('X-Inertia')) {
            return redirect()->route('companies.show', $validated['company_id'])
                ->with('success', 'Branch created successfully.');
        }

        return redirect()->route('branches.index')
            ->with('success', 'Branch created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        $branch->load(['company', 'sites.orders']);

        return Inertia::render('Branches/Show', [
            'branch' => $branch,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        $companies = Company::where('is_active', true)->get();

        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'branch' => $branch,
                'companies' => $companies,
            ]);
        }

        return Inertia::render('Branches/Edit', [
            'branch' => $branch,
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $validated = $request->validated();

        $branch->update($validated);

        ActivityLog::log('branch_updated', "Branch {$branch->name} updated", $branch, ['name' => $branch->name]);

        if ($request->header('X-Inertia')) {
            return redirect()->route('companies.show', $validated['company_id'])
                ->with('success', 'Branch updated successfully.');
        }

        return redirect()->route('branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        $hasOrders = $branch->sites()->whereHas('orders')->exists();

        if ($hasOrders) {
            return redirect()->back()
                ->with('error', 'This branch cannot be deleted because it has collection points with associated orders.');
        }

        ActivityLog::log('branch_deleted', "Branch {$branch->name} deleted", $branch, ['name' => $branch->name]);
        $branch->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('companies.show', $branch->company_id)
                ->with('success', 'Branch deleted successfully.');
        }

        return redirect()->route('branches.index')
            ->with('success', 'Branch deleted successfully.');
    }
}
