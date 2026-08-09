<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewUserPendingApprovalNotification;
use App\Notifications\UserApprovedNotification;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Create a new user (registration).
     */
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = false; // New users are pending approval

        $user = $this->userRepository->create($data);

        $admins = User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewUserPendingApprovalNotification($user));
        }

        return $user;
    }

    /**
     * Update user.
     */
    public function updateUser(User $user, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->userRepository->update($user, $data);
    }

    /**
     * Approve user (activate).
     */
    public function approveUser(User $user): bool
    {
        $updated = $this->userRepository->update($user, ['is_active' => true]);

        if ($updated) {
            $user->notify(new UserApprovedNotification);
        }

        return $updated;
    }

    /**
     * Deactivate user.
     */
    public function deactivateUser(User $user): bool
    {
        return $this->userRepository->update($user, ['is_active' => false]);
    }

    /**
     * Upload and save avatar.
     */
    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/'.$user->avatar);
        }

        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

        $file->storeAs('avatars', $filename, 'public');

        $this->userRepository->update($user, ['avatar' => $filename]);

        return $filename;
    }

    /**
     * Delete avatar.
     */
    public function deleteAvatar(User $user): bool
    {
        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/'.$user->avatar);

            return $this->userRepository->update($user, ['avatar' => null]);
        }

        return true;
    }

    /**
     * Assign role to user.
     */
    public function assignRole(User $user, string $roleName): void
    {
        $user->assignRole($roleName);
    }

    /**
     * Remove role from user.
     */
    public function removeRole(User $user, string $roleName): void
    {
        $user->removeRole($roleName);
    }

    /**
     * Sync roles for user.
     */
    public function syncRoles(User $user, array $roleNames): void
    {
        $user->syncRoles($roleNames);
    }

    /**
     * Get paginated users with filters.
     */
    public function getPaginatedUsers(int $perPage = 15, array $filters = [], ?int $excludeUserId = null)
    {
        return $this->userRepository->paginate($perPage, $filters, $excludeUserId);
    }

    /**
     * Get pending users.
     */
    public function getPendingUsers()
    {
        return $this->userRepository->getPendingUsers();
    }

    /**
     * Get active users.
     */
    public function getActiveUsers()
    {
        return $this->userRepository->getActiveUsers();
    }

    /**
     * Delete user.
     */
    public function deleteUser(User $user): bool
    {
        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/'.$user->avatar);
        }

        return $this->userRepository->delete($user);
    }
}
