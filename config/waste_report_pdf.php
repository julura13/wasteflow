<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Waste management / Resource Intelligence PDF engine
    |--------------------------------------------------------------------------
    |
    | "browsershot" renders HTML in headless Chromium (Spatie Browsershot) so
    | layouts match a real browser. "dompdf" is the previous engine (no JS).
    |
    */
    'driver' => env('WASTE_REPORT_PDF_DRIVER', 'browsershot'),

    'fallback_to_dompdf' => filter_var(
        env('WASTE_REPORT_PDF_FALLBACK_TO_DOMPDF', true),
        FILTER_VALIDATE_BOOL
    ),

    'browsershot' => [

        'no_sandbox' => filter_var(env('BROWSERSHOT_NO_SANDBOX', true), FILTER_VALIDATE_BOOL),

        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),

        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),

        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),

        'delay_ms' => (int) env('BROWSERSHOT_PDF_DELAY_MS', 500),

        'url_delay_ms' => (int) env('BROWSERSHOT_PDF_URL_DELAY_MS', 4000),

        'timeout_seconds' => (int) env('BROWSERSHOT_PDF_TIMEOUT', 120),

        'window_width' => (int) env('BROWSERSHOT_PDF_WINDOW_WIDTH', 1200),

        'window_height' => (int) env('BROWSERSHOT_PDF_WINDOW_HEIGHT', 2000),

        'margin_top_mm' => (float) env('BROWSERSHOT_PDF_MARGIN_TOP', 0),

        'margin_right_mm' => (float) env('BROWSERSHOT_PDF_MARGIN_RIGHT', 0),

        'margin_bottom_mm' => (float) env('BROWSERSHOT_PDF_MARGIN_BOTTOM', 0),

        'margin_left_mm' => (float) env('BROWSERSHOT_PDF_MARGIN_LEFT', 0),

        'wait_for_network_idle' => filter_var(
            env('BROWSERSHOT_PDF_WAIT_NETWORK_IDLE', false),
            FILTER_VALIDATE_BOOL
        ),

        'emulate_print_media' => filter_var(
            env('BROWSERSHOT_PDF_EMULATE_PRINT', true),
            FILTER_VALIDATE_BOOL
        ),

        'print_background' => true,
    ],

];
