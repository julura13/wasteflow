<?php

use App\Models\Branch;
use App\Models\ClientMonthlyMaterialSummary;
use App\Models\Company;
use App\Models\Material;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('deletes order and removes weights and documents and updates client monthly summaries', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'Provider', 'is_active' => true]);

    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'Test Grade'], ['is_active' => true]);
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
    ]);

    $collectionDate = Carbon::parse('2025-12-15');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'pending',
        'requested_collection_date' => $collectionDate,
    ]);

    $stream = OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'nett_weight' => 100.5,
    ]);

    $summary = ClientMonthlyMaterialSummary::where('company_id', $company->id)
        ->where('year', 2025)
        ->where('month', 12)
        ->where('material_id', $material->id)
        ->first();

    expect($summary)->not->toBeNull()
        ->and((float) $summary->total_weight)->toBe(100.5)
        ->and($summary->order_count)->toBeGreaterThan(0);

    $media = Media::create([
        'mediable_type' => Order::class,
        'mediable_id' => $order->id,
        'file_name' => 'doc.pdf',
        'original_name' => 'doc.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => 'orders/doc.pdf',
        'collection' => 'supporting_documents',
    ]);

    $response = $this->actingAs($user)->post(route('orders.delete', $order), [
        'reason' => 'incorrect_order',
        'reason_details' => '',
    ]);

    $response->assertRedirect(route('orders.index'));
    $response->assertSessionHas('success');

    expect(Order::find($order->id))->toBeNull();
    expect(OrderWasteStream::where('order_id', $order->id)->count())->toBe(0);
    expect(Media::where('mediable_type', Order::class)->where('mediable_id', $order->id)->count())->toBe(0);

    $summaryAfter = ClientMonthlyMaterialSummary::where('company_id', $company->id)
        ->where('year', 2025)
        ->where('month', 12)
        ->where('material_id', $material->id)
        ->first();

    if ($summaryAfter) {
        expect((float) $summaryAfter->total_weight)->toBe(0.0);
        expect($summaryAfter->order_count)->toBe(0);
    }
});

it('allows deleting order in any status when user can manage', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'Provider', 'is_active' => true]);

    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('orders.delete', $order), [
        'reason' => 'incorrect_order',
        'reason_details' => '',
    ]);

    $response->assertRedirect(route('orders.index'));
    $response->assertSessionHas('success');
    expect(Order::find($order->id))->toBeNull();
});
