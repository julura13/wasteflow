<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consolidated Order - {{ $serviceProvider->name }} - {{ $collectionDate->format('d-m-Y') }}</title>
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
            padding: 30px 40px;
            margin: 0;
        }
        .header {
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 15px;
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
        .header-title {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: auto;
        }
        .header-title h1 {
            color: #dc2626;
            font-size: 16px;
            margin: 0;
        }
        .collection-date {
            color: #dc2626;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .service-provider {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .consolidated-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .consolidated-table th {
            background-color: #f3f4f6;
            color: #dc2626;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #d1d5db;
        }
        .consolidated-table td {
            padding: 10px;
            border: 1px solid #d1d5db;
            font-size: 10px;
        }
        .consolidated-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .special-instructions {
            font-weight: bold;
            color: #dc2626;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 9px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="header-logo">
                <img src="{{ asset('images/logo.png') }}" alt="WasteFlow Logo">
            </div>
            <div class="header-title">
                <h1>Consolidated Waste Collection Order</h1>
            </div>
        </div>
        <div class="collection-date">
            Collection Date: {{ $collectionDate->format('d-m-Y') }}
        </div>
        <div class="service-provider">
            Service Provider: {{ $serviceProvider->name }}
        </div>
    </div>

    @if($orders->count() > 0)
        <table class="consolidated-table">
            <thead>
                <tr>
                    <th>Site Name</th>
                    <th>REL Skip</th>
                    <th>No of Wheelie Bins</th>
                    <th>Skips / 30M3</th>
                    <th>Special Instructions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $quantityTypes = [
                        // Waste types
                        'rel_skip' => 'REL Skip',
                        'wheelie_bins' => 'Wheelie Bins',
                        'skips_30m2' => 'Skips / 30M3',
                        // Recycling types
                        'scrap_load' => 'Scrap Load',
                        'loose_bags' => 'Loose Bags',
                        'cage_8m3' => '8m³ Cage',
                        'cage_20m3' => '20m³ Cage',
                        'other' => 'Other'
                    ];
                @endphp
                @foreach($orders as $order)
                    @php
                        $site = $order->site ?? null;
                        $quantityLines = $order->quantity_lines ?? [];
                        $orderType = $order->order_type ?? 'waste';
                        
                        // Extract quantities by type
                        $relSkip = 0;
                        $wheelieBins = 0;
                        $skips30m3 = 0;
                        $recyclingContainers = [];
                        
                        if (!empty($quantityLines) && is_array($quantityLines)) {
                            foreach ($quantityLines as $line) {
                                $type = $line['quantity_type'] ?? '';
                                $quantity = $line['quantity'] ?? 0;
                                
                                if ($type === 'rel_skip') {
                                    $relSkip = $quantity;
                                } elseif ($type === 'wheelie_bins') {
                                    $wheelieBins = $quantity;
                                } elseif ($type === 'skips_30m2') {
                                    $skips30m3 = $quantity;
                                } elseif ($orderType === 'recycling') {
                                    // For recycling orders, collect container info
                                    $typeLabel = $quantityTypes[$type] ?? ucfirst(str_replace('_', ' ', $type));
                                    if ($type === 'other' && !empty($line['description'] ?? '')) {
                                        $typeLabel .= ' (' . $line['description'] . ')';
                                    }
                                    $recyclingContainers[] = $quantity . ' x ' . $typeLabel;
                                }
                            }
                        }
                        
                        // Get special instructions from notes
                        $specialInstructions = $order->notes ?? '';
                        if ($orderType === 'recycling' && !empty($recyclingContainers)) {
                            $specialInstructions = (!empty($specialInstructions) ? $specialInstructions . ' | ' : '') . implode(', ', $recyclingContainers);
                        }
                    @endphp
                    <tr>
                        <td>
                            @if($site)
                                {{ optional(optional($site->branch)->company)->name ?? '' }}<br>
                                {{ optional($site->branch)->name ?? '' }}<br>
                                {{ $site->name ?? '' }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $orderType === 'waste' ? ($relSkip > 0 ? $relSkip : '') : '' }}</td>
                        <td>{{ $orderType === 'waste' ? ($wheelieBins > 0 ? $wheelieBins : '') : '' }}</td>
                        <td>{{ $orderType === 'waste' ? ($skips30m3 > 0 ? $skips30m3 : '') : '' }}</td>
                        <td class="{{ !empty($specialInstructions) ? 'special-instructions' : '' }}">
                            {{ $specialInstructions }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            No orders found for the selected date and service provider.
        </div>
    @endif

    <div class="footer">
        <p>This is an official consolidated order form generated on {{ now()->format('d M Y, H:i') }}</p>
        <p>Collection Date: {{ $collectionDate->format('d-m-Y') }} | Service Provider: {{ $serviceProvider->name }}</p>
    </div>
</body>
</html>

