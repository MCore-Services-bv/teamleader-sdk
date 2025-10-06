# Migrate

Utility endpoints for migrating from the deprecated Teamleader API to the new UUID-based API. These endpoints help translate old numeric IDs, activity types, and tax rates into their new UUID equivalents.

## Endpoint

`migrate`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ⚠️ Partial (via `batchIds()` helper method)

## Available Methods

### `activityType()`

Translate activity type names (meeting, call, task) into their respective activity type UUIDs.

**Parameters:**
- `type` (string, required): Activity type - must be one of: `meeting`, `call`, or `task`

**Returns:** Array with activity type UUID and type

**Example:**
```php
$result = $teamleader->migrate()->activityType('meeting');

// Returns:
// [
//     'data' => [
//         'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
//         'type' => 'meeting'
//     ]
// ]

// Migrate all activity types
$activityTypes = ['meeting', 'call', 'task'];
$uuids = [];

foreach ($activityTypes as $type) {
    $result = $teamleader->migrate()->activityType($type);
    $uuids[$type] = $result['data']['id'];
}
```

### `taxRate()`

Translate tax rates from the deprecated API into new UUID tax rates.

**Parameters:**
- `departmentId` (string, required): Department UUID
- `taxRate` (string, required): Tax rate as a string (e.g., "21", "6", "0")

**Returns:** Array with tax rate UUID

**Example:**
```php
$result = $teamleader->migrate()->taxRate(
    '6ad54ec6-ee2d-4500-afe6-0917c1aa7a38',
    '21'
);

// Returns:
// [
//     'data' => [
//         'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
//         'type' => 'taxRate'
//     ]
// ]

// Migrate multiple tax rates for a department
$departmentId = '6ad54ec6-ee2d-4500-afe6-0917c1aa7a38';
$taxRates = ['0', '6', '12', '21'];
$taxRateUuids = [];

foreach ($taxRates as $rate) {
    $result = $teamleader->migrate()->taxRate($departmentId, $rate);
    $taxRateUuids[$rate] = $result['data']['id'];
}
```

### `id()`

Translate old numeric IDs from the deprecated API into new UUIDs.

