<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Summary notification recipients
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of email addresses to receive the daily recurring
    | orders summary. Leave empty to skip the email.
    |
    | Example: RECURRING_ORDERS_NOTIFY_EMAILS="ops@example.com,admin@example.com"
    |
    */

    'notify_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('RECURRING_ORDERS_NOTIFY_EMAILS', ''))
    ))),

];
