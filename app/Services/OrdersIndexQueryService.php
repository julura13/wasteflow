<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class OrdersIndexQueryService
{
    /**
     * Build the base query for orders index/export with search, status and order_type filters.
     *
     * @param  array<int, string>  $orderTypes  ['waste'], ['recycling'], or ['waste','recycling']/[] for all
     * @param  array<int, int>  $serviceProviderIds  Empty = no filter; non-empty = restrict to these providers
     */
    public function buildForUser(User $user, ?string $search, ?string $status, array $orderTypes, ?string $requestedCollectionDateFrom = null, ?string $requestedCollectionDateTo = null, array $serviceProviderIds = []): Builder
    {
        $query = Order::with(['site.branch.company', 'company', 'branch', 'creator', 'serviceProvider']);

        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $query->when($search, function ($q, $search) {
            $term = '%'.trim($search).'%';
            $q->where(function ($q) use ($term) {
                $q->where('orders.tracking_number', 'like', $term)
                    ->orWhere('orders.slip_number', 'like', $term)
                    ->orWhereHas('site', fn ($siteQ) => $siteQ->where('sites.name', 'like', $term))
                    ->orWhereHas('branch', fn ($branchQ) => $branchQ->where('branches.name', 'like', $term))
                    ->orWhereHas('company', fn ($companyQ) => $companyQ->where('companies.name', 'like', $term))
                    ->orWhereHas('serviceProvider', fn ($spQ) => $spQ->where('service_providers.name', 'like', $term));
            });
        });

        $query->when($status, fn ($q, $status) => $q->where('orders.status', $status));

        if (count($orderTypes) === 1) {
            $query->where('orders.order_type', $orderTypes[0]);
        }

        if ($serviceProviderIds !== []) {
            $query->whereIn('orders.service_provider_id', $serviceProviderIds);
        }

        $query->when($requestedCollectionDateFrom, function ($q) use ($requestedCollectionDateFrom) {
            $q->whereDate('orders.requested_collection_date', '>=', $requestedCollectionDateFrom);
        });
        $query->when($requestedCollectionDateTo, function ($q) use ($requestedCollectionDateTo) {
            $q->whereDate('orders.requested_collection_date', '<=', $requestedCollectionDateTo);
        });

        return $query;
    }

    /**
     * Sort exports by service provider (A–Z), then by newest order first. Unassigned providers sort last.
     */
    public function applyExportOrdering(Builder $query): Builder
    {
        return $query
            ->leftJoin('service_providers', 'orders.service_provider_id', '=', 'service_providers.id')
            ->select('orders.*')
            ->orderByRaw('service_providers.name IS NULL ASC')
            ->orderBy('service_providers.name')
            ->orderByDesc('orders.created_at');
    }

    /**
     * @return array{0: ?string, 1: ?string} Dates as Y-m-d or null when missing/invalid
     */
    public function parseRequestedCollectionDateRangeInput(mixed $from, mixed $to): array
    {
        $fromParsed = $this->parseDateInputYmd($from);
        $toParsed = $this->parseDateInputYmd($to);

        if ($fromParsed && $toParsed && $fromParsed->gt($toParsed)) {
            [$fromParsed, $toParsed] = [$toParsed, $fromParsed];
        }

        return [
            $fromParsed?->format('Y-m-d'),
            $toParsed?->format('Y-m-d'),
        ];
    }

    private function parseDateInputYmd(mixed $value): ?Carbon
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Try formats in order of specificity
        $formats = [
            'Y-m-d',  // ISO: 2026-06-01 (standard browser value)
            'Y/m/d',  // ZA/Asian display: 2026/06/01
            'm/d/Y',  // US display: 06/01/2026
            'd/m/Y',  // UK/ZA manual entry: 01/06/2026
            'd-m-Y',  // DD-MM-YYYY
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                // try next format
            }
        }

        return null;
    }
}
