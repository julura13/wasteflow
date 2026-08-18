<?php

use App\Http\Controllers\ReportController;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RecoveryRatingTierSeeder;
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
        'companyNameFontSize' => 30.0,
        'percentageDisplay' => '82.0',
        'monthYearUpper' => 'JULY 2026',
        'completeDateUpper' => '31 JULY 2026',
        'dateFontSize' => 12.5,
        'tierNameUpper' => 'GOLD',
        'tierColor' => '#D4AF37',
        'summaryFontSize' => 13.5,
        'summaryLineHeight' => 1.5,
    ])->render();

    expect($html)->toContain('GOLD')
        ->and($html)->toContain('RESOURCE RECOVERY RATING')
        ->and($html)->toContain('#D4AF37')
        ->and($html)->toContain('82.0');
});

it('falls back to the original certificate wording when no tier is resolved', function () {
    $html = view('reports.client-monthly-certificate-pdf', [
        'companyNameUpper' => 'ACME CO',
        'companyNameFontSize' => 30.0,
        'percentageDisplay' => '82.0',
        'monthYearUpper' => 'JULY 2026',
        'completeDateUpper' => '31 JULY 2026',
        'dateFontSize' => 12.5,
        'tierNameUpper' => null,
        'tierColor' => null,
        'summaryFontSize' => 13.5,
        'summaryLineHeight' => 1.5,
    ])->render();

    expect($html)->not->toContain('RESOURCE RECOVERY RATING')
        ->and($html)->toContain('DEMONSTRATING THE');
});

it('shrinks the certificate company-name font size for long company names, measured against the real font metrics', function () {
    $controller = new ReflectionClass(ReportController::class);
    $method = $controller->getMethod('certificateCompanyNameFontSize');
    $method->setAccessible(true);
    $instance = app(ReportController::class);

    expect($method->invoke($instance, 'ACME CO'))->toBe(30.0)
        ->and($method->invoke($instance, 'A MODERATELY LONG COMPANY NAME LTD'))->toBe(16.0)
        ->and($method->invoke($instance, 'DCP CAPE TOWN DEPOT(DURBAN CONTAINER PARK)'))->toBe(13.0);
});

it('fits "DEVONBOSCH ESTATE" and "VAN RIEBEECKSTRAND PRIMARY SCHOOL" on one line at a legible size, both sent to us as broken screenshots', function () {
    // Regression test: two customers sent screenshots of their certificates broken in slightly
    // different ways. "DEVONBOSCH ESTATE" (17 chars) wrapped its second line onto the summary
    // text below it - the old character-count heuristic classified it into the same "<=20 chars
    // => 30pt" bucket as "WESKUS MALL" (11 chars), but it measures wider at 30pt than the field
    // allowed. "VAN RIEBEECKSTRAND PRIMARY SCHOOL" (34 chars) also wrapped for the same reason.
    // Fixed by both measuring the real font metrics instead of guessing from length, and by
    // widening the field itself - the certificate background has much more clear space to the
    // right than the original field width assumed.
    $controller = new ReflectionClass(ReportController::class);
    $method = $controller->getMethod('certificateCompanyNameFontSize');
    $method->setAccessible(true);
    $instance = app(ReportController::class);

    expect($method->invoke($instance, 'DEVONBOSCH ESTATE'))->toBe(30.0)
        ->and($method->invoke($instance, 'VAN RIEBEECKSTRAND PRIMARY SCHOOL'))->toBeGreaterThanOrEqual(16.0);
});

it('shrinks the certificate summary font size as the assembled sentence grows', function () {
    $controller = new ReflectionClass(ReportController::class);
    $method = $controller->getMethod('certificateSummaryFontSize');
    $method->setAccessible(true);
    $instance = app(ReportController::class);

    expect($method->invoke($instance, str_repeat('A', 100)))->toBe(['size' => 13.5, 'lineHeight' => 1.5])
        ->and($method->invoke($instance, str_repeat('A', 170)))->toBe(['size' => 11.5, 'lineHeight' => 1.35])
        ->and($method->invoke($instance, str_repeat('A', 200)))->toBe(['size' => 9.5, 'lineHeight' => 1.25])
        ->and($method->invoke($instance, str_repeat('A', 250)))->toBe(['size' => 8.0, 'lineHeight' => 1.2]);
});

it('shrinks the certificate date font size for longer month names, so the date never wraps onto the "Date" label below it', function () {
    $controller = new ReflectionClass(ReportController::class);
    $method = $controller->getMethod('certificateDateFontSize');
    $method->setAccessible(true);
    $instance = app(ReportController::class);

    expect($method->invoke($instance, '31 MAY 2026'))->toBe(12.5)
        ->and($method->invoke($instance, '30 JUNE 2026'))->toBe(12.5)
        ->and($method->invoke($instance, '31 AUGUST 2026'))->toBeLessThan(12.5)
        ->and($method->invoke($instance, '30 SEPTEMBER 2026'))->toBeLessThan($method->invoke($instance, '31 AUGUST 2026'));
});

it('positions the company-name field with a gap below the "WASTEFLOW CONGRATULATES" label baked into the certificate background', function () {
    // Regression test: the field used to start at the same vertical position as the label
    // above it (top: 113.5mm), so the company name sat flush against "WASTEFLOW
    // CONGRATULATES" with no visible gap. It now starts further down.
    $path = resource_path('views/reports/client-monthly-certificate-pdf.blade.php');
    $contents = file_get_contents($path);

    expect($contents)->toContain('.company-name')
        ->and($contents)->not->toContain('top: 113.5mm');
});

it('falls back to the maximum font size when the font file used for measurement is missing, so the certificate still renders', function () {
    $controller = new ReflectionClass(ReportController::class);
    $method = $controller->getMethod('fontSizeToFitOneLine');
    $method->setAccessible(true);
    $instance = app(ReportController::class);

    expect($method->invoke($instance, 'ANY TEXT', '/nonexistent/font.ttf', 100.0, 30.0, 10.0))->toBe(30.0);
});

it('resolves a Resource Recovery Rating tier for the certificate based on the diversion percentage achieved', function () {
    $this->seed(RecoveryRatingTierSeeder::class);

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
