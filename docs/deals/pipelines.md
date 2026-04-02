# Deal Pipelines

Manage deal pipelines in Teamleader Focus.

## Overview

The Deal Pipelines resource manages the sales pipelines that contain deal phases. Each pipeline has a name and an
optional default flag. Deleting a pipeline requires specifying where to migrate each of its phases' deals.

Access via `Teamleader::dealPipelines()`.

## Endpoint

`dealPipelines`

## Capabilities

| Capability  | Supported                               |
|-------------|-----------------------------------------|
| Pagination  | ✅ Supported                             |
| Filtering   | ✅ Supported (`ids`, `status`)           |
| Sorting     | ❌ Not supported                         |
| Sideloading | ❌ Not supported                         |
| Creation    | ✅ Supported                             |
| Update      | ✅ Supported                             |
| Deletion    | ✅ Supported (requires phase migrations) |

> **Note:** The source contains `Log::debug()` calls inside `list()` that log request and response details. This is
> existing behaviour, not something you need to account for.

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$pipelines = Teamleader::dealPipelines()->list();

$pipelines = Teamleader::dealPipelines()->list(['status' => 'open']);

$pipelines = Teamleader::dealPipelines()->list([], ['page_size' => 20, 'page_number' => 1]);
```

> **Status filter:** `status` is coerced to an array internally. Pass `'open'` or `['open']` — both work.

---

### `info(string $id)`

```php
$pipeline = Teamleader::dealPipelines()->info('pipeline-uuid');
```

---

### `create(array $data)`

**Required:** `name`. Throws `InvalidArgumentException` if absent.

```php
$pipeline = Teamleader::dealPipelines()->create(['name' => 'Enterprise Pipeline']);
```

---

### `update(mixed $id, array $data)`

**Required:** `name`. The `id` is injected into the request body. Throws `InvalidArgumentException` if `name` is empty.

```php
Teamleader::dealPipelines()->update('pipeline-uuid', ['name' => 'Enterprise Sales Pipeline']);
```

---

### `delete(string $id, array $migratePhases = [])`

Deletes a pipeline. `$migratePhases` is an array of `{old_phase_id, new_phase_id}` objects specifying where each phase's
deals should be moved. Pass an empty array to delete without migrations (only safe if all phases are already empty).

```php
Teamleader::dealPipelines()->delete('pipeline-uuid', [
    ['old_phase_id' => 'source-phase-uuid-1', 'new_phase_id' => 'target-phase-uuid-1'],
    ['old_phase_id' => 'source-phase-uuid-2', 'new_phase_id' => 'target-phase-uuid-2'],
]);

// No migration (only if phases are empty)
Teamleader::dealPipelines()->delete('pipeline-uuid', []);
```

---

### `duplicate(string $id)`

Copies a pipeline along with all its phases.

```php
$newPipeline = Teamleader::dealPipelines()->duplicate('source-pipeline-uuid');
```

---

### `markAsDefault(string $id)`

Sets a pipeline as the default for new deals.

```php
Teamleader::dealPipelines()->markAsDefault('pipeline-uuid');
```

---

## Helper Methods

| Method              | Filter applied             |
|---------------------|----------------------------|
| `open()`            | `status: open`             |
| `pendingDeletion()` | `status: pending_deletion` |
| `byIds(array $ids)` | `ids`                      |

```php
$open    = Teamleader::dealPipelines()->open();
$pending = Teamleader::dealPipelines()->pendingDeletion();
```

---

## Filters

| Filter   | Type            | Description                                            |
|----------|-----------------|--------------------------------------------------------|
| `ids`    | array           | Filter by pipeline UUIDs                               |
| `status` | string or array | `open` or `pending_deletion` — string coerced to array |

**Valid status values:** `open`, `pending_deletion`

---

## Response Structure

```php
[
    'data' => [
        [
            'id'      => 'pipeline-uuid',
            'name'    => 'Enterprise Sales Pipeline',
            'default' => true,
        ],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 3],
]
```

---

## Usage Examples

### Create a pipeline and set as default

```php
$pipeline = Teamleader::dealPipelines()->create(['name' => 'SMB Pipeline']);
Teamleader::dealPipelines()->markAsDefault($pipeline['data']['id']);
```

### Safely delete a pipeline with phase migration

```php
$sourcePhases = Teamleader::dealPhases()->forPipeline('old-pipeline-uuid');
$targetPhases = Teamleader::dealPhases()->forPipeline('new-pipeline-uuid');

// Map by position (assumes same number of phases)
$migrations = [];
foreach ($sourcePhases['data'] as $i => $sourcePhase) {
    if (isset($targetPhases['data'][$i])) {
        $migrations[] = [
            'old_phase_id' => $sourcePhase['id'],
            'new_phase_id' => $targetPhases['data'][$i]['id'],
        ];
    }
}

Teamleader::dealPipelines()->delete('old-pipeline-uuid', $migrations);
```

### Find the default pipeline

```php
$pipelines = Teamleader::dealPipelines()->list();

$default = null;
foreach ($pipelines['data'] as $pipeline) {
    if ($pipeline['default'] === true) {
        $default = $pipeline;
        break;
    }
}
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing name on create
try {
    Teamleader::dealPipelines()->create([]);
} catch (InvalidArgumentException $e) {
    // 'Pipeline name is required'
}

// Non-array migrations on delete
try {
    Teamleader::dealPipelines()->delete('pipeline-uuid', 'wrong-type');
} catch (InvalidArgumentException $e) {
    // 'Pipeline deletion expects an array of phase migrations as the second parameter'
}
```

---

## Related Resources

- [[Deal-Phases]] — Phases belong to pipelines
- [[Deals]] — Deals are assigned to a pipeline via their phase
- [[Filtering]] — Filter and pagination reference
