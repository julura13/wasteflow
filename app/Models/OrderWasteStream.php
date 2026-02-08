<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderWasteStream extends Model
{
    use HasFactory;

    protected $table = 'order_waste_streams';

    protected $fillable = [
        'order_id',
        'material_id',
        'gross_weight',
        'tare_weight',
        'nett_weight',
        'quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gross_weight' => 'decimal:3',
            'tare_weight' => 'decimal:3',
            'nett_weight' => 'decimal:3',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($wasteStream) {
            if (empty($wasteStream->nett_weight) && !empty($wasteStream->gross_weight)) {
                $wasteStream->nett_weight = $wasteStream->gross_weight - ($wasteStream->tare_weight ?? 0);
            }
        });

        // Track old values for updates - store in a property that persists through the update
        static::updating(function ($wasteStream) {
            // Store old values before they're changed
            $wasteStream->old_nett_weight = $wasteStream->getOriginal('nett_weight');
            $wasteStream->old_material_id = $wasteStream->getOriginal('material_id');
        });

        // Update summaries after save (create or update)
        static::saved(function ($wasteStream) {
            // Ensure order relationship is loaded
            if (!$wasteStream->relationLoaded('order')) {
                $wasteStream->load('order');
            }

            $service = app(\App\Services\ClientMonthlySummaryService::class);
            
            // Check if this was a new record (wasRecentlyCreated is set by Laravel)
            $isNewRecord = $wasteStream->wasRecentlyCreated;
            
            // If this was an update, subtract old weight first
            if (isset($wasteStream->old_nett_weight) && isset($wasteStream->old_material_id)) {
                $service->updateSummaries(
                    $wasteStream,
                    $wasteStream->old_nett_weight,
                    $wasteStream->old_material_id,
                    false // Not a new record
                );
            } else {
                // New record
                $service->updateSummaries($wasteStream, null, null, $isNewRecord);
            }
        });

        // Remove weight from summaries when deleted
        static::deleted(function ($wasteStream) {
            // Ensure order relationship is loaded before deletion
            if (!$wasteStream->relationLoaded('order')) {
                $wasteStream->load('order');
            }

            $service = app(\App\Services\ClientMonthlySummaryService::class);
            $service->removeWeight($wasteStream);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function getRebateAmountAttribute(): float
    {
        if (!$this->material || !$this->material->rebate_offered || !$this->material->rebate_rate) {
            return 0;
        }

        return $this->nett_weight * $this->material->rebate_rate;
    }

    public function getClientRebateAmountAttribute(): float
    {
        $rebateAmount = $this->rebate_amount;
        
        $companyRebatePercentage = null;
        
        if (!$this->relationLoaded('order')) {
            $this->load('order');
        }
        
        if ($this->order->status === 'finalized' && $this->order->company_rebate_percentage !== null) {
            $companyRebatePercentage = $this->order->company_rebate_percentage;
        } else {
            if (!$this->order->relationLoaded('site')) {
                $this->order->load('site.branch.company');
            }
            
            $company = $this->order->site->branch->company ?? null;
            
            if ($company && isset($company->rebate_percentage)) {
                $companyRebatePercentage = $company->rebate_percentage;
            }
        }
        
        if ($companyRebatePercentage !== null && $companyRebatePercentage !== '' && is_numeric($companyRebatePercentage)) {
            $clientShare = (float) $companyRebatePercentage;
        } else {
            $clientShare = $this->material->client_rebate_share ?? 100;
        }
        
        return ($rebateAmount * $clientShare) / 100;
    }
}

