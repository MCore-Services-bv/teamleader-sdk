# Teamleader Focus SDK for Laravel

A comprehensive Laravel package for integrating with the Teamleader Focus API, featuring automatic token management, rate limiting, sideloading support, and a fluent interface for all API operations.

## Features

- **Complete OAuth 2 Flow** - Authorization URL generation and callback handling
- **Automatic Token Management** - Token refresh, storage, and validation
- **Intelligent Rate Limiting** - Sliding window rate limiting with automatic throttling
- **Sideloading Support** - Fluent interface for including related resources
- **API Version Management** - Support for API versioning with easy upgrades
- **Comprehensive Error Handling** - Teamleader-specific error parsing
- **Resource-Based Architecture** - Organized endpoints by functionality
- **Extensive Logging** - Detailed logging for debugging and monitoring

## Requirements

- PHP 8.2+
- Laravel 10.0+ or 11.0+
- Guzzle HTTP 7.0+

## Installation

Install the package via Composer:

```bash
composer require mcore-services/teamleader-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="McoreServices\TeamleaderSDK\TeamleaderServiceProvider"
```

## Configuration

Add your Teamleader credentials to your `.env` file:

```env
TEAMLEADER_CLIENT_ID=your_client_id
TEAMLEADER_CLIENT_SECRET=your_client_secret
TEAMLEADER_REDIRECT_URI={$APP_URL}/auth/teamleader/callback
```

### Required Setup

1. Register your integration on the [Teamleader Marketplace](https://marketplace.teamleader.eu/)
2. Configure your redirect URIs in the Teamleader developer portal
3. Set the appropriate scopes for your integration

## Quick Start

### Authentication

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

// Inject the SDK
public function __construct(private TeamleaderSDK $teamleader) {}

// Redirect to Teamleader for authorization
public function authorize()
{
    $state = Str::random(32);
    session(['teamleader_state' => $state]);
    
    return $this->teamleader->authorize($state);
}

// Handle the callback
public function callback(Request $request)
{
    $code = $request->get('code');
    $state = $request->get('state');
    
    // Validate state for security
    if ($state !== session('teamleader_state')) {
        return redirect('/auth/error');
    }
    
    if ($this->teamleader->handleCallback($code, $state)) {
        return redirect('/dashboard')->with('success', 'Connected!');
    }
    
    return redirect('/auth/error');
}
```

### Basic Usage

```php
// Get current user info
$user = $this->teamleader->users()->me();

// List companies with pagination
$companies = $this->teamleader->companies()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Create a new contact
$contact = $this->teamleader->contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com'
]);

// Get a specific deal
$deal = $this->teamleader->deals()->info('deal-uuid-here');
```

### Sideloading (Include Related Resources)

```php
// Get deals with customer and user information in one request
$deals = $this->teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->list();

// Multiple includes using fluent interface
$companies = $this->teamleader->companies()
    ->with(['responsible_user', 'addresses'])
    ->list();

// Get specific deal with all common relationships
$deal = $this->teamleader->deals()
    ->withCommonRelationships()
    ->info('deal-uuid');
```

### Filtering and Searching

```php
// Filter contacts by company
$contacts = $this->teamleader->contacts()->list([
    'company_id' => 'company-uuid'
]);

// Search deals updated since a specific date
$recentDeals = $this->teamleader->deals()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);

