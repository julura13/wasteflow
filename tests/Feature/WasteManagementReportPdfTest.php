<?php

use App\Jobs\GenerateWasteManagementPdfJob;
use App\Models\Company;
use App\Models\User;
use App\Models\WasteManagementReportExport;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('produces a valid PDF after the export job runs', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::create(['name' => 'Acme Recycling Ltd', 'is_active' => true]);

    $export = WasteManagementReportExport::query()->create([
        'uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        'user_id' => $user->id,
        'status' => WasteManagementReportExport::STATUS_PENDING,
        'disk' => 'local',
        'filename' => 'placeholder.pdf',
        'filters' => [
            'company_id' => $company->id,
            'branch_id' => null,
            'site_id' => null,
            'month' => 4,
            'year' => 2026,
        ],
        'expires_at' => now()->addDay(),
    ]);

    GenerateWasteManagementPdfJob::dispatchSync($export->id);

    $export->refresh();
    expect($export->status)->toBe(WasteManagementReportExport::STATUS_COMPLETED)
        ->and($export->path)->not->toBeNull();

    $stored = \Illuminate\Support\Facades\Storage::disk('local')->get($export->path);
    expect(substr($stored, 0, 4))->toBe('%PDF');

    $this->actingAs($user)->get(route('reports.waste-management-pdf.download', ['uuid' => $export->uuid]))
        ->assertOk();
});

it('shows the waste management report page with query filters preserved', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::create(['name' => 'Acme Recycling Ltd', 'is_active' => true]);

    $this->actingAs($user)->get(route('reports.waste-management', [
        'company_id' => $company->id,
        'month' => 4,
        'year' => 2026,
    ]))
        ->assertSuccessful();
});

it('forbids users without view-reports from downloading', function () {
    $user = User::factory()->create();

    $export = WasteManagementReportExport::query()->create([
        'uuid' => 'bbbbbbbb-cccc-dddd-eeee-ffffffffffff',
        'user_id' => $user->id,
        'status' => WasteManagementReportExport::STATUS_COMPLETED,
        'disk' => 'local',
        'path' => 'waste-management-reports/test.pdf',
        'filename' => 'WasteFlow_Resource_Intelligence_Report.pdf',
        'filters' => [
            'company_id' => 1,
            'branch_id' => null,
            'site_id' => null,
            'month' => 1,
            'year' => 2026,
        ],
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('reports.waste-management-pdf.download', ['uuid' => $export->uuid]))
        ->assertForbidden();
});

it('queues a waste management PDF export when requested', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::create(['name' => 'Queue Co', 'is_active' => true]);

    $response = $this->actingAs($user)->post(route('reports.waste-management-pdf.request'), [
        'company_id' => $company->id,
        'branch_id' => '',
        'site_id' => '',
        'month' => 4,
        'year' => 2026,
    ]);

    $response->assertRedirect();

    $export = WasteManagementReportExport::query()->first();
    expect($export)->not->toBeNull()
        ->and($export->user_id)->toBe($user->id)
        ->and($export->status)->toBe(WasteManagementReportExport::STATUS_PENDING)
        ->and($export->filters['company_id'])->toBe($company->id);

    Queue::assertPushed(GenerateWasteManagementPdfJob::class, function (GenerateWasteManagementPdfJob $job) use ($export) {
        return $job->wasteManagementReportExportId === $export->id;
    });
});

it('returns export status as json for the owning user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $export = WasteManagementReportExport::query()->create([
        'uuid' => 'cccccccc-dddd-eeee-ffff-000000000001',
        'user_id' => $user->id,
        'status' => WasteManagementReportExport::STATUS_COMPLETED,
        'disk' => 'local',
        'path' => 'waste-management-reports/test.pdf',
        'filename' => 'WasteFlow_Resource_Intelligence_Report_2026-02.pdf',
        'filters' => [
            'company_id' => 1,
            'branch_id' => null,
            'site_id' => null,
            'month' => 2,
            'year' => 2026,
        ],
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($user)->getJson(route('reports.waste-management-pdf.status', $export->uuid));

    $response->assertOk()
        ->assertJsonPath('status', WasteManagementReportExport::STATUS_COMPLETED)
        ->assertJsonStructure(['download_url', 'error_message']);
});