**Parameters:**
- `type` (string, required): Resource type (see [Supported Resource Types](#supported-resource-types))
- `id` (int, required): Old numeric ID

**Returns:** Array with new UUID and resource type

**Example:**
```php
// Migrate a contact ID
$result = $teamleader->migrate()->id('contact', 123);

// Returns:
// [
//     'data' => [
//         'type' => 'contact',
//         'id' => '6ad54ec6-ee2d-4500-afe6-0917c1aa7a38'
//     ]
// ]

// Migrate an invoice ID
$result = $teamleader->migrate()->id('invoice', 456);

// Migrate a deal ID
$result = $teamleader->migrate()->id('deal', 789);
```

**Important Note:** The response type may differ from the request type:
- `task` becomes `todo` in the response
- `meeting` and `call` become `event` in the response

### `batchIds()`

Convenience method to migrate multiple IDs of the same type at once. This method calls the API multiple times and returns a mapping of old IDs to new UUIDs.

**Parameters:**
- `type` (string, required): Resource type
- `ids` (array, required): Array of old numeric IDs

**Returns:** Associative array mapping old IDs to new UUIDs

**Example:**
```php
$oldContactIds = [1, 2, 3, 4, 5];

$mapping = $teamleader->migrate()->batchIds('contact', $oldContactIds);

// Returns:
// [
//     1 => 'uuid-1',
//     2 => 'uuid-2',
//     3 => 'uuid-3',
//     4 => 'uuid-4',
//     5 => 'uuid-5'
// ]

// Use the mapping to update your database
foreach ($mapping as $oldId => $newUuid) {
    // Update your local database
    DB::table('contacts')
        ->where('old_id', $oldId)
        ->update(['teamleader_uuid' => $newUuid]);
}
```

## Helper Methods

### `getActivityTypes()`

Get all valid activity types.

**Example:**
```php
$types = $teamleader->migrate()->getActivityTypes();
// Returns: ['meeting', 'call', 'task']
```

### `getResourceTypes()`

Get all valid resource types for ID migration.

**Example:**
```php
$types = $teamleader->migrate()->getResourceTypes();
// Returns: ['account', 'user', 'department', 'product', ...]
```

### `isValidActivityType()`

Check if an activity type is valid.

**Example:**
```php
if ($teamleader->migrate()->isValidActivityType('meeting')) {
    // Valid activity type
}
```

### `isValidResourceType()`

Check if a resource type is valid for migration.

**Example:**
```php
if ($teamleader->migrate()->isValidResourceType('contact')) {
    // Valid resource type
}
```

## Supported Resource Types

The `id()` and `batchIds()` methods support the following resource types:

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
- `task` (returns as `todo` in response)
- `meeting` (returns as `event` in response)
- `call` (returns as `event` in response)
- `ticket`
- `invoice`
- `creditNote`
- `subscription`
- `quotation`
- `timeTracking`
- `customField`

## Common Migration Patterns

### Migrate All Contacts

```php
$migrate = $teamleader->migrate();

// Get all old contact IDs from your database
$oldIds = DB::table('contacts')->pluck('old_teamleader_id')->toArray();

// Batch migrate
$mapping = $migrate->batchIds('contact', $oldIds);

// Update your database
foreach ($mapping as $oldId => $newUuid) {
    DB::table('contacts')
        ->where('old_teamleader_id', $oldId)
        ->update(['teamleader_uuid' => $newUuid]);
}
```

### Migrate Multiple Resource Types

```php
$migrate = $teamleader->migrate();

$resourceTypes = ['contact', 'company', 'invoice', 'deal'];

foreach ($resourceTypes as $type) {
    // Get old IDs from your database
    $oldIds = DB::table($type . 's')->pluck('old_id')->toArray();
    
    // Migrate
    $mapping = $migrate->batchIds($type, $oldIds);
    
    // Update database
    foreach ($mapping as $oldId => $newUuid) {
        DB::table($type . 's')
            ->where('old_id', $oldId)
            ->update(['uuid' => $newUuid]);
    }
}
```

### Migrate Tax Rates for All Departments

```php
$migrate = $teamleader->migrate();

// Get all departments
$departments = $teamleader->departments()->list();

// Common tax rates in Belgium
$taxRates = ['0', '6', '12', '21'];

$taxRateMapping = [];

foreach ($departments['data'] as $department) {
    $deptId = $department['id'];
    $taxRateMapping[$deptId] = [];
    
    foreach ($taxRates as $rate) {
        try {
            $result = $migrate->taxRate($deptId, $rate);
            $taxRateMapping[$deptId][$rate] = $result['data']['id'];
        } catch (\Exception $e) {
            // Some tax rates may not exist for all departments
            continue;
        }
    }
}
```

### Migrate Activity Types

```php
$migrate = $teamleader->migrate();

$activityTypes = ['meeting', 'call', 'task'];
$activityTypeUuids = [];

foreach ($activityTypes as $type) {
    $result = $migrate->activityType($type);
    $activityTypeUuids[$type] = $result['data']['id'];
}

// Store in config or database
config(['teamleader.activity_types' => $activityTypeUuids]);
```

## Response Structures

### activityType()
```php
[
    'data' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'meeting'  // or 'call', 'task'
    ]
]
```

### taxRate()
```php
[
    'data' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'taxRate'
    ]
]
```

### id()
```php
[
    'data' => [
        'type' => 'contact',  // May differ from request (task->todo, meeting->event)
        'id' => '6ad54ec6-ee2d-4500-afe6-0917c1aa7a38'
    ]
]
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $result = $teamleader->migrate()->id('contact', 123);
} catch (ValidationException $e) {
    // Handle validation errors (invalid type, invalid ID format)
    echo "Validation error: " . $e->getMessage();
} catch (NotFoundException $e) {
    // Handle case where old ID doesn't exist
    echo "Old ID not found: " . $e->getMessage();
} catch (TeamleaderException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage();
}
```

## Best Practices

### 1. Batch Processing with Rate Limiting

```php
$migrate = $teamleader->migrate();
$oldIds = [1, 2, 3, /* ... thousands more ... */];

// Process in chunks to avoid rate limits
$chunks = array_chunk($oldIds, 100);

foreach ($chunks as $chunk) {
    $mapping = $migrate->batchIds('contact', $chunk);
    
    // Update your database
    foreach ($mapping as $oldId => $newUuid) {
        // ... update logic
    }
    
    // Optional: Add delay between chunks to respect rate limits
    sleep(1);
}
```

### 2. Handle Missing IDs Gracefully

```php
$migrate = $teamleader->migrate();

foreach ($oldIds as $oldId) {
    try {
        $result = $migrate->id('contact', $oldId);
        $newUuid = $result['data']['id'];
        
        // Update database
        DB::table('contacts')
            ->where('old_id', $oldId)
            ->update(['uuid' => $newUuid]);
            
    } catch (NotFoundException $e) {
        // Log IDs that couldn't be migrated
        Log::warning("Contact ID {$oldId} not found in Teamleader");
        
        // Mark as needs manual review
        DB::table('contacts')
            ->where('old_id', $oldId)
            ->update(['migration_status' => 'not_found']);
    }
}
```

### 3. Store Mappings for Future Reference

```php
$migrate = $teamleader->migrate();

// Migrate and store mapping in a separate table
$mapping = $migrate->batchIds('contact', $oldIds);

foreach ($mapping as $oldId => $newUuid) {
    DB::table('id_mappings')->insert([
        'resource_type' => 'contact',
        'old_id' => $oldId,
        'new_uuid' => $newUuid,
        'migrated_at' => now()
    ]);
}
```

### 4. Validate Before Migration

```php
$migrate = $teamleader->migrate();

// Validate resource type
if (!$migrate->isValidResourceType($type)) {
    throw new \InvalidArgumentException("Invalid resource type: {$type}");
}

// Validate activity type
if (!$migrate->isValidActivityType($activityType)) {
    throw new \InvalidArgumentException("Invalid activity type: {$activityType}");
}

// Then proceed with migration
$result = $migrate->id($type, $oldId);
```

## Important Notes

- These endpoints are specifically for migrating from the deprecated Teamleader API
- The response type may differ from the request type (task→todo, meeting/call→event)
- Old IDs must be positive integers
- Tax rates must be provided as strings (e.g., "21" not 21)
- Department ID is required for tax rate migration
- The `batchIds()` method is a convenience wrapper that makes multiple API calls
- Consider implementing proper error handling and logging for production migrations
- Store the old ID to new UUID mappings for future reference

## Migration Checklist

When migrating from the old API to the new API:

- [ ] Migrate all activity types (meeting, call, task)
- [ ] Migrate tax rates for each department
- [ ] Migrate contacts
- [ ] Migrate companies
- [ ] Migrate deals and deal phases
- [ ] Migrate invoices and credit notes
- [ ] Migrate projects and milestones
- [ ] Migrate tasks
- [ ] Migrate time tracking entries
- [ ] Migrate custom fields
- [ ] Update all references in your local database
- [ ] Test the migrated data thoroughly
- [ ] Keep the old ID to UUID mappings for troubleshooting

## See Also

- [Departments](../general/departments.md) - Required for tax rate migration
- [Contacts](../crm/contacts.md) - Working with contacts after migration
- [Companies](../crm/companies.md) - Working with companies after migration
- [Invoices](../invoicing/invoices.md) - Working with invoices after migration
