<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\ManagementReportService;
use App\Traits\ScopeByClientTrait;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManagementReportController extends Controller
{
    use ScopeByClientTrait;

    public function __construct(
        protected ManagementReportService $managementReport,
    ) {}

    /**
     * Management report: total waste diverted % and container-type totals, one row per client, for a single month.
     */
    public function index(Request $request)
    {
        [$month, $year, $rows] = $this->managementReportPayload($request);

        return Inertia::render('Reports/ManagementReport', [
            'rows' => $rows,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * CSV export for the management report (same scope and month as the on-screen report).
     */
    public function export(Request $request)
    {
        [$month, $year, $rows] = $this->managementReportPayload($request);

        $filename = 'management_report_'.sprintf('%04d-%02d', $year, $month).'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Customer',
                'Total waste diverted %',
                'Total waste managed (kg)',
                'Container totals',
            ]);
            foreach ($rows as $row) {
                $containerSummary = collect($row['container_totals'])
                    ->map(fn ($c) => $c['name'].': '.$c['quantity'])
                    ->implode(', ');

                fputcsv($out, [
                    $row['company_name'],
                    $row['total_waste_diverted_percentage'],
                    $row['total_waste_managed_kg'],
                    $containerSummary,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * PDF export for the management report (same scope and month as the on-screen report).
     */
    public function exportPdf(Request $request)
    {
        [$month, $year, $rows] = $this->managementReportPayload($request);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('reports.management-report-pdf', [
            'rows' => $rows,
            'month' => $month,
            'year' => $year,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'management_report_'.sprintf('%04d-%02d', $year, $month).'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array{0: int, 1: int, 2: list<array<string, mixed>>}
     */
    public function managementReportPayload(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2100'],
        ]);
        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);

        $companies = $this->scopeCompaniesForUser();
        $rows = $this->managementReport->buildForCompanies($companies, $month, $year);

        return [$month, $year, $rows];
    }
}
