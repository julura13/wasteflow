<?php

namespace Database\Seeders;

use App\Models\WasteType;
use Illuminate\Database\Seeder;

class WasteTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wasteTypes = [
            // General Waste Types
            ['name' => 'General Waste', 'code' => 'GEN', 'description' => 'Mixed general waste', 'unit' => 'kg', 'is_commodity' => false],
            ['name' => 'Cardboard', 'code' => 'CAR', 'description' => 'Cardboard packaging', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 2.50], // R per kg
            ['name' => 'Paper', 'code' => 'PAP', 'description' => 'Office paper and documents', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 1.80], // R per kg
            
            // Plastic Types
            ['name' => 'PET Clear', 'code' => 'PET-C', 'description' => 'Clear PET bottles', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 6.50], // R per kg
            ['name' => 'PET Colored', 'code' => 'PET-COL', 'description' => 'Colored PET bottles', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 4.20], // R per kg
            ['name' => 'HDPE', 'code' => 'HDPE', 'description' => 'High-density polyethylene', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 3.80], // R per kg
            ['name' => 'LDPE', 'code' => 'LDPE', 'description' => 'Low-density polyethylene', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 2.50], // R per kg
            
            // Metal Types
            ['name' => 'Aluminum Cans', 'code' => 'ALU-CAN', 'description' => 'Aluminum beverage cans', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 18.50], // R per kg
            ['name' => 'Steel Cans', 'code' => 'STEEL-CAN', 'description' => 'Steel food cans', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 2.80], // R per kg
            ['name' => 'Mixed Metal', 'code' => 'METAL-MIX', 'description' => 'Mixed metal scrap', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 3.50], // R per kg
            
            // Glass Types
            ['name' => 'Clear Glass', 'code' => 'GLASS-C', 'description' => 'Clear glass bottles and jars', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 0.80], // R per kg
            ['name' => 'Colored Glass', 'code' => 'GLASS-COL', 'description' => 'Colored glass bottles', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 0.50], // R per kg
            ['name' => 'Mixed Glass', 'code' => 'GLASS-MIX', 'description' => 'Mixed glass', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 0.30], // R per kg
            
            // Organic Waste
            ['name' => 'Food Waste', 'code' => 'FOOD', 'description' => 'Organic food waste', 'unit' => 'kg', 'is_commodity' => false],
            ['name' => 'Garden Waste', 'code' => 'GARDEN', 'description' => 'Garden and plant waste', 'unit' => 'kg', 'is_commodity' => false],
            
            // Electronic Waste
            ['name' => 'Electronic Waste', 'code' => 'E-WASTE', 'description' => 'Electronic devices and components', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 12.00], // R per kg
            ['name' => 'Batteries', 'code' => 'BATTERY', 'description' => 'Various battery types', 'unit' => 'kg', 'is_commodity' => true, 'commodity_value' => 15.00], // R per kg
            
            // Hazardous Waste
            ['name' => 'Hazardous Waste', 'code' => 'HAZ', 'description' => 'Hazardous materials', 'unit' => 'kg', 'is_commodity' => false],
            ['name' => 'Chemical Waste', 'code' => 'CHEM', 'description' => 'Chemical compounds', 'unit' => 'kg', 'is_commodity' => false],
        ];

        foreach ($wasteTypes as $wasteType) {
            WasteType::create($wasteType);
        }
    }
}