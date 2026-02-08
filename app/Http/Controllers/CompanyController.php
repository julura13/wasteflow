<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyUserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyUserService $companyUserService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companies = Company::with(['branches.sites'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%");
            })
            ->when($request->status !== null, function ($query, $status) {
                $query->where('is_active', $status);
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Companies/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'rebate_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        $company->load(['branches.sites.orders']);
        $assignedUsers = $this->companyUserService->getUsersForCompany($company);
        
        $companies = Company::with('branches')->where('is_active', true)->get();

        return Inertia::render('Companies/Show', [
            'company' => $company,
            'companies' => $companies,
            'assignedUsers' => $assignedUsers->map(function ($user) use ($company) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'role' => $user->getRoleForCompany($company->id),
                ];
            }),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        return Inertia::render('Companies/Edit', [
            'company' => $company,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'rebate_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}