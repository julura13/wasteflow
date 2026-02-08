<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users (WasteFlow staff and client users).
     */
    public function index(Request $request)
    {
        $users = User::with(['roles', 'company:id,name'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->has('active') && $request->active !== '', function ($query) use ($request) {
                $query->where('is_active', (bool) $request->active);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'active']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
            'company_id' => 'nullable|exists:companies,id',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $validated['company_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
            'company_id' => 'nullable|exists:companies,id',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->company_id = $validated['company_id'] ?? null;
        $user->phone = $validated['phone'] ?? null;
        $user->is_active = $validated['is_active'] ?? true;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }
}
