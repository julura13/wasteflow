<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waste Stream Collection Report</title>
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
        .breakdown-group {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .breakdown-heading {
            background-color: #1e3a5f;
            color: white;
            font-weight: bold;
            padding: 5px 8px;
            font-size: 11px;
        }
        .breakdown-table {
            margin-bottom: 0;
        }
        .breakdown-table thead th {
            background-color: #e5e7eb;
            color: #1e3a5f;
        }
        .breakdown-table tbody td {
            font-weight: 400;
        }
        .breakdown-table tfoot td {
            font-weight: bold;
            background-color: #f3f4f6;
            border: 1px solid #ddd;
            border-top: 2px solid #1e3a5f;
            padding: 5px 4px;
        }
        .breakdown-table tfoot td.text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $reportLogoSrc = null;
        $logoPath = public_path('images/logo-white-bg-dark-text.jpeg');
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
        <h1>WASTE STREAM COLLECTION REPORT</h1>
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
    </div>

    @forelse($wasteStreamBreakdown as $group)
    <div class="breakdown-group">
        <div class="breakdown-heading">{{ $group['heading'] }}</div>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th class="date-col">Date</th>
                    <th>Order No</th>
                    <th>Slip No</th>
                    <th>Qty</th>
                    <th class="text-right">Weight (kg)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['rows'] as $row)
                <tr>
                    <td class="date-col">{{ \Carbon\Carbon::parse($row['date'])->format(\App\Support\DisplayDate::CALENDAR) }}</td>
                    <td>{{ $row['tracking_number'] }}</td>
                    <td>{{ $row['slip_number'] }}</td>
                    <td>{!! nl2br(e($row['quantity'])) !!}</td>
                    <td class="text-right">{{ number_format($row['weight'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Subtotal</td>
                    <td class="text-right">{{ number_format($group['subtotal_weight'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @empty
    <p>No waste stream collection data found for the selected filters.</p>
    @endforelse

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
