<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WasteFlow Resource Intelligence Report — {{ $reportData['scopeDisplayName'] ?? $reportData['companyName'] }}</title>
    <style>
        @if(!empty($pdfFontRegularUrl))
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $pdfFontRegularUrl }}') format('truetype');
        }
        @endif
        @if(!empty($pdfFontBoldUrl))
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 700;
            src: url('{{ $pdfFontBoldUrl }}') format('truetype');
        }
        @endif
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #111827;
            line-height: 1.4;
            padding: 6mm 6mm;
        }
        @page { margin: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .border-sep { border-top: 4px solid #f3f4f6; }

        .report-header {
            background-color: #9AD993;
            text-align: center;
            padding: 14px 16px;
        }
        .report-header .scope-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        .report-header .report-title {
            font-size: 10px;
            font-weight: 600;
            color: #1f2937;
            margin-top: 4px;
        }
        .report-header .period { font-size: 9px; color: #374151; margin-top: 3px; }

        .summary-strip {
            font-size: 8.8px;
            font-weight: 600;
            color: #4b5563;
            border: 1px solid #e5e7eb;
            border-radius: 2px;
            background: #f9fafb;
            padding: 7px 10px;
            margin: 9px 0 10px;
        }

        .section-title-navy {
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            padding: 6px 8px;
            color: #fff;
            background: #1F3A5F;
        }
        .carbon-accounting-title {
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            color: #1F3A5F;
            padding: 4px 0 8px;
        }

        .env-page-header {
            background-color: #9AD993;
            text-align: center;
            padding: 12px 16px;
        }
        .env-page-header .t1 { font-size: 14px; font-weight: bold; color: #111827; }
        .env-page-header .t2 { font-size: 10px; font-weight: 600; color: #1f2937; margin-top: 2px; }
        .env-page-header .t3 { font-size: 9px; color: #374151; margin-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; font-size: 8px; }
        table.data thead th {
            background: #1F3A5F;
            color: #fff;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #1F3A5F;
            font-weight: 600;
        }
        table.data thead th.text-right, table.data td.text-right { text-align: right; }
        table.data tbody td {
            padding: 4px 6px;
            border: 1px solid #e5e7eb;
        }
        table.data tbody tr:nth-child(odd) { background: #fff; }
        table.data tbody tr:nth-child(even) { background: #f9fafb; }
        table.data tbody tr.total-row { background: #c9dde8; font-weight: bold; }
        table.data.subhead-commodity thead th { background: #4a7c9b; border-color: #3d6a86; }

        .pie-legend { font-size: 8.3px; line-height: 1.25; }
        .pie-legend--beside { vertical-align: middle; text-align: left; padding: 0 0 0 2px; }
        .pie-legend .row { margin-bottom: 2px; }
        .swatch { display: inline-block; width: 11px; height: 11px; border-radius: 1px; vertical-align: middle; margin-right: 5px; }
        .pie-with-legend { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 9px; }
        .pie-with-legend .pie-cell { width: 66.67%; vertical-align: middle; text-align: center; padding: 0; }
        .pie-with-legend .pie-legend-td { width: 33.33%; }
        .donut-block-bottom { width: 100%; border-collapse: collapse; border-spacing: 0; table-layout: fixed; margin-top: 6.5mm; }
        .donut-block-bottom tr.donut-row-2 .donut-cell { padding-top: 4mm; }
        .donut-block-bottom .donut-cell { padding: 0 2px 2px; }

        .donut-cell { text-align: center; vertical-align: top; width: 16.66%; padding: 2px; }
        .donut-class-title { font-size: 9.8px; font-weight: 700; color: #374151; margin-bottom: 3px; letter-spacing: 0.02em; }
        .donut-wrap { position: relative; width: 142px; height: 142px; margin: 0 auto 3px; }
        .donut-img { display: block; width: 142px; height: 142px; }
        .donut-center {
            position: absolute; left: 0; top: 0; width: 142px; height: 142px; line-height: 142px;
            text-align: center; font-size: 12px; font-weight: bold; color: #111827;
        }
        .donut-box {
            background: #f3f4f6;
            border-radius: 2px;
            padding: 6px 4px;
            margin-top: 1px;
            min-height: 56px;
            display: table;
            width: 100%;
        }
        .donut-box > div {
            display: block;
            width: 100%;
            text-align: center;
            white-space: normal;
            word-break: break-word;
        }
        .donut-box .lbl { font-size: 7.8px; color: #6b7280; line-height: 1.25; }
        .donut-box .val { font-size: 10.2px; font-weight: bold; color: #111827; line-height: 1.35; margin-top: 2px; }

        .landfill-cube { margin: 0 auto 3px; text-align: center; }
        .landfill-cube img { display: block; margin: 0 auto; width: 88px; height: auto; max-height: 92px; object-fit: contain; }
        .landfill-visual { width: 142px; height: 142px; margin: 0 auto 3px; text-align: center; }
        .landfill-visual .landfill-cube { margin-top: 2px; }
        .landfill-visual .landfill-label { font-size: 7.8px; color: #0f766e; font-weight: 600; margin-top: 2px; }

        .kpi-grid { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 6px 0; }
        .kpi-grid td { width: 25%; vertical-align: top; padding: 3px; }
        .kpi-card {
            border-radius: 4px; padding: 6px; text-align: center; min-height: 75px; font-size: 7px; border: 1px solid;
        }
        .kpi-card.blue   { background: #eff6ff; border-color: #bfdbfe; }
        .kpi-card.cyan   { background: #ecfeff; border-color: #a5f3fc; }
        .kpi-card.green  { background: #eff6ff; border-color: #bfdbfe; }
        .kpi-card.amber  { background: #fffbeb; border-color: #fde68a; }
        .kpi-card .kpi-ic { display: block; margin: 0 auto 3px; width: 20px; height: 20px; }
        .kpi-card .kpi-lbl { color: #4b5563; margin-bottom: 3px; line-height: 1.2; }
        .kpi-card .kpi-val { font-size: 10px; font-weight: bold; }
        .kpi-card.blue  .kpi-val { color: #1d4ed8; }
        .kpi-card.cyan  .kpi-val { color: #0891b2; }
        .kpi-card.green .kpi-val { color: #1d4ed8; }
        .kpi-card.amber .kpi-val { color: #b45309; }
        .kpi-hint { font-size: 6px; color: #6b7280; line-height: 1.2; margin-top: 2px; }

        .equiv-grid { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 6px; }
        .equiv-grid td { width: 33.33%; vertical-align: top; padding: 3px; }
        .equiv-card { border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px; text-align: center; font-size: 7px; }
        .equiv-card.indigo  { background: #eef2ff; border-color: #c7d2fe; }
        .equiv-card.orange  { background: #fff7ed; border-color: #fed7aa; }
        .equiv-card.violet  { background: #f5f3ff; border-color: #ddd6fe; }
        .equiv-card .eq-ic { display: block; margin: 0 auto 3px; width: 18px; height: 18px; }
        .equiv-card .eq-l { color: #6b7280; margin-bottom: 2px; line-height: 1.2; }
        .equiv-card .eq-v { font-weight: bold; font-size: 9px; }
        .equiv-card.indigo  .eq-v { color: #4338ca; }
        .equiv-card.orange  .eq-v { color: #c2410c; }
        .equiv-card.violet  .eq-v { color: #7c3aed; }

        .carbon-explain {
            border: 1px solid #93c5fd;
            background: #eff6ff;
            border-radius: 2px;
            padding: 8px 10px;
            font-size: 7.5px;
            line-height: 1.45;
            color: #1f2937;
            margin-top: 6px;
        }
        .carbon-explain strong { color: #111827; }
        .carbon-explain .num { color: #1d4ed8; font-weight: bold; }

        .methodology { background: #f9fafb; padding: 10px; font-size: 8.5px; color: #4b5563; line-height: 1.5; }
        .methodology h3 { font-size: 9px; color: #1F3A5F; margin-bottom: 5px; }
        .methodology p { margin-bottom: 4px; }
        .methodology .foot { border-top: 1px solid #d1d5db; margin-top: 5px; padding-top: 5px; font-weight: 600; color: #374151; }
        .methodology-logo { text-align: center; margin-top: 8px; padding-top: 6px; }
        .methodology-logo img { height: 36px; width: auto; max-width: 200px; }

        .cumulative-hdr { margin-top: 6px; margin-bottom: 10px; }
        .cumulative-dash { margin: 0; }
        .cumulative-hrow { width: 100%; border-collapse: collapse; margin: 0 0 4px; }
        .cumulative-hrow .lbl {
            width: 38%;
            font-size: 6.3px;
            line-height: 1.2;
            vertical-align: middle;
            color: #374151;
            padding: 0 3px 0 0;
        }
        .cumulative-hrow .barcell { vertical-align: middle; }
        .cumulative-hrow .track { height: 15px; background: #f3f4f6; border-radius: 0 2px 2px 0; }
        .cumulative-hrow .bar-table { width: 100%; height: 15px; border-collapse: collapse; }
        .cumulative-hrow .bar-table td { padding: 0; height: 15px; }
        .cumulative-hrow .valc {
            width: 16%;
            text-align: right;
            font-size: 7px;
            font-weight: 600;
            color: #111827;
            vertical-align: middle;
        }
        .methodology-merged { margin-top: 8px; padding-top: 8px; border-top: 4px solid #f3f4f6; }
        .equiv-section-title { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #374151; margin: 8px 0 2px; }
        .equiv-section-note { font-size: 6.5px; color: #6b7280; margin-bottom: 4px; }
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

        $reportLogoSrc = null;
        $logoPath = public_path('images/logo.png');
        if (is_file($logoPath)) {
            $reportLogoSrc = 'data:'.mime_content_type($logoPath).';base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $ct = $reportData['classificationTotals'] ?? [];
        $ei = $reportData['environmentalImpact'] ?? [];
        $summary = $reportData['summary'] ?? [];
        $grades = $reportData['grades'] ?? [];
        $materialsCO2eTotals = $reportData['materialsCO2eTotals'] ?? [];

        $gradeTotal = (float) ($grades['generalWaste'] ?? 0) + (float) ($grades['nonCompactableWaste'] ?? 0)
            + (float) ($grades['hazardousWaste'] ?? 0) + (float) ($grades['organicsRecovered'] ?? 0);

        $pieLegendRows = array_values(array_filter(
            $reportData['wasteStreamTotals'] ?? [],
            static fn (array $row): bool => ((float) ($row['value'] ?? 0)) > 0
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

        $allCommodities = collect($reportData['recyclingCommodities'] ?? [])
            ->merge($reportData['recyclingCommodities2'] ?? [])
            ->filter(static fn (array $c): bool => ! empty($c['name']) && (float) ($c['qty'] ?? 0) > 0)
            ->values();
        $nCommodities = $allCommodities->count();
        $half = $nCommodities > 0 ? (int) ceil($nCommodities / 2) : 0;
        $commodityCol1 = $allCommodities->slice(0, $half);
        $commodityCol2 = $allCommodities->slice($half);

        $landfillIconPath = public_path('images/landfill-space-avoided-icon.png');
        $landfillIconSrc = is_file($landfillIconPath)
            ? 'data:'.mime_content_type($landfillIconPath).';base64,'.base64_encode((string) file_get_contents($landfillIconPath))
            : null;

        $iconCloud = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>');
        $iconDroplet = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>');
        $iconTree = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 14 3 3.3a1 1 0 0 1-.7 1.7H4.7a1 1 0 0 1-.7-1.7L7 14h-.3a1 1 0 0 1-.7-1.7L9 9h-.2A1 1 0 0 1 8 7.3L12 3l4 4.3a1 1 0 0 1-.8 1.7H15l3 3.3a1 1 0 0 1-.7 1.7H17Z"/><path d="M12 22v-3"/></svg>');
        $iconZap = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>');
        $iconTruck = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>');
        $iconFuel = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 4 0v-6.998a2 2 0 0 0-.59-1.42L18 5"/><path d="M14 21V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16"/><path d="M2 21h13"/><path d="M3 9h11"/></svg>');
        $iconCar = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-2 2-1.5-3.7A2 2 0 0 0 15.646 5H8.4a2 2 0 0 0-1.903 1.257L5 10 3 8"/><path d="M7 14h.01"/><path d="M17 14h.01"/><rect width="18" height="8" x="3" y="10" rx="2"/><path d="M5 18v2"/><path d="M19 18v2"/></svg>');

        $cumulativeRows = $reportData['cumulativeImpact'] ?? [];
        $cumulativeValues = array_map(
            static fn (array $r): float => (float) ($r['value'] ?? 0),
            is_array($cumulativeRows) ? $cumulativeRows : []
        );
        $cumulativeAxisMax = 1.0;
        if ($cumulativeValues !== []) {
            $cumulativeAxisMax = (float) max($cumulativeValues);
            $cumulativeAxisMax = max(12000.0, ceil($cumulativeAxisMax * 1.1 / 1000) * 1000);
        }
        $cumulativeAxisTicks = [];
        for ($ti = 0; $ti < 5; $ti++) {
            $cumulativeAxisTicks[] = $ti / 4 * $cumulativeAxisMax;
        }
    @endphp

    {{-- Page 1: waste treatment (matches ResourceIntelligence page 1) --}}
    <div class="page">
        <div class="report-header">
            <div class="scope-name">{{ $reportData['scopeDisplayName'] ?? '—' }}</div>
            <div class="report-title">WasteFlow Resource Intelligence Report</div>
            <div class="period">{{ $reportData['reportingPeriodLabel'] ?? '' }}</div>
        </div>

        <div class="summary-strip">
            Summary of Waste Treatment Outputs and achievements at a glance (kg per waste category)
        </div>

        {{-- Main waste stream chart + legend side by side --}}
        <table class="pie-with-legend">
            <tr>
                <td class="pie-cell">
                    @if(!empty($chartDisplay['page1_waste_stream_pie']))
                        <img src="{{ $chartDisplay['page1_waste_stream_pie'] }}" alt="Waste streams" style="max-width:100%; max-height:430px;">
                    @endif
                </td>
                <td class="pie-legend pie-legend--beside pie-legend-td">
                    @forelse($pieLegendRows as $row)
                        <div class="row">
                            <span class="swatch" style="background-color:{{ $row['color'] ?? '#9AD993' }};"></span>
                            <strong>{{ $row['name'] }}</strong>
                            <span style="color:#6b7280;"> — {{ number_format($row['value'] ?? 0, 2) }} kg</span>
                        </div>
                    @empty
                        <div style="color:#9ca3af;">No data</div>
                    @endforelse
                </td>
            </tr>
        </table>

        {{-- Classification doughnuts + landfill (bottom of page 1) --}}
        <table class="donut-block-bottom">
            <tr>
                @foreach(['page1_donut_avoidance','page1_donut_recycling','page1_donut_recovery'] as $dk)
                    <td class="donut-cell" style="width:33.33%;">
                        <div class="donut-class-title">{{ $donutTitleByKey[$dk] }}</div>
                        <div class="donut-wrap">
                            @if(!empty($chartDisplay[$dk]))
                                <img class="donut-img" src="{{ $chartDisplay[$dk] }}" alt="">
                            @endif
                            <div class="donut-center">{{ number_format($donutPctByKey[$dk] ?? 0, 1) }}%</div>
                        </div>
                        <div class="donut-box">
                            @if($dk === 'page1_donut_avoidance')
                                <div class="lbl">Total Avoidance</div>
                                <div class="val">{{ number_format($ct['avoidance']['total'] ?? 0, 2) }} kg</div>
                            @elseif($dk === 'page1_donut_recycling')
                                <div class="lbl">Total Recycling</div>
                                <div class="val">{{ number_format($ct['recycling']['total'] ?? 0, 2) }} kg</div>
                            @else
                                <div class="lbl">Total Recovery</div>
                                <div class="val">{{ number_format($ct['recovery']['total'] ?? 0, 2) }} kg</div>
                            @endif
                        </div>
                    </td>
                @endforeach
            </tr>
            <tr class="donut-row-2">
                @foreach(['page1_donut_disposal','page1_donut_diverted'] as $dk)
                    <td class="donut-cell" style="width:33.33%;">
                        <div class="donut-class-title">{{ $donutTitleByKey[$dk] }}</div>
                        <div class="donut-wrap">
                            @if(!empty($chartDisplay[$dk]))
                                <img class="donut-img" src="{{ $chartDisplay[$dk] }}" alt="">
                            @endif
                            <div class="donut-center">{{ number_format($donutPctByKey[$dk] ?? 0, 1) }}%</div>
                        </div>
                        <div class="donut-box">
                            @if($dk === 'page1_donut_disposal')
                                <div class="lbl">Total Disposal</div>
                                <div class="val">{{ number_format($ct['disposal']['total'] ?? 0, 2) }} kg</div>
                            @else
                                <div class="lbl">Total Diverted</div>
                                <div class="val">{{ number_format($ct['diverted']['total'] ?? 0, 2) }} kg</div>
                            @endif
                        </div>
                    </td>
                @endforeach
                <td class="donut-cell" style="width:33.33%;">
                    <div class="donut-class-title">LANDFILL SAVED</div>
                    <div class="landfill-visual">
                        <div class="landfill-cube">
                            @if(!empty($landfillIconSrc))
                                <img src="{{ $landfillIconSrc }}" alt="" />
                            @endif
                        </div>
                        <div class="landfill-label">Landfill space avoided</div>
                    </div>
                    <div class="donut-box">
                        <div class="lbl">Total Landfill Space Saved</div>
                        <div class="val">{{ number_format($summary['landfillSpaceSaved'] ?? 0, 2) }} m³</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Page 2: recycling recovered + carbon accounting --}}
    <div class="page border-sep">
        <div class="report-header">
            <div class="scope-name">{{ $reportData['scopeDisplayName'] ?? '—' }}</div>
            <div class="report-title">WasteFlow Resource Intelligence Report</div>
            <div class="period">{{ $reportData['reportingPeriodLabel'] ?? '' }}</div>
        </div>

        <table class="data" style="width:100%; margin-top:4mm;">
            <thead>
                <tr>
                    <th>Grade</th>
                    <th class="text-right">Weight KGS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>General Waste</td>
                    <td class="text-right">{{ number_format($grades['generalWaste'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Non Compactable Waste</td>
                    <td class="text-right">{{ number_format($grades['nonCompactableWaste'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Hazardous Waste</td>
                    <td class="text-right">{{ number_format($grades['hazardousWaste'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Organics Recovered</td>
                    <td class="text-right">{{ number_format($grades['organicsRecovered'] ?? 0, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL WASTE</td>
                    <td class="text-right">{{ number_format($gradeTotal, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top:4px;">
            <div class="section-title-navy">RECYCLING RECOVERED</div>
            @if($commodityCol1->isEmpty() && $commodityCol2->isEmpty())
                <div style="border:1px solid #e5e7eb; border-top:0; padding:10px; text-align:center; color:#9ca3af; font-size:8px;">No recycling data for this period.</div>
            @else
                <table style="width:100%; border-collapse:collapse; border:1px solid #e5e7eb; border-top:0;">
                    <tr>
                        <td style="width:50%; vertical-align:top; padding:0; border-right:1px solid #e5e7eb;">
                            <table class="data subhead-commodity" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Commodity</th>
                                        <th class="text-right">QTY (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commodityCol1 as $c)
                                        <tr>
                                            <td>{{ $c['name'] }}</td>
                                            <td class="text-right" style="font-weight:600;">{{ number_format($c['qty'] ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td style="width:50%; vertical-align:top; padding:0;">
                            <table class="data subhead-commodity" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Commodity</th>
                                        <th class="text-right">QTY (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commodityCol2 as $c)
                                        <tr>
                                            <td>{{ $c['name'] }}</td>
                                            <td class="text-right" style="font-weight:600;">{{ number_format($c['qty'] ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            @endif
        </div>

        <div class="carbon-accounting-title" style="margin-top:10px;">Carbon Accounting Report</div>

        <table class="data">
            <thead>
                <tr>
                    <th>Material</th>
                    <th class="text-right">Weight (kg)</th>
                    <th class="text-right">Upstream (Scope 3) Avoided (kg CO₂e)</th>
                    <th class="text-right">Landfill Avoided (kg CO₂e)</th>
                    <th class="text-right">Total Lifecycle Avoided (kg CO₂e)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['materialsCO2e'] as $row)
                    <tr>
                        <td>{{ $row['material'] }}</td>
                        <td class="text-right">{{ number_format($row['weight'] ?? 0, 2) }}</td>
                        <td class="text-right">{{ number_format($row['scope3EF'] ?? 0, 2) }}</td>
                        <td class="text-right">{{ number_format($row['landfillAvoidanceEF'] ?? 0, 2) }}</td>
                        <td class="text-right" style="font-weight:600;">{{ number_format($row['lifecycleSaving'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>TOTALS</td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($materialsCO2eTotals['scope3EF'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($materialsCO2eTotals['landfillAvoidanceEF'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($materialsCO2eTotals['lifecycleSaving'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="carbon-explain">
            <p>
                <strong>Total Upstream (Scope 3) Avoided (kg CO₂e): </strong>
                <span class="num">{{ number_format($materialsCO2eTotals['scope3EF'] ?? 0, 2) }}</span>
                — Indirect carbon emissions avoided through diversion of waste to recycling streams.
            </p>
            <p>
                <strong>Total Landfill Emissions Avoided (kg CO₂e): </strong>
                <span class="num">{{ number_format($materialsCO2eTotals['landfillAvoidanceEF'] ?? 0, 2) }}</span>
                — Carbon emissions avoided by preventing waste from being disposed to landfill.
            </p>
            <p>
                <strong>Total Lifecycle Carbon Avoided (kg CO₂e): </strong>
                <span class="num">{{ number_format($materialsCO2eTotals['lifecycleSaving'] ?? 0, 2) }}</span>
                — Combined total of upstream and landfill emissions avoided.
            </p>
        </div>
    </div>

    {{-- Page 3: environmental impact (matches ResourceIntelligence) --}}
    <div class="page border-sep">
        <div class="env-page-header">
            <div class="t1">WasteFlow Resource Intelligence Report</div>
            <div class="t2">Environmental Impact &amp; Resource Saving</div>
            <div class="t3">{{ $reportData['reportingPeriodLabel'] ?? '' }}</div>
        </div>

        <div style="padding:8px 0 4px; font-size:9px; font-weight:600; color:#111827;">Environmental Impact &amp; Resource Savings</div>

        <table class="kpi-grid">
            <tr>
                <td>
                    <div class="kpi-card blue">
                        <img class="kpi-ic" src="{{ $iconCloud }}" alt="">
                        <div class="kpi-lbl">Total Lifecycle Carbon Avoided (kg CO₂e)</div>
                        <div class="kpi-val">{{ number_format($ei['co2Saved'] ?? 0, 0) }} kg CO₂e</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-card cyan">
                        <img class="kpi-ic" src="{{ $iconDroplet }}" alt="">
                        <div class="kpi-lbl">Water Saved</div>
                        <div class="kpi-val">{{ number_format($ei['waterSaved'] ?? 0, 0) }} kL</div>
                        <div class="kpi-hint">Water Savings (kL – Recycling vs Virgin Production)</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-card green">
                        <img class="kpi-ic" src="{{ $iconTree }}" alt="">
                        <div class="kpi-lbl">Trees Saved</div>
                        <div class="kpi-val">{{ number_format($ei['treesSaved'] ?? 0, 0) }} trees</div>
                        <div class="kpi-hint">Trees saved – Paper based tree conversion</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-card amber">
                        <img class="kpi-ic" src="{{ $iconZap }}" alt="">
                        <div class="kpi-lbl">Electricity Equivalent (kWh – SA Grid)</div>
                        <div class="kpi-val">{{ number_format($ei['electricityEquivalentKwhSaGrid'] ?? 0, 0) }} kWh</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="equiv-section-title">Carbon Equivalency Indicators</div>
        <div class="equiv-section-note">From lifecycle saving, SA factors — see docs/Dashboard &amp; Reports - Metrics</div>

        <table class="equiv-grid">
            <tr>
                <td>
                    <div class="equiv-card indigo">
                        <img class="eq-ic" src="{{ $iconTruck }}" alt="">
                        <div class="eq-l">Transport Equivalent (km Avoided)</div>
                        <div class="eq-v">{{ number_format($ei['transportEquivalentKm'] ?? 0, 0) }} km</div>
                    </div>
                </td>
                <td>
                    <div class="equiv-card orange">
                        <img class="eq-ic" src="{{ $iconFuel }}" alt="">
                        <div class="eq-l">Fuel Equivalent (L Petrol Avoided)</div>
                        <div class="eq-v">{{ number_format($ei['fuelEquivalentLitresPetrol'] ?? 0, 0) }} L</div>
                    </div>
                </td>
                <td>
                    <div class="equiv-card violet">
                        <img class="eq-ic" src="{{ $iconCar }}" alt="">
                        <div class="eq-l">Cars Off the Road (Annual Equiv.)</div>
                        <div class="eq-v">{{ number_format($ei['carsOffRoadAnnualEquivalent'] ?? 0, 1) }} cars</div>
                    </div>
                </td>
            </tr>
        </table>

        @if(!empty($cumulativeRows))
            <div class="cumulative-dash">
                <div class="cumulative-hdr">
                    <div class="section-title-navy" style="margin-top:4px;">CUMULATIVE IMPACT DASHBOARD</div>
                </div>
                @foreach($cumulativeRows as $cRow)
                    @php
                        $cVal = (float) ($cRow['value'] ?? 0);
                        $cMax = $cumulativeAxisMax > 0 ? $cumulativeAxisMax : 1.0;
                        $cPct = min(100, max(0, ($cVal / $cMax) * 100));
                        $cRest = 100 - $cPct;
                        $cColor = $cRow['color'] ?? '#6b7280';
                    @endphp
                    <table class="cumulative-hrow" style="table-layout:fixed;">
                        <tr>
                            <td class="lbl" style="width:38%;">{{ $cRow['name'] ?? '' }}</td>
                            <td class="barcell" style="width:46%;">
                                <div class="track">
                                    <table class="bar-table" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="width:{{ number_format($cPct, 2, '.', '') }}%; background:{{ $cColor }}; border-radius:0 2px 2px 0;">&#8203;</td>
                                            <td style="width:{{ number_format($cRest, 2, '.', '') }}%; background:transparent;">&#8203;</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td class="valc" style="width:16%;">{{ number_format($cVal, 0) }}</td>
                        </tr>
                    </table>
                @endforeach
                <table style="width:100%; table-layout:fixed; margin:2px 0 0; font-size:5.5px; color:#6b7280;">
                    <tr>
                        <td style="width:38%;">&#8203;</td>
                        <td style="width:46%;">
                            <table style="width:100%; table-layout:fixed;">
                                <tr>
                                    @foreach($cumulativeAxisTicks as $t)
                                        <td style="text-align:center;">{{ number_format($t, 0) }}</td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                        <td style="width:16%;">&#8203;</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="methodology methodology-merged">
            <h3>Methodology &amp; Data Sources</h3>
            <p>This report has been prepared using verified waste data collected on-site and processed through the WasteFlow Resource Intelligence Portal.</p>
            <p>Carbon emission factors and environmental impact calculations are aligned with internationally recognised methodologies, including the UK Department for Environment, Food &amp; Rural Affairs (DEFRA) greenhouse gas conversion factors and industry-standard lifecycle datasets.</p>
            <p>All carbon calculations (CO₂e) are aligned with the Greenhouse Gas (GHG) Protocol, with a focus on Scope 3 emissions avoided through recycling, material recovery, and landfill diversion.</p>
            <p>Data is supported by operational records, collection data and verified waste streams ensuring a consistent and transparent reporting framework.</p>
            <div class="foot">
                All reported environmental metrics have been calculated using DEFRA-aligned emission factors in accordance with GHG Protocol best practice, international standards and applicable South African sustainability and reporting frameworks.
            </div>
            @if(!empty($reportLogoSrc))
                <div class="methodology-logo">
                    <img src="{{ $reportLogoSrc }}" alt="WasteFlow" />
                </div>
            @endif
        </div>
    </div>
</body>
</html>
