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
     * Rebate rate (R per kg) by grade name. From pricing spreadsheets.
     * Grades not listed get rebate_rate 0 when rebate_offered is Yes, null otherwise.
     */
    private static function rebateRatesByGrade(): array
    {
        return [
            // Image 1 (materials / rebate amounts)
            'HL 1' => 2.20,
            'K4' => 0.60,
            'K4 Rolls' => 0.20,
            'Label Backing' => 0.00,
            'LD Consul' => 2.79,
            'LD Clear' => 2.90,
            'LD Mix' => 2.09,
            'Light Steel Cans' => 1.50,
            'Light Steel' => 2.00,
            'Light Steel Drums' => 1.10,
            'PET Clear' => 4.00,
            'PET Mix' => 1.50,
            'PP' => 1.50,
            'PP Caps' => 1.50,
            'Tetrapak' => 0.30,
            'Tissue Paper' => 0.00,
            'Wrapping' => 1.00,
            // Image 2 (grade / rate)
            'Alu Cans' => 14.09,
            'Alu Foil' => 0.69,
            'BOPP' => 0.00,
            'CMW' => 0.20,
            'CMW Rolls' => 0.30,
            'EPS/XPS' => 0.00,
            'FN/SBM' => 0.40,
            'General Waste' => 0.00,
            'Glass' => 0.00,
            'Hangers' => 0.00,
            'HD' => 2.50,
            'HD - PP' => 2.00,
            'HD Caps' => 1.50,
            'HD Clear' => 3.00,
            'HD Crates' => 2.00,
            'HD - Colour' => 2.80,
            'HD Dark' => 2.80,
            'HD Light' => 3.00,
            'HD White' => 3.50,
            'Heavy Steel' => 2.79,
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rebateRates = self::rebateRatesByGrade();

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

            $hasRebateRate = array_key_exists($gradeName, $rebateRates);
            $rebateRate = $rebateRates[$gradeName] ?? null;
            $rebateYes = strtolower($rebate) === 'yes';
            $offerRebate = $rebateYes || $hasRebateRate;
            $rate = $offerRebate ? ($rebateRate !== null ? $rebateRate : 0) : null;

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
                    'rebate_offered' => $offerRebate,
                    'rebate_rate' => $rate,
                    'client_rebate_share' => $offerRebate ? 70 : null,
                    'backing_document' => strtolower($backing) === 'yes',
                    'notes' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