// Complex filtering with pagination and sorting
$filteredDeals = $this->teamleader->deals()->list(
    // Filters
    ['phase_id' => 'active-phase-uuid'],
    // Options
    [
        'page_size' => 25,
        'sort' => 'updated_at',
        'sort_order' => 'desc'
    ]
);
```

## Available Resources

The SDK provides access to all major Teamleader endpoints organized by functionality:

### CRM
- `companies()` - Company management
- `contacts()` - Contact management
- `businessTypes()` - Business type definitions
- `tags()` - Tag management
- `addresses()` - Address management

### Deals & Sales
- `deals()` - Deal management
- `quotations()` - Quotation handling
- `orders()` - Order management
- `dealPhases()` - Deal phase configuration
- `dealPipelines()` - Pipeline management
- `dealSources()` - Deal source tracking
- `lostReasons()` - Lost deal reasons

### Invoicing
- `invoices()` - Invoice management
- `creditnotes()` - Credit note handling
- `paymentMethods()` - Payment method configuration
- `paymentTerms()` - Payment term settings
- `subscriptions()` - Subscription management
- `taxRates()` - Tax rate configuration

### Projects & Time
- `projects()` - Project management
- `projectTasks()` - Task management within projects
- `timeTracking()` - Time tracking entries
- `timers()` - Active timer management

### General
- `users()` - User management
- `departments()` - Department structure
- `customFields()` - Custom field definitions
- `currencies()` - Currency management
- `notes()` - Note management

## Rate Limiting

The SDK automatically handles Teamleader's rate limits (200 requests per minute) with intelligent throttling:

```php
// Check current rate limit status
$stats = $this->teamleader->getRateLimitStats();
echo "Remaining requests: " . $stats['remaining'];
echo "Usage: " . $stats['usage_percentage'] . "%";

// The SDK automatically throttles when approaching limits
// and handles 429 responses with proper retry logic
```

## Error Handling

```php
$result = $this->teamleader->contacts()->create($data);

if (isset($result['error'])) {
    // Enhanced error information
    $errors = $result['errors'] ?? [$result['message']];
    
    foreach ($errors as $error) {
        Log::error("Teamleader error: " . $error);
    }
    
    // HTTP status code available
    $statusCode = $result['status_code'];
}
```

## API Version Management

```php
// Check current API version
$version = $this->teamleader->getApiVersion();

// Upgrade to a newer version
$this->teamleader->setApiVersion('2024-01-15');

// The X-Api-Version header is automatically sent with all requests
```

## Advanced Features

### Middleware for Request Tracking

Register the middleware in your HTTP kernel:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\ApiCallCounterMiddleware::class,
    ],
];
```

### Bulk Operations with Rate Limit Awareness

```php
public function bulkImport(array $contacts)
{
    foreach ($contacts as $contact) {
        // Check rate limit before each request
        $stats = $this->teamleader->getRateLimitStats();
        
        if ($stats['remaining'] <= 5) {
            // Wait for rate limit reset
            sleep($stats['seconds_until_reset'] + 1);
        }
        
        $this->teamleader->contacts()->create($contact);
    }
}
```

### Token Management

```php
// Check authentication status
if (!$this->teamleader->isAuthenticated()) {
    return redirect('/auth/teamleader');
}

// Get token information for debugging
$tokenInfo = $this->teamleader->getTokenService()->getTokenInfo();

// Manually refresh tokens if needed
$newToken = $this->teamleader->getTokenService()->refreshTokenIfNeeded();

// Clear tokens (logout)
$this->teamleader->logout();
```

## Configuration Options

The package provides extensive configuration options in `config/teamleader.php`:

- **API Settings** - Timeouts, retry attempts, API version
- **Rate Limiting** - Throttling behavior, limits
- **Sideloading** - Include validation, common relationships
- **Logging** - Request/response logging, channels
- **Caching** - Response caching for static data
- **Error Handling** - Exception throwing, error parsing

## Documentation

For detailed documentation on specific features:

- [Filtering](docs/filtering.md) - Advanced filtering and search capabilities
- [Sideloading](docs/sideloading.md) - Including related resources efficiently
- [Usage Examples](docs/usage.md) - Comprehensive usage examples

## Testing

```bash
# Run the test suite
composer test

# Run with coverage
composer test-coverage
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security

If you discover any security-related issues, please email security@mcore-services.be instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [MCore Services](https://mcore-services.be)

## Support

- Email: help@mcore-services.be
- Documentation: [Teamleader API Docs](https://developer.focus.teamleader.eu/)
- Issues: [GitHub Issues](https://github.com/mcore-services/teamleader-sdk/issues)
