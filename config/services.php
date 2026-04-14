<?php

return [
    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'monflow' => [
        'suspend_delay_days' => (int) env('SUSPEND_DELAY_DAYS', 7),
        'delete_delay_days' => (int) env('DELETE_DELAY_DAYS', 30),
    ],
];
