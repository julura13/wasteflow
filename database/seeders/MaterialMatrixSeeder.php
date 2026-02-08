<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\ServiceProvider;
use App\Models\WasteStream;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class MaterialMatrixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matrix = [
            // General waste
            ['Waste', 'General Waste', 'Disposed', 'Landfill', 'No Average Weight', 'No', 'Yes', 'Yes'],
            ['Waste', 'Non Compactable Waste', 'Disposed', 'Landfill', 'Yes', 'No', 'Yes', 'Yes'],

            // Organic waste
            ['Organic Waste', 'Organics Recovered', 'Recovered', 'Compost Facility', 'Yes/No', 'No', 'Yes', 'Yes'],

            // Paper
            ['Paper', 'CMW', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'CMW Rolls', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'FN/SBM', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'HL 1', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'HL Books', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'HL Dirty', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'K4', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'K4 Rolls', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'Label Backing', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'Tetrapak', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Paper', 'Tissue Paper', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],

            // Plastics
            ['Plastic', 'BOPP', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'EPS/XPS', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'Hangers', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD - Colour', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD - PP', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD Clear', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD Crates', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD Dark', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD Light', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'HD White', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'LD Consul', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'LD Clear', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'LD Mix', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'PET Clear', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'PET Mix', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'PET Strapping', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'PP Caps', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'PP', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Plastic', 'Wrapping', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],

            ['Aluminium', 'Alu Cans', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Aluminium', 'Alu Foil', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],

            // Metals
            ['Metal', 'Heavy Steel', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Metal', 'Light Steel Cans', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Metal', 'Light Steel Drums', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Metal', 'Light Steel', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],

            // Wood
            ['Wood', 'Wood Loose', 'Recovered', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Wood', 'Wood Pallet', 'Recovered', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],
            ['Wood', 'Wood Loose', 'Disposed', 'Landfill', 'Yes', 'No', 'Yes', 'Yes'],

            // Hazardous waste
            ['Hazardous Waste', 'Fluorescent Tubes', 'Recovered', 'Specialist Recycler', 'Yes', 'No', 'Yes', 'Yes'],
            ['Hazardous Waste', 'Fats, Oils & Grease', 'Recovery', 'FOG Recycling', 'Yes', 'No', 'Yes', 'Yes'],

            // Glass
            ['Glass', 'Glass', 'Recycling', 'Recycling Facility', 'Yes', 'Yes', 'Yes', 'Yes'],

            // Garden waste
            ['Garden Waste', 'Garden Waste', 'Recovered', 'Compost Facility', 'Yes', 'No', 'Yes', 'Yes'],
        ];

        $facilityMeta = [
            'Landfill' => ['facility_type' => 'landfill', 'requires_weight' => true],
            'Recycling Facility' => ['facility_type' => 'recycling', 'requires_weight' => true],
            'Compost Facility' => ['facility_type' => 'compost', 'requires_weight' => true],
            'Specialist Recycler' => ['facility_type' => 'specialist', 'requires_weight' => true],
            'FOG Recycling' => ['facility_type' => 'fog_recycling', 'requires_weight' => true],
        ];

        $defaultServiceProviderId = ServiceProvider::query()
            ->where('name', 'Cape Waste Services')
            ->value('id') ?? ServiceProvider::query()->value('id');

        foreach ($matrix as $row) {
            [$streamName, $gradeName, $classificationName, $facilityName, $weight, $rebate, $backing, $providerFlag] = $row;

            $wasteStream = WasteStream::firstOrCreate(
                ['name' => $streamName],
                ['description' => null, 'is_default' => $streamName === 'Waste', 'is_active' => true]
            );

            $grade = Grade::firstOrCreate(
                ['name' => $gradeName],
                ['description' => null, 'is_active' => true]
            );

            $classification = Classification::firstOrCreate(
                ['name' => $classificationName],
                ['description' => null, 'is_active' => true]
            );

            $meta = Arr::get($facilityMeta, $facilityName, ['facility_type' => 'general', 'requires_weight' => true]);

            $facility = Facility::firstOrCreate(
                ['name' => $facilityName],
                [
                    'description' => null,
                    'facility_type' => $meta['facility_type'],
                    'requires_weight' => $meta['requires_weight'],
                    'is_active' => true,
                ]
            );

            $serviceProviderId = null;
            if (strtolower($providerFlag) === 'yes' && $defaultServiceProviderId) {
                $serviceProviderId = $defaultServiceProviderId;
            }

            Material::updateOrCreate(
                [
                    'waste_stream_id' => $wasteStream->id,
                    'grade_id' => $grade->id,
                    'classification_id' => $classification->id,
                    'facility_id' => $facility->id,
                    'service_provider_id' => $serviceProviderId,
                ],
                [
                    'weight_required' => $weight,
                    'rebate_offered' => strtolower($rebate) === 'yes',
                    'rebate_rate' => strtolower($rebate) === 'yes' ? 0 : null,
                    'client_rebate_share' => strtolower($rebate) === 'yes' ? 70 : null,
                    'backing_document' => strtolower($backing) === 'yes',
                    'notes' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
