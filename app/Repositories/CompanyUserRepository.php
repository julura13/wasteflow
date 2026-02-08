<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CompanyUserRepository
{
    /**
     * Attach user to company.
     */
    public function attachUserToCompany(User $user, Company $company, string $role = 'viewer'): void
    {
        if (!$user->companies()->where('companies.id', $company->id)->exists()) {
            $user->companies()->attach($company->id, ['role' => $role]);
        } else {
            $user->companies()->updateExistingPivot($company->id, ['role' => $role]);
        }
    }

    /**
     * Detach user from company.
     */
    public function detachUserFromCompany(User $user, Company $company): void
    {
        $user->companies()->detach($company->id);
    }

    /**
     * Update user role in company.
     */
    public function updateUserRole(User $user, Company $company, string $role): void
    {
        $user->companies()->updateExistingPivot($company->id, ['role' => $role]);
    }

    /**
     * Get users for a company.
     */
    public function getUsersForCompany(Company $company): Collection
    {
        return $company->users()->with(['roles'])->get();
    }

    /**
     * Get companies for a user.
     */
    public function getCompaniesForUser(User $user): Collection
    {
        return $user->companies()->get();
    }

    /**
     * Check if user belongs to company.
     */
    public function userBelongsToCompany(User $user, Company $company): bool
    {
        return $user->companies()->where('companies.id', $company->id)->exists();
    }

    /**
     * Get user role in company.
     */
    public function getUserRoleInCompany(User $user, Company $company): ?string
    {
        $pivot = $user->companies()->where('companies.id', $company->id)->first()?->pivot;
        return $pivot?->role;
    }

    /**
     * Sync users to company (replace all users with new list).
     */
    public function syncUsersToCompany(Company $company, array $userIds, array $roles = []): void
    {
        $syncData = [];
        foreach ($userIds as $index => $userId) {
            $syncData[$userId] = ['role' => $roles[$index] ?? 'viewer'];
        }
        $company->users()->sync($syncData);
    }
}

