<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer order frequencies — {{ $lookbackMonths }} month lookback</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7px;
            color: #1f2937;
            line-height: 1.25;
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
        th { background-color: #f3f4f6; font-weight: bold; font-size: 6.5px; }
        th.group-waste { background-color: #d1fae5; color: #065f46; }
        th.group-recycling { background-color: #e0f2fe; color: #075985; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr:nth-child(even) td { background-color: #f9fafb; }
        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
            font-size: 6px;
            color: #6b7280;
            text-align: center;
        }
        .col-customer { width: 11%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Customer order frequencies</h1>
        <div class="meta">
            Finalized orders only — lookback {{ $lookbackMonths }} {{ $lookbackMonths === 1 ? 'month' : 'months' }}
            — generated {{ now()->format(\App\Support\DisplayDate::CALENDAR_DATETIME) }}
        </div>
    </div>

    @if(count($rows) === 0)
        <p>No companies in scope.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="col-customer">Customer</th>
                    <th colspan="4" class="group-waste">Waste orders</th>
                    <th colspan="4" class="group-recycling">Recycling orders</th>
                </tr>
                <tr>
                    <th class="group-waste">Last finalized</th>
                    <th class="group-waste">Days since</th>
                    <th class="group-waste">In period</th>
                    <th class="group-waste">Avg / mo</th>
                    <th class="group-recycling">Last finalized</th>
                    <th class="group-recycling">Days since</th>
                    <th class="group-recycling">In period</th>
                    <th class="group-recycling">Avg / mo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php
                        $w = $row['waste'];
                        $r = $row['recycling'];
                    @endphp
                    <tr>
                        <td>{{ $row['company_name'] }}</td>
                        <td>{{ isset($w['last_finalized_date']) ? \App\Support\DisplayDate::formatOrEmpty($w['last_finalized_date']) : '—' }}</td>
                        <td class="num">{{ $w['days_since_last_finalized'] ?? '—' }}</td>
                        <td class="num">{{ $w['finalized_orders_in_period'] }}</td>
                        <td class="num">{{ number_format($w['average_orders_per_month'], 2) }}</td>
                        <td>{{ isset($r['last_finalized_date']) ? \App\Support\DisplayDate::formatOrEmpty($r['last_finalized_date']) : '—' }}</td>
                        <td class="num">{{ $r['days_since_last_finalized'] ?? '—' }}</td>
                        <td class="num">{{ $r['finalized_orders_in_period'] }}</td>
                        <td class="num">{{ number_format($r['average_orders_per_month'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">WasteFlow — Customer order frequencies (waste vs recycling)</div>
</body>
</html>
