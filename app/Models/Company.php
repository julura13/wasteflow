<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'contact_person',
        'registration_number',
        'rebate_percentage',
        'default_waste_service_provider_id',
        'default_recycling_service_provider_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rebate_percentage' => 'decimal:2',
        ];
    }

    public function defaultWasteServiceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'default_waste_service_provider_id');
    }

    public function defaultRecyclingServiceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'default_recycling_service_provider_id');
    }

    /**
     * Get the branches for the company.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the users for the company (legacy direct relationship).
     */
    public function directUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the users for the company (many-to-many relationship).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get all collection points through branches.
     */
    public function collectionPoints(): HasManyThrough
    {
        return $this->hasManyThrough(Site::class, Branch::class);
    }

    /**
     * Get all orders for the company.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
