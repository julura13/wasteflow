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
            font-size: 9px;
            color: #333;
            line-height: 1.3;
        }
        @page {
            margin: 15mm;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 15px 20px;
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .header .filters {
            font-size: 10px;
            margin-top: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        table thead {
            background-color: #1e3a5f;
            color: white;
        }
        table thead th {
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #2c5a7a;
            font-weight: 600;
        }
        table thead th.text-right {
            text-align: right;
        }
        table tbody td {
            padding: 4px;
            border: 1px solid #ddd;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        table tbody td.text-right {
            text-align: right;
        }
        .summary-box {
            margin-top: 15px;
            padding: 12px;
            background-color: #ecfdf5;
            border: 2px solid #2563eb;
            border-radius: 4px;
        }
        .summary-box h3 {
            font-size: 11px;
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
            font-size: 10px;
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>WASTE COLLECTION &amp; RECYCLING REPORT</h1>
        <div class="filters">
            Period: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} – {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Company</th>
                <th>Branch</th>
                <th>Site</th>
                <th>Grade</th>
                <th class="text-right">Weight (kg)</th>
                <th class="text-right">Rate (R/kg)</th>
                <th class="text-right">Total (R)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rebateData as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}</td>
                <td>{{ $item['company_name'] }}</td>
                <td>{{ $item['branch_name'] }}</td>
                <td>{{ $item['site_name'] }}</td>
                <td>{{ $item['grade'] }}</td>
                <td class="text-right">{{ number_format($item['weight'], 2) }}</td>
                <td class="text-right">{{ number_format($item['rate'], 2) }}</td>
                <td class="text-right">{{ number_format($item['total'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">No rebate data found for the selected filters.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

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
        <div class="summary-row">
            <span class="summary-label">Average Rate:</span>
            <span class="summary-value">R {{ ($totalWeight ?? 0) > 0 ? number_format(($totalRebate ?? 0) / $totalWeight, 2) : '0.00' }} / kg</span>
        </div>
    </div>

    <div class="footer">
        <strong>WASTEFLOW</strong> – Sustainable Waste Management
    </div>
</body>
</html>
