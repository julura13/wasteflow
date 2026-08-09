<?php

use App\Jobs\GenerateWasteStreamCollectionPdfJob;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Models\WasteStreamCollectionReportExport;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('queues a waste stream collection pdf export and creates a pending export record', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Waste Stream PDF Queue Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);

    $response = $this->actingAs($user)->post(route('reports.waste-stream-collection-pdf.request'), [
        'start_date' => '2026-03-01',
        'end_date' => '2026-04-30',
        'company_id' => $company->id,
        'branch_id' => '',
        'site_id' => '',
    ]);

    $response->assertRedirect();

    $export = WasteStreamCollectionReportExport::query()->first();
    expect($export)->not->toBeNull()
        ->and($export->user_id)->toBe($user->id)
        ->and($export->status)->toBe(WasteStreamCollectionReportExport::STATUS_PENDING)
        ->and($export->filters['start_date'])->toBe('2026-03-01')
        ->and($export->filters['end_date'])->toBe('2026-04-30');

    Queue::assertPushed(GenerateWasteStreamCollectionPdfJob::class, function (GenerateWasteStreamCollectionPdfJob $job) use ($export) {
        return $job->wasteStreamCollectionReportExportId === $export->id;
    });
});

it('shares the waste stream collection pdf export uuid via inertia flash on the next request', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->post(route('reports.waste-stream-collection-pdf.request'), [
        'start_date' => '2026-03-01',
        'end_date' => '2026-04-30',
    ]);

    $response->assertSessionHas('waste_stream_collection_pdf_export_uuid');

    // Regression guard for #middleware-flash-whitelist: HandleInertiaRequests only shares
    // flash keys it explicitly lists — a new export flash key must be added there too, or
    // the frontend's "Generating PDF..." UI never appears despite the export being created.
    $follow = $this->actingAs($user)->get(route('reports.waste-stream-collection'));
    $follow->assertOk();
    $follow->assertInertia(fn (Assert $page) => $page
        ->where('flash.waste_stream_collection_pdf_export_uuid', fn ($value) => $value !== null)
    );
});

it('returns waste stream collection export status as json for the owning user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $export = WasteStreamCollectionReportExport::query()->create([
        'uuid' => 'aaaaaaaa-bbbb-cccc-dddd-ffffffffffff',
        'user_id' => $user->id,
        'status' => WasteStreamCollectionReportExport::STATUS_COMPLETED,
        'disk' => 'local',
        'path' => 'waste-stream-collection-reports/test.pdf',
        'filename' => 'Waste_Stream_Collection_Report_2026-03-01_to_2026-04-30.pdf',
        'filters' => [
            'start_date' => '2026-03-01',
            'end_date' => '2026-04-30',
            'company_id' => null,
            'branch_id' => null,
            'site_id' => null,
        ],
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($user)->getJson(route('reports.waste-stream-collection-pdf.status', $export->uuid));

    $response->assertOk()
        ->assertJsonPath('status', WasteStreamCollectionReportExport::STATUS_COMPLETED)
        ->assertJsonStructure(['download_url', 'error_message']);
});
