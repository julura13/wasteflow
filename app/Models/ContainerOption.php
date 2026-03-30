<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContainerOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type',
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
            if (empty($containerOption->slug) && ! empty($containerOption->name) && ! empty($containerOption->order_type)) {
                $containerOption->slug = Str::slug($containerOption->order_type.'-'.$containerOption->name);
            }
        });
    }

    public function scopeForOrderType($query, string $orderType)
    {
        return $query->where('order_type', $orderType);
    }
}
