<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecoveryRatingTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_percentage',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_percentage' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Resolve the tier a diversion percentage falls into (highest min_percentage <= $percentage).
     */
    public static function forPercentage(float $percentage): ?self
    {
        return static::query()
            ->where('min_percentage', '<=', $percentage)
            ->orderByDesc('min_percentage')
            ->first();
    }
}
