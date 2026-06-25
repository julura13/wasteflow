<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class ContainerOption extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'order_type',
        'name',
        'slug',
        'is_active',
        'default_weight',
        'show_in_summary',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_weight' => 'float',
            'show_in_summary' => 'boolean',
        ];
    }

    public function searchableAs(): string
    {
        return 'container_options';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order_type' => $this->order_type,
            'slug' => $this->slug,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_active;
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
