<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\ServiceProvider;
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
            ->orderBy('name')
            ->paginate(15)
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
        return Inertia::render('Companies/Create', [
            'serviceProviders' => ServiceProvider::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        $company = Company::create($request->validated());

        ActivityLog::log('company_created', "Company {$company->name} created", $company, ['name' => $company->name]);

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
            'serviceProviders' => ServiceProvider::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        ActivityLog::log('company_updated', "Company {$company->name} updated", $company, ['name' => $company->name]);

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        ActivityLog::log('company_deleted', "Company {$company->name} deleted", $company, ['name' => $company->name]);
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}
