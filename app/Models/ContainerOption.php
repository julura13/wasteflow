<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContainerOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'default_weight',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_weight' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $containerOption) {
            if (empty($containerOption->slug) && !empty($containerOption->name)) {
                $containerOption->slug = Str::slug($containerOption->name);
            }
        });
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}

