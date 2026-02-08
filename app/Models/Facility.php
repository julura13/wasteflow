<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'facility_type',
        'requires_weight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_weight' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $facility) {
            if (empty($facility->slug) && !empty($facility->name)) {
                $facility->slug = Str::slug($facility->name);
            }
        });
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}

