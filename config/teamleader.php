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
        'sanitize_logs' => env('TEAMLEADER_SANITIZE_LOGS', true),
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
    | Store frequently used Teamleader UUIDs here to keep your codebase clean.
    | Access these values using: config('teamleader.custom_fields.contact.field_name')
    |
    | To get UUIDs from Teamleader, use the SDK list methods:
    | - Teamleader::customFields()->list();
    | - Teamleader::departments()->list();
    | - Teamleader::users()->list();
    | etc.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Custom Fields
    |--------------------------------------------------------------------------
    |
    | Store custom field UUIDs for easy reference throughout your application.
    | Usage: config('teamleader.custom_fields.contact.newsletter_subscription')
    |
    */
    'custom_fields' => [
        'contact' => [
            // Example: 'newsletter_subscription' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            // Example: 'preferred_language' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ],

        'company' => [
            // Example: 'industry_segment' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            // Example: 'annual_revenue' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ],

        'deal' => [
            // Example: 'deal_priority' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            // Example: 'competitor' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ],

        'project' => [
            // Example: 'project_complexity' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            // Example: 'project_manager' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ],

        'invoice' => [
            // Example: 'payment_reference' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    |
    | Store department UUIDs for filtering and assignment.
    | Usage: config('teamleader.departments.sales')
    |
    */
    'departments' => [
        // Example: 'sales' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'marketing' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'support' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'development' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | Store user UUIDs for assignments and filtering.
    | Usage: config('teamleader.users.sales_manager')
    |
    */
    'users' => [
        // Example: 'sales_manager' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'account_manager' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'support_lead' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Teams
    |--------------------------------------------------------------------------
    |
    | Store team UUIDs for group operations.
    | Usage: config('teamleader.teams.sales_team')
    |
    */
    'teams' => [
        // Example: 'sales_team' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'support_team' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Deal Pipelines
    |--------------------------------------------------------------------------
    |
    | Store pipeline UUIDs for deal management.
    | Usage: config('teamleader.pipelines.sales')
    |
    */
    'pipelines' => [
        // Example: 'sales' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'enterprise' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'partnerships' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Deal Phases
    |--------------------------------------------------------------------------
    |
    | Store phase UUIDs for deal progression.
    | Usage: config('teamleader.deal_phases.qualified')
    |
    */
    'deal_phases' => [
        // Example: 'new' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'qualified' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'proposal' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'negotiation' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'closed_won' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Deal Sources
    |--------------------------------------------------------------------------
    |
    | Store deal source UUIDs for tracking lead origins.
    | Usage: config('teamleader.deal_sources.website')
    |
    */
    'deal_sources' => [
        // Example: 'website' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'referral' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'cold_call' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'social_media' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'event' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lost Reasons
    |--------------------------------------------------------------------------
    |
    | Store lost reason UUIDs for deal analysis.
    | Usage: config('teamleader.lost_reasons.price')
    |
    */
    'lost_reasons' => [
        // Example: 'price' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'timing' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'competitor' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'no_budget' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Work Types
    |--------------------------------------------------------------------------
    |
    | Store work type UUIDs for time tracking and billing.
    | Usage: config('teamleader.work_types.consulting')
    |
    */
    'work_types' => [
        // Example: 'consulting' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'development' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'design' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'project_management' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'support' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Price Lists
    |--------------------------------------------------------------------------
    |
    | Store price list UUIDs for product pricing.
    | Usage: config('teamleader.price_lists.standard')
    |
    */
    'price_lists' => [
        // Example: 'standard' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'wholesale' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'retail' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'enterprise' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Terms
    |--------------------------------------------------------------------------
    |
    | Store payment term UUIDs for invoicing.
    | Usage: config('teamleader.payment_terms.net_30')
    |
    */
    'payment_terms' => [
        // Example: 'immediate' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'net_30' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'net_60' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'end_of_month' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Rates
    |--------------------------------------------------------------------------
    |
    | Store tax rate UUIDs for invoicing and quotations.
    | Usage: config('teamleader.tax_rates.vat_21')
    |
    */
    'tax_rates' => [
        // Example: 'vat_21' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'vat_12' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'vat_6' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'vat_0' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Types
    |--------------------------------------------------------------------------
    |
    | Store business type UUIDs for company classification.
    | Usage: config('teamleader.business_types.bv')
    |
    */
    'business_types' => [
        // Belgium examples:
        // 'bv' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // Besloten Vennootschap
        // 'nv' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // Naamloze Vennootschap
        // 'vof' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // Vennootschap onder firma

        // Netherlands examples:
        // 'bv_nl' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // 'nv_nl' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',

        // Other examples:
        // 'ltd' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // UK Limited Company
        // 'gmbh' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // German GmbH
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Categories
    |--------------------------------------------------------------------------
    |
    | Store product category UUIDs for product organization.
    | Usage: config('teamleader.product_categories.software')
    |
    */
    'product_categories' => [
        // Example: 'software' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'hardware' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'services' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'consulting' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Types
    |--------------------------------------------------------------------------
    |
    | Store activity type UUIDs for calendar events.
    | Usage: config('teamleader.activity_types.meeting')
    |
    */
    'activity_types' => [
        // Example: 'meeting' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'call' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'task' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Call Outcomes
    |--------------------------------------------------------------------------
    |
    | Store call outcome UUIDs for call tracking.
    | Usage: config('teamleader.call_outcomes.interested')
    |
    */
    'call_outcomes' => [
        // Example: 'interested' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'not_interested' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'follow_up' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'voicemail' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket Statuses
    |--------------------------------------------------------------------------
    |
    | Store ticket status UUIDs for ticket management.
    | Usage: config('teamleader.ticket_statuses.open')
    |
    */
    'ticket_statuses' => [
        // Example: 'open' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'in_progress' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'waiting_customer' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'resolved' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'closed' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Commercial Discounts
    |--------------------------------------------------------------------------
    |
    | Store commercial discount UUIDs for pricing.
    | Usage: config('teamleader.commercial_discounts.volume')
    |
    */
    'commercial_discounts' => [
        // Example: 'volume' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'seasonal' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'loyalty' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Units of Measure
    |--------------------------------------------------------------------------
    |
    | Store unit of measure UUIDs for products.
    | Usage: config('teamleader.units_of_measure.hour')
    |
    */
    'units_of_measure' => [
        // Example: 'hour' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'day' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'piece' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'meter' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'kilogram' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Templates
    |--------------------------------------------------------------------------
    |
    | Store document template UUIDs for invoice/quotation generation.
    | Usage: config('teamleader.document_templates.invoice_standard')
    |
    */
    'document_templates' => [
        // Example: 'invoice_standard' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'invoice_detailed' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'quotation_standard' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'creditnote_standard' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Project Groups
    |--------------------------------------------------------------------------
    |
    | Store project group UUIDs for project organization.
    | Usage: config('teamleader.project_groups.client_projects')
    |
    */
    'project_groups' => [
        // Example: 'client_projects' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'internal_projects' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        // Example: 'r_and_d' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    |
    | Commonly used tag names for easy reference.
    | Note: Tags don't have UUIDs, but storing common tag names here
    | helps maintain consistency across your application.
    | Usage: config('teamleader.tags.vip')
    |
    */
    'tags' => [
        // Example: 'vip' => 'VIP Customer',
        // Example: 'enterprise' => 'Enterprise',
        // Example: 'needs_attention' => 'Needs Attention',
        // Example: 'hot_lead' => 'Hot Lead',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Your organization's default currency code.
    | Usage: config('teamleader.default_currency')
    |
    */
    'default_currency' => env('TEAMLEADER_DEFAULT_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Default Country
    |--------------------------------------------------------------------------
    |
    | Your organization's default country code (ISO 3166-1 alpha-2).
    | Usage: config('teamleader.default_country')
    |
    */
    'default_country' => env('TEAMLEADER_DEFAULT_COUNTRY', 'BE'),
];
