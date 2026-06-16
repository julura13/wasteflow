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

    /*
    |--------------------------------------------------------------------------
    | Summary SMS recipient
    |--------------------------------------------------------------------------
    |
    | Single phone number to receive a short SMS confirming the daily
    | recurring orders run, sent via the Communicator microservice. Leave
    | empty to skip the SMS. Requires COMMUNICATOR_ENABLED=true.
    |
    | Example: RECURRING_ORDERS_SMS_TO="27817878984"
    |
    */

    'sms_to' => env('RECURRING_ORDERS_SMS_TO'),

];
