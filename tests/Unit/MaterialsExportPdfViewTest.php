<?php

use App\Models\Material;
use Carbon\Carbon;
use Illuminate\Support\Collection;

uses(Tests\TestCase::class);

it('renders material definitions export blade', function () {
    $html = view('materials.export-pdf', [
        'materials' => new Collection,
        'filterSummary' => 'Filters: none (all materials)',
        'generatedAt' => Carbon::parse('2026-04-06 12:00:00'),
    ])->render();

    expect($html)->toContain('MATERIAL DEFINITIONS');
    expect($html)->toContain('No materials match');
});

it('renders material row in export blade', function () {
    $material = new Material([
        'weight_required' => 'Yes',
        'rebate_offered' => true,
        'rebate_rate' => 2.5,
        'client_rebate_share' => 80,
        'backing_document' => false,
        'is_active' => true,
        'notes' => 'Test note for PDF',
    ]);
    $material->id = 99;
    $material->setRelation('wasteStream', (object) ['name' => 'Paper']);
    $material->setRelation('grade', (object) ['name' => 'HL 1']);
    $material->setRelation('classification', (object) ['name' => 'Recycling']);
    $material->setRelation('facility', (object) ['name' => 'Recycling Facility']);
    $material->setRelation('serviceProvider', null);

    $html = view('materials.export-pdf', [
        'materials' => collect([$material]),
        'filterSummary' => 'Filters: none',
        'generatedAt' => Carbon::parse('2026-04-06 12:00:00'),
    ])->render();

    expect($html)->toContain('HL 1');
    expect($html)->toContain('Paper');
    expect($html)->toContain('99');
});
