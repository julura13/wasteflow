<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportDuplicateCompanies extends Command
{
    protected $signature = 'companies:report-duplicates
                            {--max-order-matches=20 : Maximum potential duplicate order groups shown per duplicate company name}';

    protected $description = 'Show duplicated company names with company IDs, related counts, and potential order overlaps';

    public function handle(): int
    {
        $duplicateGroups = Company::query()
            ->selectRaw('LOWER(TRIM(name)) as normalized_name')
            ->selectRaw('COUNT(*) as duplicate_count')
            ->whereNotNull('name')
            ->whereRaw("TRIM(name) != ''")
            ->groupBy(DB::raw('LOWER(TRIM(name))'))
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('normalized_name')
            ->get();

        if ($duplicateGroups->isEmpty()) {
            $this->info('No duplicate company names were found.');

            return self::SUCCESS;
        }

        $this->warn('Duplicate company names found:');

        foreach ($duplicateGroups as $group) {
            $companies = Company::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [$group->normalized_name])
                ->withCount([
                    'branches',
                    'collectionPoints as sites_count',
                    'orders',
                ])
                ->orderBy('id')
                ->get();

            $displayName = $companies->first()?->name ?? $group->normalized_name;
            $companyIds = $companies->pluck('id');
            $maxOrderMatches = max((int) $this->option('max-order-matches'), 1);

            $this->newLine();
            $this->line(sprintf('%s (%d records)', $displayName, $group->duplicate_count));
            $this->table(
                ['Company ID', 'Branches', 'Sites', 'Orders'],
                $companies->map(fn (Company $company): array => [
                    $company->id,
                    $company->branches_count,
                    $company->sites_count,
                    $company->orders_count,
                ])->all()
            );

            $overlapSummary = $this->buildOrderOverlapSummary($companyIds, $maxOrderMatches);

            $this->line('Potential duplicate orders across company IDs:');
            $this->table(
                ['Method', 'Matched Order Groups', 'Orders Involved'],
                [
                    ['Shared slip number', $overlapSummary['shared_slip_groups'], $overlapSummary['shared_slip_orders']],
                    ['Shared fingerprint', $overlapSummary['shared_fingerprint_groups'], $overlapSummary['shared_fingerprint_orders']],
                ]
            );

            if ($overlapSummary['matches']->isNotEmpty()) {
                $this->table(
                    ['Match Method', 'Company IDs', 'Order IDs', 'Match Key'],
                    $overlapSummary['matches']->all()
                );
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return array{
     *     shared_slip_groups: int,
     *     shared_slip_orders: int,
     *     shared_fingerprint_groups: int,
     *     shared_fingerprint_orders: int,
     *     matches: Collection<int, array{string, string, string, string}>
     * }
     */
    private function buildOrderOverlapSummary(Collection $companyIds, int $maxOrderMatches): array
    {
        $orders = Order::query()
            ->whereIn('company_id', $companyIds->all())
            ->select([
                'id',
                'company_id',
                'tracking_number',
                'slip_number',
                'site_id',
                'order_type',
                'requested_collection_date',
                'actual_collection_date',
                'actual_quantity',
                'estimated_quantity',
            ])
            ->get();

        $sharedSlip = $orders
            ->filter(fn (Order $order): bool => filled($order->slip_number))
            ->groupBy(fn (Order $order): string => mb_strtolower(trim((string) $order->slip_number)))
            ->filter(fn (Collection $group): bool => $this->containsMultipleCompanies($group))
            ->values();

        $sharedFingerprint = $orders
            ->groupBy(fn (Order $order): string => $this->buildOrderFingerprint($order))
            ->filter(fn (Collection $group): bool => $this->containsMultipleCompanies($group))
            ->values();

        $matches = collect()
            ->concat($this->formatMatchRows($sharedSlip, 'Shared slip number'))
            ->concat($this->formatMatchRows($sharedFingerprint, 'Shared fingerprint'))
            ->take($maxOrderMatches)
            ->values();

        return [
            'shared_slip_groups' => $sharedSlip->count(),
            'shared_slip_orders' => $sharedSlip->sum(fn (Collection $group): int => $group->count()),
            'shared_fingerprint_groups' => $sharedFingerprint->count(),
            'shared_fingerprint_orders' => $sharedFingerprint->sum(fn (Collection $group): int => $group->count()),
            'matches' => $matches,
        ];
    }

    private function containsMultipleCompanies(Collection $orders): bool
    {
        return $orders->pluck('company_id')->unique()->count() > 1;
    }

    private function buildOrderFingerprint(Order $order): string
    {
        return implode('|', [
            $order->order_type ?? '',
            $order->site_id ?? '',
            $order->requested_collection_date ?? '',
            $order->actual_collection_date ?? '',
            $order->actual_quantity ?? '',
            $order->estimated_quantity ?? '',
        ]);
    }

    /**
     * @param  Collection<int, Collection<int, Order>>  $groups
     * @return Collection<int, array{string, string, string, string}>
     */
    private function formatMatchRows(Collection $groups, string $method): Collection
    {
        return $groups->map(function (Collection $orders) use ($method): array {
            $firstOrder = $orders->first();
            $matchKey = $method === 'Shared slip number'
                ? (string) $firstOrder?->slip_number
                : $this->buildOrderFingerprint($firstOrder);

            return [
                $method,
                $orders->pluck('company_id')->unique()->sort()->join(', '),
                $orders->pluck('id')->sort()->join(', '),
                $matchKey,
            ];
        })->values();
    }
}
