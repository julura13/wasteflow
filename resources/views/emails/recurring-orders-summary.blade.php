<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurring Orders Summary</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background-color: #f9fafb;
        }
        .email-container { max-width: 640px; margin: 0 auto; background-color: #ffffff; }
        .header { background-color: rgb(34, 74, 64); padding: 30px 20px; text-align: center; }
        .header-logo { color: #ffffff; font-size: 24px; font-weight: bold; }
        .content { padding: 32px 30px; }
        .title { color: #2563eb; font-size: 22px; font-weight: bold; margin-bottom: 8px; }
        .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 24px; }
        .stats { display: flex; gap: 16px; margin-bottom: 28px; }
        .stat-box {
            flex: 1;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .stat-box.skipped { background-color: #fefce8; border-color: #fef08a; }
        .stat-number { font-size: 28px; font-weight: bold; color: #1d4ed8; }
        .stat-box.skipped .stat-number { color: #a16207; }
        .stat-label { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #374151;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e5e7eb;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 28px; font-size: 13px; }
        th {
            background-color: #f9fafb;
            text-align: left;
            padding: 8px 10px;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        td { padding: 9px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-waste { background-color: #fed7aa; color: #9a3412; }
        .badge-recycling { background-color: #bfdbfe; color: #1e40af; }
        .empty-note { color: #9ca3af; font-style: italic; font-size: 13px; margin-bottom: 28px; }
        .footer {
            background-color: #f3f4f6;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="header-logo">WasteFlow</div>
        </div>

        <div class="content">
            <h1 class="title">Recurring Orders Summary</h1>
            <p class="subtitle">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</p>

            {{-- Stats --}}
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number">{{ count($created) }}</div>
                    <div class="stat-label">Order{{ count($created) === 1 ? '' : 's' }} Created</div>
                </div>
                <div class="stat-box skipped">
                    <div class="stat-number">{{ count($skipped) }}</div>
                    <div class="stat-label">Already Existed</div>
                </div>
            </div>

            {{-- Created orders --}}
            <p class="section-title">Orders Created</p>
            @if(count($created) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Service Provider</th>
                            <th>Containers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($created as $row)
                        <tr>
                            <td style="font-family: monospace; font-size: 12px;">{{ $row['tracking_number'] }}</td>
                            <td>
                                <strong>{{ $row['company'] }}</strong>
                                @if($row['branch'])
                                    <br><span style="color:#6b7280;font-size:12px;">{{ $row['branch'] }}</span>
                                @endif
                                @if($row['site'])
                                    <br><span style="color:#9ca3af;font-size:12px;">{{ $row['site'] }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $row['order_type'] }}">
                                    {{ ucfirst($row['order_type']) }}
                                </span>
                            </td>
                            <td>{{ $row['service_provider'] }}</td>
                            <td style="font-size:12px;">{{ $row['containers'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="empty-note">No orders were created — no active templates matched today.</p>
            @endif

            {{-- Skipped --}}
            @if(count($skipped) > 0)
                <p class="section-title">Skipped (already existed)</p>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($skipped as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['company'] }}</strong>
                                @if($row['branch'])
                                    <br><span style="color:#6b7280;font-size:12px;">{{ $row['branch'] }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $row['order_type'] }}">
                                    {{ ucfirst($row['order_type']) }}
                                </span>
                            </td>
                            <td style="color:#6b7280;">Order already exists for {{ $date }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} WasteFlow. All rights reserved.</p>
            <p style="margin-top: 5px;">This is an automated email from the recurring orders scheduler.</p>
        </div>
    </div>
</body>
</html>
