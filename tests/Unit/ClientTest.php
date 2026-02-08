<?php

use App\Models\Client;

it('has correct fillable attributes', function () {
    $client = new Client();
    
    expect($client->getFillable())->toContain('name', 'email', 'phone', 'company', 'address', 'status', 'contract_start_date', 'contract_end_date', 'monthly_fee');
});

it('has correct casts', function () {
    $client = new Client();
    
    expect($client->getCasts())->toHaveKey('contract_start_date');
    expect($client->getCasts())->toHaveKey('contract_end_date');
    expect($client->getCasts())->toHaveKey('monthly_fee');
    expect($client->getCasts()['contract_start_date'])->toBe('date');
    expect($client->getCasts()['contract_end_date'])->toBe('date');
    expect($client->getCasts()['monthly_fee'])->toBe('decimal:2');
});

it('can be instantiated', function () {
    $client = new Client();
    
    expect($client)->toBeInstanceOf(Client::class);
});

it('has default status', function () {
    $client = new Client();
    
    expect($client->status)->toBeNull();
});

it('can set attributes', function () {
    $client = new Client();
    $client->name = 'Test Client';
    $client->email = 'test@example.com';
    
    expect($client->name)->toBe('Test Client');
    expect($client->email)->toBe('test@example.com');
});
