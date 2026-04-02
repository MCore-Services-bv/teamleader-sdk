# Legacy Projects

Manage legacy projects in Teamleader Focus.

## Overview

Legacy Projects is the original Teamleader project system. Unlike Projects v2, work is organised into milestones (
phases) rather than groups, and each project must have at least one milestone and one participant at creation.

Access via `Teamleader::legacyProjects()`.

> **Only available on accounts that have not yet migrated to Projects v2.** Check
> with `Teamleader::accounts()->isUsingLegacyProjects()`.
>
> **`forCustomer()` signature is `(id, type)` — id first, type second.** This is the opposite of the
> v2 `Projects::forCustomer()`.
>
> **No sideloading.**

## Endpoint

`projects`

## Capabilities

| Capability  | Supported                                     |
|-------------|-----------------------------------------------|
| Pagination  | ✅ Supported                                   |
| Filtering   | ✅ Supported                                   |
| Sorting     | ✅ Supported (`due_on`, `title`, `created_at`) |
| Sideloading | ❌ Not supported                               |
| Creation    | ✅ Supported                                   |
| Update      | ✅ Supported                                   |
| Deletion    | ✅ Supported                                   |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$projects = Teamleader::legacyProjects()->list();

$projects = Teamleader::legacyProjects()->list(
    ['status' => 'active'],
    ['sort' => [['field' => 'due_on', 'order' => 'asc']], 'page_size' => 50]
);
```

---

### `info(string $id)`

```php
$project = Teamleader::legacyProjects()->info('project-uuid');
```

---

### `create(array $data)`

Four fields are required and validated:

| Required       | Notes                                     |
|----------------|-------------------------------------------|
| `title`        | Project title                             |
| `starts_on`    | Start date (YYYY-MM-DD)                   |
| `milestones`   | Array — at least one milestone required   |
| `participants` | Array — at least one participant required |

```php
$project = Teamleader::legacyProjects()->create([
    'title'        => 'Office Renovation',
    'starts_on'    => '2025-05-01',
    'milestones'   => [
        [
            'name'                  => 'Phase 1: Planning',
            'due_on'                => '2025-05-31',
            'responsible_user_id'   => 'user-uuid',
            'billing_method'        => 'time_and_materials',
        ],
    ],
    'participants' => [
        ['type' => 'user', 'id' => 'user-uuid'],
    ],
    'customer'     => ['type' => 'company', 'id' => 'company-uuid'],
    'description'  => 'Full office renovation project',
]);
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body.

```php
Teamleader::legacyProjects()->update('project-uuid', ['title' => 'Office Renovation — Updated']);
```

---

### `delete(mixed $id)`

```php
Teamleader::legacyProjects()->delete('project-uuid');
```

---

### `close(string $id)`

Closes the project **and all its phases and tasks**.

```php
Teamleader::legacyProjects()->close('project-uuid');
```

---

### `reopen(string $id)`

```php
Teamleader::legacyProjects()->reopen('project-uuid');
```

---

## Helper Methods

### `forCustomer(string $customerId, string $customerType = 'company')`

> ⚠️ **Id first, type second** — opposite of `Projects::forCustomer()`.

```php
$projects = Teamleader::legacyProjects()->forCustomer('company-uuid');              // type defaults to 'company'
$projects = Teamleader::legacyProjects()->forCustomer('contact-uuid', 'contact');
```

### Other helpers

```php
Teamleader::legacyProjects()->byStatus('active');       // active | on_hold | done | cancelled
Teamleader::legacyProjects()->forParticipant('user-uuid');
Teamleader::legacyProjects()->search('renovation');
Teamleader::legacyProjects()->updatedSince('2025-01-01T00:00:00+02:00');
```

---

## Filters

| Filter           | Type   | Description                              |
|------------------|--------|------------------------------------------|
| `customer`       | object | `{type: contact\|company, id: uuid}`     |
| `status`         | string | `active`, `on_hold`, `done`, `cancelled` |
| `participant_id` | string | Filter by participant UUID               |
| `term`           | string | Search title or description              |
| `updated_since`  | string | ISO 8601 datetime                        |

---

## Sorting

Valid sort fields: `due_on`, `title`, `created_at`

---

## Error Handling

```php
use InvalidArgumentException;

// Missing required field
try {
    Teamleader::legacyProjects()->create(['title' => 'Test']); // missing starts_on, milestones, participants
} catch (InvalidArgumentException $e) {
    // "Field 'starts_on' is required for creating a project"
}

// Empty milestones array
try {
    Teamleader::legacyProjects()->create([
        'title' => 'Test', 'starts_on' => '2025-01-01',
        'milestones' => [], 'participants' => [['type' => 'user', 'id' => 'uuid']],
    ]);
} catch (InvalidArgumentException $e) {
    // 'At least one milestone is required'
}
```

---

## Related Resources

- [[Legacy-Milestones]] — Phases within a legacy project
- [[Projects]] — Projects v2 (use for accounts already migrated)
- [[Accounts]] — Check which version the account is on
