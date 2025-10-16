# Filtering Guide

Comprehensive guide to filtering resources in the Teamleader SDK.

## Overview

The Teamleader SDK provides powerful filtering capabilities to help you retrieve exactly the data you need. Most resources support filtering through the `list()` method.

## Basic Filtering

### Simple Filters

Pass filters as the first parameter to the `list()` method:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Filter by status
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);

// Filter by multiple criteria
$contacts = Teamleader::contacts()->list([
    'status' => 'active',
    'company_id' => 'company-uuid'
]);
```

## Common Filter Types

### Status Filters

Most resources support filtering by status:

```php
// Active resources only
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);

// Deactivated resources
$contacts = Teamleader::contacts()->list([
    'status' => 'deactivated'
]);
```

### ID Filters

Filter by one or multiple IDs:

```php
// Single ID
$companies = Teamleader::companies()->list([
    'ids' => ['company-uuid-1']
]);

// Multiple IDs
$companies = Teamleader::companies()->list([
    'ids' => [
        'company-uuid-1',
        'company-uuid-2',
        'company-uuid-3'
    ]
]);
```

### Date Filters

Filter resources by date ranges:

```php
// Updated since a specific date
$contacts = Teamleader::contacts()->list([
    'updated_since' => '2024-01-01'
]);

// Created within a date range
$deals = Teamleader::deals()->list([
    'created_after' => '2024-01-01',
    'created_before' => '2024-12-31'
]);
```

### Tag Filters

Filter resources by tags:

```php
// Single tag
$companies = Teamleader::companies()->list([
    'tags' => ['vip']
]);

// Multiple tags
$companies = Teamleader::companies()->list([
    'tags' => ['vip', 'enterprise', 'partner']
]);
```

## Advanced Filtering

### Email Filters

Email filters require a specific structure:

```php
// Filter by primary email
$companies = Teamleader::companies()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'info@example.com'
    ]
]);

// Using the helper method (recommended)
$companies = Teamleader::companies()->byEmail('info@example.com');
```

### Search Filters

Use the `term` filter for full-text search:

```php
// Search across multiple fields
$contacts = Teamleader::contacts()->list([
    'term' => 'john@example.com'
]);

// Using the helper method (recommended)
$contacts = Teamleader::contacts()->search('john@example.com');
```

### Relationship Filters

Filter by related resources:

```php
// Contacts for a specific company
$contacts = Teamleader::contacts()->list([
    'company_id' => 'company-uuid'
]);

// Using the helper method (recommended)
$contacts = Teamleader::contacts()->forCompany('company-uuid');

// Deals for a customer
$deals = Teamleader::deals()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

### Custom Field Filters

Filter by custom field values:

```php
$companies = Teamleader::companies()->list([
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'specific-value'
        ]
    ]
]);
```

## Resource-Specific Filters

Different resources support different filters. Here are some common examples:

### Companies

```php
// By VAT number
$companies = Teamleader::companies()->byVatNumber('BE0123456789');

// By name (fuzzy search)
$companies = Teamleader::companies()->byName('Acme');

// By business type
$companies = Teamleader::companies()->list([
    'business_type_id' => 'business-type-uuid'
]);

// By responsible user
$companies = Teamleader::companies()->list([
    'responsible_user_id' => 'user-uuid'
]);
```

### Contacts

```php
// By email
$contacts = Teamleader::contacts()->byEmail('john@example.com');

// For a specific company
$contacts = Teamleader::contacts()->forCompany('company-uuid');

// With specific tags
$contacts = Teamleader::contacts()->withTags(['vip', 'active']);

// By decision maker status
$contacts = Teamleader::contacts()->list([
    'decision_maker' => true
]);
```

### Deals

```php
// By phase
$deals = Teamleader::deals()->list([
    'phase_id' => 'phase-uuid'
]);

// By pipeline
$deals = Teamleader::deals()->list([
    'pipeline_id' => 'pipeline-uuid'
]);

// By value range
$deals = Teamleader::deals()->list([
    'estimated_value_min' => 1000,
    'estimated_value_max' => 10000,
    'currency' => 'EUR'
]);

// By responsible user
$deals = Teamleader::deals()->list([
    'responsible_user_id' => 'user-uuid'
]);
```

### Invoices

```php
// By status
$invoices = Teamleader::invoices()->list([
    'status' => 'booked'
]);

// By payment status
$invoices = Teamleader::invoices()->list([
    'payment_status' => 'paid'
]);

// By customer
$invoices = Teamleader::invoices()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// By date range
$invoices = Teamleader::invoices()->list([
    'invoice_date_from' => '2024-01-01',
    'invoice_date_to' => '2024-12-31'
]);
```

### Projects

```php
// By status
$projects = Teamleader::projects()->list([
    'status' => 'active'
]);

// By customer
$projects = Teamleader::projects()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// By start date
$projects = Teamleader::projects()->list([
    'starts_after' => '2024-01-01'
]);
```

## Combining Filters with Options

Filters can be combined with pagination, sorting, and sideloading:

```php
// Complex query with multiple options
$companies = Teamleader::companies()->list(
    // Filters
    [
        'status' => 'active',
        'tags' => ['vip'],
        'updated_since' => '2024-01-01'
    ],
    // Options
    [
        'page_size' => 50,
        'page_number' => 1,
        'sort' => 'name',
        'sort_order' => 'asc',
        'include' => 'responsible_user,addresses'
    ]
);
```

## Helper Methods

The SDK provides convenient helper methods for common filtering patterns:

### Active/Deactivated Status

```php
// Get only active resources
$companies = Teamleader::companies()->active();

// Get only deactivated resources
$companies = Teamleader::companies()->deactivated();
```

