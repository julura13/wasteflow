<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orders Export - {{ now()->format('Y-m-d') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.2;
            padding: 15px 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header h1 { font-size: 14px; color: #2563eb; }
        .header .meta { font-size: 9px; color: #6b7280; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; text-align: left; border: 1px solid #d1d5db; }
        th { background-color: #f3f4f6; font-weight: bold; font-size: 9px; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Orders Export</h1>
        <div class="meta">Generated {{ now()->format('d/m/Y H:i') }} — {{ count($orders) }} order(s)</div>
    </div>
    @if($orders->isEmpty())
        <p>No orders match the current filters.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Company</th>
                    <th>Branch</th>
                    <th>Site</th>
                    <th>Service Provider</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Actual</th>
                    <th>Slip #</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    @php
                        $site = $order->site;
                        $branch = $site?->branch ?? $order->branch;
                        $company = $branch?->company ?? $order->company;
                    @endphp
                    <tr>
                        <td>{{ $order->tracking_number }}</td>
                        <td>{{ $company?->name ?? '—' }}</td>
                        <td>{{ $branch?->name ?? '—' }}</td>
                        <td>{{ $site?->name ?? '—' }}</td>
                        <td>{{ $order->serviceProvider?->name ?? (is_string($order->service_provider) ? $order->service_provider : '—') }}</td>
                        <td>{{ $order->order_type === 'waste' ? 'Waste' : 'Recycling' }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->requested_collection_date ? $order->requested_collection_date->format('Y-m-d') : '—' }}</td>
                        <td>{{ $order->actual_collection_date ? $order->actual_collection_date->format('Y-m-d') : '—' }}</td>
                        <td>{{ $order->slip_number ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <div class="footer">WasteFlow — Orders export</div>
</body>
</html>
