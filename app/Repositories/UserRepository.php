<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Get all users with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = [], ?int $excludeUserId = null): LengthAwarePaginator
    {
        $query = User::with(['companies', 'roles']);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'pending') {
                $query->where('is_active', false);
            } elseif ($filters['status'] === 'active') {
                $query->where('is_active', true);
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['company_id'])) {
            $query->whereHas('companies', function ($q) use ($filters) {
                $q->where('companies.id', $filters['company_id']);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all users.
     */
    public function all(array $filters = []): Collection
    {
        $query = User::with(['companies', 'roles']);

        if (isset($filters['status'])) {
            if ($filters['status'] === 'pending') {
                $query->where('is_active', false);
            } elseif ($filters['status'] === 'active') {
                $query->where('is_active', true);
            }
        }

        if (isset($filters['company_id'])) {
            $query->whereHas('companies', function ($q) use ($filters) {
                $q->where('companies.id', $filters['company_id']);
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User
    {
        return User::with(['companies', 'roles'])->find($id);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update user.
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Delete user.
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Get pending users.
     */
    public function getPendingUsers(): Collection
    {
        return User::where('is_active', false)
            ->with(['companies', 'roles'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active users.
     */
    public function getActiveUsers(): Collection
    {
        return User::where('is_active', true)
            ->with(['companies', 'roles'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get users by company ID.
     */
    public function getUsersByCompany(int $companyId): Collection
    {
        return User::whereHas('companies', function ($query) use ($companyId) {
            $query->where('companies.id', $companyId);
        })
        ->with(['companies', 'roles'])
        ->orderBy('name')
        ->get();
    }
}

