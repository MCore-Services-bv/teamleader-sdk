# Usage Guide

Complete guide to using the Teamleader SDK for Laravel.

## Installation

Install the package via Composer:

```bash
composer require mcore-services/teamleader-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=teamleader-config
```

## Configuration

Add your Teamleader credentials to your `.env` file:

```env
TEAMLEADER_CLIENT_ID=your_client_id
TEAMLEADER_CLIENT_SECRET=your_client_secret
TEAMLEADER_REDIRECT_URI=${APP_URL}/teamleader/callback
```

### Configuration Options

The SDK provides several configuration options in `config/teamleader.php`:

- **API Version**: Set the Teamleader API version to use
- **Timeouts**: Configure connection, read, and total request timeouts
- **Retry Behavior**: Set the number of retry attempts and delay between retries
- **Rate Limiting**: Configure API rate limit behavior

### Populating Teamleader Variables

The `config/teamleader.php` file includes a comprehensive `Teamleader Variables` section where you can store frequently used UUIDs. This keeps your codebase clean and maintainable by centralizing all Teamleader resource IDs.

#### Quick UUID Export

Use the built-in artisan command to export all UUIDs:

```bash
# Export all UUIDs
php artisan teamleader:export-uuids

# Export specific resource UUIDs
php artisan teamleader:export-uuids --resource=departments
php artisan teamleader:export-uuids --resource=users
php artisan teamleader:export-uuids --resource=deal-phases
php artisan teamleader:export-uuids --resource=custom-fields
```

The command outputs properly formatted configuration ready to copy into your `config/teamleader.php` file.

#### Manual UUID Retrieval

You can also manually retrieve UUIDs using tinker:

```bash
php artisan tinker
```

```php
// Example: Get departments
Teamleader::departments()->list();

// Example: Get work types  
Teamleader::workTypes()->list();

// Example: Get custom fields
Teamleader::customFields()->list();
```

#### Using Configured UUIDs

Once you've populated your config file, reference UUIDs throughout your application:

```php
// Creating a deal
Teamleader::deals()->create([
    'title' => 'New Enterprise Deal',
    'phase_id' => config('teamleader.deal_phases.qualified'),
    'source_id' => config('teamleader.deal_sources.website'),
    'department_id' => config('teamleader.departments.sales'),
]);

// Creating an invoice
Teamleader::invoices()->create([
    'payment_term_id' => config('teamleader.payment_terms.net_30'),
    'grouped_lines' => [[
        'line_items' => [[
            'quantity' => 1,
            'tax_rate_id' => config('teamleader.tax_rates.vat_21'),
            // ... other fields
        ]]
    ]]
]);

// Time tracking entry
Teamleader::timeTracking()->create([
    'work_type_id' => config('teamleader.work_types.consulting'),
    // ... other fields
]);
```

#### Benefits

Centralizing UUIDs in config provides:
- **Clean code**: `config('teamleader.departments.sales')` vs a raw UUID string
- **Environment-specific**: Different IDs for dev/staging/production
- **Easy updates**: Change UUIDs in one location
- **Better readability**: Self-documenting code
- **IDE support**: Autocomplete for config keys

## Authentication

The SDK uses OAuth 2.0 for authentication. Here's how to set it up:

### 1. Redirect to Authorization

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// In your controller
public function redirectToTeamleader()
{
    return Teamleader::authorize();
}
```

### 2. Handle Callback

```php
public function handleTeamleaderCallback(Request $request)
{
    $code = $request->get('code');
    $state = $request->get('state');
    
    if (Teamleader::handleCallback($code, $state)) {
        return redirect('/dashboard')->with('success', 'Connected to Teamleader!');
    }
    
    return redirect('/settings')->with('error', 'Failed to connect to Teamleader');
}
```

### 3. Check Authentication Status

```php
if (Teamleader::isAuthenticated()) {
    // User is authenticated
}
```

### 4. Logout

```php
Teamleader::logout();
```

## Basic Usage

### Using the Facade

The simplest way to use the SDK is via the Facade:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all companies
$companies = Teamleader::companies()->list();

// Get a specific contact
$contact = Teamleader::contacts()->info('contact-uuid');

// Create a new deal
$deal = Teamleader::deals()->create([
    'title' => 'New Sales Opportunity',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'estimated_value' => [
        'amount' => 5000,
        'currency' => 'EUR'
    ]
]);
```

