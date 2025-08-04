<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Enabled
    |--------------------------------------------------------------------------
    |
    | This value determines if the analytics tracking is enabled.
    | When disabled, no tracking data will be collected.
    |
    */
    'enabled' => env('ANALYTICS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | External API configuration for sending analytics data.
    | api_url and api_key are REQUIRED for the package to work.
    | app_name is optional and helps identify your app in the analytics dashboard.
    |
    */
    'api_url' => env('ANALYTICS_API_URL'), // REQUIRED
    'api_key' => env('ANALYTICS_API_KEY'), // REQUIRED
    'app_name' => env('ANALYTICS_APP_NAME', null), // Optional - for multi-app setups

    /*
    |--------------------------------------------------------------------------
    | GDPR Compliance
    |--------------------------------------------------------------------------
    |
    | Data is ALWAYS anonymized immediately upon collection for GDPR compliance.
    | This setting cannot be changed - it's always true.
    |
    */
    'anonymize_immediately' => true, // IMMER true

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue processing for analytics data.
    | Recommended for better performance.
    |
    */
    'queue_enabled' => env('ANALYTICS_QUEUE_ENABLED', true),
];