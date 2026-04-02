# Projects

Manage projects in Teamleader Focus (Projects API v2).

## Overview

The Projects resource is the central hub of the v2 project management system. Projects contain groups, tasks, and
materials, and can be linked to deals, quotations, customers, and owners.

Access via `Teamleader::projects()`.

> **Requires Projects v2.** If your account uses Legacy Projects, use `Teamleader::legacyProjects()` instead. Check
> with `Teamleader::accounts()->isUsingProjectsV2()`.
>
> **`delete()` requires a strategy** — defaults to `unlink_tasks_and_time_trackings`.
>
> **`close()` requires a strategy** — defaults to `none`.
>
> **Customer filter key is `customers` (plural array)**, not `customer` (singular object).
>
> **Sideload via `options['includes']`** (plural) in `list()`.

## Endpoint

`projects-v2/projects`

## Capabilities

| Capability  | Supported                                       |
|-------------|-------------------------------------------------|
| Pagination  | ✅ Supported                                     |
| Filtering   | ✅ Supported                                     |
| Sorting     | ✅ Supported (many fields)                       |
| Sideloading | ✅ Supported (`legacy_project`, `custom_fields`) |
| Creation    | ✅ Supported                                     |
| Update      | ✅ Supported                                     |
| Deletion    | ✅ Supported                                     |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$projects = Teamleader::projects()->list();

$projects = Teamleader::projects()->list(
    ['status' => 'open'],
    ['sort' => [['field' => 'title', 'order' => 'asc']], 'page_size' => 50]
);

// With sideloading
$projects = Teamleader::projects()->list([], ['includes' => 'legacy_project,custom_fields']);
```

---

### `info(string $id, mixed $includes = null)`

```php
$project = Teamleader::projects()->info('project-uuid');
$project = Teamleader::projects()->info('project-uuid', ['legacy_project', 'custom_fields']);
$project = Teamleader::projects()->with('custom_fields')->info('project-uuid');
```

---

### `create(array $data)`

Only `title` is required. `billing_method` and `color` are validated when provided.

| Required | Notes         |
|----------|---------------|
| `title`  | Project title |

**Billing methods:** `time_and_materials`, `fixed_price`, `non_billable`

```php
$project = Teamleader::projects()->create([
    'title'          => 'Website Redesign',
    'billing_method' => 'time_and_materials',
    'color'          => '#00B2B2',
    'start_date'     => '2025-05-01',
    'end_date'       => '2025-08-31',
    'customers'      => [['type' => 'company', 'id' => 'company-uuid']],
]);
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body.

```php
Teamleader::projects()->update('project-uuid', ['title' => 'Website Redesign v2']);
```

---

### `delete(mixed $id, string $deleteStrategy = 'unlink_tasks_and_time_trackings')`

**Delete strategies (validated):**

| Strategy                             | Behaviour                                                    |
|--------------------------------------|--------------------------------------------------------------|
| `unlink_tasks_and_time_trackings`    | Default — keeps tasks and time entries, removes project link |
| `delete_tasks_and_time_trackings`    | Deletes tasks and all time entries                           |
| `delete_tasks_unlink_time_trackings` | Deletes tasks, keeps time entries unlinked                   |

```php
Teamleader::projects()->delete('project-uuid');
Teamleader::projects()->delete('project-uuid', 'delete_tasks_and_time_trackings');
```

---

### `duplicate(string $id, string $title)`

Creates a copy of the project with a new title.

```php
$copy = Teamleader::projects()->duplicate('project-uuid', 'Website Redesign – Phase 2');
```

---

### `close(string $id, string $closingStrategy = 'none')`

**Closing strategies (validated):**

| Strategy                           | Behaviour                                  |
|------------------------------------|--------------------------------------------|
| `none`                             | Default — just closes the project          |
| `mark_tasks_and_materials_as_done` | Also marks all tasks and materials as done |

```php
Teamleader::projects()->close('project-uuid');
Teamleader::projects()->close('project-uuid', 'mark_tasks_and_materials_as_done');
```

---

### `reopen(string $id)`

```php
Teamleader::projects()->reopen('project-uuid');
```

---

### `assign(string $id, string $assigneeType, string $assigneeId)` / `unassign()`

Assignee type validated: `user`, `team`.

```php
Teamleader::projects()->assign('project-uuid', 'user', 'user-uuid');
Teamleader::projects()->unassign('project-uuid', 'team', 'team-uuid');
```

---

### Deal, Quotation, and Owner management

```php
Teamleader::projects()->addDeal('project-uuid', 'deal-uuid');
Teamleader::projects()->removeDeal('project-uuid', 'deal-uuid');

Teamleader::projects()->addQuotation('project-uuid', 'quotation-uuid');
Teamleader::projects()->removeQuotation('project-uuid', 'quotation-uuid');

Teamleader::projects()->addOwner('project-uuid', 'user-uuid');
Teamleader::projects()->removeOwner('project-uuid', 'user-uuid');
```

---

## Helper Methods

### Status shortcuts

```php
Teamleader::projects()->open();
Teamleader::projects()->closed();
Teamleader::projects()->running();
Teamleader::projects()->overdue();
Teamleader::projects()->overBudget();
```

### Other helpers

```php
Teamleader::projects()->search('website');
Teamleader::projects()->byIds(['uuid-1', 'uuid-2']);
Teamleader::projects()->forCustomer('company', 'company-uuid'); // type first, then id
Teamleader::projects()->forDeal('deal-uuid');
Teamleader::projects()->forQuotation('quotation-uuid');
```

---

## Filters

| Filter          | Type   | Description                                                      |
|-----------------|--------|------------------------------------------------------------------|
| `ids`           | array  | Filter by project UUIDs                                          |
| `status`        | string | `open`, `planned`, `running`, `overdue`, `over_budget`, `closed` |
| `customers`     | array  | `[{type: contact\|company, id: uuid}]`                           |
| `deal_ids`      | array  | Filter by deal UUIDs                                             |
| `quotation_ids` | array  | Filter by quotation UUIDs                                        |
| `term`          | string | Search project number, title, customer/assignee/owner names      |

---

## Sorting

Supported sort
fields: `amount_billed`, `amount_paid`, `amount_unbilled`, `cost`, `customer`, `end_date`, `external_budget_spent`, `external_budget`, `internal_budget`, `margin`, `price`, `project_key`, `start_date`, `status`, `time_budget`, `time_estimated`, `time_tracked`, `title`

---

## Sideloading

| Include          | Description                                               |
|------------------|-----------------------------------------------------------|
| `legacy_project` | Linked legacy project reference (if migrated from legacy) |
| `custom_fields`  | Custom field values on the project                        |

---

## Error Handling

```php
use InvalidArgumentException;

// Missing title
try {
    Teamleader::projects()->create(['billing_method' => 'time_and_materials']);
} catch (InvalidArgumentException $e) {
    // 'Title is required for creating a project'
}

// Invalid delete strategy
try {
    Teamleader::projects()->delete('uuid', 'archive');
} catch (InvalidArgumentException $e) {
    // 'Invalid delete strategy. Must be one of: ...'
}
```

---

## Related Resources

- [[Groups]] — Organise tasks and materials within a project
- [[Project-Tasks]] — Tasks inside a project
- [[Materials]] — Materials inside a project
- [[Project-Lines]] — Unified view of all project line items
- [[External-Parties]] — External stakeholders on a project
- [[Accounts]] — Check Projects v2 availability
- [[Legacy-Projects]] — Legacy project management
