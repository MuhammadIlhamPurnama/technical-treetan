<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Xendit Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Xendit Payment Gateway
    |
    */

    'secret_key' => env('XENDIT_SECRET_KEY'),
    'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
    
    'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
    
    'webhook_endpoints' => [
        'invoice' => '/api/webhooks/xendit/invoice',
    ],
    
    'supported_payment_methods' => [
        'BANK_TRANSFER',
        'CREDIT_CARD',
        'EWALLET',
        'RETAIL_OUTLET',
        'QR_CODE',
    ],
    
    'currency' => 'IDR',
    
    'invoice_duration' => 86400, // 24 hours in seconds
];