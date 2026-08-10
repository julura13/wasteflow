<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('downloads a certificate PDF for a company and period', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::query()->create(['name' => 'Certificate Co', 'is_active' => true]);

    $response = $this->actingAs($user)->get(route('reports.waste-management-certificate.download', [
        'company_id' => $company->id,
        'month' => 4,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('WasteFlow_Certificate_certificate-co_2026-04.pdf');
});

it('validates required company_id, month, and year', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $this->actingAs($user)->get(route('reports.waste-management-certificate.download'))
        ->assertInvalid(['company_id', 'month', 'year']);
});

it('denies access to users without the view-reports permission', function () {
    $user = User::factory()->create();

    $company = Company::query()->create(['name' => 'No Access Co', 'is_active' => true]);

    $this->actingAs($user)->get(route('reports.waste-management-certificate.download', [
        'company_id' => $company->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertForbidden();
});

it('cannot download a certificate for a company outside a scoped client user\'s access', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $ownCompany = Company::query()->create(['name' => 'Own Co', 'is_active' => true]);
    $otherCompany = Company::query()->create(['name' => 'Other Co', 'is_active' => true]);
    $client->companies()->attach($ownCompany->id);

    $this->actingAs($client)->get(route('reports.waste-management-certificate.download', [
        'company_id' => $otherCompany->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertNotFound();
});
