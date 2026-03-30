<?php

use App\Models\ContainerOption;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('container_options', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['slug']);
        });

        Schema::table('container_options', function (Blueprint $table) {
            $table->enum('order_type', ['waste', 'recycling'])->default('waste')->after('id');
        });

        DB::table('container_options')->update(['order_type' => 'waste']);

        $rows = DB::table('container_options')->orderBy('id')->get();
        foreach ($rows as $row) {
            $base = Str::slug($row->order_type.'-'.$row->name);
            $slug = $base;
            $i = 0;
            while (
                DB::table('container_options')
                    ->where('slug', $slug)
                    ->where('id', '!=', $row->id)
                    ->exists()
            ) {
                $slug = $base.'-'.(++$i);
            }
            DB::table('container_options')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('container_options', function (Blueprint $table) {
            $table->unique(['name', 'order_type']);
            $table->unique(['slug', 'order_type']);
        });

        $recyclingNames = [
            'Scrap Load',
            'Loose Bags',
            '8m³ Cage',
            '20m³ Cage',
            'Other',
        ];

        foreach ($recyclingNames as $name) {
            ContainerOption::firstOrCreate(
                ['name' => $name, 'order_type' => 'recycling'],
                ['is_active' => true]
            );
        }

        $legacyMap = [
            'scrap_load' => 'Scrap Load',
            'loose_bags' => 'Loose Bags',
            'cage_8m3' => '8m³ Cage',
            'cage_20m3' => '20m³ Cage',
            'other' => 'Other',
        ];

        Order::query()->where('order_type', 'recycling')->orderBy('id')->chunkById(100, function ($orders) use ($legacyMap) {
            foreach ($orders as $order) {
                $lines = $order->quantity_lines;
                if (! is_array($lines)) {
                    continue;
                }

                $changed = false;
                foreach ($lines as $index => $line) {
                    if (! is_array($line)) {
                        continue;
                    }
                    if (isset($line['container_option_id'])) {
                        continue;
                    }
                    $qtyType = $line['quantity_type'] ?? null;
                    if (! is_string($qtyType)) {
                        continue;
                    }
                    $label = $legacyMap[$qtyType] ?? null;
                    if ($label === null) {
                        continue;
                    }
                    $opt = ContainerOption::query()
                        ->where('order_type', 'recycling')
                        ->where('name', $label)
                        ->first();
                    if (! $opt) {
                        continue;
                    }
                    $lines[$index]['container_option_id'] = $opt->id;
                    $lines[$index]['container_option_name'] = $opt->name;
                    unset($lines[$index]['quantity_type']);
                    if ($label !== 'Other' && isset($lines[$index]['description'])) {
                        unset($lines[$index]['description']);
                    }
                    $changed = true;
                }

                if ($changed) {
                    $order->quantity_lines = array_values($lines);
                    $order->saveQuietly();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new \RuntimeException('2026_03_30_120000_add_order_type_to_container_options_table is not reversible (data and uniqueness changed).');
    }
};
