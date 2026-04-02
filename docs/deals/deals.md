# Deals

Manage sales deals in Teamleader Focus.

## Overview

The Deals resource provides full CRUD operations for deal records, plus lifecycle methods (`win`, `lose`, `move`) and
tag management. Deals represent sales opportunities moving through your pipeline from open to won or lost.

## Endpoint

`deals`

## Capabilities

| Capability  | Supported                                    |
|-------------|----------------------------------------------|
| Pagination  | ✅ Supported                                  |
| Filtering   | ✅ Supported                                  |
| Sorting     | ✅ Supported (`created_at`, `weighted_value`) |
| Sideloading | ✅ Supported                                  |
| Creation    | ✅ Supported                                  |
| Update      | ✅ Supported                                  |
| Deletion    | ✅ Supported                                  |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All deals
$deals = Teamleader::deals()->list();

// Open deals only — status must be an array
$deals = Teamleader::deals()->list(['status' => ['open']]);

// With pagination and sorting
$deals = Teamleader::deals()->list([], [
    'page_size'   => 50,
    'page_number' => 1,
    'sort'        => 'created_at',
    'sort_order'  => 'desc',
]);

// With sideloading
$deals = Teamleader::deals()->list([], ['include' => 'lead.customer,responsible_user']);
```

> **Status filter:** Always pass status as an array — `['open']`, `['won']`, `['lost']`. A plain string is coerced to an
> array internally, but the array form is canonical.

---

### `info(string $id, mixed $includes = null)`

```php
$deal = Teamleader::deals()->info('deal-uuid');

$deal = Teamleader::deals()->info('deal-uuid', 'lead.customer,responsible_user');

$deal = Teamleader::deals()
    ->withCustomer()
    ->withCurrentPhase()
    ->info('deal-uuid');
```

---

### `create(array $data)`

**Required fields (validated before the request):**

- `lead.customer.type` — `contact` or `company` (throws `InvalidArgumentException` for other values)
- `lead.customer.id` — customer UUID
- `title` — deal title

**Other validated fields:**

- `estimated_probability` — must be between `0` and `1` inclusive
- `estimated_value.currency` — must be a supported currency code

```php
$deal = Teamleader::deals()->create([
    'title'  => 'New Business Deal',
    'lead'   => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid'],
    ],
    'phase_id'                  => 'phase-uuid',
    'estimated_value'           => ['amount' => 10000, 'currency' => 'EUR'],
    'estimated_probability'     => 0.75,
    'estimated_closing_date'    => '2025-12-31',
    'responsible_user_id'       => 'user-uuid',
    'source_id'                 => 'source-uuid',
]);
```

---

### `update(mixed $id, array $data)`

The `id` is injected into the request body before posting to `deals.update`.

```php
Teamleader::deals()->update('deal-uuid', [
    'title'                  => 'Updated Deal Title',
    'estimated_probability'  => 0.90,
    'estimated_value'        => ['amount' => 15000, 'currency' => 'EUR'],
]);
```

---

### `delete(string $id)`

```php
Teamleader::deals()->delete('deal-uuid');
```

---

### `win(string $id)`

Marks a deal as won.

```php
Teamleader::deals()->win('deal-uuid');
```

---

### `lose(string $id, ?string $reasonId = null, ?string $extraInfo = null)`

Marks a deal as lost. Both `$reasonId` and `$extraInfo` are optional.

```php
// Without reason
Teamleader::deals()->lose('deal-uuid');

// With reason UUID from lostReasons resource
Teamleader::deals()->lose('deal-uuid', 'lost-reason-uuid');

// With reason and additional notes
Teamleader::deals()->lose('deal-uuid', 'lost-reason-uuid', 'Price too high for budget');
```

---

### `move(string $id, string $phaseId)`

Moves a deal to a different phase. Throws `InvalidArgumentException` if `$phaseId` is empty.

```php
Teamleader::deals()->move('deal-uuid', 'target-phase-uuid');
```

---

## Tag Methods

### `tag(string $id, string|array $tags)`

```php
Teamleader::deals()->tag('deal-uuid', ['High Priority', 'Q2']);
Teamleader::deals()->tag('deal-uuid', 'Enterprise'); // string also accepted
```

### `untag(string $id, string|array $tags)`

```php
Teamleader::deals()->untag('deal-uuid', ['Q1']);
```

---

## Helper Methods

### Status shortcuts

All accept an optional `$additionalFilters` array.

```php
$deals = Teamleader::deals()->open();
$deals = Teamleader::deals()->won();
$deals = Teamleader::deals()->lost();

// With extra filters
$deals = Teamleader::deals()->open(['responsible_user_id' => 'user-uuid']);
```

### Filter helpers

| Method                                        | Filter applied                                                 |
|-----------------------------------------------|----------------------------------------------------------------|
| `search(string $term)`                        | `term` (title, reference, customer name)                       |
| `forCustomer(string $type, string $id)`       | `customer` — throws if type is not `contact` or `company`      |
| `byPhase(string $phaseId)`                    | `phase_id`                                                     |
| `byIds(array $ids)`                           | `ids`                                                          |
| `forUser(string $userId)`                     | `responsible_user_id`                                          |
| `updatedSince(string $date)`                  | `updated_since`                                                |
| `withTags(string\|array $tags)`               | `tags`                                                         |
| `closingBetween(string $from, string $until)` | `estimated_closing_date_from` + `estimated_closing_date_until` |

```php
$deals = Teamleader::deals()->forCustomer('company', 'company-uuid');
$deals = Teamleader::deals()->byPhase('phase-uuid');
$deals = Teamleader::deals()->closingBetween('2025-07-01', '2025-09-30');
$deals = Teamleader::deals()->forUser('user-uuid', ['status' => ['open']]);
```

### Fluent include methods

| Method                  | Include                                 |
|-------------------------|-----------------------------------------|
| `withCustomer()`        | `lead.customer`                         |
| `withResponsibleUser()` | `responsible_user`                      |
| `withDepartment()`      | `department`                            |
| `withCurrentPhase()`    | `current_phase`                         |
| `withSource()`          | `source`                                |
| `withCustomFields()`    | `custom_fields`                         |
| `withAll()`             | All of the above except `custom_fields` |

```php
$deals = Teamleader::deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->withCurrentPhase()
    ->list(['status' => ['open']]);
