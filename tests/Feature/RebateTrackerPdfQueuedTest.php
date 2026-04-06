<?php

use App\Jobs\GenerateRebateTrackerPdfJob;
use App\Models\Branch;
use App\Models\Company;
use App\Models\RebateReportExport;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('queues a rebate PDF export and creates a pending export record', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'PDF Queue Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);

    $response = $this->actingAs($user)->post(route('reports.rebate-tracker-pdf.request'), [
        'start_date' => '2026-03-01',
        'end_date' => '2026-04-30',
        'company_id' => $company->id,
        'branch_id' => '',
        'site_id' => '',
    ]);

    $response->assertRedirect();

    $export = RebateReportExport::query()->first();
    expect($export)->not->toBeNull()
        ->and($export->user_id)->toBe($user->id)
        ->and($export->status)->toBe(RebateReportExport::STATUS_PENDING)
        ->and($export->filters['start_date'])->toBe('2026-03-01')
        ->and($export->filters['end_date'])->toBe('2026-04-30');

    Queue::assertPushed(GenerateRebateTrackerPdfJob::class, function (GenerateRebateTrackerPdfJob $job) use ($export) {
        return $job->rebateReportExportId === $export->id;
    });
});

it('returns export status as json for the owning user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $export = RebateReportExport::query()->create([
        'uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        'user_id' => $user->id,
        'status' => RebateReportExport::STATUS_COMPLETED,
        'disk' => 'local',
        'path' => 'rebate-reports/test.pdf',
        'filename' => 'Rebate_Tracker_2026-03-01_to_2026-04-30.pdf',
        'filters' => [
            'start_date' => '2026-03-01',
            'end_date' => '2026-04-30',
            'company_id' => null,
            'branch_id' => null,
            'site_id' => null,
        ],
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($user)->getJson(route('reports.rebate-tracker-pdf.status', $export->uuid));

    $response->assertOk()
        ->assertJsonPath('status', RebateReportExport::STATUS_COMPLETED)
        ->assertJsonStructure(['download_url', 'error_message']);
});
