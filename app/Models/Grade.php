<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $grade) {
            if (empty($grade->slug) && !empty($grade->name)) {
                $grade->slug = Str::slug($grade->name);
            }
        });
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}

