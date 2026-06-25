<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class ServiceProvider extends Model
{
    use HasFactory, Searchable;

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

    public function searchableAs(): string
    {
        return 'service_providers';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'registration_number' => $this->registration_number,
            'contact_person' => $this->contact_person,
            'slip_number_prefix' => $this->slip_number_prefix,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_active;
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
