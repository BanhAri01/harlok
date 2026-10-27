<?php

return [
    'whatsapp' => [
        'driver' => env('LAPOR_WA_DRIVER', 'log'),
        'fonnte_token' => env('FONNTE_TOKEN'),
        'fonnte_url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
        'device' => env('FONNTE_DEVICE'),
        'webhook_secret' => env('LAPOR_WEBHOOK_SECRET'),
        'daily_outbound_limit' => (int) env('LAPOR_DAILY_OUTBOUND_LIMIT', 800),
        'per_minute' => 12,
    ],

    'ai' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('LAPOR_AI_MODEL', 'claude-haiku-5-5'),
        'timeout' => 20,
        'daily_per_phone' => 30,
    ],

    'simulator' => [
        'enabled' => (bool) env('LAPOR_SIMULATOR', true),
        'per_minute' => 20,
    ],

    'market' => [
        'confirm_minutes' => 30,
        'report_active_days' => 3,
        'outlier_percent' => 30,
        'warn_below_percent' => 10,
        'lookback_days' => 7,
        'nearby_buyers_limit' => 20,
    ],
];
