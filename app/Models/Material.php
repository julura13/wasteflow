<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'waste_stream_id',
        'grade_id',
        'classification_id',
        'facility_id',
        'service_provider_id',
        'weight_required',
        'rebate_offered',
        'rebate_rate',
        'client_rebate_share',
        'backing_document',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rebate_offered' => 'boolean',
            'backing_document' => 'boolean',
            'rebate_rate' => 'decimal:2',
            'client_rebate_share' => 'decimal:2',
        ];
    }

    public function wasteStream(): BelongsTo
    {
        return $this->belongsTo(WasteStream::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function orderWasteStreams(): HasMany
    {
        return $this->hasMany(OrderWasteStream::class);
    }

    /**
     * Scope to get only active materials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}