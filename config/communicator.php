<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Communicator microservice (sndng.co.za)
    |--------------------------------------------------------------------------
    |
    | When enabled, database backup lifecycle notifications are sent via this
    | API instead of Laravel's default mailer.
    |
    */

    'enabled' => filter_var(env('COMMUNICATOR_ENABLED', false), FILTER_VALIDATE_BOOL),

    'url' => rtrim((string) env('COMMUNICATOR_URL', 'https://sndng.co.za'), '/'),

    'token' => env('COMMUNICATOR_API_TOKEN'),

];
