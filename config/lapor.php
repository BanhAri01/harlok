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
        'start_keyword' => env('LAPOR_START_KEYWORD', 'harlok'),
    ],

    'ai' => [
        'driver' => env('LAPOR_AI_DRIVER') ?: 'anthropic',
        'api_key' => env('LAPOR_AI_KEY') ?: env('ANTHROPIC_API_KEY'),
        'base_url' => env('LAPOR_AI_BASE_URL') ?: 'https://api.deepseek.com',
        'model' => env('LAPOR_AI_MODEL'),
        'models' => ['anthropic' => 'claude-haiku-5-5', 'openai' => 'deepseek-chat'],
        'timeout' => 20,
        'daily_per_phone' => 30,
        'daily_simulator' => (int) env('LAPOR_AI_DAILY_SIMULATOR', 60),
        'daily_global' => (int) env('LAPOR_AI_DAILY_GLOBAL', 500),
    ],

    'simulator' => [
        'enabled' => (bool) env('LAPOR_SIMULATOR', true),
        'per_minute' => 20,
        'keep_days' => 2,
    ],

    'market' => [
        'confirm_minutes' => 30,
        'max_quantity_kg' => 100000,
        'report_active_days' => 3,
        'outlier_percent' => 30,
        'warn_below_percent' => 10,
        'lookback_days' => 7,
        'nearby_buyers_limit' => 20,
        'notify_cooldown_minutes' => 360,
    ],
];
