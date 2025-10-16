# Tags

Manage tags in Teamleader Focus.

## Overview

The Tags resource provides read-only access to the tags in your Teamleader account. Tags are labels that can be applied to various entities (companies, contacts, deals, etc.) for organization and categorization. While you can retrieve tag information through this resource, tags are actually created when you apply them to entities using their respective resources.

**Important:** The Tags resource is read-only. You cannot create, update, or delete tags directly through this resource. Tags are automatically created when you tag an entity (company, contact, etc.) and are removed when they're no longer used.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`tags`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (tags are created when applying to entities)
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported (tags are removed when no longer in use)

## Available Methods

### `list()`

Get all tags with optional sorting and pagination.

**Parameters:**
- `filters` (array): Filters (not supported for tags)
- `options` (array): Additional options (page_size, page_number, sort, sort_order)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all tags
$tags = Teamleader::tags()->list();

// With pagination
$tags = Teamleader::tags()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Sorted alphabetically
$tags = Teamleader::tags()->list([], [
    'sort' => 'tag',
    'sort_order' => 'asc'
]);
```

## Helper Methods

The Tags resource provides convenient helper methods:

### Search Tags

```php
// Search for tags containing a term
$tags = Teamleader::tags()->search('VIP');

// This searches through all tags and returns matches
```

## Sorting

Tags can be sorted alphabetically:

```php
// Sort ascending (A-Z)
$tags = Teamleader::tags()->list([], [
    'sort' => 'tag',
    'sort_order' => 'asc'
]);

// Sort descending (Z-A)
$tags = Teamleader::tags()->list([], [
    'sort' => 'tag',
    'sort_order' => 'desc'
]);
```

## Response Structure

### Tag Object

```php
[
    'tag' => 'VIP'
]
```

**Note:** Tags are returned as simple objects with just the tag name. There is no UUID or other metadata.

### List Response

```php
[
    'data' => [
        ['tag' => 'Customer'],
        ['tag' => 'Decision Maker'],
        ['tag' => 'Enterprise'],
        ['tag' => 'Lead'],
        ['tag' => 'Partner'],
        ['tag' => 'Premium'],
        ['tag' => 'Prospect'],
        ['tag' => 'VIP']
    ]
]
```

## Usage Examples

### Get All Tags

```php
$allTags = Teamleader::tags()->list();

foreach ($allTags['data'] as $tag) {
    echo $tag['tag'] . "\n";
}
```

### Get Paginated Tags

```php
$page1 = Teamleader::tags()->list([], [
    'page_size' => 20,
    'page_number' => 1,
    'sort' => 'tag',
    'sort_order' => 'asc'
]);

$page2 = Teamleader::tags()->list([], [
    'page_size' => 20,
    'page_number' => 2,
    'sort' => 'tag',
    'sort_order' => 'asc'
]);
```

### Search for Specific Tags

```php
// Find tags containing "Customer"
$customerTags = Teamleader::tags()->search('Customer');

// Results might include: "Customer", "VIP Customer", "New Customer", etc.
```

### Get All Tags for a Dropdown

```php
function getTagsForDropdown()
{
    $allTags = [];
    $page = 1;
    
    do {
        $response = Teamleader::tags()->list([], [
            'page_size' => 100,
            'page_number' => $page,
            'sort' => 'tag',
            'sort_order' => 'asc'
        ]);
        
        $allTags = array_merge($allTags, $response['data']);
        $page++;
    } while (!empty($response['data']) && count($response['data']) === 100);
    
    // Format for dropdown
    return array_column($allTags, 'tag', 'tag');
}
```

## Common Use Cases

### 1. Build a Tag Autocomplete

```php
class TagAutocompleteService
{
    protected $tags = null;
    
    public function search($query)
    {
        if ($this->tags === null) {
            $this->loadTags();
        }
        
        $query = strtolower($query);
        
        return array_filter($this->tags, function($tag) use ($query) {
            return str_contains(strtolower($tag['tag']), $query);
        });
    }
    
