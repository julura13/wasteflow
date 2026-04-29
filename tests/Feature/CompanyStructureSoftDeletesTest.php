<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;

it('soft deletes companies, branches, and sites', function () {
    $company = Company::query()->create([
        'name' => 'Soft Delete Co',
    ]);

    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'name' => 'Soft Delete Branch',
    ]);

    $site = Site::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Soft Delete Site',
    ]);

    $company->delete();
    $branch->delete();
    $site->delete();

    $this->assertSoftDeleted('companies', ['id' => $company->id]);
    $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    $this->assertSoftDeleted('sites', ['id' => $site->id]);
});
