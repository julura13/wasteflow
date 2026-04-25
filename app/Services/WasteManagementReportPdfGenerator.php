<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Throwable;

class WasteManagementReportPdfGenerator
{
    /**
     * Render the Resource Intelligence HTML into a raw PDF string.
     */
    public function generate(string $html): string
    {
        $driver = (string) config('waste_report_pdf.driver', 'browsershot');

        if ($driver === 'browsershot') {
            try {
                return $this->generateWithBrowsershot($html);
            } catch (Throwable $e) {
                if (! config('waste_report_pdf.fallback_to_dompdf', true)) {
                    throw $e;
                }

                Log::warning('Waste report PDF: Browsershot failed, falling back to Dompdf.', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->generateWithDompdf($html);
    }

    public function generateWithBrowsershot(string $html): string
    {
        $cfg = config('waste_report_pdf.browsershot', []);

        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->landscape(false)
            ->showBackground()
            ->margins(
                (float) ($cfg['margin_top_mm'] ?? 6),
                (float) ($cfg['margin_right_mm'] ?? 6),
                (float) ($cfg['margin_bottom_mm'] ?? 6),
                (float) ($cfg['margin_left_mm'] ?? 6),
                'mm'
            )
            ->windowSize(
                (int) ($cfg['window_width'] ?? 1200),
                (int) ($cfg['window_height'] ?? 2000)
            )
            ->timeout((int) ($cfg['timeout_seconds'] ?? 120))
            ->delay((int) ($cfg['delay_ms'] ?? 500))
            ->setNodeModulePath(base_path('node_modules'))
            ->newHeadless();

        if (! empty($cfg['emulate_print_media'])) {
            $browsershot->emulateMedia('print');
        }

        if (! empty($cfg['wait_for_network_idle'])) {
            $browsershot->waitUntilNetworkIdle();
        }

        if (! empty($cfg['no_sandbox'])) {
            $browsershot->noSandbox();
        }

        if (! empty($cfg['node_binary'])) {
            $browsershot->setNodeBinary((string) $cfg['node_binary']);
        }

        if (! empty($cfg['npm_binary'])) {
            $browsershot->setNpmBinary((string) $cfg['npm_binary']);
        }

        if (! empty($cfg['chrome_path'])) {
            $browsershot->setChromePath((string) $cfg['chrome_path']);
        }

        return $browsershot->pdf();
    }

    public function generateWithDompdf(string $html): string
    {
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
