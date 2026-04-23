<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WasteFlow Resource Intelligence Report — {{ $reportData['companyName'] }}</title>
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
            line-height: 1.35;
            padding: 10mm 8mm;
        }
        @page {
            margin: 0;
        }
        .page {
            page-break-after: always;
            margin-bottom: 0;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .wc-header {
            background-color: #9AD993;
            color: #0d2b1f;
            padding: 16px 20px;
            text-align: center;
        }
        .wc-header .scope-line {
            font-size: 9px;
            font-weight: 600;
            color: #1a3d2e;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .wc-header .title {
            font-size: 17px;
            font-weight: bold;
            color: #0d2b1f;
            letter-spacing: 0.2px;
            margin-bottom: 6px;
            line-height: 1.25;
        }
        .wc-header .subtitle {
            font-size: 9px;
            color: #1e5c3a;
            margin-bottom: 4px;
        }
        .wc-header .period {
            font-size: 9px;
            color: #1a3d2e;
            font-weight: normal;
        }
        .subheader {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 6px 12px;
            text-align: center;
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 0.2px;
            margin-top: 1px;
        }
        .section-heading {
            font-size: 8px;
            font-weight: bold;
            color: #374151;
            margin: 10px 0 6px;
            text-align: center;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 8px;
        }
        table.data thead {
            background-color: #4a7c9b;
            color: white;
        }
        table.data thead th {
            padding: 5px 4px;
            text-align: left;
            border: 1px solid #2c5a7a;
            font-weight: 600;
        }
        table.data tbody td {
            padding: 3px 4px;
            border: 1px solid #ddd;
        }
        table.data tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        table.data tbody tr.total-row {
            background-color: #c9dde8;
            font-weight: bold;
        }
        .summary-box {
            border: 1px solid #1d4ed8;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }
        .summary-box tbody tr {
            background-color: #d1fae5;
        }
        .summary-box tbody tr.organics {
            background-color: #bfdbfe;
        }
        .summary-box tbody tr.diverted {
            background-color: #3b82f6;
            color: white;
        }
        .chart-container {
            text-align: center;
            margin-bottom: 6px;
        }
        .chart-container img {
            max-width: 100%;
            height: auto;
        }
        .donut-cell {
            text-align: center;
            vertical-align: top;
            width: 16.66%;
            padding: 3px 2px;
        }
        .donut-cell img {
            max-width: 76px;
            height: auto;
        }
        .donut-class-title {
            font-size: 6px;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }
        .donut-wrap {
            position: relative;
            width: 76px;
            height: 76px;
            margin: 0 auto 3px;
        }
        .donut-img {
            display: block;
            width: 76px;
            height: 76px;
        }
        .donut-center {
            position: absolute;
            left: 0;
            top: 0;
            width: 76px;
            height: 76px;
            line-height: 76px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            color: #111827;
        }
        .donut-caption-label {
            font-size: 7px;
            font-weight: bold;
            color: #111827;
            line-height: 1.25;
        }
        .donut-caption-value {
            font-size: 7px;
            font-weight: 700;
            color: #1f2937;
            margin-top: 2px;
            line-height: 1.2;
        }
        .donut-wrap-diversion {
            position: relative;
            width: 86px;
            height: 86px;
            margin: 0 auto;
        }
        .donut-wrap-diversion .donut-img {
            width: 86px;
            height: 86px;
        }
        .donut-wrap-diversion .donut-center {
            width: 86px;
            height: 86px;
            line-height: 86px;
            font-size: 10px;
        }
        .landfill-icon-wrap {
            margin: 0 auto 4px;
            width: 48px;
            height: 52px;
        }
        .landfill-icon-wrap svg {
            display: block;
            width: 48px;
            height: 52px;
        }
        .pie-row-chart {
            width: 52%;
            vertical-align: middle;
            text-align: center;
            padding: 6px 8px 6px 0;
        }
        .pie-row-chart img {
            max-width: 100%;
            max-height: 260px;
            height: auto;
        }
        .pie-row-legend {
            width: 0%;
            vertical-align: middle;
            padding: 6px 0 6px 8px;
        }
        .pie-legend-title {
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 6px;
            text-align: left;
        }
        .pie-legend-list {
            list-style: none;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.35;
        }
        .pie-legend-list li {
            margin-bottom: 4px;
            display: table;
            width: 100%;
        }
        .pie-legend-swatch {
            display: table-cell;
            width: 12px;
            vertical-align: top;
            padding-top: 2px;
        }
        .pie-legend-swatch span {
            display: block;
            width: 8px;
            height: 8px;
            border-radius: 1px;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .pie-legend-text {
            display: table-cell;
            padding-left: 6px;
            vertical-align: top;
        }
        .pie-legend-name {
            font-weight: 600;
            color: #111827;
        }
        .pie-legend-kg {
            color: #4b5563;
        }
        .pie-legend-pct {
            color: #6b7280;
            font-weight: 500;
        }
        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            table-layout: fixed;
        }
        .kpi-grid td {
            vertical-align: top;
            padding: 6px 4px;
            width: 25%;
        }
        .kpi-card {
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px 6px;
            text-align: center;
            min-height: 90px;
        }
        .kpi-card.blue { background-color: #eff6ff; border-color: #bfdbfe; }
        .kpi-card.cyan { background-color: #ecfeff; border-color: #a5f3fc; }
        .kpi-card.green { background-color: #eff6ff; border-color: #bfdbfe; }
        .kpi-card.amber { background-color: #fffbeb; border-color: #fde68a; }
        .kpi-card .kpi-icon {
            display: block;
            margin: 0 auto 5px;
            width: 22px;
            height: 22px;
        }
        .kpi-card .kpi-label {
            font-size: 7px;
            color: #4b5563;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .kpi-card .kpi-value {
            font-size: 11px;
            font-weight: bold;
        }
        .kpi-card.blue .kpi-value { color: #1d4ed8; }
        .kpi-card.cyan .kpi-value { color: #0891b2; }
        .kpi-card.green .kpi-value { color: #1d4ed8; }
        .kpi-card.amber .kpi-value { color: #b45309; }
        .equiv-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: fixed;
        }
        .equiv-grid td {
            width: 33.33%;
            vertical-align: top;
            padding: 4px;
        }
        .equiv-card {
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px 6px;
            text-align: center;
            font-size: 8px;
        }
        .equiv-card.indigo { background-color: #eef2ff; border-color: #c7d2fe; }
        .equiv-card.orange { background-color: #fff7ed; border-color: #fed7aa; }
        .equiv-card.violet { background-color: #f5f3ff; border-color: #ddd6fe; }
        .equiv-card .equiv-icon {
            display: block;
            margin: 0 auto 4px;
            width: 18px;
            height: 18px;
        }
        .equiv-card .equiv-label {
            color: #6b7280;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        .equiv-card .equiv-value {
            font-weight: bold;
            font-size: 10px;
        }
        .equiv-card.indigo .equiv-value { color: #4338ca; }
        .equiv-card.orange .equiv-value { color: #c2410c; }
        .equiv-card.violet .equiv-value { color: #7c3aed; }
        .methodology {
            margin-top: 12px;
            font-size: 8px;
            color: #374151;
            line-height: 1.45;
        }
        .methodology h3 {
            font-size: 9px;
            margin-bottom: 6px;
            color: #111827;
        }
        .methodology p {
            margin-bottom: 5px;
        }
        .methodology strong.lead {
            display: block;
            margin-top: 6px;
            font-size: 8px;
        }
        .footer {
            background: white;
            border-top: 2px solid #3b82f6;
            padding: 8px;
            text-align: center;
            margin-top: 8px;
        }
        .footer h2 {
            color: #3b82f6;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .footer p {
            color: #3b82f6;
            font-size: 9px;
            font-style: italic;
        }
        .note-foot {
            text-align: center;
            font-size: 7px;
            color: #6b7280;
            margin-top: 6px;
        }
        table.recycling-compact {
            font-size: 6.5px;
        }
        table.recycling-compact thead th,
        table.recycling-compact tbody td {
            padding: 2px 3px;
        }
    </style>
</head>
<body>
    @php
        function imageToBase64($url) {
            if (empty($url)) {
                return null;
            }

            try {
                if (strpos($url, 'http') === 0) {
                    $imageData = @file_get_contents($url);
                    if ($imageData) {
                        return 'data:image/png;base64,' . base64_encode($imageData);
                    }
                }

                $path = public_path(parse_url($url, PHP_URL_PATH));
                if (file_exists($path)) {
                    $imageData = file_get_contents($path);
                    $mimeType = mime_content_type($path);

                    return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }
            } catch (\Exception $e) {
                return null;
            }

            return null;
        }

        $chartDisplay = [];
        foreach ($chartPaths as $key => $path) {
            $chartDisplay[$key] = imageToBase64($path);
        }

        $ct = $reportData['classificationTotals'] ?? [];
        $ei = $reportData['environmentalImpact'] ?? [];

        $pieLegendRows = array_values(array_filter(
            $reportData['wasteStreamTotals'] ?? [],
            static fn (array $row): bool => ((float) ($row['value'] ?? 0)) > 0
        ));
        $pieTotalKg = array_sum(array_map(
            static fn (array $row): float => (float) ($row['value'] ?? 0),
            $pieLegendRows
        ));

        $donutPctByKey = [
            'page1_donut_avoidance' => (float) ($ct['avoidance']['percentage'] ?? 0),
            'page1_donut_recycling' => (float) ($ct['recycling']['percentage'] ?? 0),
            'page1_donut_recovery' => (float) ($ct['recovery']['percentage'] ?? 0),
            'page1_donut_disposal' => (float) ($ct['disposal']['percentage'] ?? 0),
            'page1_donut_diverted' => (float) ($ct['diverted']['percentage'] ?? 0),
        ];

        $donutTitleByKey = [
            'page1_donut_avoidance' => 'AVOIDANCE',
            'page1_donut_recycling' => 'RECYCLING',
            'page1_donut_recovery' => 'RECOVERY',
            'page1_donut_disposal' => 'DISPOSAL',
            'page1_donut_diverted' => 'DIVERTED',
        ];
    @endphp

    <!-- Page 1: Dashboard-style treatment summary -->
    <div class="page">
        <div class="wc-header">
            @foreach($reportData['reportLocationLines'] ?? [] as $line)
                <div class="scope-line">{{ $line }}</div>
            @endforeach
            <div class="title">WasteFlow Resource Intelligence Report</div>
{{--            <div class="subtitle">Carbon accounting report</div>--}}
            <div class="period">{{ $reportData['reportingPeriodLabel'] ?? '' }}</div>
        </div>

        <div class="subheader">Summary of waste treatment outputs and achievements at a glance (kg per waste category)</div>

        <table style="width:100%; border-collapse:collapse; margin-top:8px; table-layout:fixed; border-bottom: 1px solid #ccc;">
            <tr>
                <td class="pie-row-chart">
                    @if(!empty($chartDisplay['page1_waste_stream_pie']))
                        <img src="{{ $chartDisplay['page1_waste_stream_pie'] }}" alt="Waste streams by category">
                    @endif
                </td>
                <td class="pie-row-legend">
                    <div class="pie-legend-title">By waste stream (kg)</div>
                    <ul class="pie-legend-list">
                        @forelse($pieLegendRows as $row)
                            <li>
                                <span class="pie-legend-swatch"><span style="background-color:{{ $row['color'] ?? '#828282' }};"></span></span>
                                <span class="pie-legend-text">
                                    <span class="pie-legend-name">{{ $row['name'] }}</span>
                                    <span class="pie-legend-kg"> — {{ number_format($row['value'], 2) }} kg</span>@if($pieTotalKg > 0)<span class="pie-legend-pct"> ({{ number_format($row['value'] / $pieTotalKg * 100, 1) }}%)</span>@else<span class="pie-legend-pct"> (0.0%)</span>@endif
                                </span>
                            </li>
                        @empty
                            <li><span class="pie-legend-text pie-legend-name">No data</span></li>
                        @endforelse
                    </ul>
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse:collapse; margin-top:10px; table-layout:fixed;">
            <tr>
                @foreach(['page1_donut_avoidance','page1_donut_recycling','page1_donut_recovery','page1_donut_disposal','page1_donut_diverted'] as $dk)
                    <td class="donut-cell">
                        <div class="donut-class-title">{{ $donutTitleByKey[$dk] ?? '' }}</div>
                        <div class="donut-wrap">
                            @if(!empty($chartDisplay[$dk]))
                                <img class="donut-img" src="{{ $chartDisplay[$dk] }}" alt="">
                            @endif
                            <div class="donut-center">{{ number_format($donutPctByKey[$dk] ?? 0, 1) }}%</div>
                        </div>
                        @if($dk === 'page1_donut_avoidance')
                            <div class="donut-caption-label">Total avoidance</div>
                            <div class="donut-caption-value">{{ number_format($ct['avoidance']['total'] ?? 0, 2) }} kg</div>
                        @elseif($dk === 'page1_donut_recycling')
                            <div class="donut-caption-label">Total recycling</div>
                            <div class="donut-caption-value">{{ number_format($ct['recycling']['total'] ?? 0, 2) }} kg</div>
                        @elseif($dk === 'page1_donut_recovery')
                            <div class="donut-caption-label">Total recovery</div>
                            <div class="donut-caption-value">{{ number_format($ct['recovery']['total'] ?? 0, 2) }} kg</div>
                        @elseif($dk === 'page1_donut_disposal')
                            <div class="donut-caption-label">Total disposal</div>
                            <div class="donut-caption-value">{{ number_format($ct['disposal']['total'] ?? 0, 2) }} kg</div>
                        @else
                            <div class="donut-caption-label">Total diverted</div>
                            <div class="donut-caption-value">{{ number_format($ct['diverted']['total'] ?? 0, 2) }} kg</div>
                        @endif
                    </td>
                @endforeach
                <td class="donut-cell">
                    @php
                        $cubeSvg = '<svg viewBox="0 0 88 96" xmlns="http://www.w3.org/2000/svg" fill="none"><path d="M44 6 78 26v38L44 84 10 64V26L44 6Z" fill="#ccfbf1" stroke="#0f766e" stroke-width="2.25" stroke-linejoin="round"/><path d="M10 26 44 46l34-20" stroke="#0f766e" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/><path d="M44 46v38" stroke="#0f766e" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                        $cubeSrc = 'data:image/svg+xml;base64,' . base64_encode($cubeSvg);
                    @endphp
                    <div class="donut-class-title" style="color:#374151; letter-spacing:0.06em;">LANDFILL SAVED</div>
                    <div class="landfill-icon-wrap">
                        <img src="{{ $cubeSrc }}" width="48" height="52" alt="">
                    </div>
                    <div class="donut-caption-label" style="color:#0f766e;">Landfill space avoided</div>
                    <div class="donut-caption-label" style="margin-top:3px; color:#6b7280; font-size:6px; font-weight:600;">Total landfill space saved</div>
                    <div class="donut-caption-value" style="font-size:9px; color:#111827;">{{ number_format($reportData['summary']['landfillSpaceSaved'] ?? 0, 2) }} m³</div>
                </td>
            </tr>
        </table>

        <p class="note-foot">Classification splits match the dashboard (material classification). All environmental equivalencies use industry-standard factors and South African benchmarks.</p>

        <div class="section-heading">Waste breakdown &amp; diversion from landfill</div>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:38%; vertical-align:top; padding-right:6px;">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Grade</th>
                                <th style="text-align:right;">Weight (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>General Waste</td>
                                <td style="text-align:right;">{{ number_format($reportData['grades']['generalWaste'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Non Compactable Waste</td>
                                <td style="text-align:right;">{{ number_format($reportData['grades']['nonCompactableWaste'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Hazardous Waste</td>
                                <td style="text-align:right;">{{ number_format($reportData['grades']['hazardousWaste'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Organics Recovered</td>
                                <td style="text-align:right;">{{ number_format($reportData['grades']['organicsRecovered'], 2) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td>Total waste</td>
                                <td style="text-align:right;">
                                    {{ number_format(
                                        $reportData['grades']['generalWaste']
                                        + $reportData['grades']['nonCompactableWaste']
                                        + $reportData['grades']['hazardousWaste']
                                        + $reportData['grades']['organicsRecovered'],
                                        2
                                    ) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width:32%; vertical-align:middle; text-align:center;">
                    @if(!empty($chartDisplay['page1_diversion_donut']))
                        <div class="donut-wrap-diversion">
                            <img class="donut-img" src="{{ $chartDisplay['page1_diversion_donut'] }}" alt="Diversion">
                            <div class="donut-center">{{ number_format($reportData['summary']['divertedFromLandfill'] ?? 0, 1) }}%</div>
                        </div>
                    @endif
                </td>
                <td style="width:30%; vertical-align:middle; text-align:center;">
                    <div style="font-size:11px; font-weight:bold; color:#1d4ed8; line-height:1.2;">Total diversion from landfill</div>
                    <div style="font-size:22px; font-weight:bold; color:#1e3a8a; margin-top:6px;">{{ number_format($reportData['summary']['divertedFromLandfill'], 1) }}%</div>
                </td>
            </tr>
        </table>

        <div class="summary-box">
            <table class="data" style="margin:0;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;">Recycling recovered</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['summary']['recyclingRecovered'], 2) }}</td>
                    </tr>
                    <tr class="organics">
                        <td style="font-weight:600;">Organics recovered</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['summary']['organicsRecovered'], 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;">Total incoming waste</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['summary']['totalIncomingWaste'], 2) }}</td>
                    </tr>
                    <tr class="diverted">
                        <td style="font-weight:600;">Diverted from landfill</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['summary']['divertedFromLandfill'], 2) }}%</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;">Landfill space saved (m³)</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['summary']['landfillSpaceSaved'], 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;">Total lifecycle carbon avoided (kg CO₂e)</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['summary']['lifecycleSaving'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Page 2: Environmental impact + carbon materials table -->
    <div class="page">
        <div class="wc-header">
            @foreach($reportData['reportLocationLines'] ?? [] as $line)
                <div class="scope-line">{{ $line }}</div>
            @endforeach
            <div class="title">WasteFlow Resource Intelligence Report</div>
            <div class="subtitle">Environmental impact &amp; resource savings</div>
            <div class="period">{{ $reportData['reportingPeriodLabel'] ?? '' }}</div>
        </div>

        <table class="data recycling-compact">
            <thead>
                <tr>
                    <th colspan="4" style="text-align:center;">Recycling recovered</th>
                </tr>
                <tr>
                    <th>Commodity</th>
                    <th style="text-align:right;">Qty</th>
                    <th>Commodity</th>
                    <th style="text-align:right;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['recyclingCommodities'] as $index => $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td style="text-align:right;">{{ $item['qty'] }}</td>
                        <td>{{ $reportData['recyclingCommodities2'][$index]['name'] ?? '' }}</td>
                        <td style="text-align:right;">{{ $reportData['recyclingCommodities2'][$index]['qty'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $iconCloud = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>');
            $iconDroplet = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>');
            $iconTree = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 14 3 3.3a1 1 0 0 1-.7 1.7H4.7a1 1 0 0 1-.7-1.7L7 14h-.3a1 1 0 0 1-.7-1.7L9 9h-.2A1 1 0 0 1 8 7.3L12 3l4 4.3a1 1 0 0 1-.8 1.7H15l3 3.3a1 1 0 0 1-.7 1.7H17Z"/><path d="M12 22v-3"/></svg>');
            $iconZap = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>');
            $iconTruck = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>');
            $iconFuel = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/></svg>');
            $iconCar = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-2 2-1.5-3.7A2 2 0 0 0 15.646 5H8.4a2 2 0 0 0-1.903 1.257L5 10 3 8"/><path d="M7 14h.01"/><path d="M17 14h.01"/><rect width="18" height="8" x="3" y="10" rx="2"/><path d="M5 18v2"/><path d="M19 18v2"/></svg>');
        @endphp

        <table class="kpi-grid">
            <tr>
                <td>
                    <div class="kpi-card blue">
                        <img class="kpi-icon" src="{{ $iconCloud }}" alt="">
                        <div class="kpi-label">Total lifecycle carbon avoided (kg CO₂e)</div>
                        <div class="kpi-value">{{ number_format($reportData['summary']['lifecycleSaving'] ?? 0, 0) }} kg CO₂e</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-card cyan">
                        <img class="kpi-icon" src="{{ $iconDroplet }}" alt="">
                        <div class="kpi-label">Water saved (kL)</div>
                        <div class="kpi-value">{{ number_format($ei['waterSaved'] ?? 0, 2) }} kL</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-card green">
                        <img class="kpi-icon" src="{{ $iconTree }}" alt="">
                        <div class="kpi-label">Trees saved</div>
                        <div class="kpi-value">{{ number_format($ei['treesSaved'] ?? 0, 2) }} trees</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-card amber">
                        <img class="kpi-icon" src="{{ $iconZap }}" alt="">
                        <div class="kpi-label">Electricity equivalent (kWh – SA grid)</div>
                        <div class="kpi-value">{{ number_format($ei['electricityEquivalentKwhSaGrid'] ?? 0, 0) }} kWh</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="equiv-grid">
            <tr>
                <td>
                    <div class="equiv-card indigo">
                        <img class="equiv-icon" src="{{ $iconTruck }}" alt="">
                        <div class="equiv-label">Transport equivalent (km avoided)</div>
                        <div class="equiv-value">{{ number_format($ei['transportEquivalentKm'] ?? 0, 2) }} km</div>
                    </div>
                </td>
                <td>
                    <div class="equiv-card orange">
                        <img class="equiv-icon" src="{{ $iconFuel }}" alt="">
                        <div class="equiv-label">Fuel equivalent (L petrol avoided)</div>
                        <div class="equiv-value">{{ number_format($ei['fuelEquivalentLitresPetrol'] ?? 0, 2) }} L</div>
                    </div>
                </td>
                <td>
                    <div class="equiv-card violet">
                        <img class="equiv-icon" src="{{ $iconCar }}" alt="">
                        <div class="equiv-label">Cars off the road (annual equiv.)</div>
                        <div class="equiv-value">{{ number_format($ei['carsOffRoadAnnualEquivalent'] ?? 0, 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div style="padding: 8px 0;">
            <table class="data">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th style="text-align:right;">Weight (kg)</th>
                        <th style="text-align:right;">Upstream (Scope 3) avoided (kg CO₂e)</th>
                        <th style="text-align:right;">Landfill avoided (kg CO₂e)</th>
                        <th style="text-align:right;">Lifecycle carbon avoided (kg CO₂e)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['materialsCO2e'] as $material)
                        <tr>
                            <td>{{ $material['material'] }}</td>
                            <td style="text-align:right;">{{ number_format($material['weight'], 0) }}</td>
                            <td style="text-align:right;">{{ number_format($material['scope3EF'], 2) }}</td>
                            <td style="text-align:right;">{{ number_format($material['landfillAvoidanceEF'], 2) }}</td>
                            <td style="text-align:right; font-weight:600;">{{ number_format($material['lifecycleSaving'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" style="font-weight:bold;">Totals</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['materialsCO2eTotals']['scope3EF'], 2) }}</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['materialsCO2eTotals']['landfillAvoidanceEF'], 2) }}</td>
                        <td style="text-align:right; font-weight:bold;">{{ number_format($reportData['materialsCO2eTotals']['lifecycleSaving'], 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="summary-box" style="border-color:#1e3a5f;">
                <table class="data" style="margin:0;">
                    <tbody>
                        <tr>
                            <td colspan="2" style="padding:8px; font-size:8px;">
                                <strong>Total upstream (Scope 3) avoided (kg CO₂e)</strong>
                                {{ number_format($reportData['materialsCO2eTotals']['scope3EF'], 2) }}
                                — indirect emissions avoided from sending materials for recycling.
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:8px; font-size:8px;">
                                <strong>Total landfill emissions avoided (kg CO₂e)</strong>
                                {{ number_format($reportData['materialsCO2eTotals']['landfillAvoidanceEF'], 2) }}
                                — savings from diverting material from landfill.
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:8px; font-size:8px;">
                                <strong>Total lifecycle carbon avoided (kg CO₂e)</strong>
                                {{ number_format($reportData['materialsCO2eTotals']['lifecycleSaving'], 2) }}
                                — sum of upstream and landfill avoided.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Page 3: Charts + methodology -->
    <div class="page">
        <div class="wc-header">
            @foreach($reportData['reportLocationLines'] ?? [] as $line)
                <div class="scope-line">{{ $line }}</div>
            @endforeach
            <div class="title">WasteFlow Resource Intelligence Report</div>
            <div class="subtitle">Cumulative impact &amp; methodology</div>
            <div class="period">{{ $reportData['reportingPeriodLabel'] ?? '' }}</div>
        </div>

        @if(!empty($chartDisplay['page3_stacked']))
            <div class="chart-container" style="margin-top:8px;">
                <img src="{{ $chartDisplay['page3_stacked'] }}" alt="Carbon split">
            </div>
        @endif

        @if(!empty($chartDisplay['page3_cumulative']))
            <div class="section-heading">Cumulative impact dashboard</div>
            <div class="chart-container">
                <img src="{{ $chartDisplay['page3_cumulative'] }}" alt="Cumulative impact">
            </div>
            <div style="text-align:center; font-size:7px; margin-bottom:8px;">
                @foreach($reportData['cumulativeImpact'] as $item)
                    <span style="margin:0 6px;">
                        <span style="display:inline-block;width:8px;height:8px;background-color:{{ $item['color'] }};vertical-align:middle;margin-right:2px;"></span>
                        {{ $item['name'] }}
                    </span>
                @endforeach
            </div>
        @endif

        @if(!empty($chartDisplay['page3_recycling']))
            <div class="section-heading">Recycling breakdown (% of recycling weight by category)</div>
            <div class="chart-container">
                <img src="{{ $chartDisplay['page3_recycling'] }}" alt="Recycling breakdown">
            </div>
            <div style="text-align:center; font-size:7px;">
                @foreach($reportData['recyclingBreakdown'] as $item)
                    <span style="margin:0 6px;">
                        <span style="display:inline-block;width:8px;height:8px;background-color:{{ $item['color'] }};vertical-align:middle;margin-right:2px;"></span>
                        {{ $item['name'] }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="methodology">
            <h3>Methodology &amp; data sources</h3>
            <p>This report has been prepared using verified waste data collected on-site and processed through the WasteFlow Resource Intelligence portal.</p>
            <p>Carbon emission factors and environmental impact calculations are aligned with internationally recognised methodologies, including the UK Department for Environment, Food &amp; Rural Affairs (DEFRA) greenhouse gas conversion factors and industry-standard lifecycle datasets.</p>
            <p>All carbon calculations (CO₂e) follow the Greenhouse Gas (GHG) Protocol, with emphasis on Scope 3 emissions avoided through recycling, material recovery, and landfill diversion.</p>
            <p>Data is supported by operational records, collection data, and verified waste streams, ensuring a consistent and transparent reporting framework.</p>
            <strong class="lead">All reported environmental metrics use DEFRA-aligned emission factors in accordance with GHG Protocol best practice, international standards, and applicable South African sustainability and reporting frameworks.</strong>
        </div>

        <div class="footer">
            <h2>WASTEFLOW</h2>
            <p>Because waste deserves leadership</p>
        </div>
    </div>
</body>
</html>
