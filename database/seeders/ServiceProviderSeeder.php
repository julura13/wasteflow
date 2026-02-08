<?php

namespace Database\Seeders;

use App\Models\ServiceProvider;
use Illuminate\Database\Seeder;

class ServiceProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Cape Waste Services',
                'types' => ['waste_collection'],
                'email' => 'info@capewaste.co.za',
                'phone' => '+27 21 555 0100',
                'address' => '123 Industrial Road, Cape Town, 7500',
                'contact_person' => 'Thabo Mdluli',
                'registration_number' => '2015/123456/07',
                'notes' => 'Primary waste collection service for Western Cape',
            ],
            [
                'name' => 'Green Recycling Solutions',
                'types' => ['recycling'],
                'email' => 'contact@greenrecycle.co.za',
                'phone' => '+27 21 555 0200',
                'address' => '45 Recycle Avenue, Bellville, 7530',
                'contact_person' => 'Sarah van der Merwe',
                'registration_number' => '2018/234567/07',
                'notes' => 'Specializes in PET, cardboard, and paper recycling',
            ],
            [
                'name' => 'EcoSort Waste Management',
                'types' => ['general', 'waste_collection'],
                'email' => 'admin@ecosort.co.za',
                'phone' => '+27 11 555 0300',
                'address' => '78 Green Street, Johannesburg, 2001',
                'contact_person' => 'Johan Pretorius',
                'registration_number' => '2020/345678/07',
                'notes' => 'Full-service waste management and sorting',
            ],
            [
                'name' => 'SafeDispose Hazardous Waste',
                'types' => ['hazardous'],
                'email' => 'safety@safedispose.co.za',
                'phone' => '+27 31 555 0400',
                'address' => '12 Safety Lane, Durban, 4001',
                'contact_person' => 'Dr. Nomsa Khumalo',
                'registration_number' => '2017/456789/07',
                'notes' => 'Licensed for hazardous and chemical waste disposal',
            ],
            [
                'name' => 'Rapid Waste Collection',
                'types' => ['waste_collection'],
                'email' => 'info@rapidwaste.co.za',
                'phone' => '+27 21 555 0500',
                'address' => '234 Transport Road, Cape Town, 7764',
                'contact_person' => 'Michael Smith',
                'registration_number' => '2019/567890/07',
                'notes' => 'Fast response waste collection service',
            ],
            [
                'name' => 'Metal Recyclers SA',
                'types' => ['recycling'],
                'email' => 'metals@metalrecycle.co.za',
                'phone' => '+27 11 555 0600',
                'address' => '56 Scrap Yard Road, Germiston, 1401',
                'contact_person' => 'David Botha',
                'registration_number' => '2016/678901/07',
                'notes' => 'Specializes in aluminum, steel, and mixed metal recycling',
            ],
        ];

        foreach ($providers as $provider) {
            ServiceProvider::create($provider);
        }
    }
}