<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Media;
use App\Models\Order;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

function createOrderForCompany(Company $company, User $createdBy): Order
{
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = ServiceProvider::create(['name' => $company->name.' Provider', 'is_active' => true]);

    return Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $createdBy->id,
        'order_type' => 'recycling',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2026-05-10'),
    ]);
}

it('denies a company-scoped user downloading media belonging to another company\'s order', function () {
    $companyA = Company::create(['name' => 'Company A', 'is_active' => true]);
    $companyB = Company::create(['name' => 'Company B', 'is_active' => true]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $scopedUser = User::factory()->create(['company_id' => $companyA->id]);
    $scopedUser->assignRole('weights_capture');

    $otherOrder = createOrderForCompany($companyB, $admin);
    $media = Media::create([
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => $otherOrder->id,
        'file_name' => 'slip.pdf',
        'original_name' => 'slip.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => 'orders/'.$otherOrder->id.'/default/slip.pdf',
        'file_size' => 100,
        'collection' => 'default',
    ]);
    Storage::disk('local')->put($media->path, 'fake pdf content');

    $this->actingAs($scopedUser)
        ->get(route('media.download', $media->id))
        ->assertForbidden();
});

it('denies a company-scoped user deleting media belonging to another company\'s order', function () {
    $companyA = Company::create(['name' => 'Company A', 'is_active' => true]);
    $companyB = Company::create(['name' => 'Company B', 'is_active' => true]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $scopedUser = User::factory()->create(['company_id' => $companyA->id]);
    $scopedUser->assignRole('weights_capture');

    $otherOrder = createOrderForCompany($companyB, $admin);
    $media = Media::create([
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => $otherOrder->id,
        'file_name' => 'slip.pdf',
        'original_name' => 'slip.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => 'orders/'.$otherOrder->id.'/default/slip.pdf',
        'file_size' => 100,
        'collection' => 'default',
    ]);
    Storage::disk('local')->put($media->path, 'fake pdf content');

    $this->actingAs($scopedUser)
        ->delete(route('media.destroy', $media->id))
        ->assertForbidden();

    expect(Media::find($media->id))->not->toBeNull();
});

it('denies a company-scoped user uploading media to another company\'s order', function () {
    $companyA = Company::create(['name' => 'Company A', 'is_active' => true]);
    $companyB = Company::create(['name' => 'Company B', 'is_active' => true]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $scopedUser = User::factory()->create(['company_id' => $companyA->id]);
    $scopedUser->assignRole('document_capture');

    $otherOrder = createOrderForCompany($companyB, $admin);

    $this->actingAs($scopedUser)
        ->post(route('media.upload'), [
            'file' => UploadedFile::fake()->create('slip.pdf', 100),
            'mediable_type' => 'App\\Models\\Order',
            'mediable_id' => $otherOrder->id,
            'collection' => 'default',
        ])
        ->assertForbidden();

    expect(Media::where('mediable_id', $otherOrder->id)->count())->toBe(0);
});

it('allows a company-scoped user to download, upload, and delete media for their own company\'s order', function () {
    $companyA = Company::create(['name' => 'Company A', 'is_active' => true]);

    $scopedUser = User::factory()->create(['company_id' => $companyA->id]);
    $scopedUser->assignRole('document_capture');

    $order = createOrderForCompany($companyA, $scopedUser);

    $this->actingAs($scopedUser)
        ->post(route('media.upload'), [
            'file' => UploadedFile::fake()->create('slip.pdf', 100),
            'mediable_type' => 'App\\Models\\Order',
            'mediable_id' => $order->id,
            'collection' => 'default',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $media = Media::where('mediable_id', $order->id)->firstOrFail();

    $this->actingAs($scopedUser)
        ->get(route('media.download', $media->id))
        ->assertOk();

    $this->actingAs($scopedUser)
        ->delete(route('media.destroy', $media->id))
        ->assertRedirect();

    expect(Media::find($media->id))->toBeNull();
});

it('allows an admin to download media belonging to any company\'s order', function () {
    $companyB = Company::create(['name' => 'Company B', 'is_active' => true]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $order = createOrderForCompany($companyB, $admin);
    $media = Media::create([
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => $order->id,
        'file_name' => 'slip.pdf',
        'original_name' => 'slip.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => 'orders/'.$order->id.'/default/slip.pdf',
        'file_size' => 100,
        'collection' => 'default',
    ]);
    Storage::disk('local')->put($media->path, 'fake pdf content');

    $this->actingAs($admin)
        ->get(route('media.download', $media->id))
        ->assertOk();
});
