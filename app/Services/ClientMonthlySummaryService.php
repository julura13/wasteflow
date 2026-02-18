<?php

namespace App\Services;

use App\Models\ClientMonthlyMaterialSummary;
use App\Models\OrderWasteStream;
use App\Models\Order;
use Carbon\Carbon;

class ClientMonthlySummaryService
{
    /**
     * Move this order's waste stream weights in monthly summaries from the "from" month
     * (where they were previously counted, e.g. requested_collection_date) to the actual
     * collection month. Call this when an order is finalized or when actual_collection_date
     * is set/changed so the Grade Summary by month uses actual collection date.
     *
     * @param Order $order Order with actual_collection_date set (e.g. after finalize)
     * @param \Carbon\Carbon|null $oldActualCollectionDate Previous actual_collection_date, or null if first time setting
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
     * @param OrderWasteStream $orderWasteStream
     * @param float|null $oldWeight The previous weight (for updates)
     * @param int|null $oldMaterialId The previous material_id (for updates)
     * @param bool $isNewRecord Whether this is a new record (for order_count tracking)
     */
    public function updateSummaries(OrderWasteStream $orderWasteStream, ?float $oldWeight = null, ?int $oldMaterialId = null, bool $isNewRecord = false): void
    {
        $order = $orderWasteStream->order;
        
        if (!$order) {
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
     * 
     * @param OrderWasteStream $orderWasteStream
     */
    public function removeWeight(OrderWasteStream $orderWasteStream): void
    {
        // Load order if not already loaded (needed because relationship might not be accessible after deletion)
        $order = $orderWasteStream->order ?? Order::find($orderWasteStream->order_id);
        
        if (!$order || !$orderWasteStream->material_id) {
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
     * Add weight to summaries (both material and category level).
     */
    protected function addWeight(int $companyId, ?int $branchId, ?int $siteId, int $year, int $month, float $weight, int $materialId, bool $isNewRecord = false): void
    {
        // Load material to get waste_stream_id
        $material = \App\Models\Material::find($materialId);
        if (!$material) {
            return;
        }

        $wasteStreamId = $material->waste_stream_id;

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
        // Load material to get waste_stream_id
        $material = \App\Models\Material::find($materialId);
        if (!$material) {
            return;
        }

        $wasteStreamId = $material->waste_stream_id;

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
        if (!$summary->exists) {
            $summary->total_weight = 0;
            $summary->order_count = 0;
        }

        // Update weight
        $summary->total_weight = max(0, $summary->total_weight + $weightDelta);
        
        // Update order count only for new records (not updates)
        if ($isNewRecord && $weightDelta > 0) {
            $summary->order_count++;
        }

        $summary->last_updated_at = now();
        $summary->save();
    }
}
