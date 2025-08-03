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
    | Both api_url and api_key are REQUIRED for the package to work.
    |
    */
    'api_url' => env('ANALYTICS_API_URL'), // REQUIRED
    'api_key' => env('ANALYTICS_API_KEY'), // REQUIRED
    'api_timeout' => env('ANALYTICS_API_TIMEOUT', 10), // seconds

    /*
    |--------------------------------------------------------------------------
    | Auto Track
    |--------------------------------------------------------------------------
    |
    | Automatically add tracking middleware to the 'web' middleware group.
    | Set to false if you want to manually apply the middleware.
    |
    */
    'auto_track' => env('ANALYTICS_AUTO_TRACK', true),

    /*
    |--------------------------------------------------------------------------
    | Anonymization Settings
    |--------------------------------------------------------------------------
    |
    | GDPR-compliant anonymization settings.
    | Data is ALWAYS anonymized immediately upon collection.
    |
    */
    'anonymize_immediately' => true, // ALWAYS true for GDPR compliance
    'ip_anonymization_mask' => env('ANALYTICS_IP_MASK', '255.255.255.0'),
    'round_timestamps_to_hour' => env('ANALYTICS_ROUND_TIMESTAMPS', true),
    'geo_precision' => env('ANALYTICS_GEO_PRECISION', 'country'), // country only

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
    'queue_connection' => env('ANALYTICS_QUEUE_CONNECTION', 'default'),
    'queue_name' => env('ANALYTICS_QUEUE_NAME', 'analytics'),

    /*
    |--------------------------------------------------------------------------
    | Data Collection
    |--------------------------------------------------------------------------
    |
    | Configure what data should be collected.
    | All data is anonymized immediately.
    |
    */
    'track_referrers' => env('ANALYTICS_TRACK_REFERRERS', true),
    'track_query_strings' => env('ANALYTICS_TRACK_QUERY_STRINGS', false),

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | List of URL paths that should not be tracked.
    | Supports wildcard patterns using '*'.
    |
    */
    'excluded_paths' => [
        '/admin*',
        '/api*',
        '/health*',
        '/robots.txt',
        '/sitemap.xml',
        '*.json',
        '*.xml',
        '*.css',
        '*.js',
        '*.ico',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.gif',
        '*.svg',
        '*.woff*',
        '*.ttf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded User Agents
    |--------------------------------------------------------------------------
    |
    | List of user agent patterns that should not be tracked.
    | Useful for excluding bots and crawlers.
    |
    */
    'excluded_user_agents' => [
        '*bot*',
        '*crawler*',
        '*spider*',
        '*scraper*',
        'Googlebot',
        'Bingbot',
        'YandexBot',
        'facebookexternalhit',
        'Twitterbot',
        'WhatsApp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings.
    |
    */
    'async_processing' => env('ANALYTICS_ASYNC_PROCESSING', true),
    'request_timeout' => env('ANALYTICS_REQUEST_TIMEOUT', 2), // seconds (very short)
    'retry_attempts' => env('ANALYTICS_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('ANALYTICS_RETRY_DELAY', 1000), // milliseconds
];