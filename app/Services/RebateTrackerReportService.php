<?php

namespace App\Services;

use App\Models\OrderWasteStream;
use App\Support\OrderExportFormatting;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RebateTrackerReportService
{
    /**
     * Scoped, eager-loaded query of order waste streams shared by the rebate-detail and
     * waste-stream/grade breakdown builders, so the finalized/date/scope/eligibility rules
     * stay in one place.
     */
    private function scopedStreamsQuery(
        string $startDate,
        string $endDate,
        ?int $companyId,
        ?int $branchId,
        ?int $siteId,
        $user,
        array $companyIds,
    ): Builder {
        return OrderWasteStream::with([
            'order.site.branch.company',
            'order.branch.company',
            'order.company',
            'order.supportingDocuments',
            'order.serviceProvider',
            'material.grade',
            'material.wasteStream',
            'serviceProvider',
        ])
            ->whereHas('order', function ($q) use ($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds) {
                $q->where('status', 'finalized')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('actual_collection_date', [$startDate, $endDate])
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->whereNull('actual_collection_date')
                                    ->whereBetween('requested_collection_date', [$startDate, $endDate]);
                            });
                    });
                if ($companyId) {
                    $q->where('company_id', $companyId);
                }
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                if ($siteId) {
                    $q->where('site_id', $siteId);
                }
                if (! $user->isAdmin()) {
                    $q->where(function ($scoped) use ($companyIds) {
                        $scoped->whereIn('company_id', $companyIds)
                            ->orWhereHas('branch.company', function ($branchCompany) use ($companyIds) {
                                $branchCompany->whereIn('companies.id', $companyIds);
                            })
                            ->orWhereHas('site.branch.company', function ($siteCompany) use ($companyIds) {
                                $siteCompany->whereIn('companies.id', $companyIds);
                            });
                    });
                }
            })
            ->where(function ($q) {
                $q->whereNotNull('rebate_rate')->where('rebate_rate', '>', 0)
                    ->orWhereHas('material', function ($mq) {
                        $mq->where('rebate_offered', true);
                    })
                    ->orWhereHas('material.wasteStream', function ($wq) {
                        $wq->where('name', 'Organic Waste');
                    })
                    ->orWhereHas('material.grade', function ($gq) {
                        $gq->where('name', 'Organics Recovered');
                    });
            });
    }

    /**
     * Build rebate rows for finalized orders in the date range.
     *
     * Rows are aggregated by collection date (calendar day), company, branch, site, material grade,
     * and service provider. When multiple finalized orders contribute the same grade on the same day
     * for the same location and provider, weights and rebate totals are summed and distinct order
     * tracking numbers are listed together. Loads for the same date/location/grade but a different
     * provider are kept as separate rows so per-provider totals stay accurate.
     *
     * Streams are included when the line has a positive rebate rate, or the material offers rebates, or
     * the material is organic recovery (waste stream Organic Waste or grade Organics Recovered) so
     * organics appear even when material rebate_offered is false in seed data.
     *
     * The displayed date is the order's collection date: actual collection date when set, otherwise
     * requested collection date. Only finalized orders are included (not the moment a rebate field was
     * last saved on the order).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getRebateTrackerData(
        string $startDate,
        string $endDate,
        ?int $companyId,
        ?int $branchId,
        ?int $siteId,
        $user,
        array $companyIds,
    ): Collection {
        $query = $this->scopedStreamsQuery($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds);

        return $query->get()->map(function ($stream) use ($user) {
            $order = $stream->order;
            $collectionDate = $order->actual_collection_date ?? $order->requested_collection_date;
            $site = $order->site;
            $branch = $site?->branch ?? $order->branch;
            $company = $site?->branch?->company ?? $order->company ?? $branch?->company;
            $baseRate = (float) ($stream->rebate_rate ?? $stream->material?->rebate_rate ?? 0);
            $sharePercentage = $user->isAdmin() ? 100.0 : $this->resolveClientSharePercentage($stream);
            $effectiveRate = ($baseRate * $sharePercentage) / 100;
            $effectiveTotal = ((float) $stream->nett_weight) * $effectiveRate;
            $serviceProvider = $stream->serviceProvider ?? $order->serviceProvider;

            return [
                'id' => $stream->id,
                'order_id' => $stream->order_id,
                'tracking_number' => $order->tracking_number ?? '—',
                'date' => $collectionDate,
                'company_name' => $company?->name ?? '—',
                'branch_name' => $branch?->name ?? '—',
                'site_name' => $site?->name ?? '—',
                // Grouped/keyed by id (not name) below so two providers that happen to share a
                // name are never silently merged; the name is display-only.
                'service_provider_id' => $serviceProvider?->id,
                'service_provider_name' => $serviceProvider?->name ?? '—',
                'grade' => $stream->material->grade->name ?? '—',
                'weight' => $stream->nett_weight,
                'rate' => $effectiveRate,
                'total' => $effectiveTotal,
                'material_id' => $stream->material_id,
                'supporting_documents' => $order->supportingDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'original_name' => $doc->original_name,
                        'file_name' => $doc->file_name,
                    ];
                })->values()->toArray(),
            ];
        })->groupBy(function ($item) {
            return Carbon::parse($item['date'])->format('Y-m-d').'|'.($item['company_name'] ?? '').'|'.($item['branch_name'] ?? '').'|'.($item['site_name'] ?? '').'|'.$item['grade'].'|'.($item['service_provider_id'] ?? 'none');
        })->map(function ($group) {
            $trackingNumbers = $group->pluck('tracking_number')
                ->filter(fn ($t) => $t !== null && $t !== '' && $t !== '—')
                ->unique()
                ->values();

            $supportingDocuments = $group
                ->flatMap(fn ($item) => $item['supporting_documents'] ?? [])
                ->unique('id')
                ->values()
                ->all();

            return [
                'date' => $group->first()['date'],
                'company_name' => $group->first()['company_name'],
                'branch_name' => $group->first()['branch_name'],
                'site_name' => $group->first()['site_name'],
                'service_provider_id' => $group->first()['service_provider_id'],
                'service_provider_name' => $group->first()['service_provider_name'],
                'tracking_numbers' => $trackingNumbers->isEmpty() ? '—' : $trackingNumbers->implode(', '),
                'grade' => $group->first()['grade'],
                'weight' => $group->sum('weight'),
                'rate' => $group->first()['rate'],
                'total' => $group->sum('total'),
                'supporting_documents' => $supportingDocuments,
            ];
        })->values()->sortBy(['company_name', 'branch_name', 'site_name', 'date'])->values();
    }

    /**
     * Build the waste-stream/grade breakdown for the PDF: one row per order waste-stream line
     * (not aggregated across orders, since slip number and tracking number are order-specific),
     * grouped under a "{Waste Stream} - {Grade}" heading with a per-group weight subtotal.
     *
     * No pricing fields are included — this section only surfaces collection identifiers
     * (tracking number, slip number, order quantity) and weight.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getWasteStreamGradeBreakdown(
        string $startDate,
        string $endDate,
        ?int $companyId,
        ?int $branchId,
        ?int $siteId,
        $user,
        array $companyIds,
    ): Collection {
        $query = $this->scopedStreamsQuery($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds);

        return $query->get()->map(function ($stream) {
            $order = $stream->order;
            $collectionDate = $order->actual_collection_date ?? $order->requested_collection_date;

            return [
                'waste_stream' => $stream->material?->wasteStream?->name ?? 'Uncategorized',
                'grade' => $stream->material?->grade?->name ?? 'Ungraded',
                'date' => $collectionDate,
                'tracking_number' => $order->tracking_number ?? '—',
                'slip_number' => $order->slip_number ?: '—',
                'quantity' => OrderExportFormatting::collectionQuantities($order) ?: '—',
                'weight' => (float) $stream->nett_weight,
            ];
        })->groupBy(function ($item) {
            return $item['waste_stream'].'|'.$item['grade'];
        })->map(function ($rows) {
            $first = $rows->first();

            return [
                'waste_stream' => $first['waste_stream'],
                'grade' => $first['grade'],
                'heading' => $first['waste_stream'].' - '.$first['grade'],
                'rows' => $rows->sortBy('date')->values()->all(),
                'subtotal_weight' => $rows->sum('weight'),
            ];
        })->values()->sortBy(function ($group) {
            return strtolower($group['waste_stream']).'|'.strtolower($group['grade']);
        })->values();
    }

    /**
     * Summarise already-built rebate rows by service provider, for the per-provider
     * subtotal shown on the Rebate Tracker page and PDF export.
     *
     * @param  Collection<int, array<string, mixed>>  $rebateData
     * @return Collection<int, array<string, mixed>>
     */
    public function providerBreakdown(Collection $rebateData): Collection
    {
        return $rebateData
            ->groupBy(fn (array $row) => $row['service_provider_id'] ?? 'none')
            ->map(fn (Collection $rows) => [
                'provider_name' => $rows->first()['service_provider_name'],
                'weight' => $rows->sum('weight'),
                'total' => $rows->sum('total'),
            ])
            ->values()
            ->sortBy('provider_name')
            ->values();
    }

    private function resolveClientSharePercentage(OrderWasteStream $stream): float
    {
        $order = $stream->order;
        $companyRebatePercentage = null;

        if ($order->status === 'finalized' && $order->company_rebate_percentage !== null) {
            $companyRebatePercentage = $order->company_rebate_percentage;
        } else {
            $site = $order->site;
            $branch = $site?->branch ?? $order->branch;
            $company = $site?->branch?->company ?? $order->company ?? $branch?->company;
            $companyRebatePercentage = $company?->rebate_percentage;
        }

        if ($companyRebatePercentage !== null && $companyRebatePercentage !== '' && is_numeric($companyRebatePercentage)) {
            return (float) $companyRebatePercentage;
        }

        if ($stream->material?->client_rebate_share !== null && is_numeric($stream->material->client_rebate_share)) {
            return (float) $stream->material->client_rebate_share;
        }

        return 100.0;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rebateData
     * @param  array{start_date: string, end_date: string, company_id: ?int, branch_id: ?int, site_id: ?int}  $filters
     */
    public function renderRebateTrackerPdfBinary(Collection $rebateData, array $filters, float $totalRebate, float $totalWeight): string
    {
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('reports.rebate-tracker-pdf', [
            'rebateData' => $rebateData,
            'filters' => $filters,
            'totalRebate' => $totalRebate,
            'totalWeight' => $totalWeight,
            'providerBreakdown' => $this->providerBreakdown($rebateData),
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $wasteStreamBreakdown
     * @param  array{start_date: string, end_date: string, company_id: ?int, branch_id: ?int, site_id: ?int}  $filters
     */
    public function renderWasteStreamCollectionPdfBinary(Collection $wasteStreamBreakdown, array $filters, float $totalWeight): string
    {
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('reports.waste-stream-collection-pdf', [
            'wasteStreamBreakdown' => $wasteStreamBreakdown,
            'filters' => $filters,
            'totalWeight' => $totalWeight,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