### Using Dependency Injection

You can also inject the SDK into your classes:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CompanyService
{
    protected TeamleaderSDK $teamleader;
    
    public function __construct(TeamleaderSDK $teamleader)
    {
        $this->teamleader = $teamleader;
    }
    
    public function getAllCompanies()
    {
        return $this->teamleader->companies()->list();
    }
}
```

## Available Resources

The SDK provides access to all Teamleader resources:

### CRM
- `companies()` - Company management
- `contacts()` - Contact management
- `businessTypes()` - Business type information
- `tags()` - Tag management
- `addresses()` - Address management

### Deals
- `deals()` - Deal management
- `quotations()` - Quotation management
- `orders()` - Order management
- `dealPhases()` - Deal phase information
- `dealPipelines()` - Deal pipeline management
- `dealSources()` - Deal source information
- `lostReasons()` - Lost reason management

### Invoicing
- `invoices()` - Invoice management
- `creditnotes()` - Credit note management
- `paymentMethods()` - Payment method information
- `paymentTerms()` - Payment term information
- `subscriptions()` - Subscription management
- `taxRates()` - Tax rate information
- `withholdingTaxRates()` - Withholding tax rate information
- `commercialDiscounts()` - Commercial discount management

### Calendar
- `meetings()` - Meeting management
- `calls()` - Call management
- `callOutcomes()` - Call outcome information
- `calendarEvents()` - Calendar event management
- `activityTypes()` - Activity type information

### Products
- `products()` - Product management
- `productCategories()` - Product category management
- `priceLists()` - Price list management
- `unitsOfMeasure()` - Unit of measure information

### Projects
- `projects()` - Project management
- `projectLines()` - Project line management
- `tasks()` - Task management
- `materials()` - Material management
- `groups()` - Group management

### Time Tracking
- `timeTracking()` - Time tracking entries
- `timers()` - Timer management

### General
- `departments()` - Department information
- `customFields()` - Custom field definitions
- `users()` - User management
- `teams()` - Team information
- `workTypes()` - Work type information
- `notes()` - Note management
- `currencies()` - Currency information
- `closingDays()` - Closing day management
- `daysOff()` - Days off management
- `dayOffTypes()` - Day off type information
- `documentTemplates()` - Document template management
- `emailTracking()` - Email tracking

### Files
- `files()` - File management

### Templates
- `mailTemplates()` - Mail template management

### Tickets
- `tickets()` - Ticket management
- `ticketStatus()` - Ticket status information

### Other
- `webhooks()` - Webhook management
- `accounts()` - Account information
- `cloudPlatforms()` - Cloud platform information
- `migrate()` - Migration utilities

## Common Operations

### Listing Resources

```php
// Get all resources with default pagination (20 per page)
$companies = Teamleader::companies()->list();

// With custom page size
$companies = Teamleader::companies()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// With filters
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);
```

### Getting Resource Information

```php
// Get a single resource
$company = Teamleader::companies()->info('company-uuid');

// Get with related data (sideloading)
$company = Teamleader::companies()
    ->with('addresses,responsible_user')
    ->info('company-uuid');
```

### Creating Resources

```php
$company = Teamleader::companies()->create([
    'name' => 'Acme Corporation',
    'business_type_id' => 'business-type-uuid',
    'vat_number' => 'BE0123456789',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'info@acme.com'
        ]
    ]
]);
```

### Updating Resources

```php
$company = Teamleader::companies()->update('company-uuid', [
    'name' => 'Acme Corporation Ltd',
    'website' => 'https://acme.com'
]);
```

### Deleting Resources

```php
Teamleader::companies()->delete('company-uuid');
```

## Advanced Features

### Searching

Many resources support search functionality:

```php
// Search contacts by term (searches name, email, phone)
$contacts = Teamleader::contacts()->search('john@example.com');

// Search companies by email
$companies = Teamleader::companies()->byEmail('info@acme.com');

// Search companies by VAT number
$companies = Teamleader::companies()->byVatNumber('BE0123456789');
```

### Filtering by Date

```php
// Get resources updated since a date
$contacts = Teamleader::contacts()->updatedSince('2024-01-01');

