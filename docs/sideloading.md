# Sideloading Guide

Complete guide to efficiently loading related data using sideloading (includes) in the Teamleader SDK.

## Overview

Sideloading allows you to load related resources in a single API request instead of making multiple requests. This significantly improves performance and reduces API call usage.

## The Problem Sideloading Solves

Without sideloading, you'd need multiple API calls:

```php
// ❌ Bad: 3 API calls
$company = Teamleader::companies()->info('company-uuid');
$user = Teamleader::users()->info($company['responsible_user']['id']);
$businessType = Teamleader::businessTypes()->info($company['business_type']['id']);
```

With sideloading, you get everything in one call:

```php
// ✅ Good: 1 API call
$company = Teamleader::companies()
    ->with('responsible_user,business_type')
    ->info('company-uuid');
```

## Basic Sideloading

### Using the `with()` Method

The fluent `with()` method is the recommended way to sideload relationships:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Single relationship
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');

// Multiple relationships (comma-separated string)
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type')
    ->info('company-uuid');

// Multiple relationships (array)
$company = Teamleader::companies()
    ->with(['responsible_user', 'addresses', 'business_type'])
    ->info('company-uuid');

// Chaining
$company = Teamleader::companies()
    ->with('responsible_user')
    ->with('addresses')
    ->with('business_type')
    ->info('company-uuid');
```

### Using the Options Parameter

You can also specify includes in the options:

```php
// In list() calls
$companies = Teamleader::companies()->list([], [
    'include' => 'responsible_user,addresses'
]);

// In info() calls
$company = Teamleader::companies()->info('company-uuid', 'responsible_user,addresses');
```

## Common Relationships

### CRM Resources

#### Companies

Available includes:
- `responsible_user` - The user responsible for the company
- `addresses` - Company addresses
- `business_type` - Business type information
- `tags` - Company tags
- `added_by` - User who added the company
- `language` - Language information
- `custom_fields` - Custom field values

```php
// Load company with all common relationships
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type,tags')
    ->info('company-uuid');
```

#### Contacts

Available includes:
- `responsible_user` - The user responsible for the contact
- `company` - The contact's company
- `language` - Language information
- `tags` - Contact tags
- `added_by` - User who added the contact
- `custom_fields` - Custom field values

```php
// Load contact with company and responsible user
$contact = Teamleader::contacts()
    ->with('company,responsible_user')
    ->info('contact-uuid');
```

### Deal Resources

#### Deals

Available includes:
- `lead.customer` - Customer information (company or contact)
- `responsible_user` - The user responsible for the deal
- `department` - Department information
- `phase` - Current deal phase
- `source` - Deal source
- `custom_fields` - Custom field values

```php
// Load deal with customer and user information
$deal = Teamleader::deals()
    ->with('lead.customer,responsible_user,phase')
    ->info('deal-uuid');
```

#### Quotations

Available includes:
- `lead.customer` - Customer information
- `responsible_user` - Responsible user
- `department` - Department information
- `deal` - Related deal (if any)
- `grouped_lines` - Grouped quotation lines
- `custom_fields` - Custom field values

```php
$quotation = Teamleader::quotations()
    ->with('lead.customer,responsible_user,grouped_lines')
    ->info('quotation-uuid');
```

### Invoicing Resources

#### Invoices

Available includes:
- `customer` - Customer information
- `department` - Department information
- `invoicee` - Invoicee information
- `grouped_lines` - Grouped invoice lines
- `credit_notes` - Related credit notes
- `custom_fields` - Custom field values

```php
$invoice = Teamleader::invoices()
    ->with('customer,grouped_lines')
    ->info('invoice-uuid');
```

### Project Resources

#### Projects

Available includes:
- `customer` - Customer information
- `responsible_user` - Responsible user
- `department` - Department information
- `custom_fields` - Custom field values

```php
$project = Teamleader::projects()
    ->with('customer,responsible_user')
    ->info('project-uuid');
```

## Helper Methods

The SDK provides convenient helper methods for common relationships:

### Universal Helper Methods

These methods work across most resources:

```php
// Load responsible user
$company = Teamleader::companies()
    ->withResponsibleUser()
    ->info('company-uuid');

// Load department
$deal = Teamleader::deals()
    ->withDepartment()
    ->info('deal-uuid');

// Load custom fields
$contact = Teamleader::contacts()
    ->withCustomFields()
    ->info('contact-uuid');
