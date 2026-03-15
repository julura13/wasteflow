<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Models\ActivityLog;
use App\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load(['companies', 'roles']);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
                'has_avatar' => ! empty($user->avatar),
                'phone' => $user->phone,
                'companies' => $user->companies->map(function ($company) use ($user) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'role' => $user->getRoleForCompany($company->id),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->userService->updateUser($user, $request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            $user->save();
        }

        ActivityLog::log('profile_updated', "Profile updated for user {$user->email}", $user, ['user_id' => $user->id]);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Upload avatar.
     */
    public function uploadAvatar(UploadAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->userService->uploadAvatar($user, $request->file('avatar'));

        ActivityLog::log('profile_avatar_uploaded', "Avatar uploaded for user {$user->email}", $user, ['user_id' => $user->id]);

        return Redirect::route('profile.edit')->with('status', 'avatar-uploaded');
    }

    /**
     * Delete avatar.
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->userService->deleteAvatar($user);

        ActivityLog::log('profile_avatar_deleted', "Avatar deleted for user {$user->email}", $user, ['user_id' => $user->id]);

        return Redirect::route('profile.edit')->with('status', 'avatar-deleted');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        ActivityLog::log('profile_destroyed', "Account deleted for user {$user->email}", $user, ['user_id' => $user->id, 'email' => $user->email]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