// With additional filters
$contacts = Teamleader::contacts()->list([
    'updated_since' => '2024-01-01',
    'status' => 'active'
]);
```

### Sorting

```php
// Sort by a field
$companies = Teamleader::companies()->list([], [
    'sort' => 'name',
    'sort_order' => 'asc'
]);

// Multiple sort fields
$companies = Teamleader::companies()->list([], [
    'sort' => ['name', 'updated_at'],
    'sort_order' => 'desc'
]);
```

### Tagging

```php
// Add tags to a resource
Teamleader::companies()->tag('company-uuid', ['vip', 'enterprise']);

// Remove tags
Teamleader::companies()->untag('company-uuid', ['vip']);

// Manage tags (add and remove in one call)
Teamleader::companies()->manageTags('company-uuid', 
    ['new-tag'],  // tags to add
    ['old-tag']   // tags to remove
);
```

## Error Handling

The SDK automatically handles retries and provides detailed error information:

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $company = Teamleader::companies()->create([
        'name' => 'Test Company'
    ]);
} catch (TeamleaderException $e) {
    // Get error details
    $statusCode = $e->getCode();
    $message = $e->getMessage();
    $details = $e->getDetails();
    
    // Log or handle the error
    Log::error('Teamleader API error', [
        'status' => $statusCode,
        'message' => $message,
        'details' => $details
    ]);
}
```

## Rate Limiting

The SDK includes automatic rate limiting to prevent hitting API limits:

```php
// Get rate limit statistics
$stats = Teamleader::getRateLimitStats();

// Check if you're approaching the limit
if ($stats['usage_percentage'] > 80) {
    Log::warning('Approaching Teamleader API rate limit');
}
```

## API Call Statistics

Track your API usage:

```php
// Get total API calls in current session
$totalCalls = Teamleader::getApiCallCount();

// Get detailed call history
$calls = Teamleader::getApiCalls();

// Reset statistics
Teamleader::resetApiCallStats();
```

## Debugging

### Enable Debug Logging

Set your log level in `.env`:

```env
LOG_LEVEL=debug
```

### Check SDK Status

Use the built-in commands:

```bash
# Check connection status
php artisan teamleader:status

# Run health check
php artisan teamleader:health

# Validate configuration
php artisan teamleader:config:validate
```

## Best Practices

### 1. Use Dependency Injection

Prefer dependency injection over the Facade for better testability:

```php
class InvoiceService
{
    public function __construct(
        private TeamleaderSDK $teamleader
    ) {}
    
    public function createInvoice(array $data)
    {
        return $this->teamleader->invoices()->create($data);
    }
}
```

### 2. Handle Pagination Properly

Always consider pagination when listing resources:

```php
public function getAllContacts()
{
    $allContacts = [];
    $page = 1;
    
    do {
        $response = Teamleader::contacts()->list([], [
            'page_size' => 100,
            'page_number' => $page
        ]);
        
        $allContacts = array_merge($allContacts, $response['data']);
        $page++;
    } while (!empty($response['data']) && count($response['data']) === 100);
    
    return $allContacts;
}
```

### 3. Use Sideloading to Reduce API Calls

Load related data in a single request:

```php
// Instead of this (2 API calls):
$company = Teamleader::companies()->info('company-uuid');
$user = Teamleader::users()->info($company['responsible_user']['id']);

// Do this (1 API call):
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');
```

### 4. Cache Frequently Accessed Data

```php
use Illuminate\Support\Facades\Cache;

public function getBusinessTypes()
{
    return Cache::remember('teamleader.business_types', 3600, function () {
        return Teamleader::businessTypes()->list();
    });
}
```

### 5. Use Queued Jobs for Bulk Operations

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncCompaniesJob implements ShouldQueue
{
    use Queueable;
    
    public function handle(TeamleaderSDK $teamleader)
    {
        $page = 1;
        
        do {
            $companies = $teamleader->companies()->list([], [
                'page_size' => 100,
                'page_number' => $page
            ]);
            
            // Process companies...
            
            $page++;
        } while (!empty($companies['data']));
    }
}
```

## Next Steps

- Learn about [Filtering](filtering.md) to narrow down your results
- Discover [Sideloading](sideloading.md) to efficiently load related data
- Check individual resource documentation for endpoint-specific features
