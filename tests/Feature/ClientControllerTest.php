<?php

use App\Models\Client;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays clients index page', function () {
    $clients = Client::factory()->count(3)->create();

    $response = $this->get('/clients');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => 
        $page->component('Clients/Index')
            ->has('clients', 3)
            ->where('clients.0.name', $clients->first()->name)
    );
});

it('displays empty state when no clients exist', function () {
    $response = $this->get('/clients');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => 
        $page->component('Clients/Index')
            ->has('clients', 0)
    );
});

it('shows clients with correct data structure', function () {
    $client = Client::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company' => 'Test Company',
        'status' => 'active',
        'monthly_fee' => 2500.00,
    ]);

    $response = $this->get('/clients');

    $response->assertInertia(fn (Assert $page) => 
        $page->component('Clients/Index')
            ->has('clients', 1)
            ->where('clients.0.name', 'John Doe')
            ->where('clients.0.email', 'john@example.com')
            ->where('clients.0.company', 'Test Company')
            ->where('clients.0.status', 'active')
            ->where('clients.0.monthly_fee', '2500.00')
    );
});
