<?php

namespace App\Support;

use App\Models\Order;

final class OrderExportFormatting
{
    /**
     * Single line: "Company / Branch / Site" (missing parts omitted).
     */
    public static function companyBranchSite(Order $order): string
    {
        $site = $order->site;
        $branch = $site?->branch ?? $order->branch;
        $company = $branch?->company ?? $order->company;

        $parts = array_values(array_filter([
            $company?->name,
            $branch?->name,
            $site?->name,
        ], fn ($v) => is_string($v) && $v !== ''));

        return $parts === [] ? '' : implode(' / ', $parts);
    }

    /**
     * Plain-text lines for collection quantities (same idea as formatQuantityLineLabel in the UI).
     *
     * @param  array<string, mixed>  $line
     */
    public static function formatQuantityLine(array $line): string
    {
        $qty = $line['quantity'] ?? '—';
        $containerName = isset($line['container_option_name']) ? trim((string) $line['container_option_name']) : '';

        if ($containerName !== '') {
            $desc = ! empty($line['description']) ? ' ('.$line['description'].')' : '';

            return "{$qty}× {$containerName}{$desc}";
        }

        if (! empty($line['quantity_type'])) {
            $label = str_replace('_', ' ', (string) $line['quantity_type']);

            return ! empty($line['description'])
                ? "{$qty}× {$label} ({$line['description']})"
                : "{$qty}× {$label}";
        }

        return "{$qty}×";
    }

    /**
     * Multi-line string for CSV/PDF; empty when nothing to show.
     */
    public static function collectionQuantities(Order $order): string
    {
        $lines = $order->quantity_lines;
        if (is_array($lines) && $lines !== []) {
            $formatted = [];
            foreach ($lines as $line) {
                $formatted[] = self::formatQuantityLine(is_array($line) ? $line : []);
            }

            return implode("\n", $formatted);
        }

        if ($order->quantity !== null && $order->quantity_type) {
            return self::formatQuantityLine([
                'quantity' => $order->quantity,
                'quantity_type' => $order->quantity_type,
            ]);
        }

        return '';
    }
}
