# Teamleader Focus SDK for Laravel

[![Latest Version](https://img.shields.io/github/v/release/mcore-services/teamleader-sdk)](https://github.com/mcore-services/teamleader-sdk/releases)
[![PHP Version](https://img.shields.io/packagist/php-v/mcore-services/teamleader-sdk)](https://packagist.org/packages/mcore-services/teamleader-sdk)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-blue)](https://laravel.com)
[![License](https://img.shields.io/github/license/mcore-services/teamleader-sdk)](LICENSE.md)

A comprehensive, production-ready Laravel package for integrating with the Teamleader Focus API. Built with modern Laravel best practices, featuring automatic token management, intelligent rate limiting, resource sideloading, and complete coverage of all Teamleader Focus API endpoints.

## ✨ Key Features

### 🔐 Authentication & Security
- **Complete OAuth 2.0 Flow** - Authorization URL generation and secure callback handling
- **Automatic Token Management** - Smart token refresh with database and cache layers
- **Concurrent Request Safety** - Distributed locking prevents token refresh race conditions

### 🚀 Performance & Reliability
- **Intelligent Rate Limiting** - Built-in sliding window rate limiter with automatic throttling (200 req/min)
- **Response Caching** - Configurable caching for static data endpoints
- **Connection Pooling** - Optimized HTTP client with configurable timeouts
- **Retry Logic** - Automatic retry with exponential backoff for transient failures

### 📦 Developer Experience
- **Resource-Based Architecture** - Intuitive, organized access to all API endpoints
- **Fluent Sideloading Interface** - Reduce API calls by including related resources
- **Comprehensive Validation** - Request validation before API calls
- **Rich Error Handling** - Detailed, actionable error messages
- **Extensive Logging** - Debug-friendly logging with configurable levels
- **Resource Introspection** - Query capabilities of any resource programmatically

### 🎯 Complete API Coverage

**CRM Resources**
- Companies, Contacts, Business Types, Tags, Addresses

**Deals & Sales**
- Deals, Quotations, Orders, Pipelines, Phases, Sources, Lost Reasons

**Invoicing**
- Invoices, Credit Notes, Payment Methods, Payment Terms, Tax Rates, Withholding Tax Rates, Commercial Discounts, Subscriptions

**Projects & Time Tracking**
- Projects (v1 & v2), Project Tasks, Milestones, Time Tracking, Timers

**Calendar & Activities**
- Meetings, Calls, Call Outcomes, Calendar Events, Activity Types

**Products & Services**
- Products, Product Categories, Unit of Measures, Work Types

**General Management**
- Users, Departments, Custom Fields, Currencies, Notes, Files

**System & Migration**
- Webhooks, Cloud Platforms, Accounts, Migration Utilities

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 10.x, 11.x, or 12.x
- **Extensions**: ext-json, ext-mbstring
- **Database**: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+

## 🚀 Installation

### 1. Install via Composer

```bash
composer require mcore-services/teamleader-sdk
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="McoreServices\TeamleaderSDK\TeamleaderServiceProvider"
```

This creates `config/teamleader.php` with extensive configuration options.

### 3. Run Migrations

Create the tokens table for persistent OAuth token storage:

```bash
php artisan migrate
```

The SDK will create a `teamleader_tokens` table automatically.

### 4. Configure Environment

Add your Teamleader credentials to `.env`:

```env
TEAMLEADER_CLIENT_ID=your_client_id_here
TEAMLEADER_CLIENT_SECRET=your_client_secret_here
TEAMLEADER_REDIRECT_URI=${APP_URL}/auth/teamleader/callback

# Optional configurations
TEAMLEADER_API_VERSION=2023-09-26
TEAMLEADER_RATE_LIMITING_ENABLED=true
TEAMLEADER_CACHING_ENABLED=true
TEAMLEADER_LOGGING_ENABLED=true
```

### 5. Register Your Integration

1. Visit [Teamleader Marketplace](https://marketplace.teamleader.eu/)
2. Create a new integration
3. Configure your redirect URIs
4. Set appropriate scopes for your needs

## 📚 Quick Start

### Basic Setup

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CompanyController extends Controller
{
    public function __construct(
        private TeamleaderSDK $teamleader
    ) {}
}
```

### Authentication Flow

```php
// Step 1: Redirect to Teamleader for authorization
public function authorize()
{
    $state = Str::random(40);
    session(['teamleader_state' => $state]);
    
    return $this->teamleader->authorize($state);
}

// Step 2: Handle the OAuth callback
public function callback(Request $request)
{
    $code = $request->get('code');
    $state = $request->get('state');
    
    // Validate state to prevent CSRF
    if ($state !== session('teamleader_state')) {
        abort(403, 'Invalid state parameter');
    }
    
    if ($this->teamleader->handleCallback($code, $state)) {
        return redirect('/dashboard')
            ->with('success', 'Successfully connected to Teamleader!');
    }
    
    return redirect('/auth/error')
        ->with('error', 'Failed to authenticate with Teamleader');
}

// Check authentication status
public function dashboard()
{
    if (!$this->teamleader->isAuthenticated()) {
        return redirect('/auth/teamleader');
    }
    
    $user = $this->teamleader->users()->me();
    return view('dashboard', compact('user'));
}

// Logout
public function logout()
{
    $this->teamleader->logout();
    return redirect('/')->with('success', 'Logged out successfully');
}
```

## 💡 Usage Examples

### Companies

```php
// List companies with pagination
$companies = $this->teamleader->companies()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Search companies
$results = $this->teamleader->companies()->search('MCore');

// Search by specific fields
$byEmail = $this->teamleader->companies()->byEmail('info@example.com');
$byVat = $this->teamleader->companies()->byVatNumber('BE0123456789');

// Get company details with related data
$company = $this->teamleader->companies()
    ->with(['addresses', 'responsible_user', 'tags'])
    ->info('company-uuid-here');

// Create a company
$newCompany = $this->teamleader->companies()->create([
    'name' => 'Acme Corporation',
    'emails' => [
        ['type' => 'primary', 'email' => 'info@acme.com']
    ],
    'vat_number' => 'BE0123456789',
    'business_type_id' => 'business-type-uuid'
]);

// Update a company
$updated = $this->teamleader->companies()->update('company-uuid', [
    'name' => 'Acme Corp International'
]);

// Add tags
$this->teamleader->companies()->tag('company-uuid', ['VIP', 'Enterprise']);

// Link a company to another company
$this->teamleader->companies()->linkToCompany(
    'subsidiary-uuid',
    'parent-company-uuid'
);
```

### Contacts

```php
// List contacts for a company
$contacts = $this->teamleader->contacts()->list([
    'company_id' => 'company-uuid'
]);

// Search contacts
$results = $this->teamleader->contacts()->search('John Doe');

// Create a contact
$contact = $this->teamleader->contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'emails' => [
        ['type' => 'primary', 'email' => 'john.doe@example.com']
    ],
    'company_id' => 'company-uuid'
]);

// Link contact to company
$this->teamleader->contacts()->linkToCompany(
    'contact-uuid',
    'company-uuid',
    'ceo' // position
);

// Update contact
$this->teamleader->contacts()->update('contact-uuid', [
    'telephones' => [
        ['type' => 'mobile', 'number' => '+32 470 12 34 56']
    ]
]);
```

### Deals

```php
// List deals with sideloading
$deals = $this->teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->withDepartment()
    ->list();

// Get deals by phase
$activeDeals = $this->teamleader->deals()->list([
    'phase_id' => 'phase-uuid'
]);

// Get deals updated since specific date
$recentDeals = $this->teamleader->deals()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
], [
    'sort' => 'updated_at',
    'sort_order' => 'desc'
]);

// Create a deal
$deal = $this->teamleader->deals()->create([
    'title' => 'New Enterprise Deal',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'phase_id' => 'phase-uuid',
    'estimated_closing_date' => '2024-12-31',
    'estimated_value' => [
        'amount' => 50000,
        'currency' => 'EUR'
    ]
]);

// Move deal to different phase
$this->teamleader->deals()->move('deal-uuid', 'new-phase-uuid');

// Mark deal as won
$this->teamleader->deals()->win('deal-uuid');

// Mark deal as lost
$this->teamleader->deals()->lose('deal-uuid', 'lost-reason-uuid');
```

### Invoices

```php
// List invoices with customer details
$invoices = $this->teamleader->invoices()
    ->withCustomer()
    ->withResponsibleUser()
    ->list();

// Filter by status and date
$unpaidInvoices = $this->teamleader->invoices()->list([
    'status' => 'outstanding',
    'invoice_date_after' => '2024-01-01'
]);

// Create an invoice from quotation
$invoice = $this->teamleader->invoices()->create([
    'quotation_id' => 'quotation-uuid'
]);

// Create draft invoice
$draft = $this->teamleader->invoices()->draft([
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Services'
            ],
            'line_items' => [
                [
                    'quantity' => 10,
                    'description' => 'Consulting hours',
                    'unit_price' => [
                        'amount' => 100.00,
                        'currency' => 'EUR'
                    ]
                ]
            ]
        ]
    ]
]);

