<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Waste Diversion</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            margin: 0;
            size: 279.4mm 215.9mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
        }
        .page {
            position: relative;
            width: 279.4mm;
            height: 215.9mm;
        }
        .page img.background {
            position: absolute;
            top: 0;
            left: 0;
            width: 279.4mm;
            height: 215.9mm;
        }
        .field {
            position: absolute;
            text-align: center;
        }
        .congratulates-label {
            left: 20mm;
            top: 105.5mm;
            width: 243.7mm;
            height: 8mm;
            font-size: 15pt;
            letter-spacing: 0.3pt;
            color: #1a1a1a;
        }
        .company-name {
            left: 65mm;
            top: 111mm;
            width: 145mm;
            height: 14mm;
            white-space: nowrap;
            font-family: 'DejaVu Serif', serif;
            font-weight: bold;
            color: #387026;
        }
        .summary-line {
            left: 20mm;
            top: 136mm;
            width: 243.7mm;
            height: 19mm;
            color: #1a1a1a;
        }
        .certificate-date {
            left: 179.5mm;
            top: 156mm;
            width: 34.7mm;
            height: 8mm;
            color: #1a1a1a;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    @php
        $templatePath = public_path('images/certificate-template.jpg');
        $templateSrc = null;
        if (is_file($templatePath)) {
            $cacheKey = 'certificate_template_data_uri_'.filemtime($templatePath);
            $templateSrc = \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () use ($templatePath) {
                return 'data:'.mime_content_type($templatePath).';base64,'.base64_encode((string) file_get_contents($templatePath));
            });
        }
    @endphp
    <div class="page">
        @if($templateSrc)
            <img class="background" src="{{ $templateSrc }}" alt="">
        @endif

        <div class="field congratulates-label">WASTEFLOW CONGRATULATES</div>

        <div class="field company-name" style="font-size: {{ $companyNameFontSize }}pt">{{ $companyNameUpper }}</div>

        <div class="field summary-line" style="font-size: {{ $summaryFontSize }}pt; line-height: {{ $summaryLineHeight }}">
            @if($tierNameUpper)
                A DIVERSION OF <strong>{{ $percentageDisplay }}%</strong> WAS ACHIEVED FOR {{ $monthYearUpper }}, EARNING A
                <strong style="color: {{ $tierColor }}">{{ $tierNameUpper }}</strong> RESOURCE RECOVERY RATING&#8482;,
                DEMONSTRATING <strong>{{ $companyNameUpper }}&#8217;S</strong> CONTINUED WASTE DIVERSION SUCCESS
            @else
                A DIVERSION OF <strong>{{ $percentageDisplay }}%</strong> WAS ACHIEVED FOR THE MONTH OF {{ $monthYearUpper }} DEMONSTRATING THE
                CONTINUED SUCCESS OF <strong>{{ $companyNameUpper }}&#8217;S</strong> WASTE DIVERSION PROGRAM
            @endif
        </div>

        <div class="field certificate-date" style="font-size: {{ $dateFontSize }}pt">{{ $completeDateUpper }}</div>
    </div>
</body>
</html>
