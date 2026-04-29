<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\Site;
use App\Models\User;

it('reports duplicate company names with ids and related counts', function () {
    $user = User::factory()->create();

    $firstAcme = Company::query()->create(['name' => 'Acme']);
    $secondAcme = Company::query()->create(['name' => ' acme ']);
    Company::query()->create(['name' => 'Unique Co']);

    $firstAcmeBranch = Branch::query()->create([
        'company_id' => $firstAcme->id,
        'name' => 'Acme Main',
    ]);
    $secondAcmeBranch = Branch::query()->create([
        'company_id' => $secondAcme->id,
        'name' => 'Acme Secondary',
    ]);

    Site::query()->create([
        'branch_id' => $firstAcmeBranch->id,
        'name' => 'Acme Site 1',
    ]);
    Site::query()->create([
        'branch_id' => $firstAcmeBranch->id,
        'name' => 'Acme Site 2',
    ]);
    Site::query()->create([
        'branch_id' => $secondAcmeBranch->id,
        'name' => 'Acme Site 3',
    ]);

    Order::query()->create([
        'company_id' => $firstAcme->id,
        'branch_id' => $firstAcmeBranch->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => now()->toDateString(),
    ]);
    Order::query()->create([
        'company_id' => $firstAcme->id,
        'branch_id' => $firstAcmeBranch->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => now()->toDateString(),
    ]);
    Order::query()->create([
        'company_id' => $secondAcme->id,
        'branch_id' => $secondAcmeBranch->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => now()->toDateString(),
    ]);

    $this->artisan('companies:report-duplicates')
        ->expectsOutputToContain('Duplicate company names found:')
        ->expectsOutputToContain('Acme (2 records)')
        ->expectsTable(
            ['Company ID', 'Branches', 'Sites', 'Orders'],
            [
                [$firstAcme->id, 1, 2, 2],
                [$secondAcme->id, 1, 1, 1],
            ]
        )
        ->assertSuccessful();
});

it('shows a clear message when no duplicates exist', function () {
    Company::query()->create(['name' => 'Only One']);

    $this->artisan('companies:report-duplicates')
        ->expectsOutputToContain('No duplicate company names were found.')
        ->assertSuccessful();
});