    protected function loadTags()
    {
        $allTags = [];
        $page = 1;
        
        do {
            $response = Teamleader::tags()->list([], [
                'page_size' => 100,
                'page_number' => $page
            ]);
            
            $allTags = array_merge($allTags, $response['data']);
            $page++;
        } while (!empty($response['data']) && count($response['data']) === 100);
        
        $this->tags = $allTags;
    }
}
```

### 2. Cache Tags for Performance

```php
use Illuminate\Support\Facades\Cache;

function getCachedTags()
{
    return Cache::remember('teamleader_tags', 3600, function() {
        $allTags = [];
        $page = 1;
        
        do {
            $response = Teamleader::tags()->list([], [
                'page_size' => 100,
                'page_number' => $page,
                'sort' => 'tag',
                'sort_order' => 'asc'
            ]);
            
            $allTags = array_merge($allTags, $response['data']);
            $page++;
        } while (!empty($response['data']) && count($response['data']) === 100);
        
        return $allTags;
    });
}
```

### 3. Validate Tags Before Applying

```php
function validateTags(array $tags)
{
    $existingTags = Teamleader::tags()->list();
    $existingTagNames = array_column($existingTags['data'], 'tag');
    
    $invalidTags = [];
    
    foreach ($tags as $tag) {
        if (!in_array($tag, $existingTagNames)) {
            $invalidTags[] = $tag;
        }
    }
    
    if (!empty($invalidTags)) {
        // These are new tags that will be created when applied
        Log::info('New tags will be created', ['tags' => $invalidTags]);
    }
    
    return empty($invalidTags);
}
```

### 4. Generate Tag Usage Report

```php
function generateTagUsageReport()
{
    $tags = Teamleader::tags()->list();
    $report = [];
    
    foreach ($tags['data'] as $tag) {
        $tagName = $tag['tag'];
        
        // Count usage across different entities
        $companies = Teamleader::companies()->withTags([$tagName]);
        $contacts = Teamleader::contacts()->withTags([$tagName]);
        $deals = Teamleader::deals()->withTags([$tagName]);
        
        $report[] = [
            'tag' => $tagName,
            'companies' => count($companies['data']),
            'contacts' => count($contacts['data']),
            'deals' => count($deals['data']),
            'total' => count($companies['data']) + 
                      count($contacts['data']) + 
                      count($deals['data'])
        ];
    }
    
    // Sort by total usage
    usort($report, function($a, $b) {
        return $b['total'] - $a['total'];
    });
    
    return $report;
}
```

### 5. Sync Tags with External System

```php
class TagSyncService
{
    public function syncTags()
    {
        // Get all Teamleader tags
        $teamleaderTags = $this->getAllTeamleaderTags();
        
        // Get tags from external system
        $externalTags = $this->getExternalTags();
        
        // Find differences
        $onlyInTeamleader = array_diff($teamleaderTags, $externalTags);
        $onlyInExternal = array_diff($externalTags, $teamleaderTags);
        
        return [
            'teamleader_only' => $onlyInTeamleader,
            'external_only' => $onlyInExternal,
            'common' => array_intersect($teamleaderTags, $externalTags)
        ];
    }
    
    protected function getAllTeamleaderTags()
    {
        $allTags = [];
        $page = 1;
        
        do {
            $response = Teamleader::tags()->list([], [
                'page_size' => 100,
                'page_number' => $page
            ]);
            
            foreach ($response['data'] as $tag) {
                $allTags[] = $tag['tag'];
            }
            
            $page++;
        } while (!empty($response['data']) && count($response['data']) === 100);
        
        return $allTags;
    }
    
    protected function getExternalTags()
    {
        // Implementation depends on external system
        return [];
    }
}
```

## Best Practices

### 1. Cache Tags for Performance

Since tags don't change frequently, caching them can significantly improve performance:

```php
// Good: Cache tags for 1 hour
$tags = Cache::remember('tags', 3600, function() {
    return Teamleader::tags()->list();
});

