<?php

uses(Tests\TestCase::class);

it('registers the wasabi disk as an s3-compatible driver', function () {
    $wasabi = config('filesystems.disks.wasabi');

    expect($wasabi['driver'])->toBe('s3')
        ->and($wasabi['visibility'])->toBe('private')
        ->and($wasabi)->toHaveKeys([
            'key',
            'secret',
            'region',
            'bucket',
            'endpoint',
            'url',
            'use_path_style_endpoint',
        ]);
});
