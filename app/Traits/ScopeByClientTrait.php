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

        return $user !== null && ! $user->can('view-reports-all');
    }

    protected function scopeCompaniesForUser()
    {
        if (! $this->isClientScoped()) {
            return Company::where('is_active', true)->orderBy('name')->get();
        }

        $companyIds = $this->scopedCompanyIdsForUser(Auth::user());

        return Company::whereIn('id', $companyIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Enforce company/branch/site to a given user's client scope. Returns [companyId, branchId, siteId].
     */
    protected function enforceCompanyScopeForUser(?User $user, ?int $companyId, ?int $branchId = null, ?int $siteId = null): array
    {
        if ($user === null || $user->can('view-reports-all')) {
            return [$companyId, $branchId, $siteId];
        }

        $scopedCompanyIds = $this->scopedCompanyIdsForUser($user);

        if ($companyId !== null && ! in_array($companyId, $scopedCompanyIds, true)) {
            $companyId = null;
            $branchId = null;
            $siteId = null;
        }

        if ($branchId) {
            $branchQuery = Branch::where('id', $branchId);
            if ($companyId !== null) {
                $branchQuery->where('company_id', $companyId);
            } else {
                $branchQuery->whereIn('company_id', $scopedCompanyIds);
            }
            $branch = $branchQuery->first();
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

    protected function scopedCompanyIdsForUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $companyIds = $user->companies()->pluck('companies.id')->map(fn ($id) => (int) $id)->all();

        if ($user->company_id) {
            $companyIds[] = (int) $user->company_id;
        }

        return array_values(array_unique($companyIds));
    }

    /**
     * Abort with 403 if the user does not have access to this order.
     */
    protected function ensureOrderInScope(\App\Models\Order $order): void
    {
        $user = Auth::user();
        if (! $user || $user->isAdmin()) {
            return;
        }

        $scopedCompanyIds = $this->scopedCompanyIdsForUser($user);
        if (! empty($scopedCompanyIds) && ! in_array((int) $order->company_id, $scopedCompanyIds, true)) {
            abort(403, 'You do not have access to this order.');
        }
    }

    /**
     * Enforce company/branch/site to user's scope. Returns [companyId, branchId, siteId].
     */
    protected function enforceCompanyScope(?int $companyId, ?int $branchId = null, ?int $siteId = null): array
    {
        return $this->enforceCompanyScopeForUser(Auth::user(), $companyId, $branchId, $siteId);
    }
}
