<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportDuplicateCompanies extends Command
{
    protected $signature = 'companies:report-duplicates';

    protected $description = 'Show duplicated company names with company IDs and related record counts';

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
        }

        return self::SUCCESS;
    }
}
