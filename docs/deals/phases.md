# Deal Phases

Manage phases within deal pipelines in Teamleader Focus.

## Overview

The Deal Phases resource manages the stages deals move through in a pipeline — Qualification, Proposal, Negotiation, and
so on. Each phase has an attention timer and optional follow-up actions that trigger automatically when a deal enters
it.

Access via `Teamleader::dealPhases()`.

## Endpoint

`dealPhases`

## Capabilities

| Capability  | Supported                               |
|-------------|-----------------------------------------|
| Pagination  | ✅ Supported                             |
| Filtering   | ✅ Supported (`ids`, `deal_pipeline_id`) |
| Sorting     | ❌ Not supported                         |
| Sideloading | ❌ Not supported                         |
| Creation    | ✅ Supported                             |
| Update      | ✅ Supported                             |
| Deletion    | ✅ Supported (requires migration target) |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$phases = Teamleader::dealPhases()->list();

$phases = Teamleader::dealPhases()->list(['deal_pipeline_id' => 'pipeline-uuid']);

$phases = Teamleader::dealPhases()->list([], ['page_size' => 50, 'page_number' => 1]);
```

---

### `info(string $id)`

```php
$phase = Teamleader::dealPhases()->info('phase-uuid');
```

---

### `create(array $data)`

**Required (validated before the request):**

| Field                           | Notes                                  |
|---------------------------------|----------------------------------------|
| `name`                          | Phase display name                     |
| `deal_pipeline_id`              | Parent pipeline UUID                   |
| `requires_attention_after`      | Must include `amount` (int) and `unit` |
| `requires_attention_after.unit` | Must be `days` or `weeks`              |

**Optional:**

| Field               | Notes                                            |
|---------------------|--------------------------------------------------|
| `follow_up_actions` | Array of action strings — see valid values below |

```php
$phase = Teamleader::dealPhases()->create([
    'name'                     => 'Proposal Sent',
    'deal_pipeline_id'         => 'pipeline-uuid',
    'requires_attention_after' => [
        'amount' => 7,
        'unit'   => 'days',
    ],
    'follow_up_actions' => ['create_task', 'create_call'],
]);
```

**Valid `follow_up_actions` values:** `create_event`, `create_call`, `create_task`

Any other value throws `InvalidArgumentException`.

---

### `update(mixed $id, array $data)`

The `id` is injected into the request body. Validates `requires_attention_after` and `follow_up_actions` if provided.

```php
Teamleader::dealPhases()->update('phase-uuid', [
    'name'                     => 'Advanced Qualification',
    'requires_attention_after' => ['amount' => 5, 'unit' => 'days'],
]);
```

---

### `delete(string $id, string $newPhaseId)`

**Requires a `$newPhaseId`.** Existing deals in the deleted phase are migrated to this target.
Throws `InvalidArgumentException` if `$newPhaseId` is empty or missing.

```php
Teamleader::dealPhases()->delete('phase-to-delete-uuid', 'target-phase-uuid');
```

---

### `duplicate(string $id)`

Creates a copy of the phase in the same pipeline.

```php
$newPhase = Teamleader::dealPhases()->duplicate('source-phase-uuid');
```

---

### `move(string $id, string $afterPhaseId)`

Repositions a phase immediately after `$afterPhaseId` in the pipeline order.

```php
// Place phase-C after phase-A (making it second)
Teamleader::dealPhases()->move('phase-c-uuid', 'phase-a-uuid');
```

---

## Helper Methods

### `forPipeline(string $pipelineId)`

```php
$phases = Teamleader::dealPhases()->forPipeline('pipeline-uuid');
```

### `byIds(array $ids)`

```php
$phases = Teamleader::dealPhases()->byIds(['phase-uuid-1', 'phase-uuid-2']);
```

### `getAvailableFollowUpActions()`

Returns the valid follow-up action values — **no API call**.

```php
$actions = Teamleader::dealPhases()->getAvailableFollowUpActions();
// ['create_event', 'create_call', 'create_task']
```

### `getAvailableAttentionAfterUnits()`

Returns the valid unit values — **no API call**.

```php
$units = Teamleader::dealPhases()->getAvailableAttentionAfterUnits();
// ['days', 'weeks']
```

---

## Filters

| Filter             | Type   | Description                    |
|--------------------|--------|--------------------------------|
| `ids`              | array  | Filter by phase UUIDs          |
| `deal_pipeline_id` | string | Filter by parent pipeline UUID |

---

## Response Structure

```php
[
    'data' => [
        [
            'id'                       => 'phase-uuid',
            'name'                     => 'Proposal Sent',
            'deal_pipeline_id'         => 'pipeline-uuid',
            'requires_attention_after' => ['amount' => 7, 'unit' => 'days'],
            'follow_up_actions'        => ['create_task'],
            'order'                    => 2,
        ],
    ],
]
```

---

## Usage Examples

### Build a complete pipeline

```php
$pipeline = Teamleader::dealPipelines()->create(['name' => 'SMB Pipeline']);
$pid = $pipeline['data']['id'];

$phases = [
    ['name' => 'Lead',          'amount' => 3,  'unit' => 'days'],
    ['name' => 'Qualification', 'amount' => 7,  'unit' => 'days'],
    ['name' => 'Proposal',      'amount' => 2,  'unit' => 'weeks'],
    ['name' => 'Closing',       'amount' => 1,  'unit' => 'weeks'],
];

foreach ($phases as $p) {
    Teamleader::dealPhases()->create([
        'name'                     => $p['name'],
        'deal_pipeline_id'         => $pid,
        'requires_attention_after' => ['amount' => $p['amount'], 'unit' => $p['unit']],
    ]);
}
```

### Delete a phase safely

```php
$phases = Teamleader::dealPhases()->forPipeline('pipeline-uuid');

// Find target phase by name
$target = null;
foreach ($phases['data'] as $phase) {
    if ($phase['name'] === 'Closing') {
        $target = $phase['id'];
        break;
    }
}

if ($target) {
    Teamleader::dealPhases()->delete('old-phase-uuid', $target);
}
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// delete() without migration target
try {
    Teamleader::dealPhases()->delete('phase-uuid');
} catch (InvalidArgumentException $e) {
    // 'Deal phase deletion requires a target phase for deal migration.'
}

// Invalid attention unit
try {
    Teamleader::dealPhases()->create([
        'name'                     => 'Test',
        'deal_pipeline_id'         => 'uuid',
        'requires_attention_after' => ['amount' => 5, 'unit' => 'months'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'requires_attention_after unit must be "days" or "weeks"'
}

// Invalid follow-up action
try {
    Teamleader::dealPhases()->create([
        ...,
        'follow_up_actions' => ['send_email'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid follow_up_action: send_email'
}
```

---

## Related Resources

- [[Deal-Pipelines]] — Phases belong to pipelines
- [[Deals]] — Deals move through phases via `move()`
- [[Filtering]] — Filter and pagination reference