// Bad: Fetch tags on every request
$tags = Teamleader::tags()->list();
```

### 2. Use Pagination for Large Tag Lists

```php
// Good: Paginate for large lists
function getAllTags()
{
    $allTags = [];
    $page = 1;
    
    do {
        $response = Teamleader::tags()->list([], [
            'page_size' => 100,
            'page_number' => $page
        ]);
        
        $allTags = array_merge($allTags, $response['data']);
        $page++;
    } while (!empty($response['data']) && count($response['data']) === 100);
    
    return $allTags;
}
```

### 3. Sort Tags Alphabetically

Always sort tags alphabetically for better user experience:

```php
$tags = Teamleader::tags()->list([], [
    'sort' => 'tag',
    'sort_order' => 'asc'
]);
```

### 4. Use Search for Tag Filtering

```php
// Good: Use search method for filtering
$vipTags = Teamleader::tags()->search('VIP');

// Less efficient: Get all and filter manually
$allTags = Teamleader::tags()->list();
$vipTags = array_filter($allTags['data'], function($tag) {
    return str_contains($tag['tag'], 'VIP');
});
```

### 5. Remember Tags Are Auto-Created

```php
// Tags are created automatically when you tag an entity
// You don't need to check if a tag exists before applying it

// This will create the "New Tag" tag if it doesn't exist
Teamleader::companies()->tag('company-uuid', ['New Tag']);

// The tag will now appear in the tags list
$tags = Teamleader::tags()->list();
// Will include: ['tag' => 'New Tag']
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $tags = Teamleader::tags()->list();
} catch (TeamleaderException $e) {
    Log::error('Error fetching tags', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    return ['data' => []];
}
```

## How Tags Work

### Creating Tags

Tags are NOT created through the Tags resource. Instead, they are automatically created when you apply them to an entity:

```php
// This creates the tag "VIP Customer" if it doesn't exist
Teamleader::companies()->tag('company-uuid', ['VIP Customer']);

// Now the tag appears in the tags list
$tags = Teamleader::tags()->list();
// Will include: ['tag' => 'VIP Customer']
```

### Applying Tags to Entities

Tags can be applied to:
- Companies
- Contacts
- Deals
- Projects
- And other entities that support tagging

```php
// Tag a company
Teamleader::companies()->tag('company-uuid', ['Enterprise', 'Partner']);

// Tag a contact
Teamleader::contacts()->tag('contact-uuid', ['Decision Maker', 'VIP']);

// Tag a deal
Teamleader::deals()->tag('deal-uuid', ['High Priority', 'Q1 2024']);
```

### Tag Lifecycle

1. **Creation**: Tags are created automatically when first applied to an entity
2. **Usage**: Tags can be viewed through the Tags resource
3. **Removal**: Tags are automatically removed from the system when they're no longer applied to any entity

## Limitations

1. **Read-Only**: You cannot create, update, or delete tags directly through this resource
2. **No Filters**: The tags endpoint doesn't support filtering (except through the search helper method)
3. **No Individual Info**: You cannot fetch information about a single tag
4. **No Metadata**: Tags only contain the tag name, no additional metadata like usage count or creation date

```php
// Cannot do this:
// Teamleader::tags()->create(['name' => 'New Tag']); // ❌ Not supported
// Teamleader::tags()->delete('tag-name'); // ❌ Not supported
// Teamleader::tags()->info('tag-name'); // ❌ Not supported

// Instead, tags are created when applied to entities:
Teamleader::companies()->tag('company-uuid', ['New Tag']); // ✅ Creates tag
```

## Related Resources

- [Companies](companies.md) - Tag companies
- [Contacts](contacts.md) - Tag contacts
- [Deals](../deals/deals.md) - Tag deals
- [Projects](../projects/projects.md) - Tag projects

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
