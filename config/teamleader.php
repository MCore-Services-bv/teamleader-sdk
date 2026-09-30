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

    // Optional: the Teamleader account id this connection must connect to.
    // A callback that connects any other account is refused and nothing is
    // stored. Find it with `php artisan teamleader:status` after connecting.
    'expected_account_id' => env('TEAMLEADER_EXPECTED_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | One application can talk to several Teamleader accounts. The three keys
    | above are the `default` connection; add one block here per extra
    | account, and reach it with Teamleader::connection('antwerp').
    |
    | Every Teamleader account needs its own integration — its client ID and
    | secret only work in that account — so client_id and client_secret are
    | required per connection. redirect_uri may be left out: register the
    | same callback URL in each integration and it falls back to the one
    | above.
    |
    | Each connection has its own tokens, refresh lock and rate-limit window.
    |
    */
    'default' => env('TEAMLEADER_CONNECTION', 'default'),

    'connections' => [
        // 'antwerp' => [
        //     'client_id' => env('TEAMLEADER_ANTWERP_CLIENT_ID'),
        //     'client_secret' => env('TEAMLEADER_ANTWERP_CLIENT_SECRET'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    // Where API requests and OAuth calls go. Change these only for a proxy or
    // a sandbox; the defaults are Teamleader's production hosts.
    'base_url' => env('TEAMLEADER_BASE_URL', 'https://api.focus.teamleader.eu'),
    'auth_url' => env('TEAMLEADER_AUTH_URL', 'https://focus.teamleader.eu'),
    'api_version' => env('TEAMLEADER_API_VERSION', '2023-09-26'),

    /*
    |--------------------------------------------------------------------------
    | Token Renewal
    |--------------------------------------------------------------------------
    |
    | teamleader:tokens:refresh renews every connection whose access token
    | expires within refresh_before seconds. With auto_refresh on, the package
    | schedules it every ten minutes — the Laravel scheduler has to run
    | (`php artisan schedule:work`, or a cron entry for `schedule:run`).
    |
    */
    'tokens' => [
        'auto_refresh' => env('TEAMLEADER_TOKENS_AUTO_REFRESH', true),
        'refresh_before' => env('TEAMLEADER_TOKENS_REFRESH_BEFORE', 1800),
    ],

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
        // Server errors (5xx) and connection failures are retried. The delay
        // doubles per attempt from retry_delay (milliseconds), capped at 30 s.
        'retry_attempts' => env('TEAMLEADER_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('TEAMLEADER_API_RETRY_DELAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Teamleader allows 200 requests per sliding minute per integration. The
    | limit is Teamleader's, not a setting: the SDK tracks the window in Redis
    | and slows down as it fills.
    |
    | When the window is full the SDK waits for a slot rather than firing a
    | request that can only come back as a 429. That wait is capped by
    | max_wait_ms: once the cap is reached, a RateLimitExceededException is
    | thrown and the decision returns to the caller.
    |
    | The default is deliberately short. Waiting out a full window can take up
    | to a minute, which in a queue worker is a held slot and in a web request
    | is a hanging page. Catching the exception and calling release() with
    | getRetryAfter() is usually better than blocking. Raise this if you would
    | rather the SDK sit out longer stalls itself — a CLI import, for instance,
    | where blocking costs nothing.
    |
    */
    'rate_limiting' => [
        'enabled' => env('TEAMLEADER_RATE_LIMITING_ENABLED', true),
        'redis_connection' => env('TEAMLEADER_RATE_LIMIT_REDIS_CONNECTION', 'default'),

        // Maximum time the SDK will wait for a rate limit slot before throwing.
        // Set to 65000 to wait out a full sliding window.
        'max_wait_ms' => env('TEAMLEADER_RATE_LIMIT_MAX_WAIT_MS', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    // channel: where SDK logs go; null uses your default channel.
    // log_requests / log_responses: write every request / response body to
    // that channel at debug level. Off by default — bodies hold your
    // customers' data. Tokens and secrets are always redacted.
    'logging' => [
        'channel' => env('TEAMLEADER_LOG_CHANNEL'),
        'log_requests' => env('TEAMLEADER_LOG_REQUESTS', false),
        'log_responses' => env('TEAMLEADER_LOG_RESPONSES', false),
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
