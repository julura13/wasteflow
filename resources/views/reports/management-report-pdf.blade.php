<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Management Report — {{ sprintf('%04d-%02d', $year, $month) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #1f2937;
            line-height: 1.3;
            padding: 12px 14px;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 { font-size: 13px; color: #4f46e5; }
        .header .meta { font-size: 7px; color: #6b7280; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td {
            padding: 4px 5px;
            text-align: left;
            border: 1px solid #d1d5db;
            word-wrap: break-word;
            vertical-align: top;
        }
        th { background-color: #f3f4f6; font-weight: bold; font-size: 7px; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr:nth-child(even) td { background-color: #f9fafb; }
        .col-customer { width: 18%; }
        .col-diverted { width: 12%; }
        .col-managed { width: 14%; }
        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
            font-size: 6px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Management Report</h1>
        <div class="meta">
            Period: {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
            — generated {{ now()->format(\App\Support\DisplayDate::CALENDAR_DATETIME) }}
        </div>
    </div>

    @if(count($rows) === 0)
        <p>No companies in scope.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th class="col-customer">Customer</th>
                    <th class="col-diverted">Total Waste Diverted %</th>
                    <th class="col-managed">Total Waste Managed (kg)</th>
                    <th>Container Type Totals</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['company_name'] }}</td>
                        <td class="num">{{ number_format($row['total_waste_diverted_percentage'], 1) }}%</td>
                        <td class="num">{{ number_format($row['total_waste_managed_kg'], 2) }}</td>
                        <td>
                            @if(count($row['container_totals']) === 0)
                                —
                            @else
                                {{ collect($row['container_totals'])->map(fn ($c) => $c['name'].': '.$c['quantity'])->implode(', ') }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">WasteFlow — Management Report (total waste diverted % and container type totals per client)</div>
</body>
</html>
