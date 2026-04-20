<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Notifications\UserAssignedToCompanyNotification;
use App\Repositories\CompanyUserRepository;
use App\Repositories\UserRepository;

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
}
