<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMonthlyMaterialSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'site_id',
        'year',
        'month',
        'material_id',
        'waste_stream_id',
        'total_weight',
        'order_count',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'total_weight' => 'decimal:3',
            'order_count' => 'integer',
            'last_updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function wasteStream(): BelongsTo
    {
        return $this->belongsTo(WasteStream::class);
    }
}