// Book (finalize) a draft invoice
$this->teamleader->invoices()->book('invoice-uuid', '2024-10-16');

// Register payment
$this->teamleader->invoices()->registerPayment('invoice-uuid', [
    'amount' => [
        'amount' => 1000.00,
        'currency' => 'EUR'
    ],
    'paid_at' => '2024-10-16',
    'payment_method_id' => 'payment-method-uuid'
]);

// Download invoice PDF
$pdf = $this->teamleader->invoices()->download('invoice-uuid');
file_put_contents('invoice.pdf', $pdf);
```

### Projects

```php
// Check which project version the account uses
$isV2 = $this->teamleader->accounts()->isUsingProjectsV2();

// List projects
$projects = $this->teamleader->projects()
    ->withCustomer()
    ->withResponsibleUser()
    ->list();

// Filter by status
$openProjects = $this->teamleader->projects()->open();
$closedProjects = $this->teamleader->projects()->closed();

// Create a project
$project = $this->teamleader->projects()->create([
    'title' => 'Website Redesign',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'starts_on' => '2024-11-01'
]);

// Close a project
$this->teamleader->projects()->close('project-uuid');

// Assign user to project
$this->teamleader->projects()->assign(
    'project-uuid',
    'user',
    'user-uuid'
);
```

### Time Tracking

```php
// List time tracking entries
$entries = $this->teamleader->timeTracking()->list([
    'started_after' => '2024-10-01T00:00:00+00:00'
]);

