<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tracking_number',
        'company_id',
        'branch_id',
        'site_id',
        'service_provider_id',
        'created_by',
        'order_type',
        'status',
        'waste_type',
        'quantity_type',
        'quantity',
        'quantity_lines',
        'requested_collection_date',
        'actual_collection_date',
        'service_provider',
        'slip_number',
        'company_rebate_percentage',
        'estimated_quantity',
        'actual_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_collection_date' => 'date:Y-m-d',
            'actual_collection_date' => 'date:Y-m-d',
            'quantity_lines' => 'array',
            'company_rebate_percentage' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->tracking_number)) {
                $order->tracking_number = static::generateTrackingNumber($order->order_type ?? 'waste');
            }

            // Auto-populate company_id and branch_id from site if not set
            if ($order->site_id && (! $order->company_id || ! $order->branch_id)) {
                $site = Site::with('branch')->find($order->site_id);
                if ($site) {
                    if (! $order->branch_id && $site->branch_id) {
                        $order->branch_id = $site->branch_id;
                    }
                    if (! $order->company_id && $site->branch && $site->branch->company_id) {
                        $order->company_id = $site->branch->company_id;
                    }
                }
            }
        });

        static::updating(function ($order) {
            if ($order->isDirty('status') && auth()->check()) {
                app(\App\Repositories\OrderStatusHistoryRepository::class)->createForOrder(
                    $order->id,
                    $order->status,
                    auth()->id()
                );
            }
        });

        static::deleting(function ($order) {
            // Delete each waste stream via model so OrderWasteStream::deleted fires and
            // ClientMonthlySummaryService::removeWeight runs (bulk delete would skip model events).
            foreach ($order->wasteStreams as $stream) {
                $stream->delete();
            }
            // Delete media (documents) and their files from storage.
            $order->media()->delete();
        });
    }

    public static function generateTrackingNumber(string $orderType = 'waste'): string
    {
        $prefix = $orderType === 'recycling' ? 'RO' : 'WO';
        $dateCode = date('ym');
        $sequenceStart = 30001;
        $sequence = $sequenceStart;

        // Use one global sequence for the date code so the numeric part is always in order
        // (no duplicate sequence numbers across RO and WO, e.g. 30001, 30002, 30003...)
        $maxSeq = (int) static::withTrashed()
            ->where('tracking_number', 'like', '%-'.$dateCode.'-%')
            ->selectRaw('MAX(CAST(SUBSTRING(tracking_number, -5) AS UNSIGNED)) as max_seq')
            ->value('max_seq');

        $sequence = max($sequence, $maxSeq + 1);

        do {
            $trackingNumber = sprintf('%s-%s-%05d', $prefix, $dateCode, $sequence);
            $exists = static::withTrashed()
                ->where('tracking_number', $trackingNumber)
                ->exists();
            if (! $exists) {
                return $trackingNumber;
            }
            $sequence++;
        } while (true);
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function wasteStreams(): HasMany
    {
        return $this->hasMany(OrderWasteStream::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function getTotalNettWeightAttribute(): float
    {
        return $this->wasteStreams->sum('nett_weight');
    }

    public function getTotalRebateAttribute(): float
    {
        $companyRebatePercentage = null;

        if ($this->status === 'finalized' && $this->company_rebate_percentage !== null) {
            $companyRebatePercentage = $this->company_rebate_percentage;
        } else {
            if (! $this->relationLoaded('site')) {
                $this->load('site.branch.company');
            }

            $company = $this->site->branch->company ?? null;

            if ($company && isset($company->rebate_percentage)) {
                $companyRebatePercentage = $company->rebate_percentage;
            }
        }

        return $this->wasteStreams->sum(function ($stream) use ($companyRebatePercentage) {
            if ($stream->material && $stream->material->rebate_offered && $stream->material->rebate_rate) {
                $rebateAmount = $stream->nett_weight * $stream->material->rebate_rate;

                if ($companyRebatePercentage !== null && $companyRebatePercentage !== '' && is_numeric($companyRebatePercentage)) {
                    $clientShare = (float) $companyRebatePercentage;
                } else {
                    $clientShare = $stream->material->client_rebate_share ?? 100;
                }

                return ($rebateAmount * $clientShare) / 100;
            }

            return 0;
        });
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function supportingDocuments(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
            ->where('collection', 'supporting_documents');
    }

    public function canBeFinalized(): bool
    {
        return $this->status === 'documents_required'
            && $this->wasteStreams()->count() > 0
            && $this->hasRequiredSupportingDocuments();
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['scheduled', 'weight_required'],
            'scheduled' => ['weight_required'],
            'weight_required' => ['documents_required'],
            'documents_required' => ['finalized'],
            'finalized' => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    public function getCanBeFinalizedAttribute(): bool
    {
        return $this->canBeFinalized();
    }

    public function hasRequiredSupportingDocuments(): bool
    {
        return $this->supportingDocuments()->count() > 0;
    }

    public function getHasRequiredSupportingDocumentsAttribute(): bool
    {
        return $this->hasRequiredSupportingDocuments();
    }
}
