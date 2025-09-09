<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Teamleader API Credentials
    |--------------------------------------------------------------------------
    |
    | These values are required to authenticate with the Teamleader API.
    | You can obtain them by registering an application at
    | https://marketplace.teamleader.eu/
    |
    */

    'client_id' => env('TEAMLEADER_CLIENT_ID', ''),
    'client_secret' => env('TEAMLEADER_CLIENT_SECRET', ''),
    'redirect_uri' => env('TEAMLEADER_REDIRECT_URI', ''),

    /*
    |--------------------------------------------------------------------------
    | Teamleader API Configuration
    |--------------------------------------------------------------------------
    |
    | Additional configuration options for the Teamleader API.
    |
    */

    'base_url' => env('TEAMLEADER_BASE_URL', 'https://api.focus.teamleader.eu'),
    'auth_url' => env('TEAMLEADER_AUTH_URL', 'https://focus.teamleader.eu'),
    'cache_lifetime' => env('TEAMLEADER_CACHE_LIFETIME', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | API Version Management
    |--------------------------------------------------------------------------
    |
    | Teamleader API version to use. The current latest version is 2023-09-26.
    | You can upgrade to newer versions by changing this value.
    |
    */

    'api_version' => env('TEAMLEADER_API_VERSION', '2023-09-26'),

    /*
    |--------------------------------------------------------------------------
    | OAuth 2 Scopes
    |--------------------------------------------------------------------------
    |
    | Define the scopes your integration needs. These are configured in the
    | Teamleader Marketplace when you register your integration.
    | This is mainly for documentation purposes.
    |
    */

    'scopes' => env('TEAMLEADER_SCOPES', ''),

    /*
    |--------------------------------------------------------------------------
    | API Reliability Settings
    |--------------------------------------------------------------------------
    |
    | Configure timeouts and retry behavior for API requests.
    |
    */

    'api' => [
        'timeout' => env('TEAMLEADER_API_TIMEOUT', 30),
        'connect_timeout' => env('TEAMLEADER_API_CONNECT_TIMEOUT', 10),
        'read_timeout' => env('TEAMLEADER_API_READ_TIMEOUT', 25),
        'retry_attempts' => env('TEAMLEADER_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('TEAMLEADER_API_RETRY_DELAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sideloading Configuration
    |--------------------------------------------------------------------------
    |
    | Configure sideloading behavior for the SDK.
    |
    */

    'sideloading' => [
        'enabled' => env('TEAMLEADER_SIDELOADING_ENABLED', true),
        'validate_includes' => env('TEAMLEADER_VALIDATE_INCLUDES', true),
        'max_includes_per_request' => env('TEAMLEADER_MAX_INCLUDES', 10),

        // Common includes that are frequently used together
        'common_includes' => [
            'deals' => ['lead.customer', 'responsible_user', 'department'],
            'contacts' => ['company', 'responsible_user'],
            'companies' => ['responsible_user', 'addresses'],
            'quotations' => ['lead.customer', 'responsible_user', 'deal'],
            'invoices' => ['customer', 'responsible_user', 'department'],
            'projects' => ['customer', 'responsible_user', 'department'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting behavior. Teamleader uses a sliding window
    | approach with 200 requests per minute.
    |
    */

    'rate_limiting' => [
        'enabled' => env('TEAMLEADER_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('TEAMLEADER_RATE_LIMIT', 200),
        'throttle_threshold' => env('TEAMLEADER_THROTTLE_THRESHOLD', 0.7), // 70%
        'aggressive_throttling' => env('TEAMLEADER_AGGRESSIVE_THROTTLING', true),
        'respect_retry_after' => env('TEAMLEADER_RESPECT_RETRY_AFTER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Teamleader Custom Fields
    |--------------------------------------------------------------------------
    |
    | UUIDs for custom fields used in the application.
    | These can be found using the custom fields management UI.
    |
    */

    'custom_fields' => [
        'contact' => [
            'string' => env('TEAMLEADER_CUSTOM_FIELD_CONTACT_STRING', ''),
        ],

        // Add more custom fields here if needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Teamleader Price Lists
    |--------------------------------------------------------------------------
    |
    | UUIDs for price lists used in the application.
    |
    */

    'price_lists' => [
        'promo_price' => env('TEAMLEADER_PRICE_LIST_PROMO_PRICE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Note: Teamleader webhooks do not use HMAC signature verification.
    | The webhook_secret here is for webhook registration, not verification.
    |
    */

    'webhook_secret' => env('TEAMLEADER_WEBHOOK_SECRET'),
    'webhook_rate_limit' => env('TEAMLEADER_WEBHOOK_RATE_LIMIT', 100), // per minute

    /*
    |--------------------------------------------------------------------------
    | Webhook Processing Configuration
    |--------------------------------------------------------------------------
    */

    'webhook_processing' => [
        'max_retries' => env('TEAMLEADER_WEBHOOK_MAX_RETRIES', 5),
        'retry_delay' => env('TEAMLEADER_WEBHOOK_RETRY_DELAY', 30), // seconds
        'timeout' => env('TEAMLEADER_WEBHOOK_TIMEOUT', 120), // seconds
        'queue' => env('TEAMLEADER_WEBHOOK_QUEUE', 'webhooks'),
        'verify_signature' => false, // Teamleader doesn't support HMAC verification
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure what gets logged by the SDK.
    |
    */

    'logging' => [
        'enabled' => env('TEAMLEADER_LOGGING_ENABLED', true),
        'log_requests' => env('TEAMLEADER_LOG_REQUESTS', false),
        'log_responses' => env('TEAMLEADER_LOG_RESPONSES', false),
        'log_rate_limits' => env('TEAMLEADER_LOG_RATE_LIMITS', true),
        'log_token_refresh' => env('TEAMLEADER_LOG_TOKEN_REFRESH', true),
        'channel' => env('TEAMLEADER_LOG_CHANNEL', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure response caching for static data.
    |
    */

    'caching' => [
        'enabled' => env('TEAMLEADER_CACHING_ENABLED', false),
        'default_ttl' => env('TEAMLEADER_CACHE_TTL', 3600), // 1 hour
        'cache_store' => env('TEAMLEADER_CACHE_STORE', 'default'),

        // Which endpoints to cache (relatively static data)
        'cacheable_endpoints' => [
            'departments.list' => 7200, // 2 hours
            'customFields.list' => 7200,
            'currencies.list' => 86400, // 24 hours
            'users.list' => 3600, // 1 hour
            'workTypes.list' => 7200,
            'businessTypes.list' => 86400,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */

    'development' => [
        'sandbox_mode' => env('TEAMLEADER_SANDBOX_MODE', false),
        'mock_responses' => env('TEAMLEADER_MOCK_RESPONSES', false),
        'debug_mode' => env('TEAMLEADER_DEBUG_MODE', false),
        'log_all_requests' => env('TEAMLEADER_LOG_ALL_REQUESTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how errors are handled by the SDK.
    |
    */

    'error_handling' => [
        'throw_exceptions' => env('TEAMLEADER_THROW_EXCEPTIONS', false),
        'log_errors' => env('TEAMLEADER_LOG_ERRORS', true),
        'include_stack_trace' => env('TEAMLEADER_INCLUDE_STACK_TRACE', false),
        'parse_teamleader_errors' => env('TEAMLEADER_PARSE_TL_ERRORS', true),
    ],
];