// Create time tracking entry
$entry = $this->teamleader->timeTracking()->create([
    'work_type_id' => 'work-type-uuid',
    'started_at' => '2024-10-16T09:00:00+00:00',
    'ended_at' => '2024-10-16T12:00:00+00:00',
    'subject' => [
        'type' => 'project',
        'id' => 'project-uuid'
    ],
    'user_id' => 'user-uuid'
]);

// Start a timer
$timer = $this->teamleader->timers()->start([
    'work_type_id' => 'work-type-uuid',
    'subject' => [
        'type' => 'project',
        'id' => 'project-uuid'
    ]
]);

// Stop timer and create time tracking
$tracking = $this->teamleader->timers()->stop('timer-uuid');
```

### Quotations

```php
// Create a quotation
$quotation = $this->teamleader->quotations()->create([
    'deal_id' => 'deal-uuid',
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Project Services'
            ],
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Website development',
                    'unit_price' => [
                        'amount' => 5000.00,
                        'currency' => 'EUR'
                    ]
                ]
            ]
        ]
    ]
]);

// Send quotation by email
$this->teamleader->quotations()->send('quotation-uuid');

// Update quotation
$this->teamleader->quotations()->update('quotation-uuid', [
    'purchase_order_number' => 'PO-2024-123'
]);

// Download quotation PDF
$pdf = $this->teamleader->quotations()->download('quotation-uuid');
```

## 🎨 Advanced Features

### Sideloading (Include Related Resources)

Reduce API calls by loading related resources in a single request:

```php
// Using fluent interface
$deals = $this->teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->withDepartment()
    ->list();

// Using array syntax
$company = $this->teamleader->companies()
    ->with(['addresses', 'responsible_user', 'tags'])
    ->info('company-uuid');

// Load all common relationships at once
$deal = $this->teamleader->deals()
    ->withCommonRelationships()
    ->info('deal-uuid');

// Available sideloading varies by resource - check capabilities:
$capabilities = $this->teamleader->deals()->getCapabilities();
// Returns available_includes, supports_sideloading, etc.
```

### Advanced Filtering

```php
// Complex filters with pagination and sorting
$filteredDeals = $this->teamleader->deals()->list(
    // Filters
    [
        'phase_id' => 'active-phase-uuid',
        'updated_since' => '2024-01-01T00:00:00+00:00',
        'term' => 'enterprise'
    ],
    // Options
    [
        'page_size' => 50,
        'page_number' => 2,
        'sort' => 'created_at',
        'sort_order' => 'desc'
    ]
);

