<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Material Definitions — {{ $generatedAt->format('Y-m-d') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #333;
            line-height: 1.25;
        }
        @page { margin: 18mm 16mm; }
        .header {
            background-color: #2563eb;
            color: #fff;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 3px;
        }
        .header h1 {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .header .meta {
            font-size: 8px;
            opacity: 0.95;
            margin-top: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        thead {
            background-color: #1e3a5f;
            color: #fff;
        }
        th, td {
            padding: 4px 3px;
            text-align: left;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }
        th { font-weight: 600; }
        tbody tr:nth-child(even) { background-color: #f9fafb; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .notes { max-width: 72px; word-wrap: break-word; }
        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #2563eb;
            font-size: 8px;
            color: #2563eb;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MATERIAL DEFINITIONS</h1>
        <div class="meta">Generated {{ $generatedAt->format('d/m/Y H:i') }} — {{ $materials->count() }} row(s)</div>
        <div class="meta">{{ $filterSummary }}</div>
    </div>

    @if($materials->isEmpty())
        <p>No materials match the current filters.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Waste stream</th>
                    <th>Grade</th>
                    <th>Classification</th>
                    <th>Facility</th>
                    <th>Service provider</th>
                    <th>Weight req.</th>
                    <th class="center">Rebate</th>
                    <th class="num">Rate (R/kg)</th>
                    <th class="num">Client %</th>
                    <th class="center">Backing</th>
                    <th class="center">Active</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($materials as $material)
                    <tr>
                        <td>{{ $material->id }}</td>
                        <td>{{ $material->wasteStream?->name ?? '—' }}</td>
                        <td>{{ $material->grade?->name ?? '—' }}</td>
                        <td>{{ $material->classification?->name ?? '—' }}</td>
                        <td>{{ $material->facility?->name ?? '—' }}</td>
                        <td>{{ $material->serviceProvider?->name ?? '—' }}</td>
                        <td>{{ $material->weight_required }}</td>
                        <td class="center">{{ $material->rebate_offered ? 'Yes' : 'No' }}</td>
                        <td class="num">{{ $material->rebate_rate !== null ? number_format((float) $material->rebate_rate, 2) : '—' }}</td>
                        <td class="num">{{ $material->client_rebate_share !== null ? number_format((float) $material->client_rebate_share, 2) : '—' }}</td>
                        <td class="center">{{ $material->backing_document ? 'Yes' : 'No' }}</td>
                        <td class="center">{{ $material->is_active ? 'Yes' : 'No' }}</td>
                        <td class="notes">{{ $material->notes ? \Illuminate\Support\Str::limit($material->notes, 100) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <strong>WASTEFLOW</strong> — Material definitions export
    </div>
</body>
</html>
