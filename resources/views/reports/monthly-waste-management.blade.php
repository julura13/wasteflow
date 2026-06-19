<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waste Management Report - {{ $company->name }} - {{ $month->format(\App\Support\DisplayDate::CALENDAR) }}</title>
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
            margin: 15mm 20mm;
        }
        body {
            padding: 0 10px;
        }
        .page {
            min-height: 250mm;
            page-break-inside: avoid;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .page-break {
            page-break-before: always;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 24px;
            color: #2563eb;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .header .company-name {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        .header .month {
            font-size: 14px;
            color: #666;
        }
        .metrics-grid {
            width: 100%;
            margin: 40px 0;
            overflow: hidden;
        }
        .metric-box-wrapper {
            width: 33.33%;
            float: left;
            text-align: center;
            padding: 0 10px;
            box-sizing: border-box;
        }
        .metric-box {
            width: 100px;
            height: 100px;
            text-align: center;
            padding: 15px;
            background-color: #1e3a5f;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: inline-block;
            box-sizing: border-box;
        }
        .metric-icon {
            width: 45px;
            height: 45px;
            background-color: #2563eb;
            border-radius: 50%;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 45px;
            color: white;
            font-size: 28px;
            text-align: center;
        }
        .metric-label {
            font-size: 9px;
            color: #ffffff;
            margin-top: 8px;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
        }
        .metric-value {
            font-size: 26px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 3px;
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9px;
        }
        table {
            font-size: 11px;
        }
        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #2563eb !important;
            color: white !important;
            font-weight: bold !important;
            font-size: 12px !important;
        }
        table td {
            font-size: 11px;
        }
        table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .summary-box {
            background-color: #eff6ff;
            border: 2px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .summary-box h3 {
            color: #2563eb;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .summary-row {
            width: 100%;
            margin: 8px 0;
            overflow: hidden;
            clear: both;
        }
        .summary-label {
            width: 60%;
            font-weight: bold;
            float: left;
        }
        .summary-value {
            width: 40%;
            text-align: right;
            font-size: 12px;
            float: right;
        }
        .carbon-table {
            font-size: 11px;
        }
        .carbon-table th {
            background-color: #2563eb !important;
            color: white !important;
            font-weight: bold !important;
            font-size: 12px !important;
            padding: 10px !important;
        }
        .carbon-table td {
            padding: 8px;
            font-size: 11px;
        }
        .chart-placeholder {
            width: 100%;
            height: 200px;
            background-color: #f3f4f6;
            border: 2px dashed #d1d5db;
            margin: 20px 0;
            color: #9ca3af;
            font-size: 12px;
            text-align: center;
            padding-top: 80px;
        }
        .pie-chart-container {
            width: 100%;
            margin: 20px 0;
            text-align: center;
        }
        .pie-chart {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        .pie-segment {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            clip-path: polygon(50% 50%, 50% 0%, 100% 0%, 100% 50%);
        }
        .pie-legend {
            margin-top: 15px;
        }
        .pie-legend-item {
            font-size: 8px;
            margin: 3px 0;
            text-align: left;
        }
        .pie-legend-color {
            width: 10px;
            height: 10px;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
        }
        .footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            page-break-inside: avoid;
        }
        .footer-text {
            font-size: 10px;
            color: #2563eb;
            font-weight: bold;
            margin-top: 5px;
        }
        .two-column {
            width: 100%;
            overflow: hidden;
        }
        .column {
            width: 48%;
            padding: 0 1%;
            float: left;
            box-sizing: border-box;
        }
        .large-number {
            font-size: 36px;
            font-weight: bold;
            color: #2563eb;
            text-align: center;
            margin: 20px 0;
        }
        .progress-bar {
            width: 100%;
            height: 40px;
            background-color: #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
            margin: 20px 0;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background-color: #2563eb;
            color: white;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            padding-top: 10px;
        }
    </style>
</head>
<body>

<div class="page" style="page-break-after: always;">
    <div class="header" style="page-break-inside: avoid;">
        <h1>WASTE MANAGEMENT REPORT</h1>
        <div class="company-name">{{ strtoupper($company->name) }}</div>
        <div class="month">{{ $month->format(\App\Support\DisplayDate::CALENDAR) }}</div>
    </div>

    <div class="metrics-grid">
        <div class="metric-box-wrapper">
            <div class="metric-box">
                <div class="metric-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Top foliage layer (lighter green) -->
                        <ellipse cx="12" cy="7" rx="4" ry="3.5" fill="#3b82f6"/>
                        <!-- Middle foliage layer (darker green) -->
                        <ellipse cx="12" cy="10" rx="5" ry="4" fill="#2563eb"/>
                        <!-- Bottom foliage layer (darkest green) -->
                        <ellipse cx="12" cy="13" rx="5.5" ry="4.5" fill="#1d4ed8"/>
                        <!-- Additional rounded foliage details -->
                        <ellipse cx="9" cy="9" rx="2.5" ry="2.5" fill="#3b82f6" opacity="0.8"/>
                        <ellipse cx="15" cy="9" rx="2.5" ry="2.5" fill="#3b82f6" opacity="0.8"/>
                        <ellipse cx="10" cy="12" rx="2.5" ry="2.5" fill="#2563eb" opacity="0.7"/>
                        <ellipse cx="14" cy="12" rx="2.5" ry="2.5" fill="#2563eb" opacity="0.7"/>
                        <!-- Trunk -->
                        <rect x="11" y="17" width="2" height="3" rx="0.5" fill="#92400e"/>
                    </svg>
                </div>
            </div>
            <div class="metric-label">TREES SAVED</div>
            <div class="metric-value">{{ number_format($impact['trees_saved']) }}</div>
        </div>
        <div class="metric-box-wrapper">
            <div class="metric-box">
                <div class="metric-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" fill="#f97316" stroke="#ffffff" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="metric-label">ENERGY SAVED</div>
            <div class="metric-value">{{ number_format($impact['energy_saved']) }}</div>
        </div>
        <div class="metric-box-wrapper">
            <div class="metric-box">
                <div class="metric-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2.69L12 2.69C12 2.69 6 8 6 13C6 16.31 8.69 19 12 19C15.31 19 18 16.31 18 13C18 8 12 2.69 12 2.69Z" fill="#38bdf8" stroke="#ffffff" stroke-width="0.5"/>
                        <path d="M12 5C9.24 5 7 7.24 7 10C7 12.76 9.24 15 12 15C14.76 15 17 12.76 17 10C17 7.24 14.76 5 12 5Z" fill="#0ea5e9" opacity="0.7"/>
                        <ellipse cx="11" cy="9" rx="1.5" ry="2" fill="#ffffff" opacity="0.6"/>
                    </svg>
                </div>
            </div>
            <div class="metric-label">WATER SAVED (kL)</div>
            <div class="metric-value">{{ number_format($impact['water_saved'], 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">GRADE</th>
                <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;"></th>
                <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;"></th>
                <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">WEIGHT KGS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>General Waste</td>
                <td></td>
                <td></td>
                <td>{{ number_format($wasteBreakdown['general_waste'], 2) }}</td>
            </tr>
            <tr>
                <td>Non Compactable Waste</td>
                <td></td>
                <td></td>
                <td>{{ number_format($wasteBreakdown['non_compactable_waste'], 2) }}</td>
            </tr>
            <tr>
                <td>Hazardous Waste</td>
                <td></td>
                <td></td>
                <td>{{ number_format($wasteBreakdown['hazardous_waste'], 2) }}</td>
            </tr>
            <tr>
                <td>Organics Recovered</td>
                <td></td>
                <td></td>
                <td>{{ number_format($wasteBreakdown['organics_recovered'], 2) }}</td>
            </tr>
            <tr style="font-weight: bold; background-color: #e5e7eb;">
                <td>TOTAL WASTE</td>
                <td></td>
                <td></td>
                <td>{{ number_format($wasteBreakdown['general_waste'] + $wasteBreakdown['non_compactable_waste'] + $wasteBreakdown['hazardous_waste'] + $wasteBreakdown['organics_recovered'], 2) }}</td>
            </tr>
            </tbody>
    </table>

    <div style="margin-top: 20px;">
        <h3 style="font-size: 12px; margin-bottom: 10px; font-weight: bold;">RECYCLING RECOVERED</h3>
        <table>
            <thead>
                <tr>
                    <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">Commodity</th>
                    <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">QTY</th>
                    <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">Commodity</th>
                    <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">QTY</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $recyclingItems = [];
                    foreach($recyclingBreakdown as $name => $weight) {
                        $recyclingItems[] = ['name' => $name, 'weight' => $weight];
                    }
                    $itemCount = count($recyclingItems);
                    if ($itemCount > 0) {
                        $chunkSize = max(1, ceil($itemCount / 2));
                        $chunks = array_chunk($recyclingItems, $chunkSize);
                        $leftColumn = $chunks[0] ?? [];
                        $rightColumn = $chunks[1] ?? [];
                        $maxRows = max(count($leftColumn), count($rightColumn));
                    } else {
                        $leftColumn = [];
                        $rightColumn = [];
                        $maxRows = 0;
                    }
                @endphp
                @if($maxRows > 0)
                    @for($i = 0; $i < $maxRows; $i++)
                        <tr>
                            <td>{{ $leftColumn[$i]['name'] ?? '' }}</td>
                            <td>{{ isset($leftColumn[$i]) ? number_format($leftColumn[$i]['weight'], 2) : '' }}</td>
                            <td>{{ $rightColumn[$i]['name'] ?? '' }}</td>
                            <td>{{ isset($rightColumn[$i]) ? number_format($rightColumn[$i]['weight'], 2) : '' }}</td>
                        </tr>
                    @endfor
                @else
                    <tr>
                        <td colspan="4" style="text-align: center; color: #999;">No recycling data available</td>
                    </tr>
                @endif
                <tr style="font-weight: bold; background-color: #e5e7eb;">
                    <td colspan="3">Recycling Recovered</td>
                    <td>{{ number_format(array_sum($recyclingBreakdown), 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @php
        $totalWaste = $wasteBreakdown['general_waste'] + $wasteBreakdown['non_compactable_waste'] + $wasteBreakdown['hazardous_waste'] + $wasteBreakdown['organics_recovered'];
        $totalIncoming = $impact['total_incoming_waste'] ?: 1;
        $generalWastePercent = ($wasteBreakdown['general_waste'] / $totalIncoming) * 100;
        $nonCompactablePercent = ($wasteBreakdown['non_compactable_waste'] / $totalIncoming) * 100;
        $hazardousPercent = ($wasteBreakdown['hazardous_waste'] / $totalIncoming) * 100;
        $organicsPercent = ($wasteBreakdown['organics_recovered'] / $totalIncoming) * 100;
        $recyclingPercent = ($impact['total_recycling_weight'] / $totalIncoming) * 100;
    @endphp

    <div style="margin-top: 30px; border-top: 2px solid #2563eb; padding-top: 15px; page-break-inside: avoid;">
        <h3 style="font-size: 12px; margin-bottom: 15px; color: #2563eb; font-weight: bold;">WASTE BREAKDOWN</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    @php
                        $totalIncoming = $impact['total_incoming_waste'] ?: 1;
                        $generalWastePercent = ($wasteBreakdown['general_waste'] / $totalIncoming) * 100;
                        $nonCompactablePercent = ($wasteBreakdown['non_compactable_waste'] / $totalIncoming) * 100;
                        $hazardousPercent = ($wasteBreakdown['hazardous_waste'] / $totalIncoming) * 100;
                        $organicsPercent = ($wasteBreakdown['organics_recovered'] / $totalIncoming) * 100;
                        $recyclingPercent = ($impact['total_recycling_weight'] / $totalIncoming) * 100;
                        $totalPercent = $generalWastePercent + $nonCompactablePercent + $hazardousPercent + $organicsPercent + $recyclingPercent;
                        $chartSize = 200;
                        $allSegments = [
                            ['percent' => $generalWastePercent, 'color' => '#1f2937', 'label' => 'General Waste'],
                            ['percent' => $nonCompactablePercent, 'color' => '#9ca3af', 'label' => 'Non Compactable Waste'],
                            ['percent' => $hazardousPercent, 'color' => '#ef4444', 'label' => 'Hazardous Waste'],
                            ['percent' => $organicsPercent, 'color' => '#2563eb', 'label' => 'Organics Recovered'],
                            ['percent' => $recyclingPercent, 'color' => '#3b82f6', 'label' => 'Recycling Recovered'],
                        ];
                    @endphp
                    <div style="text-align: center;">
                        <div style="width: {{ $chartSize }}px; height: {{ $chartSize }}px; margin: 0 auto; position: relative;">
                            @php
                                $radius = 90;
                                $centerX = $chartSize / 2;
                                $centerY = $chartSize / 2;
                                $currentAngle = -90;
                            @endphp
                            <svg width="{{ $chartSize }}" height="{{ $chartSize }}" style="position: absolute; top: 0; left: 0;">
                                @foreach($allSegments as $idx => $segment)
                                    @if($totalPercent > 0 && $segment['percent'] > 0)
                                        @php
                                            $angle = ($segment['percent'] / $totalPercent) * 360;
                                            $startAngle = deg2rad($currentAngle);
                                            $endAngle = deg2rad($currentAngle + $angle);
                                            $x1 = $centerX + $radius * cos($startAngle);
                                            $y1 = $centerY + $radius * sin($startAngle);
                                            $x2 = $centerX + $radius * cos($endAngle);
                                            $y2 = $centerY + $radius * sin($endAngle);
                                            $largeArc = $angle > 180 ? 1 : 0;
                                        @endphp
                                        <path d="M {{ $centerX }} {{ $centerY }} L {{ $x1 }} {{ $y1 }} A {{ $radius }} {{ $radius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                              fill="{{ $segment['color'] }}" 
                                              stroke="#fff" 
                                              stroke-width="2"/>
                                        @php $currentAngle += $angle; @endphp
                                    @endif
                                @endforeach
                                <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="60" fill="white" stroke="#2563eb" stroke-width="2"/>
                                <text x="{{ $centerX }}" y="{{ $centerY - 10 }}" text-anchor="middle" font-size="24" font-weight="bold" fill="#2563eb">{{ number_format($impact['diverted_from_landfill_percent'], 1) }}%</text>
                                <text x="{{ $centerX }}" y="{{ $centerY + 10 }}" text-anchor="middle" font-size="10" fill="#666">Diverted</text>
                            </svg>
                        </div>
                        <div style="margin-top: 20px; text-align: left; display: inline-block;">
                            @foreach($allSegments as $segment)
                                <div style="font-size: 9px; margin: 5px 0; color: {{ $segment['color'] }}; font-weight: 600;">
                                    <span style="display: inline-block; width: 16px; height: 16px; background: {{ $segment['color'] }}; margin-right: 8px; vertical-align: middle; border-radius: 3px; border: 2px solid {{ $segment['color'] }}; box-shadow: 0 1px 2px rgba(0,0,0,0.2);"></span> 
                                    {{ $segment['label'] }} ({{ number_format($segment['percent'], 1) }}%)
                                </div>
                            @endforeach
                        </div>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <div class="summary-box" style="margin-top: 0;">
                        <h3 style="font-size: 12px; margin-bottom: 10px; color: #2563eb;">Summary</h3>
                        <div class="summary-row">
                            <div class="summary-label">Recycling Recovered</div>
                            <div class="summary-value">{{ number_format($impact['total_recycling_weight'], 2) }} kg</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Organics Recovered</div>
                            <div class="summary-value">{{ number_format($wasteBreakdown['organics_recovered'], 2) }} kg</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Total Incoming Waste</div>
                            <div class="summary-value">{{ number_format($impact['total_incoming_waste'], 2) }} kg</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Diverted From Landfill</div>
                            <div class="summary-value">{{ number_format($impact['diverted_from_landfill_percent'], 2) }}%</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Landfill Space Saved M³</div>
                            <div class="summary-value">{{ number_format($impact['landfill_space_saved'], 2) }}</div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-label">Total Lifecycle Carbon Avoided (kg CO₂e)</div>
                            <div class="summary-value">{{ number_format($impact['total_lifecycle_saving'], 2) }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <div style="clear: both; height: 80px; page-break-inside: avoid;"></div>
    
    <div class="footer" style="margin-top: 40px;">
        <div class="footer-text">WASTEFLOW</div>
        <div>Sustainable Waste Management</div>
    </div>
</div>

<div class="page" style="page-break-before: always;">
    <div class="header" style="page-break-inside: avoid;">
        <h1>WASTE MANAGEMENT REPORT</h1>
        <div class="company-name">{{ strtoupper($company->name) }}</div>
        <div class="month">{{ $month->format(\App\Support\DisplayDate::CALENDAR) }}</div>
    </div>

    @if(count($impact['carbon_breakdown']) > 0)
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 11px; border: 1px solid #ddd;">
        <thead>
            <tr>
                <th style="background: #2563eb; background-color: #2563eb; color: #ffffff; font-weight: bold; padding: 10px; font-size: 12px; border-top: 1px solid #2563eb; border-bottom: 1px solid #2563eb; border-left: 1px solid #2563eb; border-right: 1px solid #ffffff; text-align: center; mso-pattern: #2563eb solid;">Material</th>
                <th style="background: #2563eb; background-color: #2563eb; color: #ffffff; font-weight: bold; padding: 10px; font-size: 12px; border-top: 1px solid #2563eb; border-bottom: 1px solid #2563eb; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; text-align: center; mso-pattern: #2563eb solid;">Weight (kg)</th>
                <th style="background: #2563eb; background-color: #2563eb; color: #ffffff; font-weight: bold; padding: 10px; font-size: 11px; border-top: 1px solid #2563eb; border-bottom: 1px solid #2563eb; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; text-align: center; mso-pattern: #2563eb solid;">Upstream (Scope 3) Emissions Avoided (kg CO₂e)</th>
                <th style="background: #2563eb; background-color: #2563eb; color: #ffffff; font-weight: bold; padding: 10px; font-size: 11px; border-top: 1px solid #2563eb; border-bottom: 1px solid #2563eb; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; text-align: center; mso-pattern: #2563eb solid;">Landfill Emissions Avoided (kg CO₂e)</th>
                <th style="background: #2563eb; background-color: #2563eb; color: #ffffff; font-weight: bold; padding: 10px; font-size: 10px; border-top: 1px solid #2563eb; border-bottom: 1px solid #2563eb; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; text-align: center; mso-pattern: #2563eb solid;">Recycling Substitution Factor (Reference Only &ndash; Not Included in Total)</th>
                <th style="background: #2563eb; background-color: #2563eb; color: #ffffff; font-weight: bold; padding: 10px; font-size: 11px; border-top: 1px solid #2563eb; border-bottom: 1px solid #2563eb; border-left: 1px solid #ffffff; border-right: 1px solid #2563eb; text-align: center; mso-pattern: #2563eb solid;">Total Lifecycle Carbon Avoided (kg CO₂e)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($impact['carbon_breakdown'] as $materialType => $data)
                @php
                    $service = app(\App\Services\EnvironmentalImpactService::class);
                    $materialName = $service->getMaterialDisplayName($materialType);
                    $substitutionFactor = isset($data['weight']) && (float) $data['weight'] > 0
                        ? $data['other_offsets'] / $data['weight']
                        : 0;
                @endphp
                <tr>
                    <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ $materialName }}</td>
                    <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ number_format($data['weight'], 2) }}</td>
                    <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ number_format($data['scope3'], 2) }}</td>
                    <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ number_format($data['landfill_avoidance'], 2) }}</td>
                    <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center; color: #dc2626; font-weight: 600;">{{ number_format($substitutionFactor, 2) }}</td>
                    <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ number_format($data['lifecycle_saving'], 2) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #e5e7eb;">
                <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;"></td>
                <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;"></td>
                <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ number_format($impact['total_scope3'], 2) }}</td>
                <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ number_format($impact['total_landfill_avoidance'], 2) }}</td>
                <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center; color: #666;">&mdash;</td>
                <td style="padding: 8px; font-size: 11px; border: 1px solid #ddd; text-align: center;">{{ number_format($impact['total_lifecycle_saving'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-box" style="margin-top: 20px;">
        <div class="summary-row">
            <div class="summary-label">Material Recovery Impact (kg CO₂e)</div>
            <div class="summary-value">{{ number_format($impact['total_scope3'], 2) }}</div>
        </div>
        <div style="font-size: 8px; color: #666; margin-top: 5px;">(Avoided emissions from reduced virgin material production)</div>

        <div class="summary-row" style="margin-top: 15px;">
            <div class="summary-label">Landfill Diversion Impact (kg CO₂e)</div>
            <div class="summary-value">{{ number_format($impact['total_landfill_avoidance'], 2) }}</div>
        </div>
        <div style="font-size: 8px; color: #666; margin-top: 5px;">(Avoided methane emissions from diversion of biodegradable waste from landfill)</div>

        <div class="summary-row" style="margin-top: 15px; font-weight: bold;">
            <div class="summary-label">Total Environmental Impact (kg CO₂e)</div>
            <div class="summary-value">{{ number_format($impact['total_lifecycle_saving'], 2) }}</div>
        </div>

        <div style="margin-top: 16px; background: #fefce8; border: 1px solid #fde68a; border-radius: 2px; padding: 8px 10px; font-size: 8px; font-weight: 600; text-align: center; line-height: 1.45; color: #1f2937;">
            Landfill emissions primarily reflect the methane generation potential of biodegradable materials. Inert materials such as metals and glass have negligible associated emissions and are therefore assigned low or zero landfill emission factors.
        </div>

        <div style="margin-top: 8px; border: 1px solid #d1d5db; border-radius: 2px; padding: 8px 10px; font-size: 8px; text-align: center; line-height: 1.45; color: #1f2937;">
            Carbon emission factors and avoided emission assumptions are based on internationally recognised standards, including DEFRA (UK Government), the EPA WARM model, and peer-reviewed global life cycle assessment (LCA) datasets (e.g. Ecoinvent). Calculations are aligned with best practice under the GHG Protocol, ensuring consistency, transparency, and the avoidance of double counting.
        </div>
    </div>
    @else
    <div style="text-align: center; margin-top: 100px; color: #999;">
        <p>No carbon data available for this period.</p>
    </div>
    @endif

    <div class="footer" style="margin-top: {{ count($impact['carbon_breakdown']) > 0 ? '20px' : '200px' }};">
        <div class="footer-text">WASTEFLOW</div>
        <div>Sustainable Waste Management</div>
    </div>
</div>

<div class="page" style="page-break-before: always;">
    <div class="header" style="page-break-inside: avoid;">
        <h1>WASTE MANAGEMENT REPORT</h1>
        <div class="company-name">{{ strtoupper($company->name) }}</div>
        <div class="month">{{ $month->format(\App\Support\DisplayDate::CALENDAR) }}</div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <div style="font-size: 14px; color: #666; margin-bottom: 12px;">Total Lifecycle Carbon Avoided</div>
        <div class="large-number">{{ number_format($impact['total_lifecycle_saving'], 2) }}</div>
        <div style="font-size: 11px; color: #666; margin-bottom: 28px;">kg CO₂e</div>

        <div style="font-size: 14px; color: #333; margin: 24px 0 12px; font-weight: bold;">
            Carbon equivalency indicators (from lifecycle saving)
        </div>
        <table style="width: 100%; max-width: 520px; margin: 0 auto 24px; border-collapse: collapse; font-size: 11px;">
            <tr style="background: #eff6ff;">
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: left; font-weight: bold;">Electricity Equivalent (kWh – SA Grid)</td>
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: right;">{{ number_format($impact['electricity_equivalent_kwh_sa_grid'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: left; font-weight: bold;">Transport Equivalent (km Avoided)</td>
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: right;">{{ number_format($impact['transport_equivalent_km'] ?? 0, 2) }}</td>
            </tr>
            <tr style="background: #eff6ff;">
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: left; font-weight: bold;">Fuel Equivalent (Litres of Petrol Avoided)</td>
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: right;">{{ number_format($impact['fuel_equivalent_litres_petrol'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: left; font-weight: bold;">Cars Off the Road (Annual Equivalent)</td>
                <td style="padding: 10px; border: 1px solid #bfdbfe; text-align: right;">{{ number_format($impact['cars_off_road_annual_equivalent'] ?? 0, 4) }}</td>
            </tr>
        </table>

        <div style="font-size: 11px; color: #666; margin-top: 12px; line-height: 1.6;">
            By diverting waste from landfill and recycling efficiently, your operations are actively preventing CO₂e from entering the atmosphere.
        </div>
    </div>

    <div class="footer">
        <div class="footer-text">WASTEFLOW</div>
        <div>Sustainable Waste Management</div>
    </div>
</div>

<div class="page" style="page-break-before: always;">
    <div class="header" style="page-break-inside: avoid;">
        <h1>WASTE MANAGEMENT REPORT</h1>
        <div class="company-name">{{ strtoupper($company->name) }}</div>
        <div class="month">{{ $month->format(\App\Support\DisplayDate::CALENDAR) }}</div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <h2 style="font-size: 18px; color: #2563eb; margin-bottom: 30px;">Environmental Impact &amp; Resource Savings</h2>
        
        @php
            $waterSaved = $impact['water_saved'] ?? 0;
            $energySaved = $impact['energy_saved'] ?? 0;
            $lifecycleSaving = $impact['total_lifecycle_saving'] ?? 0;
            $impactPieSize = 200;
            $impactPieRadius = 90;
            $impactPieCenterX = $impactPieSize / 2;
            $impactPieCenterY = $impactPieSize / 2;
            $totalImpact = $waterSaved + $energySaved + $lifecycleSaving;
            $waterImpactPercent = $totalImpact > 0 ? ($waterSaved / $totalImpact) * 100 : 0;
            $energyImpactPercent = $totalImpact > 0 ? ($energySaved / $totalImpact) * 100 : 0;
            $lifecycleImpactPercent = $totalImpact > 0 ? ($lifecycleSaving / $totalImpact) * 100 : 0;
        @endphp
        
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%; padding-right: 15px; vertical-align: top; text-align: center;">
                    <div style="width: {{ $impactPieSize }}px; height: {{ $impactPieSize }}px; margin: 0 auto; position: relative;">
                        <svg width="{{ $impactPieSize }}" height="{{ $impactPieSize }}">
                            @php $currentAngle = -90; @endphp
                            @if($waterImpactPercent > 0)
                                @php
                                    $angle = ($waterImpactPercent / 100) * 360;
                                    $startAngle = deg2rad($currentAngle);
                                    $endAngle = deg2rad($currentAngle + $angle);
                                    $x1 = $impactPieCenterX + $impactPieRadius * cos($startAngle);
                                    $y1 = $impactPieCenterY + $impactPieRadius * sin($startAngle);
                                    $x2 = $impactPieCenterX + $impactPieRadius * cos($endAngle);
                                    $y2 = $impactPieCenterY + $impactPieRadius * sin($endAngle);
                                    $largeArc = $angle > 180 ? 1 : 0;
                                @endphp
                                <path d="M {{ $impactPieCenterX }} {{ $impactPieCenterY }} L {{ $x1 }} {{ $y1 }} A {{ $impactPieRadius }} {{ $impactPieRadius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                      fill="#06b6d4" 
                                      stroke="#fff" 
                                      stroke-width="2"/>
                                @php $currentAngle += $angle; @endphp
                            @endif
                            @if($energyImpactPercent > 0)
                                @php
                                    $angle = ($energyImpactPercent / 100) * 360;
                                    $startAngle = deg2rad($currentAngle);
                                    $endAngle = deg2rad($currentAngle + $angle);
                                    $x1 = $impactPieCenterX + $impactPieRadius * cos($startAngle);
                                    $y1 = $impactPieCenterY + $impactPieRadius * sin($startAngle);
                                    $x2 = $impactPieCenterX + $impactPieRadius * cos($endAngle);
                                    $y2 = $impactPieCenterY + $impactPieRadius * sin($endAngle);
                                    $largeArc = $angle > 180 ? 1 : 0;
                                @endphp
                                <path d="M {{ $impactPieCenterX }} {{ $impactPieCenterY }} L {{ $x1 }} {{ $y1 }} A {{ $impactPieRadius }} {{ $impactPieRadius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                      fill="#f59e0b" 
                                      stroke="#fff" 
                                      stroke-width="2"/>
                                @php $currentAngle += $angle; @endphp
                            @endif
                            @if($lifecycleImpactPercent > 0)
                                @php
                                    $angle = ($lifecycleImpactPercent / 100) * 360;
                                    $startAngle = deg2rad($currentAngle);
                                    $endAngle = deg2rad($currentAngle + $angle);
                                    $x1 = $impactPieCenterX + $impactPieRadius * cos($startAngle);
                                    $y1 = $impactPieCenterY + $impactPieRadius * sin($startAngle);
                                    $x2 = $impactPieCenterX + $impactPieRadius * cos($endAngle);
                                    $y2 = $impactPieCenterY + $impactPieRadius * sin($endAngle);
                                    $largeArc = $angle > 180 ? 1 : 0;
                                @endphp
                                <path d="M {{ $impactPieCenterX }} {{ $impactPieCenterY }} L {{ $x1 }} {{ $y1 }} A {{ $impactPieRadius }} {{ $impactPieRadius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                      fill="#2563eb" 
                                      stroke="#fff" 
                                      stroke-width="2"/>
                            @endif
                            <circle cx="{{ $impactPieCenterX }}" cy="{{ $impactPieCenterY }}" r="50" fill="white" stroke="#2563eb" stroke-width="2"/>
                            <text x="{{ $impactPieCenterX }}" y="{{ $impactPieCenterY - 5 }}" text-anchor="middle" font-size="16" font-weight="bold" fill="#2563eb">Impact</text>
                            <text x="{{ $impactPieCenterX }}" y="{{ $impactPieCenterY + 15 }}" text-anchor="middle" font-size="10" fill="#666">Breakdown</text>
                        </svg>
                    </div>
                    <div style="margin-top: 15px;">
                        <div style="font-size: 9px; margin: 4px 0; color: #06b6d4; font-weight: 600;">
                            <span style="display: inline-block; width: 12px; height: 12px; background: #06b6d4; margin-right: 6px; vertical-align: middle; border-radius: 2px;"></span>
                            Water Saved kL ({{ number_format($waterImpactPercent, 1) }}%)
                        </div>
                        <div style="font-size: 9px; margin: 4px 0; color: #f59e0b; font-weight: 600;">
                            <span style="display: inline-block; width: 12px; height: 12px; background: #f59e0b; margin-right: 6px; vertical-align: middle; border-radius: 2px;"></span>
                            Energy Saved ({{ number_format($energyImpactPercent, 1) }}%)
                        </div>
                        <div style="font-size: 9px; margin: 4px 0; color: #2563eb; font-weight: 600;">
                            <span style="display: inline-block; width: 12px; height: 12px; background: #2563eb; margin-right: 6px; vertical-align: middle; border-radius: 2px;"></span>
                            Total Lifecycle CO₂e ({{ number_format($lifecycleImpactPercent, 1) }}%)
                        </div>
                    </div>
                </td>
                <td style="width: 50%; padding-left: 15px; vertical-align: top;">
                    <div class="metric-box" style="margin-bottom: 20px; width: 100%;">
                        <div class="metric-label">Water Saved</div>
                        <div class="metric-value" style="font-size: 24px;">{{ number_format($waterSaved, 2) }}</div>
                        <div style="font-size: 10px; color: #ffffff; margin-top: 5px;">kL</div>
                    </div>
                    <div class="metric-box" style="margin-bottom: 20px; width: 100%;">
                        <div class="metric-label">Energy Saved</div>
                        <div class="metric-value" style="font-size: 24px;">{{ number_format($energySaved) }}</div>
                        <div style="font-size: 10px; color: #ffffff; margin-top: 5px;">kWh</div>
                    </div>
                    <div class="metric-box" style="width: 100%;">
                        <div class="metric-label">Total Lifecycle Carbon Avoided (kg CO₂e)</div>
                        <div class="metric-value" style="font-size: 24px;">{{ number_format($lifecycleSaving, 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

    </div>

    <div class="footer">
        <div class="footer-text">WASTEFLOW</div>
        <div>Sustainable Waste Management</div>
    </div>
</div>

<div class="page" style="page-break-before: always;">
    <div class="header" style="page-break-inside: avoid;">
        <h1>WASTE MANAGEMENT REPORT</h1>
        <div class="company-name">{{ strtoupper($company->name) }}</div>
        <div class="month">{{ $month->format(\App\Support\DisplayDate::CALENDAR) }}</div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <h2 style="font-size: 18px; color: #2563eb; margin-bottom: 30px;">Waste Treatment Summary (kg by Category)</h2>
        
        @php
            $totalRecycling = array_sum($impact['material_breakdown'] ?? []);
            $recyclingPieSize = 200;
            $recyclingPieRadius = 90;
            $recyclingPieCenterX = $recyclingPieSize / 2;
            $recyclingPieCenterY = $recyclingPieSize / 2;
            $recyclingColors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#6366f1', '#ef4444', '#84cc16'];
            $recyclingSegments = [];
            $colorIndex = 0;
            foreach($impact['material_breakdown'] ?? [] as $type => $weight) {
                if($weight > 0) {
                    $service = app(\App\Services\EnvironmentalImpactService::class);
                    $materialName = $service->getMaterialDisplayName($type);
                    $percentage = $totalRecycling > 0 ? ($weight / $totalRecycling) * 100 : 0;
                    $recyclingSegments[] = [
                        'name' => $materialName,
                        'weight' => $weight,
                        'percentage' => $percentage,
                        'color' => $recyclingColors[$colorIndex % count($recyclingColors)]
                    ];
                    $colorIndex++;
                }
            }
        @endphp
        
        @if($totalRecycling > 0)
        <table style="width: 100%; margin-top: 20px;">
            <tr>
                <td style="width: 50%; padding-right: 15px; vertical-align: top; text-align: center;">
                    <div style="width: {{ $recyclingPieSize }}px; height: {{ $recyclingPieSize }}px; margin: 0 auto; position: relative;">
                        <svg width="{{ $recyclingPieSize }}" height="{{ $recyclingPieSize }}">
                            @php $currentAngle = -90; @endphp
                            @foreach($recyclingSegments as $segment)
                                @php
                                    $angle = ($segment['percentage'] / 100) * 360;
                                    $startAngle = deg2rad($currentAngle);
                                    $endAngle = deg2rad($currentAngle + $angle);
                                    $x1 = $recyclingPieCenterX + $recyclingPieRadius * cos($startAngle);
                                    $y1 = $recyclingPieCenterY + $recyclingPieRadius * sin($startAngle);
                                    $x2 = $recyclingPieCenterX + $recyclingPieRadius * cos($endAngle);
                                    $y2 = $recyclingPieCenterY + $recyclingPieRadius * sin($endAngle);
                                    $largeArc = $angle > 180 ? 1 : 0;
                                @endphp
                                <path d="M {{ $recyclingPieCenterX }} {{ $recyclingPieCenterY }} L {{ $x1 }} {{ $y1 }} A {{ $recyclingPieRadius }} {{ $recyclingPieRadius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                      fill="{{ $segment['color'] }}" 
                                      stroke="#fff" 
                                      stroke-width="2"/>
                                @php $currentAngle += $angle; @endphp
                            @endforeach
                            <circle cx="{{ $recyclingPieCenterX }}" cy="{{ $recyclingPieCenterY }}" r="50" fill="white" stroke="#2563eb" stroke-width="2"/>
                            <text x="{{ $recyclingPieCenterX }}" y="{{ $recyclingPieCenterY - 5 }}" text-anchor="middle" font-size="18" font-weight="bold" fill="#2563eb">Total</text>
                            <text x="{{ $recyclingPieCenterX }}" y="{{ $recyclingPieCenterY + 15 }}" text-anchor="middle" font-size="11" fill="#666">{{ number_format($totalRecycling, 0) }} kg</text>
                        </svg>
                    </div>
                </td>
                <td style="width: 50%; padding-left: 15px; vertical-align: top;">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">Material Type</th>
                                <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">Weight (kg)</th>
                                <th style="background-color: #2563eb !important; color: white !important; font-weight: bold !important; padding: 10px !important; font-size: 12px !important;">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recyclingSegments as $segment)
                                <tr>
                                    <td style="text-align: center;">
                                        <span style="display: inline-block; width: 10px; height: 10px; background: {{ $segment['color'] }}; margin-right: 5px; vertical-align: middle; border-radius: 2px;"></span>
                                        {{ $segment['name'] }}
                                    </td>
                                    <td style="text-align: center;">{{ number_format($segment['weight'], 2) }}</td>
                                    <td style="text-align: center;">{{ number_format($segment['percentage'], 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
        @else
        <div style="text-align: center; margin: 40px 0; color: #999;">
            <p>No recycling data available for this period.</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <div class="footer-text">WASTEFLOW</div>
        <div>Sustainable Waste Management</div>
    </div>
</div>

<div class="page" style="page-break-before: always;">
    <div class="header" style="page-break-inside: avoid;">
        <h1>WASTE MANAGEMENT REPORT</h1>
        <div class="company-name">{{ strtoupper($company->name) }}</div>
        <div class="month">{{ $month->format(\App\Support\DisplayDate::CALENDAR) }}</div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <h2 style="font-size: 20px; color: #2563eb; margin-bottom: 40px;">DIVERTED FROM LANDFILL</h2>
        
        @php
            $divertedPercent = min(max($impact['diverted_from_landfill_percent'], 0), 100);
            $gaugeSize = 300;
            $centerX = $gaugeSize / 2;
            $centerY = $gaugeSize / 2;
            $radius = 120;
            $needleAngle = 180 - (($divertedPercent / 100) * 180);
        @endphp
        
        <div style="width: {{ $gaugeSize }}px; height: {{ $gaugeSize / 2 + 80 }}px; margin: 0 auto; position: relative;">
            <svg width="{{ $gaugeSize }}" height="{{ $gaugeSize / 2 + 20 }}" style="overflow: visible;">
                <defs>
                    <linearGradient id="gaugeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#ef4444;stop-opacity:1" />
                        <stop offset="50%" style="stop-color:#f59e0b;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#2563eb;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <path d="M {{ $centerX - $radius }} {{ $centerY }} A {{ $radius }} {{ $radius }} 0 0 1 {{ $centerX + $radius }} {{ $centerY }}" 
                      stroke="url(#gaugeGradient)" 
                      stroke-width="30" 
                      fill="none" 
                      stroke-linecap="round"/>
                @php
                    $needleRad = deg2rad($needleAngle);
                    $needleX = $centerX + $radius * cos($needleRad);
                    $needleY = $centerY - $radius * sin($needleRad);
                @endphp
                <line x1="{{ $centerX }}" 
                      y1="{{ $centerY }}" 
                      x2="{{ $needleX }}" 
                      y2="{{ $needleY }}" 
                      stroke="#000" 
                      stroke-width="6" 
                      stroke-linecap="round"/>
                <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="8" fill="#000"/>
            </svg>
            <div style="text-align: center; margin-top: 30px;">
                <div style="font-size: 48px; font-weight: bold; color: #2563eb;">{{ number_format($divertedPercent, 2) }}%</div>
            </div>
        </div>

        <h3 style="font-size: 16px; color: #2563eb; margin: 50px 0 30px;">WASTE vs RECOVERY</h3>
        
        @php
            $totalWasteForComparison = $wasteBreakdown['general_waste'] + $wasteBreakdown['non_compactable_waste'] + $wasteBreakdown['hazardous_waste'];
            $totalRecoveryForComparison = $impact['total_recycling_weight'] + $wasteBreakdown['organics_recovered'];
            $totalWeight = $totalWasteForComparison + $totalRecoveryForComparison;
            $wastePercent = $totalWeight > 0 ? ($totalWasteForComparison / $totalWeight) * 100 : 0;
            $recyclingPercent = $totalWeight > 0 ? ($totalRecoveryForComparison / $totalWeight) * 100 : 0;
            $pieSize = 200;
            $pieRadius = 90;
            $pieCenterX = $pieSize / 2;
            $pieCenterY = $pieSize / 2;
        @endphp
        
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%; padding-right: 15px; vertical-align: top; text-align: center;">
                    <div style="width: {{ $pieSize }}px; height: {{ $pieSize }}px; margin: 0 auto; position: relative;">
                        <svg width="{{ $pieSize }}" height="{{ $pieSize }}">
                            @if($totalWeight > 0)
                                @if($wastePercent > 0)
                                    @php
                                        $wasteAngle = ($wastePercent / 100) * 360;
                                        $startAngle = -90;
                                        $endAngle = -90 + $wasteAngle;
                                        $x1 = $pieCenterX + $pieRadius * cos(deg2rad($startAngle));
                                        $y1 = $pieCenterY + $pieRadius * sin(deg2rad($startAngle));
                                        $x2 = $pieCenterX + $pieRadius * cos(deg2rad($endAngle));
                                        $y2 = $pieCenterY + $pieRadius * sin(deg2rad($endAngle));
                                        $largeArc = $wasteAngle > 180 ? 1 : 0;
                                    @endphp
                                    <path d="M {{ $pieCenterX }} {{ $pieCenterY }} L {{ $x1 }} {{ $y1 }} A {{ $pieRadius }} {{ $pieRadius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                          fill="#ef4444" 
                                          stroke="#fff" 
                                          stroke-width="2"/>
                                @endif
                                @if($recyclingPercent > 0)
                                    @php
                                        $recyclingAngle = ($recyclingPercent / 100) * 360;
                                        $startAngle = -90 + ($wastePercent / 100) * 360;
                                        $endAngle = -90 + 360;
                                        $x1 = $pieCenterX + $pieRadius * cos(deg2rad($startAngle));
                                        $y1 = $pieCenterY + $pieRadius * sin(deg2rad($startAngle));
                                        $x2 = $pieCenterX + $pieRadius * cos(deg2rad($endAngle));
                                        $y2 = $pieCenterY + $pieRadius * sin(deg2rad($endAngle));
                                        $largeArc = $recyclingAngle > 180 ? 1 : 0;
                                    @endphp
                                    <path d="M {{ $pieCenterX }} {{ $pieCenterY }} L {{ $x1 }} {{ $y1 }} A {{ $pieRadius }} {{ $pieRadius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                          fill="#2563eb" 
                                          stroke="#fff" 
                                          stroke-width="2"/>
                                @endif
                            @endif
                            <circle cx="{{ $pieCenterX }}" cy="{{ $pieCenterY }}" r="50" fill="white" stroke="#2563eb" stroke-width="2"/>
                            <text x="{{ $pieCenterX }}" y="{{ $pieCenterY - 5 }}" text-anchor="middle" font-size="20" font-weight="bold" fill="#2563eb">Total</text>
                            <text x="{{ $pieCenterX }}" y="{{ $pieCenterY + 15 }}" text-anchor="middle" font-size="12" fill="#666">{{ number_format($totalWeight, 0) }} kg</text>
                        </svg>
                    </div>
                    <div style="margin-top: 15px; text-align: left; display: inline-block;">
                        <div style="font-size: 9px; margin: 5px 0; color: #ef4444; font-weight: 600;">
                            <span style="display: inline-block; width: 16px; height: 16px; background: #ef4444; margin-right: 8px; vertical-align: middle; border-radius: 3px; border: 2px solid #ef4444; box-shadow: 0 1px 2px rgba(0,0,0,0.2);"></span>
                            Waste ({{ number_format($wastePercent, 1) }}%)
                        </div>
                        <div style="font-size: 9px; margin: 5px 0; color: #2563eb; font-weight: 600;">
                            <span style="display: inline-block; width: 16px; height: 16px; background: #2563eb; margin-right: 8px; vertical-align: middle; border-radius: 3px; border: 2px solid #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.2);"></span>
                            Recovery ({{ number_format($recyclingPercent, 1) }}%)
                        </div>
                    </div>
                </td>
                <td style="width: 50%; padding-left: 15px; vertical-align: top;">
                    <div class="metric-box" style="margin-bottom: 20px; width: 100%;">
                        <div class="metric-label">Total Waste</div>
                        <div class="metric-value" style="font-size: 32px;">{{ number_format($totalWasteForComparison, 2) }}</div>
                        <div style="font-size: 10px; color: #ffffff; margin-top: 5px;">kg</div>
                    </div>
                    <div class="metric-box" style="margin-right: 0; width: 100%;">
                        <div class="metric-label">Recovery (Recycling + Organics)</div>
                        <div class="metric-value" style="font-size: 32px;">{{ number_format($totalRecoveryForComparison, 2) }}</div>
                        <div style="font-size: 10px; color: #ffffff; margin-top: 5px;">kg</div>
                    </div>
                </td>
            </tr>
        </table>

    </div>

    <div class="footer">
        <div class="footer-text">WASTEFLOW</div>
        <div>Sustainable Waste Management</div>
    </div>
</div>

</body>
</html>

