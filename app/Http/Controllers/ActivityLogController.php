<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Material;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    /**
     * Display activity log. Optional filter by order tracking number.
     */
    public function index(Request $request): Response
    {
        $trackingNumber = $request->input('order', '');
        $trackingNumber = is_string($trackingNumber) ? trim($trackingNumber) : '';
        $order = null;
        $entries = [];

        if ($trackingNumber !== '') {
            $order = Order::withTrashed()->where('tracking_number', $trackingNumber)->first();
            if ($order) {
                $entries = ActivityLog::query()
                    ->where([
                        'subject_type' => Order::class,
                        'subject_id' => $order->id,
                    ])
                    ->with('causer:id,name,email')
                    ->orderBy('created_at')
                    ->get();
            }
        }

        $entries = collect($entries)->map(function (ActivityLog $log) {
            $properties = $log->properties ?? [];
            if ($log->log_name === 'order_weights_saved' && ! empty($properties['weight_lines'])) {
                $properties = $this->enrichWeightLinesWithGradeNames($properties);
            }

            return [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'properties' => $properties,
                'created_at' => $log->created_at->toIso8601String(),
                'causer' => $log->causer ? [
                    'id' => $log->causer->id,
                    'name' => $log->causer->name,
                    'email' => $log->causer->email,
                ] : null,
            ];
        })->values()->all();

        return Inertia::render('ActivityLog/Index', [
            'filterOrder' => $trackingNumber,
            'order' => $order ? [
                'id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'deleted_at' => $order->deleted_at?->toIso8601String(),
            ] : null,
            'entries' => $entries,
        ]);
    }

    /**
     * Add material_name (grade name) to weight_lines that only have material_id and weight.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function enrichWeightLinesWithGradeNames(array $properties): array
    {
        $lines = $properties['weight_lines'] ?? [];
        if (! is_array($lines)) {
            return $properties;
        }

        $materialIds = collect($lines)->pluck('material_id')->filter()->unique()->values()->all();
        if (empty($materialIds)) {
            return $properties;
        }

        $materials = Material::with('grade')->whereIn('id', $materialIds)->get()->keyBy('id');
        $enriched = collect($lines)->map(function (array $line) use ($materials) {
            $material = $materials->get($line['material_id'] ?? null);
            $gradeName = $material && $material->grade ? $material->grade->name : null;
            $line['material_name'] = $gradeName ?? ($line['material_name'] ?? 'Material #'.($line['material_id'] ?? '?'));

            return $line;
        })->values()->all();

        $properties['weight_lines'] = $enriched;

        return $properties;
    }
}
