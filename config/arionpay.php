<?php

return [
    // ArionPay dashboard > your store
    'api_key' => env('ARIONPAY_API_KEY', ''),
    'api_secret' => env('ARIONPAY_API_SECRET', ''),

    // ArionPay dashboard > Webhooks > signing secret
    'webhook_secret' => env('ARIONPAY_WEBHOOK_SECRET', ''),

    // Leave as it is unless ArionPay tells you otherwise.
    'base_url' => env('ARIONPAY_BASE_URL', 'https://merchant.arionpay.com'),

    // true: invoices are test invoices (no real money) and only test webhooks are accepted.
    // Set ARIONPAY_TEST_MODE=false for real payments.
    'test_mode' => (bool) env('ARIONPAY_TEST_MODE', true),

    'webhook' => [
        // Register POST /{path} for ArionPay's payment updates (no CSRF: it is checked by its signature instead).
        'enabled' => true,
        'path' => env('ARIONPAY_WEBHOOK_PATH', 'arionpay/webhook'),
        'middleware' => [],
        // ArionPay retries an update for about 3 days; each event is handled once in this window.
        'dedupe_hours' => 96,
        // Cache store for the "already handled" marks (null = your default store).
        'cache_store' => env('ARIONPAY_CACHE_STORE'),
    ],
];
