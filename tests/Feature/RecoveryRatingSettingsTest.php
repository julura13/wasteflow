<?php

use App\Models\RecoveryRatingTier;
use App\Models\User;
use Database\Seeders\RecoveryRatingTierSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RecoveryRatingTierSeeder::class);
});

it('lists the seeded tiers ordered from highest to lowest', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get('/settings/recovery-rating')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/RecoveryRating/Index')
            ->has('tiers', 6)
            ->where('tiers.0.name', 'Platinum')
            ->where('tiers.5.name', 'Improvement Required')
        );
});

it('denies access to users without the manage-settings permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/recovery-rating')
        ->assertForbidden();
});

it('updates tier thresholds when strictly descending', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $tiers = RecoveryRatingTier::query()->orderByDesc('sort_order')->get();

    $payload = $tiers->map(fn (RecoveryRatingTier $tier) => [
        'id' => $tier->id,
        'min_percentage' => match ($tier->slug) {
            'platinum' => 95,
            'gold' => 80,
            'silver' => 65,
            'bronze' => 45,
            'developing' => 25,
            default => 0,
        },
    ])->values()->all();

    $this->actingAs($user)
        ->put('/settings/recovery-rating', ['tiers' => $payload])
        ->assertRedirect();

    expect((float) RecoveryRatingTier::where('slug', 'platinum')->value('min_percentage'))->toBe(95.0);
    expect((float) RecoveryRatingTier::where('slug', 'gold')->value('min_percentage'))->toBe(80.0);
});

it('rejects thresholds that are not strictly descending from Platinum down', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $tiers = RecoveryRatingTier::query()->orderByDesc('sort_order')->get();

    // Gold's minimum set above Platinum's - invalid ordering.
    $payload = $tiers->map(fn (RecoveryRatingTier $tier) => [
        'id' => $tier->id,
        'min_percentage' => $tier->slug === 'gold' ? 99 : $tier->min_percentage,
    ])->values()->all();

    $this->actingAs($user)
        ->put('/settings/recovery-rating', ['tiers' => $payload])
        ->assertSessionHasErrors('tiers');

    expect((float) RecoveryRatingTier::where('slug', 'gold')->value('min_percentage'))->toBe(75.0);
});

it('resolves the correct tier for a given diversion percentage', function () {
    expect(RecoveryRatingTier::forPercentage(95)->slug)->toBe('platinum');
    expect(RecoveryRatingTier::forPercentage(90)->slug)->toBe('platinum');
    expect(RecoveryRatingTier::forPercentage(89.9)->slug)->toBe('gold');
    expect(RecoveryRatingTier::forPercentage(10)->slug)->toBe('improvement-required');
});