### Updated Since

```php
// Get resources updated since a date
$contacts = Teamleader::contacts()->updatedSince('2024-01-01');
```

### Search Methods

```php
// Search contacts by term
$contacts = Teamleader::contacts()->search('john');

// Search companies by email
$companies = Teamleader::companies()->byEmail('info@acme.com');

// Search companies by VAT
$companies = Teamleader::companies()->byVatNumber('BE0123456789');

// Search companies by name
$companies = Teamleader::companies()->byName('Acme');
```

## Filter Validation

The SDK automatically validates filters to prevent invalid API calls:

```php
// Invalid filters are automatically removed
$companies = Teamleader::companies()->list([
    'status' => 'active',
    'invalid_filter' => null,      // Removed (null value)
    'empty_array' => [],           // Removed (empty array)
    'empty_string' => '',          // Removed (empty string)
    'valid_filter' => 'value'      // Kept
]);
```

## Checking Resource Capabilities

Not all resources support all filtering options. Check capabilities before filtering:

```php
// Get resource capabilities
$capabilities = Teamleader::companies()->getCapabilities();

if ($capabilities['supports_filtering']) {
    // Resource supports filtering
}

if ($capabilities['supports_sorting']) {
    // Resource supports sorting
}
```

## Pagination with Filters

When working with filtered results, remember to handle pagination:

```php
public function getAllFilteredCompanies($filters)
{
    $allCompanies = [];
    $page = 1;
    
    do {
        $response = Teamleader::companies()->list($filters, [
            'page_size' => 100,
            'page_number' => $page
        ]);
        
        $allCompanies = array_merge($allCompanies, $response['data']);
        $hasMore = count($response['data']) === 100;
        $page++;
        
    } while ($hasMore);
    
    return $allCompanies;
}
```

## Dynamic Filtering

Build filters dynamically based on user input:

```php
public function searchCompanies(Request $request)
{
    $filters = [];
    
    // Add filters based on request parameters
    if ($request->has('status')) {
        $filters['status'] = $request->status;
    }
    
    if ($request->has('tags')) {
        $filters['tags'] = explode(',', $request->tags);
    }
    
    if ($request->has('updated_since')) {
        $filters['updated_since'] = $request->updated_since;
    }
    
    if ($request->has('search')) {
        $filters['term'] = $request->search;
    }
    
    // Add pagination options
    $options = [
        'page_size' => $request->get('per_page', 20),
        'page_number' => $request->get('page', 1)
    ];
    
    return Teamleader::companies()->list($filters, $options);
}
```

## Best Practices

### 1. Use Helper Methods When Available

```php
// Instead of this:
$companies = Teamleader::companies()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'info@acme.com'
    ]
]);

// Do this:
$companies = Teamleader::companies()->byEmail('info@acme.com');
```

### 2. Filter at the API Level

Filter data at the API level instead of in your application to reduce data transfer:

```php
// Good: Filter at API level
$activeCompanies = Teamleader::companies()->list([
    'status' => 'active'
]);

// Bad: Fetch all and filter in PHP
$allCompanies = Teamleader::companies()->list();
$activeCompanies = array_filter($allCompanies['data'], function($company) {
    return $company['status'] === 'active';
});
```

### 3. Combine Filters for Specific Results

Use multiple filters to get exactly what you need:

```php
$deals = Teamleader::deals()->list([
    'phase_id' => 'active-phase-uuid',
    'responsible_user_id' => auth()->user()->teamleader_id,
    'estimated_value_min' => 5000,
    'updated_since' => now()->subDays(30)->toIso8601String()
]);
```

### 4. Cache Filtered Results

Cache frequently-used filtered results:

```php
use Illuminate\Support\Facades\Cache;

public function getActiveVIPCompanies()
{
    return Cache::remember('active_vip_companies', 3600, function () {
        return Teamleader::companies()->list([
            'status' => 'active',
            'tags' => ['vip']
        ]);
    });
}
```

### 5. Document Available Filters

When building services, document which filters are available:

```php
/**
 * Get companies matching the given criteria
 * 
 * Available filters:
 * - status: 'active' | 'deactivated'
 * - tags: array of tag names
 * - business_type_id: UUID
 * - responsible_user_id: UUID
 * - updated_since: ISO 8601 date string
 * - term: search term
 */
public function searchCompanies(array $filters): array
{
    return Teamleader::companies()->list($filters);
}
```

## Error Handling

Handle cases where filters might produce no results:

```php
$companies = Teamleader::companies()->list([
    'vat_number' => $vatNumber
]);

if (empty($companies['data'])) {
    // No companies found with this VAT number
    return response()->json([
        'message' => 'No companies found matching the criteria'
    ], 404);
}

return $companies['data'][0];
```

## Testing Filters

When testing, verify filters are applied correctly:

```php
use Tests\TestCase;

class CompanyFilterTest extends TestCase
{
    public function test_can_filter_by_status()
    {
        $companies = Teamleader::companies()->list([
            'status' => 'active'
        ]);
        
        // Verify all returned companies are active
        foreach ($companies['data'] as $company) {
            $this->assertEquals('active', $company['status']);
        }
    }
    
    public function test_can_filter_by_tags()
    {
        $companies = Teamleader::companies()->list([
            'tags' => ['vip']
        ]);
        
        // Verify all returned companies have the VIP tag
        foreach ($companies['data'] as $company) {
            $this->assertContains('vip', 
                array_column($company['tags'] ?? [], 'name')
            );
        }
    }
}
```

## Next Steps

- Learn about [Sideloading](sideloading.md) to efficiently load related data
- Check individual resource documentation for available filters
- See [Usage Guide](usage.md) for general SDK usage
