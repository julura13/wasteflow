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

it('includes the resolved Resource Recovery Rating tier name and colour in the certificate template', function () {
    $html = view('reports.client-monthly-certificate-pdf', [
        'companyNameUpper' => 'ACME CO',
        'percentageDisplay' => '82.0',
        'monthYearUpper' => 'JULY 2026',
        'completeDateUpper' => '31 JULY 2026',
        'tierNameUpper' => 'GOLD',
        'tierColor' => '#D4AF37',
    ])->render();

    expect($html)->toContain('GOLD')
        ->and($html)->toContain('RESOURCE RECOVERY RATING')
        ->and($html)->toContain('#D4AF37')
        ->and($html)->toContain('82.0');
});

it('falls back to the original certificate wording when no tier is resolved', function () {
    $html = view('reports.client-monthly-certificate-pdf', [
        'companyNameUpper' => 'ACME CO',
        'percentageDisplay' => '82.0',
        'monthYearUpper' => 'JULY 2026',
        'completeDateUpper' => '31 JULY 2026',
        'tierNameUpper' => null,
        'tierColor' => null,
    ])->render();

    expect($html)->not->toContain('RESOURCE RECOVERY RATING')
        ->and($html)->toContain('DEMONSTRATING THE');
});

it('resolves a Resource Recovery Rating tier for the certificate based on the diversion percentage achieved', function () {
    $this->seed(\Database\Seeders\RecoveryRatingTierSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::query()->create(['name' => 'Certificate Co', 'is_active' => true]);

    // No waste data recorded for the period => 0% diverted => "Improvement Required" tier.
    $response = $this->actingAs($user)->get(route('reports.waste-management-certificate.download', [
        'company_id' => $company->id,
        'month' => 4,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});
