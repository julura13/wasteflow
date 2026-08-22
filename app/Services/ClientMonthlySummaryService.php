<?php

namespace App\Services;

use App\Models\ClientMonthlyMaterialSummary;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
use Carbon\Carbon;

class ClientMonthlySummaryService
{
    /**
     * Rebuild all client monthly material summaries from order waste streams.
     * Uses each order's actual_collection_date (or requested_collection_date) to assign weight to the correct month.
     * Use this to fix incorrect totals (e.g. after reconcile-months double-counted).
     *
     * @param  int|null  $year  If set, only rebuild this year; otherwise all years.
     */
    public function rebuildFromOrderWasteStreams(?int $year = null): int
    {
        $query = OrderWasteStream::query()
            ->with([
                'order:id,company_id,branch_id,site_id,actual_collection_date,requested_collection_date',
                'material:id,waste_stream_id',
            ])
            ->whereHas('order', function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('actual_collection_date')
                        ->orWhereNotNull('requested_collection_date');
                });
            })
            ->whereNotNull('material_id')
            ->where('nett_weight', '>', 0);

        if ($year !== null) {
            $query->whereHas('order', function ($q) use ($year) {
                $q->where(function ($q2) use ($year) {
                    $q2->whereYear('actual_collection_date', $year)
                        ->orWhereYear('requested_collection_date', $year);
                });
            });
        }

        $streams = $query->get();

        $materialBuckets = []; // key => [weight => float, order_ids => Set]
        $streamBuckets = [];

        foreach ($streams as $stream) {
            $order = $stream->order;
            if (! $order) {
                continue;
            }
            $date = $order->actual_collection_date ?? $order->requested_collection_date;
            if (! $date) {
                continue;
            }
            $date = Carbon::parse($date);
            $y = (int) $date->format('Y');
            $m = (int) $date->format('n');
            $companyId = $order->company_id;
            $branchId = $order->branch_id;
            $siteId = $order->site_id;
            $b = $branchId === null ? 'n' : $branchId;
            $s = $siteId === null ? 'n' : $siteId;
            $weight = (float) $stream->nett_weight;
            $materialId = $stream->material_id;
            $wasteStreamId = $stream->material?->waste_stream_id;

            $materialKey = "{$companyId}|{$b}|{$s}|{$y}|{$m}|{$materialId}";
            if (! isset($materialBuckets[$materialKey])) {
                $materialBuckets[$materialKey] = ['weight' => 0, 'order_ids' => []];
            }
            $materialBuckets[$materialKey]['weight'] += $weight;
            $materialBuckets[$materialKey]['order_ids'][$order->id] = true;

            if ($wasteStreamId) {
                $streamKey = "{$companyId}|{$b}|{$s}|{$y}|{$m}|{$wasteStreamId}";
                if (! isset($streamBuckets[$streamKey])) {
                    $streamBuckets[$streamKey] = ['weight' => 0, 'order_ids' => []];
                }
                $streamBuckets[$streamKey]['weight'] += $weight;
                $streamBuckets[$streamKey]['order_ids'][$order->id] = true;
            }
        }

        $toDelete = ClientMonthlyMaterialSummary::query();
        if ($year !== null) {
            $toDelete->where('year', $year);
        }
        $toDelete->delete();

        $now = now();
        $rows = [];

        foreach ($materialBuckets as $key => $data) {
            [$companyId, $b, $s, $y, $m, $materialId] = explode('|', $key);
            $branchId = $b === 'n' ? null : (int) $b;
            $siteId = $s === 'n' ? null : (int) $s;
            $rows[] = [
                'company_id' => (int) $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'year' => (int) $y,
                'month' => (int) $m,
                'material_id' => (int) $materialId,
                'waste_stream_id' => null,
                'total_weight' => round($data['weight'], 3),
                'order_count' => count($data['order_ids']),
                'last_updated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach ($streamBuckets as $key => $data) {
            [$companyId, $b, $s, $y, $m, $wasteStreamId] = explode('|', $key);
            $branchId = $b === 'n' ? null : (int) $b;
            $siteId = $s === 'n' ? null : (int) $s;
            $rows[] = [
                'company_id' => (int) $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'year' => (int) $y,
                'month' => (int) $m,
                'material_id' => null,
                'waste_stream_id' => (int) $wasteStreamId,
                'total_weight' => round($data['weight'], 3),
                'order_count' => count($data['order_ids']),
                'last_updated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            ClientMonthlyMaterialSummary::insert($rows);
        }

        return count($rows);
    }

    /**
     * Move this order's waste stream weights in monthly summaries from the "from" month
     * (where they were previously counted, e.g. requested_collection_date) to the actual
     * collection month. Call this when an order is finalized or when actual_collection_date
     * is set/changed so the Grade Summary by month uses actual collection date.
     *
     * @param  Order  $order  Order with actual_collection_date set (e.g. after finalize)
     * @param  Carbon|null  $oldActualCollectionDate  Previous actual_collection_date, or null if first time setting
     */
    public function moveOrderSummariesToActualCollectionDate(Order $order, ?Carbon $oldActualCollectionDate = null): void
    {
        $toDate = $order->actual_collection_date;
        if (! $toDate) {
            return;
        }

        $toDate = Carbon::parse($toDate);
        $fromDate = $oldActualCollectionDate
            ? Carbon::parse($oldActualCollectionDate)
            : ($order->requested_collection_date ? Carbon::parse($order->requested_collection_date) : null);

        if (! $fromDate || ($fromDate->year === $toDate->year && $fromDate->month === $toDate->month)) {
            return;
        }

        $order->load('wasteStreams');
        foreach ($order->wasteStreams as $stream) {
            if (! $stream->material_id || $stream->nett_weight <= 0) {
                continue;
            }
            $weight = (float) $stream->nett_weight;
            $this->subtractWeight(
                $order->company_id,
                $order->branch_id,
                $order->site_id,
                $fromDate->year,
                $fromDate->month,
                $weight,
                $stream->material_id
            );
            $this->addWeight(
                $order->company_id,
                $order->branch_id,
                $order->site_id,
                $toDate->year,
                $toDate->month,
                $weight,
                $stream->material_id,
                false
            );
        }
    }

    /**
     * Update summaries when a weight is added or updated.
     *
     * @param  float|null  $oldWeight  The previous weight (for updates)
     * @param  int|null  $oldMaterialId  The previous material_id (for updates)
     * @param  bool  $isNewRecord  Whether this is a new record (for order_count tracking)
     */
    public function updateSummaries(OrderWasteStream $orderWasteStream, ?float $oldWeight = null, ?int $oldMaterialId = null, bool $isNewRecord = false): void
    {
        $order = $orderWasteStream->order;

        if (! $order) {
            return;
        }

        // Get the date from the order (use actual_collection_date if available, otherwise requested_collection_date)
        $date = $order->actual_collection_date ?? $order->requested_collection_date ?? Carbon::now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n'); // 1-12

        $companyId = $order->company_id;
        $branchId = $order->branch_id;
        $siteId = $order->site_id;

        // If updating, subtract the old weight first
        if ($oldWeight !== null && $oldMaterialId !== null) {
            $this->subtractWeight($companyId, $branchId, $siteId, $year, $month, $oldWeight, $oldMaterialId);
        }

        // Add the new weight
        if ($orderWasteStream->material_id && $orderWasteStream->nett_weight > 0) {
            $this->addWeight(
                $companyId,
                $branchId,
                $siteId,
                $year,
                $month,
                $orderWasteStream->nett_weight,
                $orderWasteStream->material_id,
                $isNewRecord
            );
        }
    }

    /**
     * Remove weight from summaries (when a weight is deleted).
     */
    public function removeWeight(OrderWasteStream $orderWasteStream): void
    {
        // Load order if not already loaded (needed because relationship might not be accessible after deletion)
        $order = $orderWasteStream->order ?? Order::find($orderWasteStream->order_id);

        if (! $order || ! $orderWasteStream->material_id) {
            return;
        }

        $date = $order->actual_collection_date ?? $order->requested_collection_date ?? Carbon::now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        $this->subtractWeight(
            $order->company_id,
            $order->branch_id,
            $order->site_id,
            $year,
            $month,
            $orderWasteStream->nett_weight,
            $orderWasteStream->material_id
        );
    }

    /**
     * @var array<int, int|null>
     */
    protected static array $materialWasteStreamCache = [];

    /**
     * Add weight to summaries (both material and category level).
     */
    protected function addWeight(int $companyId, ?int $branchId, ?int $siteId, int $year, int $month, float $weight, int $materialId, bool $isNewRecord = false): void
    {
        if (! array_key_exists($materialId, static::$materialWasteStreamCache)) {
            static::$materialWasteStreamCache[$materialId] = Material::find($materialId)?->waste_stream_id;
        }

        $wasteStreamId = static::$materialWasteStreamCache[$materialId];

        // Update or create material-level summary
        $this->updateSummary(
            $companyId,
            $branchId,
            $siteId,
            $year,
            $month,
            $weight,
            $materialId,
            null,
            $isNewRecord
        );

        // Update or create category-level (waste_stream) summary
        if ($wasteStreamId) {
            $this->updateSummary(
                $companyId,
                $branchId,
                $siteId,
                $year,
                $month,
                $weight,
                null,
                $wasteStreamId,
                $isNewRecord
            );
        }
    }

    /**
     * Subtract weight from summaries (both material and category level).
     */
    protected function subtractWeight(int $companyId, ?int $branchId, ?int $siteId, int $year, int $month, float $weight, int $materialId): void
    {
        if (! array_key_exists($materialId, static::$materialWasteStreamCache)) {
            static::$materialWasteStreamCache[$materialId] = Material::find($materialId)?->waste_stream_id;
        }

        $wasteStreamId = static::$materialWasteStreamCache[$materialId];

        // Subtract from material-level summary
        $this->updateSummary(
            $companyId,
            $branchId,
            $siteId,
            $year,
            $month,
            -$weight, // Negative to subtract
            $materialId,
            null
        );

        // Subtract from category-level summary
        if ($wasteStreamId) {
            $this->updateSummary(
                $companyId,
                $branchId,
                $siteId,
                $year,
                $month,
                -$weight, // Negative to subtract
                null,
                $wasteStreamId
            );
        }
    }

    /**
     * Update or create a summary record.
     */
    protected function updateSummary(int $companyId, ?int $branchId, ?int $siteId, int $year, int $month, float $weightDelta, ?int $materialId, ?int $wasteStreamId, bool $isNewRecord = false): void
    {
        $summary = ClientMonthlyMaterialSummary::firstOrNew([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'site_id' => $siteId,
            'year' => $year,
            'month' => $month,
            'material_id' => $materialId,
            'waste_stream_id' => $wasteStreamId,
        ]);

        // Initialize if new
        if (! $summary->exists) {
            $summary->total_weight = 0;
            $summary->order_count = 0;
        }

        // Update weight
        $summary->total_weight = max(0, $summary->total_weight + $weightDelta);

        // Update order count: increment when adding from a new record, decrement when removing weight
        if ($isNewRecord && $weightDelta > 0) {
            $summary->order_count++;
        } elseif ($weightDelta < 0) {
            $summary->order_count = max(0, $summary->order_count - 1);
        }

        $summary->last_updated_at = now();
        $summary->save();
    }
}
