# Sideloading (Including Related Resources)

Sideloading allows you to fetch related resources in a single API request, reducing the number of HTTP calls needed and improving performance. The Teamleader SDK provides a fluent interface for sideloading relationships.

## Table of Contents

- [What is Sideloading?](#what-is-sideloading)
- [Basic Sideloading](#basic-sideloading)
- [Fluent Interface](#fluent-interface)
- [Common Relationships](#common-relationships)
- [Advanced Sideloading](#advanced-sideloading)
- [Response Structure](#response-structure)
- [Performance Benefits](#performance-benefits)
- [Best Practices](#best-practices)

## What is Sideloading?

Sideloading (also called "compound documents") allows you to retrieve related resources as part of a single request. For example, when fetching a deal, you can also include the customer and responsible user information without making separate API calls.

### Without Sideloading (3 API calls)
```php
// Get the deal
$deal = $teamleader->deals()->info('deal-uuid');

// Get the customer (separate call)
$customer = $teamleader->companies()->info($deal['data']['lead']['customer']['id']);

// Get the responsible user (separate call)
$user = $teamleader->users()->info($deal['data']['responsible_user']['id']);
```

### With Sideloading (1 API call)
```php
// Get everything in one request
$deal = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->info('deal-uuid');

// All related data is included in the response
$customer = $deal['included']['company'][0];
$user = $deal['included']['user'][0];
```

## Basic Sideloading

### Using the `with()` Method

```php
// Include a single relationship
$deals = $teamleader->deals()
    ->with('responsible_user')
    ->list();

// Include multiple relationships
$deals = $teamleader->deals()
    ->with(['lead.customer', 'responsible_user', 'department'])
    ->list();

// Include nested relationships using dot notation
$deals = $teamleader->deals()
    ->with('lead.customer')
    ->list();
```

### Using Raw Include Parameter

```php
// Pass includes directly to list method
$deals = $teamleader->deals()->list([], [
    'include' => 'lead.customer,responsible_user'
]);

// Or for single resource
$deal = $teamleader->deals()->info('deal-uuid', [
    'include' => 'lead.customer,responsible_user,department'
]);
```

## Fluent Interface

The SDK provides a fluent interface for common relationships:

### Helper Methods

```php
// Include customer information
$deals = $teamleader->deals()->withCustomer()->list();

// Include responsible user
$contacts = $teamleader->contacts()->withResponsibleUser()->list();

// Include department
$projects = $teamleader->projects()->withDepartment()->list();

// Include company (for contacts)
$contacts = $teamleader->contacts()->withCompany()->list();

// Include all common relationships at once
$deals = $teamleader->deals()->withCommonRelationships()->list();
```

### Chaining Multiple Includes

```php
$deals = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->withDepartment()
    ->with('deal_source')
    ->list();
```

### Conditional Includes

```php
$query = $teamleader->deals();

if ($includeCustomer) {
    $query->withCustomer();
}

if ($includeUser) {
    $query->withResponsibleUser();
}

$deals = $query->list();
```

## Common Relationships

### Deals

```php
$deals = $teamleader->deals()
    ->withCustomer()           // lead.customer
    ->withResponsibleUser()    // responsible_user
    ->withDepartment()         // department
    ->with('deal_source')      // deal_source
    ->with('deal_phase')       // deal_phase
    ->list();
```

### Contacts

```php
$contacts = $teamleader->contacts()
    ->withCompany()            // company
    ->withResponsibleUser()    // responsible_user
    ->with('addresses')        // addresses
    ->with('tags')             // tags
    ->list();
```

### Companies

```php
$companies = $teamleader->companies()
    ->withResponsibleUser()    // responsible_user
    ->with('addresses')        // addresses
    ->with('tags')             // tags
    ->with('business_type')    // business_type
    ->list();
```

### Projects

```php
$projects = $teamleader->projects()
    ->withCustomer()           // customer
    ->withResponsibleUser()    // responsible_user
    ->withDepartment()         // department
    ->with('project_status')   // project_status
    ->list();
```

### Invoices

```php
$invoices = $teamleader->invoices()
    ->with('customer')         // customer
    ->withResponsibleUser()    // responsible_user
    ->withDepartment()         // department
    ->with('payment_term')     // payment_term
    ->list();
```

## Advanced Sideloading

### Nested Relationships

Use dot notation for nested relationships:

```php
// Include customer and their addresses
$deals = $teamleader->deals()
    ->with('lead.customer.addresses')
    ->list();

// Multiple nested includes
$quotations = $teamleader->quotations()
    ->with([
        'lead.customer.addresses',
        'lead.customer.business_type',
        'responsible_user.department'
    ])
    ->list();
```

### Conditional Nested Includes

```php
$deals = $teamleader->deals()
    ->withCustomer()
    ->with('lead.customer.addresses')  // Only if customer exists
    ->list();
```

### Resource-Specific Includes

Different resources support different relationships:

```php
// Time tracking with project and user
$timeEntries = $teamleader->timeTracking()
    ->with(['project', 'user', 'work_type'])
    ->list();

// Tasks with project and assignee
$tasks = $teamleader->tasks()
    ->with(['project', 'assignee', 'milestone'])
    ->list();

// Meetings with participants
$meetings = $teamleader->meetings()
    ->with(['attendees', 'organizer'])
    ->list();
```

## Response Structure

When you include related resources, they appear in the `included` section of the response:

```php
$response = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->info('deal-uuid');

// Main resource
$deal = $response['data'];

// Related resources are indexed by type
$included = $response['included'];

// Find the customer
$customerId = $deal['lead']['customer']['id'];
$customer = collect($included['company'])->firstWhere('id', $customerId);

// Find the responsible user
$userId = $deal['responsible_user']['id'];
$user = collect($included['user'])->firstWhere('id', $userId);
```

### Helper for Extracting Included Resources

```php
class IncludedResourceHelper
{
    public static function find(array $included, string $type, string $id)
    {
        if (!isset($included[$type])) {
            return null;
        }
        
        return collect($included[$type])->firstWhere('id', $id);
    }
    
    public static function findAll(array $included, string $type): array
    {
        return $included[$type] ?? [];
    }
}

// Usage
$customer = IncludedResourceHelper::find($response['included'], 'company', $customerId);
$user = IncludedResourceHelper::find($response['included'], 'user', $userId);
```

## Performance Benefits

### Reduced API Calls

```php
// Without sideloading: 1 + N queries (where N = number of deals)
$deals = $teamleader->deals()->list();
foreach ($deals['data'] as $deal) {
    // Each iteration makes 2 additional API calls
    $customer = $teamleader->companies()->info($deal['lead']['customer']['id']);
    $user = $teamleader->users()->info($deal['responsible_user']['id']);
}

// With sideloading: 1 query total
$deals = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->list();
// All related data is already included
```

### Rate Limit Efficiency

```php
// This uses only 1 API call instead of potentially 50+
$dealsWithRelated = $teamleader->deals()
    ->withCommonRelationships()
    ->list(['page_size' => 50]);

// Check rate limit usage
$stats = $teamleader->getRateLimitStats();
echo "Used only 1 request instead of 100+";
```

## Best Practices

### 1. Include Only What You Need

```php
// Good: Include only required relationships
$deals = $teamleader->deals()
    ->withCustomer()
    ->list();

// Avoid: Including everything unnecessarily
$deals = $teamleader->deals()
    ->withCommonRelationships()
    ->with(['deal_source', 'deal_phase', 'tags'])
    ->list(); // Only if you actually need all of this
```

### 2. Use Common Patterns

```php
// For displaying deal cards
$dealsForCards = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->list();

// For detailed deal view
$dealDetails = $teamleader->deals()
    ->withCommonRelationships()
    ->with(['deal_source', 'deal_phase'])
    ->info($dealId);
```

### 3. Clear Includes When Not Needed

```php
$query = $teamleader->deals()->withCustomer();

if ($lightweightQuery) {
    $query->withoutIncludes(); // Clear previously set includes
}

$deals = $query->list();
```

### 4. Validate Include Paths

The SDK automatically validates include paths:

```php
// This will be filtered out (invalid relationship)
$deals = $teamleader->deals()
    ->with('invalid_relationship')  // Ignored
    ->withCustomer()                // Valid, will be included
    ->list();
```

### 5. Handle Missing Relationships

```php
$response = $teamleader->deals()->withCustomer()->info($dealId);

$deal = $response['data'];
$customer = null;

// Check if customer reference exists
if (isset($deal['lead']['customer']['id'])) {
    $customerId = $deal['lead']['customer']['id'];
    
    // Find in included resources
    if (isset($response['included']['company'])) {
        $customer = collect($response['included']['company'])
            ->firstWhere('id', $customerId);
    }
}

// Handle case where customer might not be included
if ($customer) {
    echo "Customer: " . $customer['name'];
} else {
    echo "Customer information not available";
}
```

### 6. Caching Strategies

```php
// Cache the response with includes to avoid repeated API calls
$cacheKey = "deals_with_customers_" . md5(serialize($filters));

$deals = Cache::remember($cacheKey, 300, function() use ($filters) {
    return $teamleader->deals()
        ->withCustomer()
        ->withResponsibleUser()
        ->list($filters);
});
```

## Common Sideloading Patterns

### Dashboard Widgets

```php
// Recent deals for dashboard
$recentDeals = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->list([
        'updated_since' => now()->subDays(7)->toISOString()
    ], [
        'page_size' => 10,
        'sort' => 'updated_at',
        'sort_order' => 'desc'
    ]);
```

### Export/Reporting

```php
// Full data export with all relationships
$fullData = $teamleader->deals()
    ->withCommonRelationships()
    ->with(['deal_source', 'deal_phase', 'tags'])
    ->list([], ['page_size' => 100]);
```

### Mobile API Optimization

```php
// Optimized for mobile - minimal includes
$mobileDeals = $teamleader->deals()
    ->withCustomer() // Only customer name needed
    ->list();
```

### Search Results

```php
// Search with relevant context
$searchResults = $teamleader->contacts()
    ->withCompany()
    ->withResponsibleUser()
    ->list([
        'email' => $searchTerm
    ]);
```

This comprehensive sideloading system ensures you can efficiently fetch related data while maintaining optimal API performance and respecting rate limits.
