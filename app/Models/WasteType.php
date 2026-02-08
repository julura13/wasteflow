<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'commodity_value',
        'unit',
        'is_commodity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'commodity_value' => 'decimal:2',
            'is_commodity' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the waste streams for the waste type.
     */
    public function wasteStreams(): HasMany
    {
        return $this->hasMany(OrderWasteStream::class);
    }

    /**
     * Scope to get only commodity waste types.
     */
    public function scopeCommodities($query)
    {
        return $query->where('is_commodity', true);
    }

    /**
     * Scope to get only active waste types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}