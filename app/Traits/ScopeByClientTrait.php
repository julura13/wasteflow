<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * When a user has company_id set and does not have "view-reports-all",
 * they are a client user and should only see data for their own company.
 */
trait ScopeByClientTrait
{
    protected function isClientScoped(): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->company_id) {
            return false;
        }

        return ! $user->can('view-reports-all');
    }

    protected function scopeCompaniesForUser()
    {
        if (! $this->isClientScoped()) {
            return Company::where('is_active', true)->orderBy('name')->get();
        }

        return Company::where('id', Auth::user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Enforce company/branch/site to a given user's client scope. Returns [companyId, branchId, siteId].
     */
    protected function enforceCompanyScopeForUser(?User $user, ?int $companyId, ?int $branchId = null, ?int $siteId = null): array
    {
        if ($user === null || ! $user->company_id || $user->can('view-reports-all')) {
            return [$companyId, $branchId, $siteId];
        }

        $companyId = (int) $user->company_id;
        if ($branchId) {
            $branch = Branch::where('id', $branchId)->where('company_id', $companyId)->first();
            if (! $branch) {
                $branchId = null;
                $siteId = null;
            } elseif ($siteId) {
                $site = Site::where('id', $siteId)->where('branch_id', $branch->id)->first();
                if (! $site) {
                    $siteId = null;
                }
            }
        }

        return [$companyId, $branchId, $siteId];
    }

    /**
     * Enforce company/branch/site to user's scope. Returns [companyId, branchId, siteId].
     */
    protected function enforceCompanyScope(?int $companyId, ?int $branchId = null, ?int $siteId = null): array
    {
        return $this->enforceCompanyScopeForUser(Auth::user(), $companyId, $branchId, $siteId);
    }
}