```

### Resource-Specific Helper Methods

#### Companies

```php
$company = Teamleader::companies()
    ->withAddresses()
    ->withBusinessType()
    ->withResponsibleUser()
    ->withAddedBy()
    ->info('company-uuid');

// Or load all common relationships
$company = Teamleader::companies()
    ->withCommonRelationships()
    ->info('company-uuid');
```

#### Contacts

```php
$contact = Teamleader::contacts()
    ->withCompany()
    ->withResponsibleUser()
    ->info('contact-uuid');
```

#### Deals

```php
$deal = Teamleader::deals()
    ->withCustomer()  // Loads lead.customer
    ->withResponsibleUser()
    ->withDepartment()
    ->info('deal-uuid');
```

## Nested Relationships

Some relationships can be nested using dot notation:

```php
// Load deal with customer information
$deal = Teamleader::deals()
    ->with('lead.customer')
    ->info('deal-uuid');

// Access the nested data
$customerName = $deal['data']['lead']['customer']['name'];
```

## Sideloading in List Calls

Sideloading works with both `info()` and `list()` calls:

```php
// Load all companies with related data
$companies = Teamleader::companies()
    ->with('responsible_user,business_type')
    ->list();

// With filters
$companies = Teamleader::companies()
    ->with('responsible_user,addresses')
    ->list([
        'status' => 'active'
    ]);

// With pagination and sorting
$companies = Teamleader::companies()
    ->with('responsible_user,business_type')
    ->list(
        ['status' => 'active'],
        [
            'page_size' => 50,
            'sort' => 'name'
        ]
    );
```

## Performance Optimization

### Load Only What You Need

```php
// ❌ Bad: Loading unnecessary data
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type,tags,language,added_by,custom_fields')
    ->info('company-uuid');

// ✅ Good: Load only required relationships
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');
```

### Batch Processing with Sideloading

When processing multiple resources, sideload in the list call:

```php
// ✅ Efficient: One API call with sideloaded data
$companies = Teamleader::companies()
    ->with('responsible_user,business_type')
    ->list([], ['page_size' => 100]);

foreach ($companies['data'] as $company) {
    $userName = $company['responsible_user']['first_name'];
    $businessType = $company['business_type']['name'];
    // Process with included data
}

// ❌ Inefficient: Multiple API calls
$companies = Teamleader::companies()->list([], ['page_size' => 100]);

foreach ($companies['data'] as $company) {
    // Each iteration makes 2 additional API calls!
    $user = Teamleader::users()->info($company['responsible_user']['id']);
    $businessType = Teamleader::businessTypes()->info($company['business_type']['id']);
}
```

## Clearing Pending Includes

If you need to reset includes in a chain:

```php
$resource = Teamleader::companies()
    ->with('responsible_user')
    ->withoutIncludes()  // Clears all pending includes
    ->with('addresses')  // Start fresh
    ->info('company-uuid');
```

## Checking Available Includes

Check which relationships a resource supports:

```php
// Get resource capabilities
$capabilities = Teamleader::companies()->getCapabilities();

if ($capabilities['supports_sideloading']) {
    // This resource supports sideloading
    $defaultIncludes = $capabilities['default_includes'];
}
```

## Working with Included Data

### Accessing Sideloaded Data

```php
$company = Teamleader::companies()
    ->with('responsible_user,addresses')
    ->info('company-uuid');

// Access the main resource
$companyName = $company['data']['name'];

// Access sideloaded relationships
$userName = $company['data']['responsible_user']['first_name'];
$userEmail = $company['data']['responsible_user']['email'];

// Access array relationships
foreach ($company['data']['addresses'] as $address) {
    echo $address['line_1'];
}
```

### Checking if Relationships are Loaded

```php
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');

if (isset($company['data']['responsible_user'])) {
    // Responsible user was loaded
    $user = $company['data']['responsible_user'];
} else {
    // Responsible user was not loaded
}
```

## Best Practices

### 1. Always Use Sideloading for Related Data

```php
// ✅ Good: One API call
$deal = Teamleader::deals()
    ->with('lead.customer,responsible_user')
    ->info('deal-uuid');

// ❌ Bad: Three API calls
$deal = Teamleader::deals()->info('deal-uuid');
$customer = Teamleader::companies()->info($deal['lead']['customer']['id']);
$user = Teamleader::users()->info($deal['responsible_user']['id']);
```

### 2. Use Helper Methods for Readability

```php
// ✅ Good: Clear and readable
$company = Teamleader::companies()
    ->withResponsibleUser()
    ->withAddresses()
    ->withBusinessType()
    ->info('company-uuid');

