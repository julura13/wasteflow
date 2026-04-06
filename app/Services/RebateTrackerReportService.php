<?php

namespace App\Services;

use App\Models\OrderWasteStream;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;

class RebateTrackerReportService
{
    /**
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
        $query = OrderWasteStream::with([
            'order.site.branch.company',
            'order.branch.company',
            'order.company',
            'order.supportingDocuments',
            'material.grade',
            'material.wasteStream',
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
                    $q->whereHas('site.branch.company', function ($q) use ($companyIds) {
                        $q->whereIn('companies.id', $companyIds);
                    });
                }
            })
            ->where(function ($q) {
                $q->whereNotNull('rebate_rate')->where('rebate_rate', '>', 0)
                    ->orWhereHas('material', function ($mq) {
                        $mq->where('rebate_offered', true);
                    });
            });

        return $query->get()->map(function ($stream) {
            $order = $stream->order;
            $collectionDate = $order->actual_collection_date ?? $order->requested_collection_date;
            $site = $order->site;
            $branch = $site?->branch ?? $order->branch;
            $company = $site?->branch?->company ?? $order->company ?? $branch?->company;

            return [
                'id' => $stream->id,
                'order_id' => $stream->order_id,
                'tracking_number' => $order->tracking_number ?? '—',
                'date' => $collectionDate,
                'company_name' => $company?->name ?? '—',
                'branch_name' => $branch?->name ?? '—',
                'site_name' => $site?->name ?? '—',
                'grade' => $stream->material->grade->name ?? '—',
                'weight' => $stream->nett_weight,
                'rate' => $stream->rebate_rate ?? $stream->material?->rebate_rate ?? 0,
                'total' => $stream->client_rebate_amount,
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
            return Carbon::parse($item['date'])->format('Y-m-d').'|'.($item['company_name'] ?? '').'|'.($item['branch_name'] ?? '').'|'.($item['site_name'] ?? '').'|'.$item['grade'];
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
                'tracking_numbers' => $trackingNumbers->isEmpty() ? '—' : $trackingNumbers->implode(', '),
                'grade' => $group->first()['grade'],
                'weight' => $group->sum('weight'),
                'rate' => $group->first()['rate'],
                'total' => $group->sum('total'),
                'supporting_documents' => $supportingDocuments,
            ];
        })->values()->sortBy(['company_name', 'branch_name', 'site_name', 'date']);
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
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
