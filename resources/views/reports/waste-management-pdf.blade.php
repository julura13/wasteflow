<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waste Management Report - {{ $reportData['companyName'] }}</title>
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
            line-height: 1.4;
        }
        @page {
            margin: 15mm;
        }
        .page {
            page-break-after: always;
            margin-bottom: 20px;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .page-header {
            background-color: #1e3a5f;
            color: white;
            padding: 15px 20px;
            text-align: center;
        }
        .page-header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .page-header .company {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .page-header .date {
            font-size: 11px;
        }
        .env-impact {
            background-color: #1e3a5f;
            padding: 15px;
            display: table;
            width: 100%;
        }
        .env-icon {
            display: table-cell;
            text-align: center;
            color: white;
            width: 33.33%;
            vertical-align: top;
        }
        .env-icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 8px;
            background-color: #3b82f6;
            border: 3px solid #3b82f6;
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 24px;
        }
        .env-icon-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .env-icon-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 3px;
        }
        .content-grid {
            display: table;
            width: 100%;
            padding: 15px;
        }
        .table-section {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .chart-section {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 10px;
        }
        table thead {
            background-color: #4a7c9b;
            color: white;
        }
        table thead th {
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #2c5a7a;
            font-weight: 600;
        }
        table tbody td {
            padding: 4px;
            border: 1px solid #ddd;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tbody tr.total-row {
            background-color: #c9dde8;
            font-weight: bold;
        }
        .summary-box {
            border: 2px solid #1e3a5f;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 10px;
        }
        .summary-box tbody tr {
            background-color: #c9dde8;
        }
        .summary-box tbody tr.organics {
            background-color: #a3e635;
        }
        .summary-box tbody tr.diverted {
            background-color: #3b82f6;
            color: white;
        }
        .chart-container {
            text-align: center;
            margin-bottom: 15px;
        }
        .chart-container img {
            max-width: 100%;
            height: auto;
        }
        .footer {
            background: white;
            border-top: 2px solid #3b82f6;
            padding: 15px;
            text-align: center;
        }
        .footer h2 {
            color: #3b82f6;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        .footer p {
            color: #3b82f6;
            font-size: 11px;
            font-style: italic;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            color: #4b5563;
            text-align: center;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    @php
        // Helper function to convert image URL to base64 for PDF
        function imageToBase64($url) {
            if (empty($url)) return null;
            
            try {
                // If it's already a full URL, download it
                if (strpos($url, 'http') === 0) {
                    $imageData = @file_get_contents($url);
                    if ($imageData) {
                        return 'data:image/png;base64,' . base64_encode($imageData);
                    }
                }
                
                // If it's a local path
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
        
        // Convert chart paths to base64
        $chartBase64 = [];
        foreach ($chartPaths as $key => $path) {
            $chartBase64[$key] = imageToBase64($path);
        }
        
        // Logo
        $logoPath = public_path('images/wasteflow-logo.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoMime = mime_content_type($logoPath);
            $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        }
    @endphp

    <!-- Page 1 -->
    <div class="page">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div class="env-impact">
            <div class="env-icon">
                <div class="env-icon-circle">🌳</div>
                <div class="env-icon-label">Trees Saved</div>
                <div class="env-icon-value">{{ $reportData['environmentalImpact']['treesSaved'] }}</div>
            </div>
            <div class="env-icon">
                <div class="env-icon-circle">⚡</div>
                <div class="env-icon-label">Energy Saved</div>
                <div class="env-icon-value">{{ number_format($reportData['environmentalImpact']['energySaved']) }}</div>
            </div>
            <div class="env-icon">
                <div class="env-icon-circle">💧</div>
                <div class="env-icon-label">Litres of Water Saved</div>
                <div class="env-icon-value">{{ number_format($reportData['environmentalImpact']['waterSaved'], 2) }}</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="table-section">
                <table>
                    <thead>
                        <tr>
                            <th>GRADE</th>
                            <th style="text-align: right;">WEIGHT KGS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>General Waste</td>
                            <td style="text-align: right;">{{ $reportData['grades']['generalWaste'] }}</td>
                        </tr>
                        <tr>
                            <td>Non Compactable Waste</td>
                            <td style="text-align: right;">{{ $reportData['grades']['nonCompactableWaste'] }}</td>
                        </tr>
                        <tr>
                            <td>Hazardous Waste</td>
                            <td style="text-align: right;">{{ $reportData['grades']['hazardousWaste'] }}</td>
                        </tr>
                        <tr>
                            <td>Organics Recovered</td>
                            <td style="text-align: right;">{{ $reportData['grades']['organicsRecovered'] }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>TOTAL WASTE</td>
                            <td style="text-align: right;">
                                {{ $reportData['grades']['generalWaste'] + $reportData['grades']['nonCompactableWaste'] + $reportData['grades']['hazardousWaste'] + $reportData['grades']['organicsRecovered'] }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table>
                    <thead>
                        <tr>
                            <th colspan="4" style="text-align: center;">RECYCLING RECOVERED</th>
                        </tr>
                        <tr>
                            <th>Commodity</th>
                            <th style="text-align: right;">QTY</th>
                            <th>Commodity</th>
                            <th style="text-align: right;">QTY</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['recyclingCommodities'] as $index => $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td style="text-align: right;">{{ $item['qty'] }}</td>
                            <td>{{ $reportData['recyclingCommodities2'][$index]['name'] ?? '' }}</td>
                            <td style="text-align: right;">{{ $reportData['recyclingCommodities2'][$index]['qty'] ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="summary-box">
                    <table>
                        <tbody>
                            <tr>
                                <td style="font-weight: 600;">Recycling Recovered</td>
                                <td style="text-align: right; font-weight: bold;">{{ $reportData['summary']['recyclingRecovered'] }}</td>
                            </tr>
                            <tr class="organics">
                                <td style="font-weight: 600;">Organics Recovered</td>
                                <td style="text-align: right; font-weight: bold;">{{ $reportData['summary']['organicsRecovered'] }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Total Incoming Waste</td>
                                <td style="text-align: right; font-weight: bold;">{{ $reportData['summary']['totalIncomingWaste'] }}</td>
                            </tr>
                            <tr class="diverted">
                                <td style="font-weight: 600;">Diverted From Landfill</td>
                                <td style="text-align: right; font-weight: bold;">{{ number_format($reportData['summary']['divertedFromLandfill'], 2) }}%</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Landfill Space Saved M<sup>3</sup></td>
                                <td style="text-align: right; font-weight: bold;">{{ $reportData['summary']['landfillSpaceSaved'] }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Lifecycle Saving (kg CO<sub>2</sub>e)</td>
                                <td style="text-align: right; font-weight: bold;">{{ $reportData['summary']['lifecycleSaving'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-section">
                @if($logoBase64)
                <div class="chart-container">
                    <img src="{{ $logoBase64 }}" alt="WasteFlow Logo" style="max-height: 150px;">
                </div>
                @endif
                @if(isset($chartBase64['page1_pie']) && $chartBase64['page1_pie'])
                <div class="chart-container">
                    <img src="{{ $chartBase64['page1_pie'] }}" alt="Waste Distribution Pie Chart" style="max-width: 100%;">
                </div>
                @endif
            </div>
        </div>

        <div class="footer">
            <h2>WASTEFLOW</h2>
            <p>Sustainable Waste Management</p>
        </div>
    </div>

    <!-- Page 2: Materials CO2e Table -->
    <div class="page">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 15px;">
            <table>
                <thead>
                    <tr>
                        <th>Material</th>
                        <th style="text-align: right;">Weight</th>
                        <th style="text-align: right;">Scope 3 EF (kg CO₂e/kg)²</th>
                        <th style="text-align: right;">Landfill Avoidance EF (kg CO₂e/kg)³</th>
                        <th style="text-align: right;">Other Offsets (kg CO₂e)</th>
                        <th style="text-align: right;">Lifecycle Saving (kg CO₂e)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['materialsCO2e'] as $material)
                        <tr>
                            <td>{{ $material['material'] }}</td>
                            <td style="text-align: right;">{{ number_format($material['weight'], 0) }}</td>
                            <td style="text-align: right;">{{ number_format($material['scope3EF'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format($material['landfillAvoidanceEF'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format($material['otherOffsets'], 2) }}</td>
                            <td style="text-align: right; font-weight: 600;">{{ number_format($material['lifecycleSaving'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" style="font-weight: bold;">TOTALS</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($reportData['materialsCO2eTotals']['scope3EF'], 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($reportData['materialsCO2eTotals']['landfillAvoidanceEF'], 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($reportData['materialsCO2eTotals']['otherOffsets'], 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($reportData['materialsCO2eTotals']['lifecycleSaving'], 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="summary-box" style="margin-top: 20px;">
                <h3 style="text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 10px; padding-top: 10px;">Summary</h3>
                <div style="padding: 0 15px 15px; font-size: 10px;">
                    <p style="margin-bottom: 8px;">
                        <strong>Scope 3 CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['scope3EF'], 2) }}</strong>
                        Indirect Carbon Emissions from Sending Waste Generated for Recyclable
                    </p>
                    <p style="margin-bottom: 8px;">
                        <strong>Landfill Avoidance CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['landfillAvoidanceEF'], 2) }}</strong>
                        Carbon Emission Savings Due to Landfill Avoidance
                    </p>
                    <p style="margin-bottom: 8px;">
                        <strong>Other Offsets CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['otherOffsets'], 2) }}</strong>
                        Reference only (not included in Lifecycle Saving).
                    </p>
                    <p style="margin-bottom: 8px;">
                        <strong>Lifecycle Saving CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['lifecycleSaving'], 2) }}</strong>
                        Overall carbon benefit from managing all materials sustainably.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Page 3: Charts -->
    <div class="page">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 15px;">
            @if(isset($chartBase64['page3_stacked']) && $chartBase64['page3_stacked'])
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="font-size: 11px; margin-bottom: 8px;">(kg CO₂e)</p>
                <img src="{{ $chartBase64['page3_stacked'] }}" alt="Stacked Bar Chart" style="max-width: 100%;">
            </div>
            @endif

            @if(isset($chartBase64['page3_single']) && $chartBase64['page3_single'])
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="font-weight: bold; font-size: 13px; margin-bottom: 10px;">Total Carbon Emissions Avoided in KM</h3>
                <img src="{{ $chartBase64['page3_single'] }}" alt="Carbon Emissions Chart" style="max-width: 100%;">
                <div style="margin-top: 8px;">
                    <span style="font-size: 28px; font-weight: bold; color: #3b82f6;">{{ number_format($reportData['carbonEmissionsAvoided']) }}</span>
                </div>
            </div>
            @endif

            <div style="margin-top: 20px; font-size: 10px;">
                <p style="margin-bottom: 8px;">
                    The CO₂e saved by your recycling and organics recovery is equivalent to a car driving roughly
                    <strong>{{ number_format($reportData['carbonEmissionsAvoided']) }}</strong> km.
                </p>
                <p>
                    By diverting waste from landfill and recycling efficiently, your operations are actively preventing CO₂e from entering the atmosphere.
                </p>
            </div>
        </div>
    </div>

    <!-- Page 4: Donut Charts -->
    <div class="page">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 15px;">
            @if(isset($chartBase64['page4_cumulative']) && $chartBase64['page4_cumulative'])
            <div style="margin-bottom: 40px;">
                <h3 class="section-title">CUMULATIVE IMPACT DASHBOARD</h3>
                <div style="text-align: center; margin-bottom: 15px; font-size: 9px;">
                    @foreach($reportData['cumulativeImpact'] as $item)
                    <span style="margin: 0 8px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background-color: {{ $item['color'] }}; margin-right: 4px; vertical-align: middle;"></span>
                        {{ $item['name'] }}
                    </span>
                    @endforeach
                </div>
                <img src="{{ $chartBase64['page4_cumulative'] }}" alt="Cumulative Impact Chart" style="max-width: 100%;">
            </div>
            @endif

            @if(isset($chartBase64['page4_recycling']) && $chartBase64['page4_recycling'])
            <div>
                <h3 class="section-title">RECYCLING BREAKDOWN</h3>
                <img src="{{ $chartBase64['page4_recycling'] }}" alt="Recycling Breakdown Chart" style="max-width: 100%;">
                <div style="text-align: center; margin-top: 15px; font-size: 9px;">
                    @foreach($reportData['recyclingBreakdown'] as $item)
                    <span style="margin: 0 8px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background-color: {{ $item['color'] }}; margin-right: 4px; vertical-align: middle;"></span>
                        {{ $item['name'] }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Page 5: Gauge and Waste vs Recovery -->
    <div class="page">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 15px;">
            <div style="margin-bottom: 40px;">
                <h3 class="section-title">DIVERTED FROM LANDFILL</h3>
                <div style="text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #1e3a5f;">
                        {{ number_format($reportData['summary']['divertedFromLandfill'], 1) }}%
                    </div>
                </div>
            </div>

            @if(isset($chartBase64['page5_waste_recovery']) && $chartBase64['page5_waste_recovery'])
            <div>
                <h3 class="section-title">WASTE vs RECOVERY</h3>
                <img src="{{ $chartBase64['page5_waste_recovery'] }}" alt="Waste vs Recovery Chart" style="max-width: 100%;">
            </div>
            @endif
        </div>
    </div>
</body>
</html>
