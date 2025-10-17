# Materials

Manage materials in Teamleader Focus projects.

## Overview

The Materials resource manages physical or digital materials used in projects. Materials can be products, consumables, or any resources with billing information.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Best Practices](#best-practices)
- [Related Resources](#related-resources)

## Endpoint

`projects-v2/materials`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported (by IDs only)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`, `info()`, `create()`, `update()`, `delete()`

Similar patterns to Project Tasks. Materials support:

**Billing Methods:**
- `fixed_price` - Fixed price billing
- `unit_price` - Price per unit
- `non_billable` - Not billable

**Statuses:**
- `to_do`
- `in_progress`
- `on_hold`
- `done`

### `assign()` / `unassign()`

```php
Teamleader::materials()->assign('material-uuid', 'user', 'user-uuid');
Teamleader::materials()->unassign('material-uuid', 'user', 'user-uuid');
```

### `duplicate()`

```php
$newMaterial = Teamleader::materials()->duplicate('material-uuid');
```

## Response Structure

```json
{
  "id": "material-uuid",
  "project": {
    "type": "nextgenProject",
    "id": "project-uuid"
  },
  "title": "Web Hosting Service",
  "description": "Annual hosting package",
  "status": "done",
  "billing_method": "fixed_price",
  "fixed_price": {
    "amount": 500.00,
    "currency": "EUR"
  },
  "quantity": 1,
  "assignees": []
}
```

## Usage Examples

### Create Material with Fixed Price

```php
$material = Teamleader::materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'SSL Certificate',
    'description' => 'Annual SSL certificate',
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 150.00,
        'currency' => 'EUR'
    ],
    'quantity' => 1,
    'status' => 'to_do'
]);
```

### Create Material with Unit Price

```php
$material = Teamleader::materials()->create([
    'project_id' => 'project-uuid',
    'title' => 'Server Storage',
    'billing_method' => 'unit_price',
    'unit_price' => [
        'amount' => 10.00,
        'currency' => 'EUR'
    ],
    'quantity' => 100,  // 100 GB @ €10/GB
    'unit' => 'GB'
]);
```

## Best Practices

1. **Always specify billing method**: Required for creation
2. **Use descriptive titles**: Identify materials clearly
3. **Track quantities**: Important for inventory
4. **Update status**: Mark as done when delivered

## Related Resources

- **[Projects](projects.md)** - Parent projects
- **[Project Tasks](project-tasks.md)** - Related tasks
- **[Groups](groups.md)** - Organize materials
- **[Project Lines](project-lines.md)** - View all lines
