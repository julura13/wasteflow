<?php

uses(Tests\TestCase::class);

it('fails when the chosen disk is not s3', function () {
    $this->artisan('wasabi:buckets', ['--disk' => 'public'])
        ->assertFailed()
        ->expectsOutputToContain('not S3');
});
