# Resources

Understanding the resource architecture of the Teamleader SDK.

## Overview

The Teamleader SDK is built around a resource-based architecture. Each resource represents a specific entity type in Teamleader (companies, contacts, deals, invoices, etc.) and provides methods to interact with that entity through the Teamleader API.

This document explains how resources work, common patterns across all resources, and how to effectively use them in your application.

## Navigation

- [What is a Resource?](#what-is-a-resource)
- [Common Methods](#common-methods)
- [Resource Capabilities](#resource-capabilities)
- [Method Patterns](#method-patterns)
- [Fluent Interface](#fluent-interface)
- [Error Handling](#error-handling)
- [Resource Categories](#resource-categories)
- [Best Practices](#best-practices)
- [Advanced Usage](#advanced-usage)

## What is a Resource?

A resource is a PHP class that represents a specific entity type in Teamleader and provides methods to interact with it. Each resource extends the base `Resource` class and implements specific functionality for its entity type.

### Resource Structure

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Access a resource
$companies = Teamleader::companies();

// Call methods on the resource
$allCompanies = $companies->list();
$singleCompany = $companies->info('company-uuid');
```

### Base Resource Class

All resources inherit from the `Resource` base class, which provides:
- Common CRUD methods (`list`, `info`, `create`, `update`, `delete`)
- Filtering capabilities via `FilterTrait`
- Sideloading support
- Pagination handling
- Error handling
- Resource capability introspection

## Common Methods

Most resources implement these standard methods:

### `list()`

Retrieve a list of resources with optional filtering, sorting, and pagination.

```php
$resources = Teamleader::companies()->list(
    // Filters
    ['status' => 'active'],
    // Options
    ['page_size' => 50, 'page_number' => 1, 'sort' => 'name']
);
```

### `info()`

Get detailed information about a specific resource.

```php
$resource = Teamleader::companies()->info('uuid');

// With includes (sideloading)
$resource = Teamleader::companies()->info('uuid', 'responsible_user,addresses');
```

### `create()`

Create a new resource.

```php
$resource = Teamleader::companies()->create([
    'name' => 'Acme Corporation',
    'vat_number' => 'BE0123456789'
]);
```

### `update()`

Update an existing resource.

```php
$resource = Teamleader::companies()->update('uuid', [
    'name' => 'Acme Corp Ltd'
]);
```

### `delete()`

Delete a resource.

```php
Teamleader::companies()->delete('uuid');
```

## Resource Capabilities

Not all resources support all methods. Each resource declares its capabilities:

```php
// Check resource capabilities
$capabilities = Teamleader::companies()->getCapabilities();

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
    'default_includes' => [],
    'endpoint' => 'companies'
]
```

### Capability Matrix

| Resource | Create | Read | Update | Delete | Filter | Sort | Sideload |
|----------|--------|------|--------|--------|--------|------|----------|
| Companies | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Contacts | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Deals | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Users | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| Departments | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ |
| Custom Fields | ❌ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |

## Method Patterns

### Filtering Pattern

Resources use a consistent filtering pattern:

```php
// Basic filter
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);

// Multiple filters
$companies = Teamleader::companies()->list([
    'status' => 'active',
    'tags' => ['vip', 'enterprise'],
    'updated_since' => '2024-01-01'
]);

// Complex filters
$companies = Teamleader::companies()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'info@example.com'
    ]
]);
```

### Sorting Pattern

```php
// Simple sort
$companies = Teamleader::companies()->list([], [
    'sort' => 'name'
]);

// Detailed sort
$companies = Teamleader::companies()->list([], [
    'sort' => [
        ['field' => 'name', 'order' => 'asc']
    ]
]);

// Multiple sort fields
$companies = Teamleader::companies()->list([], [
    'sort' => [
        ['field' => 'status', 'order' => 'asc'],
        ['field' => 'name', 'order' => 'asc']
    ]
]);
```

### Pagination Pattern

```php
// Custom page size
$companies = Teamleader::companies()->list([], [
    'page_size' => 100
]);

// Specific page
$companies = Teamleader::companies()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// Paginate through all results
$allItems = [];
$page = 1;

do {
    $response = Teamleader::companies()->list([], [
        'page_size' => 100,
        'page_number' => $page
    ]);
    
    $allItems = array_merge($allItems, $response['data']);
    $hasMore = count($response['data']) === 100;
    $page++;
} while ($hasMore);
```

### Sideloading Pattern

```php
// Single include
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('uuid');

// Multiple includes
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type')
    ->info('uuid');

// Chained includes
$company = Teamleader::companies()
    ->with('responsible_user')
    ->with('addresses')
    ->info('uuid');

// Array of includes
$company = Teamleader::companies()
    ->with(['responsible_user', 'addresses'])
    ->info('uuid');
```

## Fluent Interface

Resources support a fluent interface for readable, chainable operations:

```php
// Chain multiple operations
$companies = Teamleader::companies()
    ->with('responsible_user,addresses')
    ->list(['status' => 'active'], [
        'page_size' => 50,
        'sort' => 'name'
    ]);

// Build queries progressively
$query = Teamleader::deals()
    ->withCustomer()
    ->withResponsibleUser();

if ($includePhase) {
    $query->with('phase');
}

$deals = $query->list(['status' => 'open']);
```

### Helper Methods

Many resources provide convenience methods:

```php
// Status helpers
$activeUsers = Teamleader::users()->active();
$archivedDepartments = Teamleader::departments()->archived();

// Search helpers
$companies = Teamleader::companies()->search('Acme');
$contacts = Teamleader::contacts()->byEmail('john@example.com');

// Relationship helpers
$contacts = Teamleader::contacts()->forCompany('company-uuid');
$notes = Teamleader::notes()->forDeal('deal-uuid');

// Sideloading helpers
$company = Teamleader::companies()
    ->withAddresses()
    ->withResponsibleUser()
    ->info('uuid');
```

## Error Handling

All resources use consistent error handling:

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $company = Teamleader::companies()->create([
        'name' => 'Acme Corp'
    ]);
} catch (TeamleaderException $e) {
    // Access error details
    $statusCode = $e->getCode();
    $message = $e->getMessage();
    $details = $e->getDetails();
    
    // Handle specific errors
    if ($statusCode === 422) {
        // Validation error
        Log::warning('Validation failed', $details);
    } elseif ($statusCode === 404) {
        // Resource not found
        return response()->json(['error' => 'Not found'], 404);
    } else {
        // Other error
        Log::error('API error', [
            'code' => $statusCode,
            'message' => $message
        ]);
    }
}
```

## Resource Categories

Resources are organized into logical categories:

### CRM Resources
- `companies()` - Company management
- `contacts()` - Contact management
- `businessTypes()` - Business type information
- `tags()` - Tag management
- `addresses()` - Address/location information

### Deal Resources
- `deals()` - Deal/opportunity management
- `quotations()` - Quotation management
- `orders()` - Order management
- `dealPhases()` - Deal phase configuration
- `dealPipelines()` - Pipeline management
- `dealSources()` - Deal source tracking
- `lostReasons()` - Lost reason management

### Invoicing Resources
- `invoices()` - Invoice management
- `creditnotes()` - Credit note management
- `paymentMethods()` - Payment method information
- `paymentTerms()` - Payment term configuration
- `subscriptions()` - Subscription management
- `taxRates()` - Tax rate information
- `withholdingTaxRates()` - Withholding tax rates
- `commercialDiscounts()` - Discount management

### General Resources
- `users()` - User management
- `departments()` - Department information
- `teams()` - Team management
- `customFields()` - Custom field definitions
- `workTypes()` - Work type categories
- `notes()` - Note management

### Project Resources
- `projects()` - Project management
- `projectLines()` - Project line items
- `tasks()` - Task management
- `materials()` - Material tracking

### Other Resources
- `webhooks()` - Webhook management
- `files()` - File management
- `tickets()` - Ticket system

## Best Practices

### 1. Check Capabilities Before Use

```php
$capabilities = Teamleader::customFields()->getCapabilities();

if ($capabilities['supports_creation']) {
    // Can create
    $field = Teamleader::customFields()->create($data);
} else {
    // Cannot create
    Log::info('Custom fields cannot be created via API');
}
```

### 2. Use Helper Methods

```php
// Good: Readable and clear
$activeCompanies = Teamleader::companies()->active();

// Less ideal: More verbose
$activeCompanies = Teamleader::companies()->list([
    'status' => 'active'
]);
```

### 3. Leverage Sideloading

```php
// Good: One API call
$company = Teamleader::companies()
    ->with('responsible_user,addresses')
    ->info('uuid');

// Bad: Three API calls
$company = Teamleader::companies()->info('uuid');
$user = Teamleader::users()->info($company['data']['responsible_user']['id']);
$addresses = $company['data']['addresses']; // If even available
```

### 4. Handle Pagination Properly

```php
// Good: Handle all pages
function getAllDeals() {
    $allDeals = [];
    $page = 1;
    
    do {
        $response = Teamleader::deals()->list([], [
            'page_size' => 100,
            'page_number' => $page
        ]);
        
        $allDeals = array_merge($allDeals, $response['data']);
        $page++;
    } while (!empty($response['data']) && count($response['data']) === 100);
    
    return $allDeals;
}

// Bad: Only first page
$deals = Teamleader::deals()->list();
```

### 5. Cache Read-Only Resources

```php
// Good: Cache static data
$businessTypes = Cache::remember('business_types', 7200, function() {
    return Teamleader::businessTypes()->list();
});

// Bad: Fetch every time
$businessTypes = Teamleader::businessTypes()->list();
```

## Advanced Usage

### Dynamic Resource Access

```php
// Access resources dynamically
$resourceName = 'companies';
$resource = Teamleader::$resourceName();

// Or using magic method
$resource = Teamleader::{$resourceName}();
```

### Custom Resource Wrappers

```php
class CompanyRepository
{
    public function findByVatNumber($vatNumber)
    {
        $companies = Teamleader::companies()->byVatNumber($vatNumber);
        return $companies['data'][0] ?? null;
    }
    
    public function getActiveWithUsers()
    {
        return Teamleader::companies()
            ->withResponsibleUser()
            ->list(['status' => 'active']);
    }
}
```

### Resource Introspection

```php
// Get resource documentation
$docs = Teamleader::companies()->getDocumentation();

// Returns comprehensive information:
[
    'resource' => 'Companies',
    'endpoint' => 'companies',
    'description' => 'Manage companies in Teamleader Focus',
    'capabilities' => [...],
    'methods' => [...],
    'common_filters' => [...],
    'available_includes' => [...],
    'usage_examples' => [...]
]
```

### Batch Operations

Some resources support batch operations:

```php
// Check if batch is supported
if (Teamleader::companies()->getCapabilities()['supports_batch']) {
    // Perform batch operation
    $result = Teamleader::companies()->batch('create', [
        ['name' => 'Company 1'],
        ['name' => 'Company 2'],
        ['name' => 'Company 3']
    ]);
}
```

## Testing with Resources

```php
use Tests\TestCase;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class CompanyResourceTest extends TestCase
{
    public function test_can_list_companies()
    {
        $companies = Teamleader::companies()->list();
        
        $this->assertIsArray($companies);
        $this->assertArrayHasKey('data', $companies);
    }
    
    public function test_can_filter_companies()
    {
        $activeCompanies = Teamleader::companies()->list([
            'status' => 'active'
        ]);
        
        foreach ($activeCompanies['data'] as $company) {
            $this->assertEquals('active', $company['status']);
        }
    }
    
    public function test_can_sideload_relationships()
    {
        $company = Teamleader::companies()
            ->withResponsibleUser()
            ->info('test-company-uuid');
        
        $this->assertArrayHasKey('responsible_user', $company['data']);
    }
}
```

## Resource Development

When extending the SDK with custom resources:

```php
namespace App\TeamleaderResources;

use McoreServices\TeamleaderSDK\Resources\Resource;

class CustomResource extends Resource
{
    protected string $description = 'My custom resource';
    
    // Set capabilities
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = false;
    
    // Define available includes
    protected array $availableIncludes = [
        'related_data'
    ];
    
    // Define common filters
    protected array $commonFilters = [
        'status' => 'Filter by status',
        'type' => 'Filter by type'
    ];
    
    // Implement required method
    protected function getBasePath(): string
    {
        return 'customResource';
    }
    
    // Add custom methods
    public function customMethod()
    {
        return $this->api->request('POST', $this->getBasePath() . '.customAction');
    }
}
```

## See Also

- [Usage Guide](usage.md) - General SDK usage
- [Filtering](filtering.md) - Advanced filtering techniques
- [Sideloading](sideloading.md) - Efficiently load related data
- Individual resource documentation in their respective categories