// ❌ Less readable: Magic strings
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type')
    ->info('company-uuid');
```

### 3. Group Related Sideloads

```php
// ✅ Good: Organized by concern
$deal = Teamleader::deals()
    ->with([
        // Customer information
        'lead.customer',
        
        // Deal metadata
        'phase',
        'source',
        'responsible_user',
        
        // Department info
        'department'
    ])
    ->info('deal-uuid');
```

### 4. Cache Sideloaded Results

```php
use Illuminate\Support\Facades\Cache;

public function getCompanyWithRelations($companyId)
{
    $cacheKey = "company.{$companyId}.with_relations";
    
    return Cache::remember($cacheKey, 3600, function () use ($companyId) {
        return Teamleader::companies()
            ->with('responsible_user,business_type,addresses')
            ->info($companyId);
    });
}
```

### 5. Document Required Includes

When building services, document which relationships are needed:

```php
/**
 * Get company summary with all required relationships
 * 
 * Includes:
 * - responsible_user: For display name and contact info
 * - business_type: For categorization
 * - addresses: For location data
 */
public function getCompanySummary(string $companyId): array
{
    return Teamleader::companies()
        ->with('responsible_user,business_type,addresses')
        ->info($companyId);
}
```

## Common Patterns

### Building a Detail View

```php
public function show($id)
{
    $company = Teamleader::companies()
        ->with([
            'responsible_user',
            'addresses',
            'business_type',
            'tags'
        ])
        ->info($id);
    
    return view('companies.show', [
        'company' => $company['data']
    ]);
}
```

### Building a List View

```php
public function index(Request $request)
{
    $companies = Teamleader::companies()
        ->with('responsible_user,business_type')
        ->list(
            ['status' => 'active'],
            [
                'page_size' => 20,
                'page_number' => $request->get('page', 1)
            ]
        );
    
    return view('companies.index', [
        'companies' => $companies['data']
    ]);
}
```

### Building an API Response

```php
public function apiShow($id)
{
    $deal = Teamleader::deals()
        ->with([
            'lead.customer',
            'responsible_user',
            'phase',
            'department'
        ])
        ->info($id);
    
    return response()->json([
        'data' => $deal['data'],
        'included' => [
            'customer' => $deal['data']['lead']['customer'],
            'user' => $deal['data']['responsible_user'],
            'phase' => $deal['data']['phase']
        ]
    ]);
}
```

### Conditional Sideloading

```php
public function getCompany($id, $includeRelations = false)
{
    $query = Teamleader::companies();
    
    if ($includeRelations) {
        $query->withCommonRelationships();
    }
    
    return $query->info($id);
}
```

## Error Handling

Handle cases where includes might not be available:

```php
$company = Teamleader::companies()
    ->with('responsible_user,addresses')
    ->info('company-uuid');

// Always check if relationship exists
$responsibleUserName = $company['data']['responsible_user']['first_name'] 
    ?? 'No user assigned';

// Or use null coalescing
$addresses = $company['data']['addresses'] ?? [];
```

## Testing with Sideloading

```php
use Tests\TestCase;

class CompanySideloadingTest extends TestCase
{
    public function test_can_load_company_with_relationships()
    {
        $company = Teamleader::companies()
            ->with('responsible_user,addresses')
            ->info('test-company-uuid');
        
        // Verify relationships are loaded
        $this->assertArrayHasKey('responsible_user', $company['data']);
        $this->assertArrayHasKey('addresses', $company['data']);
        
        // Verify relationship data structure
        $this->assertArrayHasKey('first_name', $company['data']['responsible_user']);
        $this->assertIsArray($company['data']['addresses']);
    }
}
```

## Configuration

You can configure default includes in `config/teamleader.php`:

```php
'sideloading' => [
    'defaults' => [
        'companies' => ['responsible_user', 'business_type'],
        'contacts' => ['company', 'responsible_user'],
        'deals' => ['lead.customer', 'responsible_user', 'phase'],
    ],
    
    'max_includes' => 10,  // Maximum number of includes per request
],
```

## Next Steps

- Learn about [Filtering](filtering.md) to narrow down your results
- See [Usage Guide](usage.md) for general SDK usage
- Check individual resource documentation for available relationships