// Filter by multiple IDs
$specific = $this->teamleader->companies()->list([
    'ids' => ['uuid-1', 'uuid-2', 'uuid-3']
]);

// Email filter with proper structure
$byEmail = $this->teamleader->contacts()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'john@example.com'
    ]
]);
```

### Rate Limiting Management

```php
// Check current rate limit status
$stats = $this->teamleader->getRateLimitStats();

echo "Requests made: " . $stats['requests_made'];
echo "Remaining: " . $stats['remaining'];
echo "Usage: " . $stats['usage_percentage'] . "%";
echo "Resets in: " . $stats['seconds_until_reset'] . " seconds";

// SDK automatically throttles when approaching limits
// and respects 429 responses with retry logic

// For bulk operations, check limits periodically:
foreach ($largeDataset as $item) {
    $stats = $this->teamleader->getRateLimitStats();
    
    if ($stats['remaining'] <= 10) {
        // Wait for rate limit reset
        sleep($stats['seconds_until_reset'] + 1);
    }
    
    $this->teamleader->contacts()->create($item);
}
```

### Resource Capabilities Introspection

```php
// Get capabilities for any resource
$capabilities = $this->teamleader->invoices()->getCapabilities();

// Returns:
[
    'supports_pagination' => true,
    'supports_filtering' => true,
    'supports_sorting' => true,
    'supports_sideloading' => true,
    'supports_creation' => true,
    'supports_update' => true,
    'supports_deletion' => true,
    'supports_batch' => false,
    'default_includes' => ['customer', 'responsible_user'],
    'endpoint' => 'invoices'
]

// Get comprehensive documentation
$docs = $this->teamleader->deals()->getDocumentation();

// Returns detailed information about:
// - Available methods
// - Common filters
// - Available includes
// - Usage examples
// - Rate limit costs
// - Response formats
```

### Custom Fields

```php
// List custom fields for a resource
$customFields = $this->teamleader->customFields()->list([
    'context' => 'contact'
]);

// Get custom field definition
$field = $this->teamleader->customFields()->info('custom-field-uuid');

