<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WasteStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $wasteStream) {
            if (empty($wasteStream->slug) && !empty($wasteStream->name)) {
                $wasteStream->slug = Str::slug($wasteStream->name);
            }
        });
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}