```

---

## Filters

| Filter                         | Type            | Description                              |
|--------------------------------|-----------------|------------------------------------------|
| `ids`                          | array           | Filter by UUIDs                          |
| `term`                         | string          | Searches title, reference, customer name |
| `status`                       | array           | `new`, `open`, `won`, `lost`             |
| `customer`                     | array           | `{type: contact\|company, id: uuid}`     |
| `phase_id`                     | string          | Phase UUID                               |
| `pipeline_ids`                 | array           | Pipeline UUIDs                           |
| `responsible_user_id`          | string or array | User UUID(s)                             |
| `tags`                         | array           | Tag names                                |
| `estimated_closing_date`       | string          | Exact closing date                       |
| `estimated_closing_date_from`  | string          | Closing date from (inclusive)            |
| `estimated_closing_date_until` | string          | Closing date until (inclusive)           |
| `updated_since`                | string          | ISO 8601 datetime                        |
| `created_before`               | string          | ISO 8601 datetime                        |

---

## Sorting

| Field            | Description                   |
|------------------|-------------------------------|
| `created_at`     | Deal creation date            |
| `weighted_value` | Probability × estimated value |

```php
$deals = Teamleader::deals()->list([], [
    'sort'       => 'weighted_value',
    'sort_order' => 'desc',
]);
```

---

## Sideloading

| Include            | Description                          |
|--------------------|--------------------------------------|
| `lead.customer`    | Customer record (company or contact) |
| `responsible_user` | Responsible user                     |
| `department`       | Assigned department                  |
| `current_phase`    | Current pipeline phase               |
| `source`           | Deal source                          |
| `custom_fields`    | Custom field values                  |

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'                      => 'deal-uuid',
            'title'                   => 'New Business Deal',
            'status'                  => 'open',
            'reference'               => 'D-2025-001',
            'lead'                    => [
                'customer'  => ['type' => 'company', 'id' => 'company-uuid'],
                'contact_person' => null,
            ],
            'estimated_value'         => ['amount' => 10000.0, 'currency' => 'EUR'],
            'estimated_probability'   => 0.75,
            'estimated_closing_date'  => '2025-12-31',
            'weighted_value'          => ['amount' => 7500.0, 'currency' => 'EUR'],
            'current_phase'           => ['type' => 'dealPhase', 'id' => 'phase-uuid'],
            'responsible_user'        => ['type' => 'user', 'id' => 'user-uuid'],
            'created_at'              => '2025-01-15T10:00:00+00:00',
            'updated_at'              => '2025-03-01T09:00:00+00:00',
        ],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 48],
]
```

---

## Usage Examples

### Track a deal through the pipeline

```php
$deal = Teamleader::deals()->create([
    'title' => 'Cloud Migration Project',
    'lead'  => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
    'phase_id'            => 'qualification-phase-uuid',
    'estimated_value'     => ['amount' => 25000, 'currency' => 'EUR'],
    'estimated_probability' => 0.3,
]);

// Move forward
Teamleader::deals()->move($deal['data']['id'], 'proposal-phase-uuid');
Teamleader::deals()->update($deal['data']['id'], ['estimated_probability' => 0.65]);
Teamleader::deals()->move($deal['data']['id'], 'closing-phase-uuid');
Teamleader::deals()->win($deal['data']['id']);
```

### Mark as lost with reason

```php
$reasons = Teamleader::lostReasons()->list();
$priceReason = collect($reasons['data'])->firstWhere('name', 'Price');

Teamleader::deals()->lose(
    'deal-uuid',
    $priceReason['id'],
    'Budget was 30% below our minimum'
);
```

### Get deals closing this quarter

```php
$deals = Teamleader::deals()
    ->withCustomer()
    ->closingBetween('2025-07-01', '2025-09-30', ['status' => ['open']]);
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid customer type on create
try {
    Teamleader::deals()->create([
        'title' => 'Test',
        'lead'  => ['customer' => ['type' => 'lead', 'id' => 'uuid']],
    ]);
} catch (InvalidArgumentException $e) {
    // "Invalid customer type: lead. Must be 'contact' or 'company'"
}

// move() with empty phase
try {
    Teamleader::deals()->move('deal-uuid', '');
} catch (InvalidArgumentException $e) {
    // 'Phase ID is required to move a deal'
}

// Invalid probability
try {
    Teamleader::deals()->create([...  'estimated_probability' => 1.5]);
} catch (InvalidArgumentException $e) {
    // 'Estimated probability must be a number between 0 and 1 (inclusive)'
}
```

---

## Related Resources

- [[Quotations]] — Quotations belong to deals
- [[Orders]] — Orders created from accepted quotations
- [[Deal-Phases]] — Phases deals move through
- [[Deal-Pipelines]] — Pipelines containing phases
- [[Deal-Sources]] — Source reference list
- [[Lost-Reasons]] — Lost reason reference list
- [[Companies]] — Companies as deal customers
- [[Contacts]] — Contacts as deal customers
- [[Sideloading]] — Loading related data
- [[Filtering]] — Filter and pagination reference
