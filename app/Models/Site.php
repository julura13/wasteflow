<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'address',
        'contact_person',
        'phone',
        'email',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the branch that owns the collection point.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the company through the branch.
     */
    public function getCompanyAttribute()
    {
        return $this->branch?->company;
    }

    /**
     * Get the company name through the branch.
     */
    public function getCompanyNameAttribute(): ?string
    {
        if ($this->branch_id && $this->branch && $this->branch->company) {
            return $this->branch->company->name;
        }
        
        return null;
    }

    /**
     * Get the users for the site.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the orders for the site.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}