// Use custom fields in create/update
$contact = $this->teamleader->contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Custom value'
        ]
    ]
]);
```

### Error Handling

```php
try {
    $company = $this->teamleader->companies()->create($data);
} catch (\Exception $e) {
    // SDK provides structured error information
    Log::error('Teamleader API Error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}

// Alternatively, check response structure
$result = $this->teamleader->contacts()->create($data);

if (isset($result['error'])) {
    // Handle error
    $errors = $result['errors'] ?? [$result['message']];
    $statusCode = $result['status_code'];
    
    foreach ($errors as $error) {
        Log::error("API Error: {$error}");
    }
}
```

### Webhooks

```php
// Register a webhook
$webhook = $this->teamleader->webhooks()->register([
    'url' => 'https://your-app.com/webhooks/teamleader',
    'types' => [
        'contact.added',
        'contact.updated',
        'deal.won',
        'invoice.booked'
    ]
]);

// List webhooks
$webhooks = $this->teamleader->webhooks()->list();

// Unregister webhook
$this->teamleader->webhooks()->unregister('webhook-uuid');

// Handle webhook in your controller
public function handle(Request $request)
{
    $payload = $request->all();
    
    // Process webhook based on type
    match ($payload['type']) {
        'contact.added' => $this->handleContactAdded($payload),
        'deal.won' => $this->handleDealWon($payload),
        default => Log::info('Unhandled webhook type', $payload)
    };
    
    return response()->json(['status' => 'ok']);
}
```

## 🛠️ Configuration

The SDK provides extensive configuration in `config/teamleader.php`:

### API Settings
```php
'api' => [
    'timeout' => 30,              // Request timeout in seconds
    'connect_timeout' => 10,      // Connection timeout
    'read_timeout' => 25,         // Read timeout
    'retry_attempts' => 3,        // Number of retries for failed requests
    'retry_delay' => 1000,        // Delay between retries (ms)
],
```

### Rate Limiting
```php
'rate_limiting' => [
    'enabled' => true,
    'requests_per_minute' => 200,     // Teamleader's limit
    'throttle_threshold' => 0.7,       // Start throttling at 70%
    'aggressive_throttling' => true,   // More conservative approach
    'respect_retry_after' => true,    // Honor 429 response headers
],
```

### Sideloading
```php
'sideloading' => [
    'enabled' => true,
    'validate_includes' => true,           // Validate include names
    'max_includes_per_request' => 10,     // Prevent excessive includes
    
    // Pre-configured common includes
    'common_includes' => [
        'deals' => ['lead.customer', 'responsible_user', 'department'],
        'contacts' => ['company', 'responsible_user'],
        // ... more defaults
    ],
],
```

### Logging
```php
'logging' => [
    'enabled' => true,
    'log_requests' => false,          // Log all outgoing requests
    'log_responses' => false,         // Log all responses
    'log_rate_limits' => true,        // Log rate limit info
    'log_token_refresh' => true,      // Log token refresh events
    'channel' => 'default',           // Laravel log channel
],
```

### Caching
```php
'caching' => [
    'enabled' => true,
    'default_ttl' => 3600,           // 1 hour
    'cache_store' => 'default',      // Laravel cache store
    
    // Static endpoints to cache
    'cacheable_endpoints' => [
        'departments.list' => 7200,
        'currencies.list' => 86400,
        // ... more endpoints
    ],
],
```

## 🔧 Artisan Commands

The SDK provides helpful Artisan commands:

### Check Connection Status
```bash
php artisan teamleader:status
```

Shows:
- Authentication status
- Current API version
- Rate limit usage
- Token expiration
- Account information

### Health Check
```bash
php artisan teamleader:health
```

Performs comprehensive health checks:
- Configuration validation
- API connectivity
- Token validity
- Rate limiter status
- Cache connectivity

### Validate Configuration
```bash
php artisan teamleader:config-validate
```

Validates your configuration and provides suggestions for optimization.

## 📖 Available Resources & Methods

### Core CRUD Operations

Most resources support these standard methods:
- `list($filters, $options)` - List resources with filtering, pagination, sorting
- `info($id, $includes)` - Get single resource with optional sideloading
- `create($data)` - Create new resource
- `update($id, $data)` - Update existing resource
- `delete($id)` - Delete resource (where supported)

### Resource-Specific Methods

Many resources include convenience methods:
- **Companies**: `search()`, `byEmail()`, `byVatNumber()`, `tag()`, `linkToCompany()`
- **Deals**: `move()`, `win()`, `lose()`, `create()`, `update()`
- **Invoices**: `draft()`, `book()`, `send()`, `registerPayment()`, `download()`
- **Projects**: `open()`, `closed()`, `close()`, `assign()`, `unassign()`
- **Contacts**: `forCompany()`, `linkToCompany()`, `tag()`

Check resource documentation with: `$resource->getDocumentation()`

## 🧪 Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyze
```

## 📚 Additional Documentation

- [Filtering Guide](docs/filtering.md) - Advanced filtering and search capabilities
- [Sideloading Guide](docs/sideloading.md) - Efficient resource inclusion
- [Usage Examples](docs/usage.md) - Comprehensive real-world examples
- [Migration Guide](docs/migration.md) - Migrating from legacy Teamleader API
- [Teamleader API Docs](https://developer.focus.teamleader.eu/) - Official API documentation

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`composer test`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

Please follow PSR-12 coding standards and write tests for new features.

## 🔒 Security

If you discover any security-related issues, please email security@mcore-services.be instead of using the issue tracker. We take security seriously and will respond promptly.

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for information on what has changed recently.

## 📜 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## 🙏 Credits

- **MCore Services** - [https://mcore-services.be](https://mcore-services.be)
- Built with ❤️ for the Laravel and Teamleader communities

## 💬 Support

- **Documentation**: [README.md](README.md)
- **Email**: help@mcore-services.be
- **Issues**: [GitHub Issues](https://github.com/mcore-services/teamleader-sdk/issues)
- **Discussions**: [GitHub Discussions](https://github.com/mcore-services/teamleader-sdk/discussions)
- **Teamleader API**: [developer.focus.teamleader.eu](https://developer.focus.teamleader.eu/)

## 🗺️ Roadmap

- [ ] GraphQL support (when available from Teamleader)
- [ ] Bulk operations helper
- [ ] Enhanced caching strategies
- [ ] WebSocket support for real-time updates
- [ ] Laravel Pulse integration
- [ ] Improved test coverage tooling
- [ ] CLI tool for quick API exploration

---

**Made with ❤️ by [MCore Services](https://mcore-services.be)**
