# Departments

Read department information in Teamleader Focus.

## Overview

The Departments resource provides read-only access to the departments defined in your Teamleader account. Departments
are used to organise your team and can be assigned to deals, invoices, projects, and other resources.

Departments cannot be created, updated, or deleted through the API — they are managed in the Teamleader interface.

## Endpoint

`departments`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ❌ Not supported |
| Filtering   | ✅ Supported     |
| Sorting     | ✅ Supported     |
| Sideloading | ❌ Not supported |
| Creation    | ❌ Not supported |
| Update      | ❌ Not supported |
| Deletion    | ❌ Not supported |

> **Note on pagination:** The `list()` method does not apply page parameters. All matching departments are returned in a
> single response.

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns departments matching the given filters, with optional sorting.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All departments
$departments = Teamleader::departments()->list();

// Active departments only
$departments = Teamleader::departments()->list([
    'status' => ['active'],
]);

// Sorted by name ascending
$departments = Teamleader::departments()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']],
]);

// Active departments, sorted by name
$departments = Teamleader::departments()->list(
    ['status' => ['active']],
    ['sort' => [['field' => 'name', 'order' => 'asc']]]
);
```

---

### `info(string $id)`

Returns a single department by UUID.

```php
$department = Teamleader::departments()->info('department-uuid');

$name      = $department['data']['name'];
$status    = $department['data']['status'];
$isDefault = $department['data']['default'];
```

---

## Helper Methods

### `active()`

Shorthand for `list(['status' => ['active']])`.

```php
$departments = Teamleader::departments()->active();
```

### `archived()`

Shorthand for `list(['status' => ['archived']])`.

```php
$departments = Teamleader::departments()->archived();
```

### `byIds(array $ids)`

Shorthand for `list(['ids' => $ids])`.

```php
$departments = Teamleader::departments()->byIds([
    'uuid-1',
    'uuid-2',
]);
```

---

## Filters

### `ids`

Filter by an array of department UUIDs.

```php
$departments = Teamleader::departments()->list([
    'ids' => ['uuid-1', 'uuid-2'],
]);
```

### `status`

Filter by status. Must be passed as an **array**.

| Value      | Description          |
|------------|----------------------|
| `active`   | Active departments   |
| `archived` | Archived departments |

```php
// Active only
$departments = Teamleader::departments()->list([
    'status' => ['active'],
]);

// Both statuses
$departments = Teamleader::departments()->list([
    'status' => ['active', 'archived'],
]);
```

---

## Sorting

Pass a sort array in the options argument. Available sort fields:

| Field                | Description                            |
|----------------------|----------------------------------------|
| `name`               | Sort alphabetically by department name |
| `created_at`         | Sort by creation date                  |
| `default_department` | Default departments first (ascending)  |

```php
// Sort by name ascending
$departments = Teamleader::departments()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']],
]);

// Default departments first
$departments = Teamleader::departments()->list([], [
    'sort' => [['field' => 'default_department', 'order' => 'asc']],
]);
```

You can also use a simple string shorthand — the SDK normalises it to the array format:

```php
$departments = Teamleader::departments()->list([], [
    'sort' => 'name',
]);
```

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'            => 'department-uuid',
            'name'          => 'Sales',
            'status'        => 'active',   // 'active' or 'archived'
            'default'       => true,        // whether this is the default department
            'email_address' => 'sales@example.com',
        ],
    ],
]
```

### `info()` response

```php
[
    'data' => [
        'id'            => 'department-uuid',
        'name'          => 'Sales',
        'status'        => 'active',
        'default'       => true,
        'email_address' => 'sales@example.com',
    ],
]
```

---

## Usage Examples

### Get default department ID

```php
$departments = Teamleader::departments()->active();

$defaultId = null;
foreach ($departments['data'] as $department) {
    if ($department['default']) {
        $defaultId = $department['id'];
        break;
    }
}
```

### Build a department select list

```php
$departments = Teamleader::departments()->active();

$options = array_column($departments['data'], 'name', 'id');
// ['uuid-1' => 'Sales', 'uuid-2' => 'Finance', ...]
```

### Cache department data

Departments change infrequently. Cache them to avoid unnecessary API calls:

```php
$departments = Cache::remember('tl_departments', 3600, function () {
    return Teamleader::departments()->active();
});
```

### Validate a department UUID

```php
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;

function isValidDepartment(string $id): bool
{
    try {
        $dept = Teamleader::departments()->info($id);
        return $dept['data']['status'] === 'active';
    } catch (NotFoundException $e) {
        return false;
    }
}
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $department = Teamleader::departments()->info('department-uuid');
} catch (NotFoundException $e) {
    // Department does not exist
    Log::warning('Department not found', ['id' => 'department-uuid']);
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Users]] — Users belong to departments
- [[Deals]] — Deals can be assigned to a department
- [[Invoices]] — Invoices can be assigned to a department
- [[Filtering]] — Filter and sort reference
