<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'types',
        'email',
        'phone',
        'address',
        'contact_person',
        'registration_number',
        'notes',
        'slip_number_prefix',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'types' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the orders for the service provider.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'service_provider_id');
    }

    /**
     * Scope to get only active service providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get service providers by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->whereJsonContains('types', $type);
    }
}