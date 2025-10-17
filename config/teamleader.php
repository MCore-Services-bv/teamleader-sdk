<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Teamleader API Credentials
    |--------------------------------------------------------------------------
    |
    | Get these from https://marketplace.teamleader.eu/
    |
    */
    'client_id' => env('TEAMLEADER_CLIENT_ID'),
    'client_secret' => env('TEAMLEADER_CLIENT_SECRET'),
    'redirect_uri' => env('TEAMLEADER_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'base_url' => env('TEAMLEADER_BASE_URL', 'https://api.focus.teamleader.eu'),
    'auth_url' => env('TEAMLEADER_AUTH_URL', 'https://focus.teamleader.eu'),
    'api_version' => env('TEAMLEADER_API_VERSION', '2023-09-26'),

    /*
    |--------------------------------------------------------------------------
    | Configuration Validation
    |--------------------------------------------------------------------------
    |
    | Validate SDK configuration when the application boots.
    |
    | Recommended settings:
    | - Development: true (catch issues early)
    | - Production: true (log critical errors)
    | - Testing: false (avoid noise in tests)
    |
    | When enabled:
    | - Development: Logs warnings and errors, shows console output
    | - Production: Only logs critical configuration errors
    | - Caches validation results to avoid repeated checks
    |
    */
    'validate_on_boot' => env('TEAMLEADER_VALIDATE_ON_BOOT', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
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
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Teamleader allows 200 requests per sliding minute. The SDK automatically
    | throttles requests when approaching this limit.
    |
    */
    'rate_limiting' => [
        'enabled' => env('TEAMLEADER_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('TEAMLEADER_RATE_LIMIT', 200),
        'throttle_threshold' => env('TEAMLEADER_THROTTLE_THRESHOLD', 0.7),
        'aggressive_throttling' => env('TEAMLEADER_AGGRESSIVE_THROTTLING', true),
        'respect_retry_after' => env('TEAMLEADER_RESPECT_RETRY_AFTER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sideloading Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how related resources are loaded.
    |
    */
    'sideloading' => [
        'enabled' => env('TEAMLEADER_SIDELOADING_ENABLED', true),
        'validate_includes' => env('TEAMLEADER_VALIDATE_INCLUDES', true),
        'max_includes_per_request' => env('TEAMLEADER_MAX_INCLUDES', 10),

        // Pre-configured common includes for each resource
        'common_includes' => [
            'deals' => ['lead.customer', 'responsible_user', 'department', 'phase'],
            'quotations' => ['deal', 'department', 'responsible_user'],
            'invoices' => ['customer', 'department', 'responsible_user'],
            'companies' => ['responsible_user', 'business_type', 'addresses'],
            'contacts' => ['company', 'responsible_user', 'addresses'],
            'projects' => ['customer', 'responsible_user', 'department'],
            'custom_fields' => ['definition'],
            'price_lists' => ['currency'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('TEAMLEADER_LOGGING_ENABLED', true),
        'log_requests' => env('TEAMLEADER_LOG_REQUESTS', false),
        'log_responses' => env('TEAMLEADER_LOG_RESPONSES', false),
        'log_rate_limits' => env('TEAMLEADER_LOG_RATE_LIMITS', true),
        'log_token_refresh' => env('TEAMLEADER_LOG_TOKEN_REFRESH', true),
        'channel' => env('TEAMLEADER_LOG_CHANNEL', config('logging.default')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: In production, use redis or database cache driver, NOT file!
    |
    */
    'caching' => [
        'enabled' => env('TEAMLEADER_CACHING_ENABLED', false),
        'default_ttl' => env('TEAMLEADER_CACHE_TTL', 3600),
        'cache_store' => env('TEAMLEADER_CACHE_STORE', config('cache.default')),

        // Which endpoints to cache (relatively static data)
        'cacheable_endpoints' => [
            'departments.list' => 7200,
            'customFields.list' => 7200,
            'custom_fields.list' => 7200,
            'currencies.list' => 86400,
            'users.list' => 3600,
            'workTypes.list' => 7200,
            'businessTypes.list' => 86400,
            'priceLists.list' => 7200,
            'price_lists.list' => 7200,
            'taxRates.list' => 86400,
            'paymentTerms.list' => 86400,
            'unitOfMeasure.list' => 86400,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing Configuration
    |--------------------------------------------------------------------------
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
    | throw_exceptions: When false, errors are returned in response arrays.
    |                   When true, exceptions are thrown and must be caught.
    |
    */
    'error_handling' => [
        'throw_exceptions' => env('TEAMLEADER_THROW_EXCEPTIONS', false),
        'log_errors' => env('TEAMLEADER_LOG_ERRORS', true),
        'include_stack_trace' => env('TEAMLEADER_INCLUDE_STACK_TRACE', false),
        'parse_teamleader_errors' => env('TEAMLEADER_PARSE_TL_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Teamleader Variables
    |--------------------------------------------------------------------------
    |
    | To keep the .env file clean, we'll add variables in this file so you can re-use them throughout your codebase.
    |
    */

    'custom_fields' => [
        'contact' => [
            'custom_field_name' => 'custom_field_uuid',
        ],
        'company' => [
            'custom_field_name' => 'custom_field_uuid',
        ],

        // Add more custom fields here if needed
    ],

    'price_lists' => [
        'price_lists_name' => 'price_list_uuid',
    ],
];
