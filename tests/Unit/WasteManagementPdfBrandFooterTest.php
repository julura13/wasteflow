<?php

uses(Tests\TestCase::class);

it('includes the brand closing block and tagline in the resource intelligence pdf template', function () {
    $path = resource_path('views/reports/waste-management-pdf.blade.php');
    $contents = file_get_contents($path);
    expect($contents)->toContain('brand-closing')
        ->and($contents)->toContain('Sustainable Waste Management')
        ->and($contents)->not->toContain('class="brand-name">WASTEFLOW</div>');
});
