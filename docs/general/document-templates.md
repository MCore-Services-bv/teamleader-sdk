# Document Templates

Read document template definitions in Teamleader Focus.

## Overview

The Document Templates resource provides read-only access to the document templates configured in your Teamleader
account. Templates are used when generating formatted documents such as invoices, quotations, and delivery notes.

Every call to `list()` requires **both** `department_id` and `document_type` — the API will not accept a request without
them, and the SDK throws an `InvalidArgumentException` before the request is sent if either is missing.

`info()`, `create()`, `update()`, and `delete()` are not supported and throw a `BadMethodCallException` if called.

## Endpoint

`documentTemplates`

## Capabilities

| Capability  | Supported                                      |
|-------------|------------------------------------------------|
| Pagination  | ❌ Not supported                                |
| Filtering   | ✅ Required (`department_id` + `document_type`) |
| Sorting     | ❌ Not supported                                |
| Sideloading | ❌ Not supported                                |
| Creation    | ❌ Not supported                                |
| Update      | ❌ Not supported                                |
| Deletion    | ❌ Not supported                                |

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns document templates matching the given filters. Both `department_id` and `document_type` are required —
an `InvalidArgumentException` is thrown if either is absent.

**Required filters:**

| Key             | Type   | Description                                           |
|-----------------|--------|-------------------------------------------------------|
| `department_id` | string | Department UUID                                       |
| `document_type` | string | Document type — see [Document Types](#document-types) |

**Optional filters:**

| Key      | Type  | Description                                               |
|----------|-------|-----------------------------------------------------------|
| `status` | array | `['active']`, `['archived']`, or `['active', 'archived']` |

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Invoice templates for a department
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'invoice',
]);

// Active quotation templates only
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'quotation',
    'status'        => ['active'],
]);
```

---

## Helper Methods

### `byType(string $departmentId, string $documentType, array $additionalFilters = [])`

Convenience wrapper around `list()`. Accepts an optional array of additional filters (e.g. `status`).

```php
// All templates of a type
$templates = Teamleader::documentTemplates()->byType(
    'department-uuid',
    'invoice'
);

// With status filter
$templates = Teamleader::documentTemplates()->byType(
    'department-uuid',
    'invoice',
    ['status' => ['active']]
);
```

### `activeForDepartment(string $departmentId, string $documentType)`

Shorthand for `byType($departmentId, $documentType, ['status' => ['active']])`.

```php
$templates = Teamleader::documentTemplates()->activeForDepartment(
    'department-uuid',
    'quotation'
);
```

### `archivedForDepartment(string $departmentId, string $documentType)`

Shorthand for `byType($departmentId, $documentType, ['status' => ['archived']])`.

```php
$templates = Teamleader::documentTemplates()->archivedForDepartment(
    'department-uuid',
    'invoice'
);
```

### `allForDepartment(string $departmentId, ?array $documentTypes = null)`

Fetches templates across all (or a specified subset of) document types for a department. Because `document_type` is
required by the API, this method makes **one API call per document type**. If no `$documentTypes` array is provided, it
iterates over all seven types returned by `getAvailableDocumentTypes()`.

Failures on individual type calls are silently skipped; the remaining types are still fetched.

```php
// All types — up to 7 API calls
$all = Teamleader::documentTemplates()->allForDepartment('department-uuid');

// Specific types only — 2 API calls
$selected = Teamleader::documentTemplates()->allForDepartment(
    'department-uuid',
    ['invoice', 'quotation']
);
```

**Response structure differs from `list()`:**

```php
[
    'data' => [/* merged array of all template objects */],
    'meta' => [
        'department_id'        => 'department-uuid',
        'document_types_checked' => ['delivery_note', 'invoice', ...],
        'total_templates'      => 12,
    ],
]
```

---

## Introspection Helpers

```php
// All valid document type strings
$types = Teamleader::documentTemplates()->getAvailableDocumentTypes();

// Valid status values
$statuses = Teamleader::documentTemplates()->getAvailableStatuses();

// Display name map for building UI selects
$names = Teamleader::documentTemplates()->getDocumentTypeDisplayNames();
// ['delivery_note' => 'Delivery Note', 'invoice' => 'Invoice', ...]
```

---

## Document Types

| Value                 | Display Name         |
|-----------------------|----------------------|
| `delivery_note`       | Delivery Note        |
| `invoice`             | Invoice              |
| `order`               | Order                |
| `order_confirmation`  | Order Confirmation   |
| `quotation`           | Quotation            |
| `timetracking_report` | Time Tracking Report |
| `workorder`           | Work Order           |

---

## Filters

### `department_id` *(required)*

The UUID of the department whose templates you want.

```php
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'invoice',
]);
```

### `document_type` *(required)*

The document type string — must be one of the values in [Document Types](#document-types).

### `status` *(optional)*

Must be passed as an **array**.

| Value      | Description        |
|------------|--------------------|
| `active`   | Active templates   |
| `archived` | Archived templates |

```php
// Active only
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'invoice',
    'status'        => ['active'],
]);

// Both
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'invoice',
    'status'        => ['active', 'archived'],
]);
```

---

## Response Structure

### `list()` / `byType()` response

```php
[
    'data' => [
        [
            'id'   => 'template-uuid',
            'name' => 'Standard Invoice',
        ],
    ],
]
```

---

## Usage Examples

### Get the default template for invoicing

```php
$templates = Teamleader::documentTemplates()->activeForDepartment(
    config('teamleader.departments.sales'),
    'invoice'
);

$defaultTemplate = $templates['data'][0] ?? null;
```

### Build a template select list for a UI

```php
$templates = Teamleader::documentTemplates()->activeForDepartment(
    'department-uuid',
    'quotation'
);

$options = array_column($templates['data'], 'name', 'id');
// ['uuid-1' => 'Standard Quotation', 'uuid-2' => 'Premium Quotation', ...]
```

### Cache templates per department and type

```php
$cacheKey  = 'tl_templates_' . $departmentId . '_' . $documentType;
$templates = Cache::remember($cacheKey, 3600, function () use ($departmentId, $documentType) {
    return Teamleader::documentTemplates()->activeForDepartment($departmentId, $documentType);
});
```

---

## Error Handling

```php
use BadMethodCallException;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing required filter — thrown before the request is sent
try {
    $templates = Teamleader::documentTemplates()->list([
        'department_id' => 'department-uuid',
        // document_type missing
    ]);
} catch (InvalidArgumentException $e) {
    // 'document_type is required for document templates'
    Log::error($e->getMessage());
}

// Unsupported methods all throw BadMethodCallException
try {
    Teamleader::documentTemplates()->info('template-uuid');
} catch (BadMethodCallException $e) {
    // Use list() with filters instead
}

// API-level errors
try {
    $templates = Teamleader::documentTemplates()->byType('department-uuid', 'invoice');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Departments]] — `department_id` required for every request
- [[Invoices]] — Templates are applied when generating invoices
- [[Quotations]] — Templates are applied when generating quotations
- [[Filtering]] — Filter reference
