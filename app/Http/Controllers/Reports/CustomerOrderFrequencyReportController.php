<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\CustomerOrderFrequencyReportService;
use App\Support\DisplayDate;
use App\Traits\ScopeByClientTrait;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerOrderFrequencyReportController extends Controller
{
    use ScopeByClientTrait;

    public function __construct(
        protected CustomerOrderFrequencyReportService $customerOrderFrequencyReport,
    ) {}

    /**
     * Customer order frequency: last finalized order and average finalized orders per month (waste vs recycling).
     */
    public function index(Request $request)
    {
        [$lookbackMonths, $rows] = $this->customerOrderFrequencyReportPayload($request);

        return Inertia::render('Reports/CustomerOrderFrequencies', [
            'rows' => $rows,
            'lookback_months' => $lookbackMonths,
        ]);
    }

    /**
     * CSV export for customer order frequency report (same scope and lookback as the on-screen report).
     */
    public function export(Request $request)
    {
        [$lookbackMonths, $rows] = $this->customerOrderFrequencyReportPayload($request);

        $filename = 'customer_order_frequencies_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows, $lookbackMonths) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Customer',
                'Lookback months',
                'Waste last finalized',
                'Waste days since last',
                'Waste finalized in period',
                'Waste avg per month',
                'Recycling last finalized',
                'Recycling days since last',
                'Recycling finalized in period',
                'Recycling avg per month',
            ]);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['company_name'],
                    $lookbackMonths,
                    DisplayDate::formatOrEmpty($row['waste']['last_finalized_date'] ?? null),
                    $row['waste']['days_since_last_finalized'] ?? '',
                    $row['waste']['finalized_orders_in_period'],
                    $row['waste']['average_orders_per_month'],
                    DisplayDate::formatOrEmpty($row['recycling']['last_finalized_date'] ?? null),
                    $row['recycling']['days_since_last_finalized'] ?? '',
                    $row['recycling']['finalized_orders_in_period'],
                    $row['recycling']['average_orders_per_month'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * PDF export for customer order frequency report (same scope and lookback as the on-screen report).
     */
    public function exportPdf(Request $request)
    {
        [$lookbackMonths, $rows] = $this->customerOrderFrequencyReportPayload($request);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('reports.customer-order-frequencies-pdf', [
            'rows' => $rows,
            'lookbackMonths' => $lookbackMonths,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'customer_order_frequencies_'.now()->format('Y-m-d_His').'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array{0: int, 1: list<array<string, mixed>>}
     */
    public function customerOrderFrequencyReportPayload(Request $request): array
    {
        $validated = $request->validate([
            'lookback_months' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ]);
        $lookbackMonths = (int) ($validated['lookback_months'] ?? 12);

        $companies = $this->scopeCompaniesForUser();
        $rows = $this->customerOrderFrequencyReport->buildForCompanies($companies, $lookbackMonths, now());

        return [$lookbackMonths, $rows];
    }
}
