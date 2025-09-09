# Filtering and Search

The Teamleader SDK provides powerful filtering capabilities through the `FilterTrait`, allowing you to search, filter, sort, and paginate results efficiently.

## Table of Contents

- [Basic Filtering](#basic-filtering)
- [Common Filter Types](#common-filter-types)
- [Sorting](#sorting)
- [Pagination](#pagination)
- [Combined Queries](#combined-queries)
- [Advanced Filtering](#advanced-filtering)
- [Filter Validation](#filter-validation)

## Basic Filtering

All list endpoints support filtering through an array of filter criteria:

```php
// Filter contacts by company
$contacts = $teamleader->contacts()->list([
    'company_id' => 'company-uuid-here'
]);

// Filter deals by phase
$deals = $teamleader->deals()->list([
    'phase_id' => 'active-phase-uuid'
]);

// Multiple filters
$invoices = $teamleader->invoices()->list([
    'customer_id' => 'customer-uuid',
    'status' => 'draft'
]);
```

## Common Filter Types

### Date Filters

Most resources support date-based filtering:

```php
// Items updated since a specific date
$recentDeals = $teamleader->deals()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);

// Items created within a date range
$invoices = $teamleader->invoices()->list([
    'invoice_date_after' => '2024-01-01T00:00:00+00:00',
    'invoice_date_before' => '2024-03-31T23:59:59+00:00'
]);
```

### Status Filters

Filter by various status fields:

```php
// Draft invoices
$draftInvoices = $teamleader->invoices()->list([
    'status' => 'draft'
]);

// Active deals
$activeDeals = $teamleader->deals()->list([
    'status' => 'active'
]);

// Completed projects
$completedProjects = $teamleader->projects()->list([
    'status' => 'completed'
]);
```

### Relationship Filters

Filter by related entities:

```php
// Contacts belonging to a specific company
$contacts = $teamleader->contacts()->list([
    'company_id' => 'company-uuid'
]);

// Deals assigned to a specific user
$userDeals = $teamleader->deals()->list([
    'responsible_user_id' => 'user-uuid'
]);

// Projects in a specific department
$departmentProjects = $teamleader->projects()->list([
    'department_id' => 'department-uuid'
]);
```

### Search Filters

Some endpoints support text search:

```php
// Search companies by name
$companies = $teamleader->companies()->list([
    'name' => 'ACME'
]);

// Search contacts by email domain
$contacts = $teamleader->contacts()->list([
    'email' => '@example.com'
]);
```

## Sorting

Sort results by any supported field:

```php
// Sort by single field (ascending by default)
$deals = $teamleader->deals()->list([], [
    'sort' => 'created_at'
]);

// Sort descending
$recentDeals = $teamleader->deals()->list([], [
    'sort' => 'updated_at',
    'sort_order' => 'desc'
]);

// Multiple sort fields
$contacts = $teamleader->contacts()->list([], [
    'sort' => ['last_name', 'first_name'],
    'sort_order' => 'asc'
]);
```

### Advanced Sorting Configuration

For complex sorting requirements:

```php
// Custom sort configuration
$deals = $teamleader->deals()->list([], [
    'sort' => [
        ['field' => 'phase_id', 'order' => 'asc'],
        ['field' => 'value', 'order' => 'desc'],
        ['field' => 'updated_at', 'order' => 'desc']
    ]
]);
```

## Pagination

Control result pagination:

```php
// Basic pagination
$companies = $teamleader->companies()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// Large result sets
$allContacts = $teamleader->contacts()->list([], [
    'page_size' => 100,  // Maximum allowed by Teamleader
    'page_number' => 1
]);
```

### Pagination Helper

```php
public function getAllContacts()
{
    $allContacts = [];
    $page = 1;
    
    do {
        $result = $this->teamleader->contacts()->list([], [
            'page_size' => 100,
            'page_number' => $page
        ]);
        
        $contacts = $result['data'] ?? [];
        $allContacts = array_merge($allContacts, $contacts);
        
        $meta = $result['meta'] ?? [];
        $hasMore = count($contacts) === 100;
        
        $page++;
    } while ($hasMore);
    
    return $allContacts;
}
```

## Combined Queries

Combine filters, sorting, and pagination:

```php
$filteredDeals = $teamleader->deals()->list(
    // Filters
    [
        'phase_id' => 'active-phase-uuid',
        'updated_since' => '2024-01-01T00:00:00+00:00',
        'responsible_user_id' => 'user-uuid'
    ],
    // Options
    [
        'page_size' => 25,
        'page_number' => 1,
        'sort' => 'value',
        'sort_order' => 'desc'
    ]
);
```

## Advanced Filtering

### Array Filters

Some endpoints support filtering by multiple values:

```php
// Filter by multiple deal phases
$deals = $teamleader->deals()->list([
    'phase_id' => ['phase-1-uuid', 'phase-2-uuid', 'phase-3-uuid']
]);

// Filter by multiple statuses
$invoices = $teamleader->invoices()->list([
    'status' => ['draft', 'sent']
]);
```

### Numeric Range Filters

Filter by numeric ranges where supported:

```php
// Deals with value above threshold
$highValueDeals = $teamleader->deals()->list([
    'minimum_value' => 10000
]);

// Projects within budget range
$projects = $teamleader->projects()->list([
    'minimum_budget' => 5000,
    'maximum_budget' => 50000
]);
```

### Custom Field Filters

Filter by custom fields (where supported):

```php
$contacts = $teamleader->contacts()->list([
    'custom_fields' => [
        'custom-field-uuid' => 'desired-value'
    ]
]);
```

### Tag Filters

Filter by tags:

```php
// Contacts with specific tags
$taggedContacts = $teamleader->contacts()->list([
    'tags' => ['vip-client', 'priority']
]);
```

## Filter Validation

The SDK automatically validates and cleans filters:

```php
// Empty values are automatically removed
$deals = $teamleader->deals()->list([
    'phase_id' => 'active-phase-uuid',
    'user_id' => '',           // This will be removed
    'status' => null,          // This will be removed
    'tags' => []               // This will be removed
]);
```

### Manual Filter Building

For complex scenarios, you can build filters manually:

```php
use McoreServices\TeamleaderSDK\Traits\FilterTrait;

class CustomFilterService
{
    use FilterTrait;
    
    public function buildComplexQuery(array $criteria)
    {
        $params = [];
        
        // Apply filters
        $params = $this->applyFilters($params, $criteria['filters'] ?? []);
        
        // Apply sorting
        $params = $this->applySorting(
            $params, 
            $criteria['sort'] ?? null, 
            $criteria['sort_order'] ?? 'asc'
        );
        
        // Apply pagination
        $params = $this->applyPagination(
            $params, 
            $criteria['page_size'] ?? 20, 
            $criteria['page_number'] ?? 1
        );
        
        return $params;
    }
}
```

## Performance Tips

### Efficient Filtering

1. **Use specific filters** - More specific filters reduce result sets and improve performance
2. **Limit page size** - Use appropriate page sizes (20-100 items)
3. **Use date filters** - Filter by `updated_since` for incremental syncing
4. **Combine filters** - Multiple filters are AND-ed together for precision

```php
// Efficient: Get only what you need
$recentActiveDeals = $teamleader->deals()->list([
    'phase_id' => 'active-phase-uuid',
    'updated_since' => now()->subDays(7)->toISOString()
], [
    'page_size' => 50
]);

// Less efficient: Large result set without filters
$allDeals = $teamleader->deals()->list([], [
    'page_size' => 100
]);
```

### Rate Limit Considerations

When performing multiple filtered queries:

```php
public function getBatchedResults(array $filters)
{
    $results = [];
    
    foreach ($filters as $filter) {
        // Check rate limits before each request
        $stats = $this->teamleader->getRateLimitStats();
        
        if ($stats['remaining'] < 10) {
            // Wait for rate limit reset
            sleep($stats['seconds_until_reset'] + 1);
        }
        
        $results[] = $this->teamleader->deals()->list($filter);
    }
    
    return $results;
}
```

## Common Filter Patterns

### Recently Updated Items

```php
$recentlyUpdated = $teamleader->contacts()->list([
    'updated_since' => now()->subHours(1)->toISOString()
]);
```

### Items by Date Range

```php
$monthlyInvoices = $teamleader->invoices()->list([
    'created_after' => now()->startOfMonth()->toISOString(),
    'created_before' => now()->endOfMonth()->toISOString()
]);
```

### User-Specific Items

```php
$myDeals = $teamleader->deals()->list([
    'responsible_user_id' => auth()->user()->teamleader_user_id
]);
```

### Department Items

```php
$departmentProjects = $teamleader->projects()->list([
    'department_id' => $user->department->teamleader_id
]);
```

## Error Handling

Handle filtering errors gracefully:

```php
try {
    $result = $teamleader->deals()->list($filters, $options);
    
    if (isset($result['error'])) {
        Log::warning('Filter query failed', [
            'filters' => $filters,
            'error' => $result['message']
        ]);
        
        // Fall back to unfiltered results
        return $teamleader->deals()->list();
    }
    
    return $result;
} catch (Exception $e) {
    Log::error('Filtering exception', [
        'filters' => $filters,
        'exception' => $e->getMessage()
    ]);
    
    throw $e;
}
```

This comprehensive filtering system allows you to efficiently query Teamleader's API while respecting rate limits and following best practices for performance and reliability.
