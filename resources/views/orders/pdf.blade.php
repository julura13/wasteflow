<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Form - {{ $order->tracking_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.2;
            padding: 20px 30px;
            margin: 0;
        }
        @page {
            margin: 15mm;
            padding: 0;
        }
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 10px;
        }
        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .header-logo {
            display: table-cell;
            vertical-align: middle;
            width: auto;
        }
        .header-logo img {
            max-height: 70px;
            padding: 8px;
            width: auto;
            background-color: rgb(34, 74, 64);
        }
        .header-logo-text {
            max-height: 70px;
            padding: 8px;
            width: auto;
            background-color: rgb(34, 74, 64);
            color: white;
            font-weight: bold;
            display: inline-block;
            font-size: 16px;
        }
        .header-title {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: auto;
        }
        .header-title h1 {
            color: #2563eb;
            font-size: 14px;
            margin: 0;
        }
        .header-info {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        .header-info-left {
            display: table-cell;
            width: 100%;
            vertical-align: top;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            background-color: #f3f4f6;
            padding: 8px;
            font-weight: bold;
            font-size: 12px;
            color: #2563eb;
            border-left: 4px solid #2563eb;
            margin-bottom: 6px;
        }
        .info-block {
            margin-bottom: 12px;
            padding: 4px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .info-block-title {
            font-weight: bold;
            font-size: 11px;
            color: #2563eb;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-item {
            /* margin-bottom: 4px; */
            padding: 2px 0;
        }
        .info-item-label {
            font-weight: bold;
            display: inline-block;
            width: 140px;
        }
        .info-item-value {
            display: inline-block;
        }
        .quantity-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .quantity-table th {
            background-color: #2563eb;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        .quantity-table td {
            padding: 8px;
            border: 1px solid #e5e7eb;
        }
        .quantity-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .notes {
            background-color: #f9fafb;
            padding: 15px;
            border-left: 4px solid #2563eb;
            margin-top: 15px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 9px;
            page-break-inside: avoid;
        }
        .section {
            page-break-inside: avoid;
            margin-bottom: 10px;
        }
        .info-block {
            page-break-inside: avoid;
        }
        .quantity-table {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="header-logo">
                @php
                    $logoPath = public_path('images/logo.png');
                    if (file_exists($logoPath) && is_readable($logoPath)) {
                        $logoData = base64_encode(file_get_contents($logoPath));
                        $logoMime = mime_content_type($logoPath);
                        $logoBase64 = 'data:' . $logoMime . ';base64,' . $logoData;
                    } else {
                        $logoBase64 = null;
                    }
                @endphp
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="WasteFlow Logo" style="max-height: 70px; padding: 8px; width: auto; background-color: rgb(34, 74, 64);">
                @else
                    <div class="header-logo-text">WasteFlow</div>
                @endif
            </div>
            <div class="header-title">
                <h1>Waste Collection Order Form</h1>
            </div>
        </div>
        <div class="header-info">
            <div class="header-info-left">
                <strong>Tracking Number:</strong> {{ $order->tracking_number }}<br>
                <strong>Order Date:</strong> {{ $order->created_at->format(\App\Support\DisplayDate::CALENDAR_DATETIME) }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Collection Details</div>
        
        @php
            $company = $order->site?->branch?->company ?? $order->company ?? null;
            $branch = $order->site?->branch ?? $order->branch ?? null;
            $site = $order->site ?? null;
        @endphp

        <!-- Company Information -->
        @if($company)
        <div class="info-block">
            <!-- <div class="info-block-title"></div> -->
            <div class="info-item">
                <span class="info-item-label">Company name:</span>
                <span class="info-item-value">{{ $company->name ?? 'N/A' }}</span>
            </div>
            @if($company->registration_number)
            <div class="info-item">
                <span class="info-item-label">Registration Number:</span>
                <span class="info-item-value">{{ $company->registration_number }}</span>
            </div>
            @endif
            @if($company->address)
            <div class="info-item">
                <span class="info-item-label">Address:</span>
                <span class="info-item-value">{{ $company->address }}</span>
            </div>
            @endif
            @if($company->contact_person)
            <div class="info-item">
                <span class="info-item-label">Contact Person:</span>
                <span class="info-item-value">{{ $company->contact_person }}</span>
            </div>
            @endif
            @if($company->phone)
            <div class="info-item">
                <span class="info-item-label">Phone:</span>
                <span class="info-item-value">{{ $company->phone }}</span>
            </div>
            @endif
            @if($company->email)
            <div class="info-item">
                <span class="info-item-label">Email:</span>
                <span class="info-item-value">{{ $company->email }}</span>
            </div>
            @endif

            <!-- Branch Information -->
        @if($branch)
            <!-- <div class="info-block-title">Branch</div> -->
            <div class="info-item">
                <span class="info-item-label">Branch name:</span>
                <span class="info-item-value">{{ $branch->name ?? 'N/A' }}</span>
            </div>
            @if($branch->address)
            <div class="info-item">
                <span class="info-item-label">Address:</span>
                <span class="info-item-value">{{ $branch->address }}</span>
            </div>
            @endif
            @if($branch->contact_person)
            <div class="info-item">
                <span class="info-item-label">Contact Person:</span>
                <span class="info-item-value">{{ $branch->contact_person }}</span>
            </div>
            @endif
            @if($branch->phone)
            <div class="info-item">
                <span class="info-item-label">Phone:</span>
                <span class="info-item-value">{{ $branch->phone }}</span>
            </div>
            @endif
            @if($branch->email)
            <div class="info-item">
                <span class="info-item-label">Email:</span>
                <span class="info-item-value">{{ $branch->email }}</span>
            </div>
            @endif
        @endif
        </div>
        @endif

        

        <!-- Site Information -->
        @if($site)
        <div class="info-block">
            <!-- <div class="info-block-title">Collection Site</div> -->
            <div class="info-item">
                <span class="info-item-label">Site name:</span>
                <span class="info-item-value">{{ $site->name ?? 'N/A' }}</span>
            </div>
            @if($site->address)
            <div class="info-item">
                <span class="info-item-label">Address:</span>
                <span class="info-item-value">{{ $site->address }}</span>
            </div>
            @endif
            @if($site->contact_person)
            <div class="info-item">
                <span class="info-item-label">Contact Person:</span>
                <span class="info-item-value">{{ $site->contact_person }}</span>
            </div>
            @endif
            @if($site->phone)
            <div class="info-item">
                <span class="info-item-label">Phone:</span>
                <span class="info-item-value">{{ $site->phone }}</span>
            </div>
            @endif
            @if($site->email)
            <div class="info-item">
                <span class="info-item-label">Email:</span>
                <span class="info-item-value">{{ $site->email }}</span>
            </div>
            @endif
            @if($site->latitude && $site->longitude)
            <div class="info-item">
                <span class="info-item-label">GPS Coordinates:</span>
                <span class="info-item-value">{{ number_format($site->latitude, 6) }}, {{ number_format($site->longitude, 6) }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Service Provider and Collection Dates -->
        <div class="info-block">
            <div class="info-block-title" style="font-weight: bold; font-size: 11px; color: #2563eb; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb;">Collection Information</div>
            <div class="info-item">
                <span class="info-item-label">Service Provider:</span>
                <span class="info-item-value">{{ $order->serviceProvider->name ?? $order->service_provider ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-item-label">Requested Collection Date:</span>
                <span class="info-item-value">{{ $order->requested_collection_date ? $order->requested_collection_date->format(\App\Support\DisplayDate::CALENDAR) : 'N/A' }}</span>
            </div>
            @if($order->actual_collection_date)
            <div class="info-item">
                <span class="info-item-label">Actual Collection Date:</span>
                <span class="info-item-value">{{ $order->actual_collection_date->format(\App\Support\DisplayDate::CALENDAR) }}</span>
            </div>
            @endif
        </div>
    </div>

    @if($order->order_type === 'recycling' && $order->waste_type)
    <div class="section">
        <div class="section-title">Material Information</div>
        <div class="info-block">
            <div class="info-item">
                <span class="info-item-label">Material Type:</span>
                <span class="info-item-value">{{ $order->waste_type }}</span>
            </div>
        </div>
    </div>
    @endif

    <div class="section">
        <!-- <div class="section-title">Container Quantities</div> -->
        <table class="quantity-table">
            <thead>
                <tr>
                    <th>Container Type</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $quantityTypes = [
                        // Waste types
                        'rel_skip' => 'REL Skip',
                        'wheelie_bins' => 'Wheelie Bins',
                        'skips_30m2' => '30m² Skips',
                        // Recycling types
                        'scrap_load' => 'Scrap Load',
                        'loose_bags' => 'Loose Bags',
                        'cage_8m3' => '8m³ Cage',
                        'cage_20m3' => '20m³ Cage',
                        'other' => 'Other'
                    ];
                    $quantityLines = $order->quantity_lines ?? [];
                    $totalContainers = 0;
                @endphp
                    @if(!empty($quantityLines) && is_array($quantityLines))
                    @foreach($quantityLines as $line)
                        @php
                            $totalContainers += $line['quantity'] ?? 0;
                            // quantity_lines use container_option_name; legacy recycling may still have quantity_type
                            $typeLabel = $line['container_option_name'] ?? ($quantityTypes[$line['quantity_type'] ?? ''] ?? ucfirst(str_replace('_', ' ', $line['quantity_type'] ?? '')));
                            if (($line['quantity_type'] ?? '') === 'other' && !empty($line['description'] ?? '')) {
                                $typeLabel .= ' (' . $line['description'] . ')';
                            }
                        @endphp
                        <tr>
                            <td>{{ $typeLabel }}</td>
                            <td>{{ $line['quantity'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" style="text-align: center; color: #6b7280;">No quantity information available</td>
                    </tr>
                @endif
                @if($totalContainers > 0)
                <tr style="background-color: #dbeafe; font-weight: bold;">
                    <td>Total Containers:</td>
                    <td>{{ $totalContainers }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        @if($order->estimated_quantity)
        <div style="margin-top: 10px;">
            <strong>Estimated Quantity:</strong> {{ $order->estimated_quantity }}
        </div>
        @endif
        @if($order->actual_quantity)
        <div style="margin-top: 5px;">
            <strong>Actual Quantity:</strong> {{ $order->actual_quantity }}
        </div>
        @endif
    </div>

    @if($order->slip_number)
    <div class="section">
        <div class="section-title">Collection Information</div>
        <div class="info-block">
            <div class="info-item">
                <span class="info-item-label">Slip Number:</span>
                <span class="info-item-value">{{ $order->slip_number }}</span>
            </div>
        </div>
    </div>
    @endif

    @if($order->waste_streams && $order->waste_streams->count() > 0)
    <div class="section">
        <div class="section-title">Waste Streams & Weights</div>
        <table class="quantity-table">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Weight (kg)</th>
                    @if($order->order_type === 'recycling')
                    <th>Rebate</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($order->waste_streams as $stream)
                <tr>
                    <td>
                        {{ $stream->material->grade->name ?? '—' }} - {{ $stream->material->waste_stream->name ?? '—' }}
                    </td>
                    <td>{{ number_format($stream->nett_weight ?? $stream->gross_weight ?? 0, 3) }}</td>
                    @if($order->order_type === 'recycling')
                    <td>
                        @if($stream->material->rebate_offered && $stream->material->rebate_rate)
                            @php
                                $companyRebatePercentage = null;
                                
                                if ($order->status === 'finalized' && $order->company_rebate_percentage !== null) {
                                    $companyRebatePercentage = $order->company_rebate_percentage;
                                } else {
                                    $company = $order->site->branch->company ?? null;
                                    $companyRebatePercentage = $company->rebate_percentage ?? null;
                                }
                                
                                if ($companyRebatePercentage !== null && $companyRebatePercentage !== '') {
                                    $clientShare = $companyRebatePercentage;
                                } else {
                                    $clientShare = $stream->material->client_rebate_share ?? 100;
                                }
                            @endphp
                            R{{ number_format(($stream->nett_weight ?? 0) * $stream->material->rebate_rate * $clientShare / 100, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
                @if($order->order_type === 'recycling' && $order->total_rebate)
                <tr style="background-color: #dbeafe; font-weight: bold;">
                    <td>Total Rebate:</td>
                    <td colspan="2">R{{ number_format($order->total_rebate, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    @if($order->notes)
    <div class="section">
        <div><strong>Additional Notes:</strong></div>
        <div style="border: 1px solid lightgray; border-radius: 4px; padding: 4px; margin-top: 10px;">
            {{ $order->notes }}
        </div>
    </div>
    @endif

    <div class="section">
        <div class="info-block">
            <div class="info-item">
                <span class="info-item-label">Created By:</span>
                <span class="info-item-value">{{ $order->creator->name ?? 'N/A' }}</span>
            </div>
            @if($order->status)
            <div class="info-item">
                <span class="info-item-label">Status:</span>
                <span class="info-item-value">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>This is an official order form generated on {{ now()->format(\App\Support\DisplayDate::CALENDAR_DATETIME) }}</p>
        <p>Order Tracking Number: {{ $order->tracking_number }}</p>
    </div>
</body>
</html>

