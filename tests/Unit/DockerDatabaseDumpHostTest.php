<?php

use App\Support\DockerDatabaseDumpHost;

uses(Tests\TestCase::class);

it('does not change unrelated hostnames', function () {
    expect(DockerDatabaseDumpHost::resolve('db.example.com', 'mysql'))->toBe('db.example.com')
        ->and(DockerDatabaseDumpHost::resolve('127.0.0.1', 'mysql'))->toBe('127.0.0.1')
        ->and(DockerDatabaseDumpHost::resolve('localhost', 'mysql'))->toBe('localhost');
});

it('returns the compose hostname when fallback is disabled', function () {
    expect(DockerDatabaseDumpHost::resolve('mysql', 'mysql', false))->toBe('mysql');
});
