<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'company_id',
        'branch_id',
        'site_id',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the company that the user belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that the user belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the site that the user belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the orders created by the user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    /**
     * Get the companies that the user belongs to (many-to-many).
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the avatar URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && Storage::disk('public')->exists('avatars/'.$this->avatar)) {
            return '/storage/avatars/'.$this->avatar;
        }

        return $this->getDefaultAvatarUrl();
    }

    /**
     * Get default avatar URL (initials).
     */
    public function getDefaultAvatarUrl(): string
    {
        $initials = strtoupper(substr($this->name, 0, 1));

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=6366f1&color=fff&size=128';
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a company user.
     */
    public function isCompanyUser(): bool
    {
        return $this->hasRole('company_user');
    }

    /**
     * Get company IDs that user belongs to.
     */
    public function getCompanyIdsAttribute(): array
    {
        return $this->companies()->pluck('companies.id')->toArray();
    }

    /**
     * Check if user belongs to a specific company.
     */
    public function belongsToCompany(int $companyId): bool
    {
        return $this->companies()->where('companies.id', $companyId)->exists();
    }

    /**
     * Get user's role for a specific company.
     */
    public function getRoleForCompany(int $companyId): ?string
    {
        $pivot = $this->companies()->where('companies.id', $companyId)->first()?->pivot;

        return $pivot?->role;
    }

    /**
     * Check if user is a manager for any company.
     */
    public function isManagerForAnyCompany(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->companies()
            ->wherePivot('role', 'manager')
            ->exists();
    }

    /**
     * Check if user is a viewer for all companies (no manager role).
     */
    public function isViewerOnly(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        $hasManagerRole = $this->companies()
            ->wherePivot('role', 'manager')
            ->exists();

        return ! $hasManagerRole && $this->isCompanyUser();
    }

    /**
     * Check if user can create orders.
     */
    public function canCreateOrders(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isManagerForAnyCompany();
    }

    /**
     * Check if user is a manager for a specific company.
     */
    public function isManagerForCompany(int $companyId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->getRoleForCompany($companyId) === 'manager';
    }

    /**
     * Check if user can manage orders for a specific company.
     */
    public function canManageOrdersForCompany(int $companyId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->getRoleForCompany($companyId);

        return $role === 'manager';
    }
}
