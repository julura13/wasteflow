<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waste Management Report - {{ $reportData['companyName'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .page-header {
            background-color: #1e3a5f;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .page-header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .page-header .company {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .page-header .date {
            font-size: 14px;
        }
        .env-impact {
            background-color: #1e3a5f;
            padding: 20px;
            display: flex;
            justify-content: center;
            gap: 60px;
        }
        .env-icon {
            text-align: center;
            color: white;
        }
        .env-icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            background-color: #3b82f6;
            border: 4px solid #3b82f6;
        }
        .env-icon-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
        }
        .env-icon-value {
            font-size: 24px;
            font-weight: bold;
            margin-top: 5px;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }
        .table-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table thead {
            background-color: #4a7c9b;
            color: white;
        }
        table thead th {
            padding: 8px;
            text-align: left;
            border: 1px solid #2c5a7a;
            font-weight: 600;
        }
        table tbody td {
            padding: 6px 8px;
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
            border-radius: 4px;
            overflow: hidden;
        }
        .summary-box table {
            margin: 0;
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
        .chart-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .chart-container {
            text-align: center;
        }
        .chart-container img {
            max-width: 100%;
            height: auto;
        }
        .footer {
            background: white;
            border-top: 2px solid #3b82f6;
            padding: 20px;
            text-align: center;
        }
        .footer h2 {
            color: #3b82f6;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .footer p {
            color: #3b82f6;
            font-size: 14px;
            font-style: italic;
        }
        .print-button {
            max-width: 1200px;
            margin: 20px auto;
            text-align: right;
        }
        .print-button button {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }
        .print-button button:hover {
            background-color: #2563eb;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .report-container {
                box-shadow: none;
                page-break-after: always;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Page 1 -->
    <div class="report-container">
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
                <div class="chart-container">
                    <img src="{{ asset('images/wasteflow-logo.png') }}" alt="WasteFlow Logo" style="max-height: 200px;">
                </div>
                @if(isset($chartPaths['page1_pie']) && $chartPaths['page1_pie'])
                <div class="chart-container">
                    <img src="{{ $chartPaths['page1_pie'] }}" alt="Waste Distribution Pie Chart">
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
    <div class="report-container">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 20px;">
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

            <div class="summary-box" style="margin-top: 30px;">
                <h3 style="text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 15px; padding-top: 15px;">Summary</h3>
                <div style="padding: 0 20px 20px;">
                    <p style="margin-bottom: 10px; font-size: 13px;">
                        <strong>Scope 3 CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['scope3EF'], 2) }}</strong>
                        Indirect Carbon Emissions from Sending Waste Generated for Recyclable
                    </p>
                    <p style="margin-bottom: 10px; font-size: 13px;">
                        <strong>Landfill Avoidance CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['landfillAvoidanceEF'], 2) }}</strong>
                        Carbon Emission Savings Due to Landfill Avoidance
                    </p>
                    <p style="margin-bottom: 10px; font-size: 13px;">
                        <strong>Other Offsets CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['otherOffsets'], 2) }}</strong>
                        Additional CO2 savings from recycling, energy recovery, and avoiding virgin material production.
                    </p>
                    <p style="margin-bottom: 10px; font-size: 13px;">
                        <strong>Lifecycle Saving CO₂e (kg)</strong>
                        <strong style="color: #dc2626;">{{ number_format($reportData['materialsCO2eTotals']['lifecycleSaving'], 2) }}</strong>
                        Overall carbon benefit from managing all materials sustainably.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Page 3: Charts -->
    <div class="report-container">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 20px;">
            @if(isset($chartPaths['page3_stacked']) && $chartPaths['page3_stacked'])
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="font-size: 14px; margin-bottom: 10px;">(kg CO₂e)</p>
                <img src="{{ $chartPaths['page3_stacked'] }}" alt="Stacked Bar Chart" style="max-width: 100%;">
            </div>
            @endif

            @if(isset($chartPaths['page3_single']) && $chartPaths['page3_single'])
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="font-weight: bold; font-size: 16px; margin-bottom: 15px;">Total Carbon Emissions Avoided in KM</h3>
                <img src="{{ $chartPaths['page3_single'] }}" alt="Carbon Emissions Chart" style="max-width: 100%;">
                <div style="margin-top: 10px;">
                    <span style="font-size: 36px; font-weight: bold; color: #3b82f6;">{{ number_format($reportData['carbonEmissionsAvoided']) }}</span>
                </div>
            </div>
            @endif

            <div style="margin-top: 30px; font-size: 13px;">
                <p style="margin-bottom: 10px;">
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
    <div class="report-container">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 20px;">
            @if(isset($chartPaths['page4_cumulative']) && $chartPaths['page4_cumulative'])
            <div style="margin-bottom: 50px;">
                <h3 style="text-align: center; font-weight: bold; font-size: 18px; color: #4b5563; margin-bottom: 20px;">CUMULATIVE IMPACT DASHBOARD</h3>
                <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                    @foreach($reportData['cumulativeImpact'] as $item)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 16px; height: 16px; background-color: {{ $item['color'] }}; border-radius: 2px;"></div>
                        <span style="font-size: 12px;">{{ $item['name'] }}</span>
                    </div>
                    @endforeach
                </div>
                <img src="{{ $chartPaths['page4_cumulative'] }}" alt="Cumulative Impact Chart" style="max-width: 100%;">
            </div>
            @endif

            @if(isset($chartPaths['page4_recycling']) && $chartPaths['page4_recycling'])
            <div>
                <h3 style="text-align: center; font-weight: bold; font-size: 18px; color: #4b5563; margin-bottom: 20px;">RECYCLING BREAKDOWN</h3>
                <img src="{{ $chartPaths['page4_recycling'] }}" alt="Recycling Breakdown Chart" style="max-width: 100%;">
                <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
                    @foreach($reportData['recyclingBreakdown'] as $item)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 16px; height: 16px; background-color: {{ $item['color'] }}; border-radius: 2px;"></div>
                        <span style="font-size: 12px;">{{ $item['name'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Page 5: Gauge and Waste vs Recovery -->
    <div class="report-container">
        <div class="page-header">
            <h1>WASTE MANAGEMENT REPORT</h1>
            <div class="company">{{ $reportData['companyName'] }}</div>
            <div class="date">{{ $reportData['reportDate'] }}</div>
        </div>

        <div style="padding: 20px;">
            <div style="margin-bottom: 50px;">
                <h3 style="text-align: center; font-weight: bold; font-size: 18px; color: #4b5563; margin-bottom: 30px;">DIVERTED FROM LANDFILL</h3>
                <div style="text-align: center;">
                    <!-- Gauge chart will be rendered here - for now showing percentage -->
                    <div style="font-size: 48px; font-weight: bold; color: #1e3a5f;">
                        {{ number_format($reportData['summary']['divertedFromLandfill'], 1) }}%
                    </div>
                </div>
            </div>

            @if(isset($chartPaths['page5_waste_recovery']) && $chartPaths['page5_waste_recovery'])
            <div>
                <h3 style="text-align: center; font-weight: bold; font-size: 18px; margin-bottom: 30px;">WASTE vs RECOVERY</h3>
                <img src="{{ $chartPaths['page5_waste_recovery'] }}" alt="Waste vs Recovery Chart" style="max-width: 100%;">
            </div>
            @endif
        </div>
    </div>

    <div class="print-button">
        <button onclick="window.print()">Print Report</button>
        <a href="{{ route('reports.waste-management-pdf') }}" style="margin-left: 10px; background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">Download PDF</a>
    </div>
</body>
</html>
