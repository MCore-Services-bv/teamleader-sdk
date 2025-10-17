# Migrate

Utility endpoints for migrating from the deprecated Teamleader API to the new UUID-based API.

## Overview

The Migrate resource provides utility methods to help migrate from the old numeric ID-based Teamleader API to the new UUID-based API. These endpoints translate old IDs to new UUIDs, making the migration process smoother for existing integrations.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [activityType()](#activitytype)
    - [taxRate()](#taxrate)
    - [id()](#id)
- [Helper Methods](#helper-methods)
- [Supported Resource Types](#supported-resource-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`migrate`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `activityType()`

Translate activity type (meeting, call, task) into activity type UUID.

**Parameters:**
- `type` (string): Activity type (meeting, call, or task)

**Returns:** Array with activity type UUID

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Translate 'meeting' to UUID
$result = Teamleader::migrate()->activityType('meeting');
$activityTypeId = $result['data']['id'];
$activityTypeType = $result['data']['type'];

// Use the UUID in new API calls
```

### `taxRate()`

Translate old tax rate percentage to new UUID tax rate.

**Parameters:**
- `departmentId` (string): Department UUID
- `taxRate` (string): Tax rate as string (e.g., "21", "6", "0")

**Returns:** Array with tax rate UUID

**Example:**
```php
// Translate 21% tax rate to UUID
$result = Teamleader::migrate()->taxRate('department-uuid', '21');
$taxRateId = $result['data']['id'];

// Use in invoice creation
$invoice = Teamleader::invoices()->create([
    // ... other fields
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Products'
            ],
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Product',
                    'unit_price' => [
                        'amount' => 100.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => $taxRateId
                ]
            ]
        ]
    ]
]);
```

### `id()`

Translate old numeric ID to new UUID.

**Parameters:**
- `type` (string): Resource type (contact, company, invoice, etc.)
- `id` (int): Old numeric ID

**Returns:** Array with new UUID and type

**Example:**
```php
// Translate old contact ID
$result = Teamleader::migrate()->id('contact', 123);
$newUuid = $result['data']['id'];
$resourceType = $result['data']['type'];

// Use new UUID
$contact = Teamleader::contacts()->info($newUuid);
```

## Helper Methods

### Batch ID Migration

```php
// Migrate multiple IDs of the same type
$oldIds = [1, 2, 3, 4, 5];
$mapping = Teamleader::migrate()->batchIds('contact', $oldIds);

// Returns: [1 => 'new-uuid-1', 2 => 'new-uuid-2', ...]
```

### Get Available Activity Types

```php
// Get list of valid activity types
$types = Teamleader::migrate()->getActivityTypes();

// Returns: ['meeting', 'call', 'task']
```

### Get Available Resource Types

```php
// Get list of migratable resource types
$types = Teamleader::migrate()->getResourceTypes();

// Returns: ['contact', 'company', 'invoice', ...]
```

## Supported Resource Types

### Activity Types

Valid activity types for migration:

- `meeting`
- `call`
- `task`

### Resource Types

Valid resource types for ID migration:

- `account`
- `user`
- `department`
- `product`
- `contact`
- `company`
- `deal`
- `dealPhase`
- `project`
- `milestone`
- `task`
- `meeting`
- `call`
- `ticket`
- `invoice`
- `creditNote`
- `subscription`
- `quotation`
- `timeTracking`
- `customField`

**Note:** In responses, some types are transformed:
- `task` becomes `todo`
- `meeting` and `call` become `event`

## Response Structure

### Activity Type Response

```php
[
    'data' => [
        'id' => 'activity-type-uuid',
        'type' => 'meeting' // or 'call', 'task'
    ]
]
```

### Tax Rate Response

```php
[
    'data' => [
        'id' => 'tax-rate-uuid',
        'type' => 'taxRate'
    ]
]
```

### ID Migration Response

```php
[
    'data' => [
        'type' => 'contact', // or 'todo', 'event', etc.
        'id' => 'new-uuid-here'
    ]
]
```

## Usage Examples

### Migrate Activity Type

```php
// Get UUID for meeting activity type
$result = Teamleader::migrate()->activityType('meeting');
$activityTypeId = $result['data']['id'];

// Use in event creation
$event = Teamleader::calendarEvents()->create([
    'activity_type_id' => $activityTypeId,
    'title' => 'Client meeting',
    'starts_at' => '2025-10-20T10:00:00+00:00',
    'ends_at' => '2025-10-20T11:00:00+00:00'
]);
```

### Migrate Tax Rate

```php
// Get UUID for 21% VAT
$result = Teamleader::migrate()->taxRate('department-uuid', '21');
$taxRateId = $result['data']['id'];

// Use in product creation
$product = Teamleader::products()->create([
    'name' => 'Product Name',
    'tax_rate_id' => $taxRateId,
    'unit_price' => [
        'amount' => 100.00,
        'currency' => 'EUR'
    ]
]);
```

### Migrate Contact IDs

```php
// Old contact IDs from legacy system
$oldContactIds = [123, 456, 789];
$newUuids = [];

foreach ($oldContactIds as $oldId) {
    $result = Teamleader::migrate()->id('contact', $oldId);
    $newUuids[$oldId] = $result['data']['id'];
}

// Use new UUIDs
foreach ($newUuids as $oldId => $newUuid) {
    echo "Old ID {$oldId} -> New UUID {$newUuid}\n";
}
```

### Batch ID Migration

```php
// Migrate multiple contacts at once
$oldIds = [1, 2, 3, 4, 5];
$mapping = Teamleader::migrate()->batchIds('contact', $oldIds);

// Update database with new UUIDs
foreach ($mapping as $oldId => $newUuid) {
    DB::table('contacts')
        ->where('legacy_id', $oldId)
        ->update(['teamleader_uuid' => $newUuid]);
}
```

### Migrate Invoice References

```php
// Migrate old invoice IDs in your system
$oldInvoiceIds = DB::table('orders')
    ->whereNotNull('teamleader_invoice_id')
    ->pluck('teamleader_invoice_id')
    ->unique()
    ->toArray();

foreach ($oldInvoiceIds as $oldId) {
    try {
        $result = Teamleader::migrate()->id('invoice', $oldId);
        $newUuid = $result['data']['id'];
        
        // Update all orders with this invoice
        DB::table('orders')
            ->where('teamleader_invoice_id', $oldId)
            ->update(['teamleader_invoice_uuid' => $newUuid]);
            
    } catch (Exception $e) {
        Log::error("Failed to migrate invoice ID {$oldId}");
    }
}
```

### Build Migration Mapping

```php
function buildIdMapping(string $type, array $oldIds): array
{
    $mapping = [
        'successful' => [],
        'failed' => []
    ];
    
    foreach ($oldIds as $oldId) {
        try {
            $result = Teamleader::migrate()->id($type, $oldId);
            $mapping['successful'][$oldId] = $result['data']['id'];
        } catch (Exception $e) {
            $mapping['failed'][$oldId] = $e->getMessage();
            Log::error("Failed to migrate {$type} ID {$oldId}: {$e->getMessage()}");
        }
    }
    
    return $mapping;
}

// Usage
$mapping = buildIdMapping('contact', [1, 2, 3, 4, 5]);

echo "Successfully migrated: " . count($mapping['successful']) . "\n";
echo "Failed: " . count($mapping['failed']) . "\n";
```

## Common Use Cases

### 1. Database Migration Script

```php
class TeamleaderMigrationScript
{
    public function migrateContacts(): void
    {
        $contacts = DB::table('contacts')
            ->whereNotNull('teamleader_legacy_id')
            ->whereNull('teamleader_uuid')
            ->get();
        
        foreach ($contacts as $contact) {
            try {
                $result = Teamleader::migrate()->id('contact', $contact->teamleader_legacy_id);
                
                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update([
                        'teamleader_uuid' => $result['data']['id'],
                        'migrated_at' => now()
                    ]);
                    
                Log::info("Migrated contact {$contact->id}");
                
            } catch (Exception $e) {
                Log::error("Failed to migrate contact {$contact->id}: {$e->getMessage()}");
            }
        }
    }
    
    public function migrateInvoices(): void
    {
        // Similar logic for invoices
    }
    
    public function migrateCompanies(): void
    {
        // Similar logic for companies
    }
}

// Run migration
$migration = new TeamleaderMigrationScript();
$migration->migrateContacts();
$migration->migrateInvoices();
$migration->migrateCompanies();
```

### 2. Legacy API Wrapper

```php
class LegacyTeamleaderWrapper
{
    private $idCache = [];
    
    public function getContact(int $legacyId): array
    {
        $uuid = $this->getLegacyIdAsUuid('contact', $legacyId);
        return Teamleader::contacts()->info($uuid);
    }
    
    public function getInvoice(int $legacyId): array
    {
        $uuid = $this->getLegacyIdAsUuid('invoice', $legacyId);
        return Teamleader::invoices()->info($uuid);
    }
    
    private function getLegacyIdAsUuid(string $type, int $legacyId): string
    {
        $cacheKey = "{$type}_{$legacyId}";
        
        if (!isset($this->idCache[$cacheKey])) {
            $result = Teamleader::migrate()->id($type, $legacyId);
            $this->idCache[$cacheKey] = $result['data']['id'];
        }
        
        return $this->idCache[$cacheKey];
    }
}

// Usage
$wrapper = new LegacyTeamleaderWrapper();
$contact = $wrapper->getContact(123); // Uses old ID
```

### 3. Activity Type Resolver

```php
class ActivityTypeResolver
{
    private static $activityTypes = null;
    
    public static function resolve(string $type): string
    {
        if (self::$activityTypes === null) {
            self::$activityTypes = self::loadActivityTypes();
        }
        
        return self::$activityTypes[$type] ?? null;
    }
    
    private static function loadActivityTypes(): array
    {
        $types = ['meeting', 'call', 'task'];
        $mapping = [];
        
        foreach ($types as $type) {
            $result = Teamleader::migrate()->activityType($type);
            $mapping[$type] = $result['data']['id'];
        }
        
        return $mapping;
    }
}

// Usage
$meetingTypeId = ActivityTypeResolver::resolve('meeting');
```

### 4. Tax Rate Migration Helper

```php
class TaxRateMigrator
{
    private $cache = [];
    
    public function getTaxRateUuid(string $departmentId, string $rate): string
    {
        $cacheKey = "{$departmentId}_{$rate}";
        
        if (!isset($this->cache[$cacheKey])) {
            $result = Teamleader::migrate()->taxRate($departmentId, $rate);
            $this->cache[$cacheKey] = $result['data']['id'];
        }
        
        return $this->cache[$cacheKey];
    }
    
    public function migrateInvoiceLineItems(array $lineItems, string $departmentId): array
    {
        foreach ($lineItems as &$item) {
            if (isset($item['tax_rate_percentage'])) {
                $rate = (string) $item['tax_rate_percentage'];
                $item['tax_rate_id'] = $this->getTaxRateUuid($departmentId, $rate);
                unset($item['tax_rate_percentage']);
            }
        }
        
        return $lineItems;
    }
}
```

## Best Practices

### 1. Cache Migrated IDs

```php
// Cache migrated IDs to avoid repeated API calls
class IdMigrationCache
{
    public static function getUuid(string $type, int $legacyId): string
    {
        $cacheKey = "teamleader_migrate_{$type}_{$legacyId}";
        
        return Cache::remember($cacheKey, 86400, function() use ($type, $legacyId) {
            $result = Teamleader::migrate()->id($type, $legacyId);
            return $result['data']['id'];
        });
    }
}
```

### 2. Handle Migration Errors Gracefully

```php
function migrateIdSafely(string $type, int $legacyId): ?string
{
    try {
        $result = Teamleader::migrate()->id($type, $legacyId);
        return $result['data']['id'];
    } catch (Exception $e) {
        Log::error("Migration failed for {$type} ID {$legacyId}", [
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
```

### 3. Batch Migrations for Efficiency

```php
// Migrate in batches rather than one at a time
function migrateBatch(string $type, array $legacyIds, int $batchSize = 100): array
{
    $results = [];
    $batches = array_chunk($legacyIds, $batchSize);
    
    foreach ($batches as $batch) {
        $batchResults = Teamleader::migrate()->batchIds($type, $batch);
        $results = array_merge($results, $batchResults);
        
        // Small delay to avoid rate limiting
        usleep(100000); // 100ms
    }
    
    return $results;
}
```

### 4. Validate Resource Types

```php
function isValidResourceType(string $type): bool
{
    $validTypes = Teamleader::migrate()->getResourceTypes();
    return in_array($type, $validTypes);
}

// Usage
if (isValidResourceType($requestedType)) {
    $uuid = Teamleader::migrate()->id($requestedType, $legacyId);
}
```

### 5. Log Migration Progress

```php
function migrateWithLogging(string $type, array $legacyIds): array
{
    $total = count($legacyIds);
    $processed = 0;
    $results = ['success' => [], 'failed' => []];
    
    foreach ($legacyIds as $legacyId) {
        try {
            $result = Teamleader::migrate()->id($type, $legacyId);
            $results['success'][$legacyId] = $result['data']['id'];
        } catch (Exception $e) {
            $results['failed'][$legacyId] = $e->getMessage();
        }
        
        $processed++;
        
        if ($processed % 10 === 0) {
            Log::info("Migration progress: {$processed}/{$total}");
        }
    }
    
    Log::info("Migration complete", [
        'total' => $total,
        'successful' => count($results['success']),
        'failed' => count($results['failed'])
    ]);
    
    return $results;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Migrate activity type
try {
    $result = Teamleader::migrate()->activityType('meeting');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        Log::error('Invalid activity type');
    }
}

// Migrate tax rate
try {
    $result = Teamleader::migrate()->taxRate('department-uuid', '21');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::error('Department not found');
    } elseif ($e->getCode() === 422) {
        Log::error('Invalid tax rate');
    }
}

// Migrate ID
try {
    $result = Teamleader::migrate()->id('contact', 123);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::error('Legacy ID not found');
    } elseif ($e->getCode() === 422) {
        Log::error('Invalid resource type');
    }
}
```

## Important Notes

### 1. One-Way Migration

This is a one-way migration tool. You cannot convert new UUIDs back to old numeric IDs.

### 2. Legacy API Deprecation

These endpoints are designed to help with the transition from the deprecated API. Once migrated, use the new UUID-based endpoints directly.

### 3. Response Type Changes

Some resource types are transformed in responses:
- `task` → `todo`
- `meeting`/`call` → `event`

### 4. Not All IDs May Exist

If a legacy ID doesn't exist in the system, the migration will fail with a 404 error.

### 5. Rate Limiting

Be mindful of API rate limits when performing bulk migrations. Implement delays between requests if necessary.

## Migration Checklist

When migrating from legacy API to UUID-based API:

1. **Identify Legacy IDs**: Find all places where old numeric IDs are stored
2. **Run Migration**: Use migrate endpoints to convert IDs to UUIDs
3. **Update Database**: Store new UUIDs in your database
4. **Update Code**: Change code to use new API endpoints
5. **Test**: Verify all integrations work with new UUIDs
6. **Deploy**: Roll out changes to production
7. **Monitor**: Watch for any migration-related issues
8. **Cleanup**: Remove old ID references once stable

## Related Resources

- [Contacts](../crm/contacts.md) - Contact management
- [Companies](../crm/companies.md) - Company management
- [Invoices](../invoicing/invoices.md) - Invoice management
- [Calendar Events](../calendar/events.md) - Event management
- [Tax Rates](../invoicing/tax-rates.md) - Tax rate information

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Migration Guide](../migration-guide.md) - Complete migration guide
