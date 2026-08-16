<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Notifications\UserAssignedToCompanyNotification;
use App\Repositories\CompanyUserRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class CompanyUserService
{
    public function __construct(
        private CompanyUserRepository $companyUserRepository,
        private UserRepository $userRepository
    ) {}

    /**
     * Assign user to company.
     */
    public function assignUserToCompany(User $user, Company $company, string $role = 'viewer'): void
    {
        $wasAlreadyAssigned = $this->companyUserRepository->userBelongsToCompany($user, $company);

        $this->companyUserRepository->attachUserToCompany($user, $company, $role);

        if (! $user->isAdmin() && ! $user->hasRole('company_user')) {
            $user->assignRole('company_user');
        }

        if ($user->is_active && ! $wasAlreadyAssigned) {
            $user->notify(new UserAssignedToCompanyNotification($company, $role));
        }
    }

    /**
     * Remove user from company.
     */
    public function removeUserFromCompany(User $user, Company $company): void
    {
        $this->companyUserRepository->detachUserFromCompany($user, $company);
    }

    /**
     * Update user role in company.
     */
    public function updateUserRole(User $user, Company $company, string $role): void
    {
        $this->companyUserRepository->updateUserRole($user, $company, $role);
    }

    /**
     * Get users for company.
     */
    public function getUsersForCompany(Company $company)
    {
        return $this->companyUserRepository->getUsersForCompany($company);
    }

    /**
     * Get companies for user.
     */
    public function getCompaniesForUser(User $user)
    {
        return $this->companyUserRepository->getCompaniesForUser($user);
    }

    /**
     * Check if user belongs to company.
     */
    public function userBelongsToCompany(User $user, Company $company): bool
    {
        return $this->companyUserRepository->userBelongsToCompany($user, $company);
    }

    /**
     * Get user role in company.
     */
    public function getUserRoleInCompany(User $user, Company $company): ?string
    {
        return $this->companyUserRepository->getUserRoleInCompany($user, $company);
    }

    /**
     * Get company IDs for user.
     */
    public function getCompanyIdsForUser(User $user): array
    {
        $companyIds = $user->company_ids;

        if ($user->company_id) {
            $companyIds[] = (int) $user->company_id;
        }

        return array_values(array_unique(array_map('intval', $companyIds)));
    }

    /**
     * Check if user can access company data.
     */
    public function canAccessCompany(User $user, Company $company): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->userBelongsToCompany($user, $company);
    }

    /**
     * Scope a query on a model with a `companies()` BelongsToMany relation (e.g. Media) so
     * only rows visible to the given user are returned. Internal staff (any role other than
     * client/company_user) always see everything. External users only see rows with no
     * companies attached (public/unrestricted, the default) or attached to a company they
     * belong to.
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if (! $user->hasRole('client') && ! $user->hasRole('company_user')) {
            return $query;
        }

        $companyIds = $this->getCompanyIdsForUser($user);

        return $query->where(function (Builder $q) use ($companyIds): void {
            $q->doesntHave('companies');
            if (! empty($companyIds)) {
                $q->orWhereHas('companies', fn (Builder $cq) => $cq->whereIn('companies.id', $companyIds));
            }
        });
    }

    /**
     * Whether the given model instance (with a `companies()` relation) is visible to the user.
     * Object-level counterpart to {@see scopeVisibleToUser()} for single-record authorization
     * checks (e.g. download/view routes) where a query scope isn't applicable.
     */
    public function canViewRestrictedModel(User $user, mixed $model): bool
    {
        if (! $user->hasRole('client') && ! $user->hasRole('company_user')) {
            return true;
        }

        if ($model->companies()->doesntExist()) {
            return true;
        }

        $companyIds = $this->getCompanyIdsForUser($user);

        return $model->companies()->whereIn('companies.id', $companyIds)->exists();
    }
}
