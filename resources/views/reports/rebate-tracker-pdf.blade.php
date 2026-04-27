<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waste Collection & Recycling Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        @page {
            margin: 24mm 22mm;
        }
        .page-shell {
            padding: 0 4mm;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 15px 20px;
            text-align: center;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header .scope {
            font-size: 18px;
            font-weight: 400;
            margin-top: 0;
        }
        .header .filters {
            font-size: 14px;
            font-weight: 400;
            margin-top: 5px;
            letter-spacing: -0.02em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        table thead {
            background-color: #1e3a5f;
            color: white;
        }
        table thead th {
            padding: 7px 5px;
            text-align: left;
            border: 1px solid #2c5a7a;
            font-weight: 600;
            font-size: 10.5px;
        }
        table thead th.text-right {
            text-align: right;
        }
        table tbody td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            font-weight: 600;
            color: #222;
            font-size: 10.5px;
        }
        table tbody td.tracking-cell {
            max-width: 120px;
            word-wrap: break-word;
            word-break: break-word;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        table tbody td.text-right {
            text-align: right;
        }
        table thead th.date-col,
        table tbody td.date-col {
            white-space: nowrap;
            width: 1%;
        }
        .summary-box {
            margin-bottom: 15px;
            padding: 12px;
            background-color: #ecfdf5;
            border: 2px solid #2563eb;
            border-radius: 4px;
        }
        .client-logo {
            text-align: center;
            margin-top: 15px;
            padding-top: 6px;
        }
        .client-logo img {
            height: 192px;
            width: auto;
            max-width: 200px;
        }
        .summary-box h3 {
            font-size: 12px;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .summary-label {
            display: table-cell;
            font-weight: 600;
            width: 40%;
        }
        .summary-value {
            display: table-cell;
            font-weight: bold;
            text-align: right;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #2563eb;
            text-align: center;
            font-size: 11px;
            color: #2563eb;
        }
    </style>
</head>
<body>
    @php
        $reportLogoSrc = null;
        $logoPath = public_path('images/logo.png');
        if (is_file($logoPath)) {
            $reportLogoSrc = 'data:'.mime_content_type($logoPath).';base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $scopeParts = array_values(array_filter([
            $filters['company_name'] ?? null,
            $filters['branch_name'] ?? null,
            $filters['site_name'] ?? null,
        ], static fn (?string $part): bool => $part !== null && $part !== ''));
        $scopeDisplayName = $scopeParts !== [] ? implode(' - ', $scopeParts) : 'All Locations';
    @endphp
    <div class="page-shell">
    <div class="header">
        <h1>WASTE COLLECTION &amp; RECYCLING REPORT</h1>
        <div class="scope">{{ $scopeDisplayName }}</div>
        <div class="filters">
            Period: {{ \Carbon\Carbon::parse($filters['start_date'])->format(\App\Support\DisplayDate::CALENDAR) }} – {{ \Carbon\Carbon::parse($filters['end_date'])->format(\App\Support\DisplayDate::CALENDAR) }}
        </div>
    </div>

    <div class="summary-box">
        <h3>Summary</h3>
        <div class="summary-row">
            <span class="summary-label">Total Weight:</span>
            <span class="summary-value">{{ number_format($totalWeight ?? 0, 2) }} kg</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Rebate:</span>
            <span class="summary-value">R {{ number_format($totalRebate ?? 0, 2) }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="date-col">Date</th>
                <th>Company</th>
                <th>Branch</th>
                <th>Site</th>
                <th>Tracking No</th>
                <th>Grade</th>
                <th class="text-right">Weight (kg)</th>
                <th class="text-right">Rate (R/kg)</th>
                <th class="text-right">Total (R)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rebateData as $item)
            <tr>
                <td class="date-col">{{ \Carbon\Carbon::parse($item['date'])->format(\App\Support\DisplayDate::CALENDAR) }}</td>
                <td>{{ $item['company_name'] }}</td>
                <td>{{ $item['branch_name'] }}</td>
                <td>{{ $item['site_name'] }}</td>
                <td class="tracking-cell">{{ $item['tracking_numbers'] ?? '—' }}</td>
                <td>{{ $item['grade'] }}</td>
                <td class="text-right">{{ number_format($item['weight'], 2) }}</td>
                <td class="text-right">{{ number_format($item['rate'], 2) }}</td>
                <td class="text-right">{{ number_format($item['total'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px;">No rebate data found for the selected filters.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($reportLogoSrc))
        <div class="client-logo">
            <img src="{{ $reportLogoSrc }}" alt="WasteFlow" />
        </div>
    @endif

    <div class="footer">
        <strong>WASTEFLOW</strong> – Sustainable Waste Management
    </div>
    </div>
</body>
</html>
