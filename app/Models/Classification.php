<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classification extends Model
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
        static::saving(function (self $classification) {
            if (empty($classification->slug) && !empty($classification->name)) {
                $classification->slug = Str::slug($classification->name);
            }
        });
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}

