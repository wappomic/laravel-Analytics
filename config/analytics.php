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
    'queue_connection' => env('ANALYTICS_QUEUE_CONNECTION', 'redis'),
    'queue_name' => env('ANALYTICS_QUEUE_NAME', 'analytics'),

    /*
    |--------------------------------------------------------------------------
    | Session Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Configure session-based visitor tracking for unique visitors.
    | Uses anonymous hash to identify same user across multiple page views.
    | No cookies required - GDPR compliant.
    |
    */
    'session_tracking_enabled' => env('ANALYTICS_SESSION_TRACKING_ENABLED', true),
    'session_ttl_hours' => env('ANALYTICS_SESSION_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Excluded Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Routes that should NOT be tracked by the analytics middleware.
    | Supports wildcard patterns using '*'. 
    | 
    | Examples:
    | - '/admin*' matches '/admin', '/admin/users', '/admin/dashboard'
    | - '*.json' matches any route ending with .json
    | - '/api/*' matches any route starting with /api/
    |
    | You can customize this list or set it to an empty array [] to track all routes.
    | Individual routes can still be excluded using ->withoutMiddleware('analytics.tracking')
    |
    */
    'excluded_routes' => [
        '/admin*',
        '/api*',
        '/broadcasting*',
        'broadcasting*',
        '*broadcasting*',
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
];