# Deal Phases

Manage deal phases in Teamleader Focus.

## Overview

The Deal Phases resource allows you to manage the phases (stages) within deal pipelines. Each phase represents a step in your sales process, from initial contact through to closing. Phases help track where deals are in your sales cycle and can trigger follow-up actions.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [duplicate()](#duplicate)
    - [move()](#move)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`dealPhases`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported (requires migration)

## Available Methods

### `list()`

Get all deal phases with optional filtering and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all phases
$phases = Teamleader::dealPhases()->list();

// Get phases for specific pipeline
$phases = Teamleader::dealPhases()->list([
    'deal_pipeline_id' => 'pipeline-uuid'
]);

// With pagination
$phases = Teamleader::dealPhases()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);
```

### `info()`

Get detailed information about a specific phase.

**Parameters:**
- `id` (string): Phase UUID

**Example:**
```php
$phase = Teamleader::dealPhases()->info('phase-uuid');
```

### `create()`

Create a new deal phase.

**Parameters:**
- `data` (array): Phase data

**Example:**
```php
$phase = Teamleader::dealPhases()->create([
    'name' => 'Qualification',
    'deal_pipeline_id' => 'pipeline-uuid',
    'requires_attention_after' => [
        'amount' => 7,
        'unit' => 'days'
    ],
    'follow_up_actions' => ['create_task']
]);
```

### `update()`

Update an existing deal phase.

**Parameters:**
- `id` (string): Phase UUID
- `data` (array): Updated phase data

**Example:**
```php
$phase = Teamleader::dealPhases()->update('phase-uuid', [
    'name' => 'Advanced Qualification',
    'requires_attention_after' => [
        'amount' => 5,
        'unit' => 'days'
    ]
]);
```

### `delete()`

Delete a deal phase. Requires migrating existing deals to another phase.

**Parameters:**
- `id` (string): Phase UUID to delete
- `newPhaseId` (string): Target phase UUID for deal migration

**Example:**
```php
// Delete phase and move all deals to another phase
Teamleader::dealPhases()->delete('phase-to-delete-uuid', 'target-phase-uuid');
```

**Important:** You must specify a target phase to migrate existing deals. If you try to delete a phase without providing a migration target, an exception will be thrown.

### `duplicate()`

Duplicate an existing phase.

**Parameters:**
- `id` (string): Source phase UUID

**Example:**
```php
$newPhase = Teamleader::dealPhases()->duplicate('source-phase-uuid');
```

### `move()`

Move a phase to a new position in the pipeline.

**Parameters:**
- `id` (string): Phase UUID to move
- `afterPhaseId` (string): Phase UUID to place this phase after

**Example:**
```php
// Move phase to position after another phase
Teamleader::dealPhases()->move('phase-uuid', 'after-this-phase-uuid');
```

## Helper Methods

### `forPipeline()`

Get all phases for a specific pipeline.

```php
$phases = Teamleader::dealPhases()->forPipeline('pipeline-uuid');
```

### `byIds()`

Get specific phases by their UUIDs.

```php
$phases = Teamleader::dealPhases()->byIds([
    'phase-uuid-1',
    'phase-uuid-2'
]);
```

### Helper Information Methods

```php
// Get available follow-up actions
$actions = Teamleader::dealPhases()->getAvailableFollowUpActions();
// Returns: ['create_event', 'create_call', 'create_task']

// Get available attention after units
$units = Teamleader::dealPhases()->getAvailableAttentionAfterUnits();
// Returns: ['days', 'weeks']
```

## Filters

### Available Filters

#### `ids`
Filter by specific phase UUIDs.

```php
$phases = Teamleader::dealPhases()->list([
    'ids' => ['phase-uuid-1', 'phase-uuid-2']
]);
```

#### `deal_pipeline_id`
Filter by pipeline UUID.

```php
$phases = Teamleader::dealPhases()->list([
    'deal_pipeline_id' => 'pipeline-uuid'
]);
```

## Response Structure

### Phase Object

```php
[
    'id' => 'phase-uuid',
    'name' => 'Qualification',
    'deal_pipeline_id' => 'pipeline-uuid',
    'requires_attention_after' => [
        'amount' => 7,
        'unit' => 'days'
    ],
    'follow_up_actions' => ['create_task'],
    'order' => 1
]
```

### Follow-up Actions

Phases can have automated follow-up actions:

- `create_event` - Automatically create a calendar event
- `create_call` - Automatically create a call task
- `create_task` - Automatically create a task

### Attention Timer

The `requires_attention_after` setting triggers a notification when a deal has been in a phase too long:

```php
[
    'amount' => 7,      // Number of time units
    'unit' => 'days'    // 'days' or 'weeks'
]
```

## Usage Examples

### Create a Complete Phase

```php
$phase = Teamleader::dealPhases()->create([
    'name' => 'Proposal Sent',
    'deal_pipeline_id' => 'pipeline-uuid',
    'requires_attention_after' => [
        'amount' => 2,
        'unit' => 'weeks'
    ],
    'follow_up_actions' => [
        'create_task',
        'create_call'
    ]
]);
```

### Reorder Phases in Pipeline

```php
// Get all phases for a pipeline
$phases = Teamleader::dealPhases()->forPipeline('pipeline-uuid');

// Move the third phase to second position
Teamleader::dealPhases()->move(
    $phases['data'][2]['id'],  // Phase to move
    $phases['data'][0]['id']   // Place after first phase
);
```

### Duplicate Phase to Another Pipeline

```php
// Duplicate a phase
$newPhase = Teamleader::dealPhases()->duplicate('source-phase-uuid');

// Update it to belong to different pipeline
Teamleader::dealPhases()->update($newPhase['data']['id'], [
    'deal_pipeline_id' => 'different-pipeline-uuid'
]);
```

### Delete Phase Safely

```php
// Get phases for pipeline
$phases = Teamleader::dealPhases()->forPipeline('pipeline-uuid');

// Find target phase for migration
$targetPhase = null;
foreach ($phases['data'] as $phase) {
    if ($phase['name'] === 'Lost') {
        $targetPhase = $phase['id'];
        break;
    }
}

if ($targetPhase) {
    // Delete old phase, migrating deals to target
    Teamleader::dealPhases()->delete('old-phase-uuid', $targetPhase);
}
```

## Common Use Cases

### 1. Setup Standard Sales Pipeline

```php
function createStandardPipeline($pipelineId)
{
    $standardPhases = [
        ['name' => 'Lead', 'days' => 3],
        ['name' => 'Qualification', 'days' => 5],
        ['name' => 'Proposal', 'days' => 7],
        ['name' => 'Negotiation', 'days' => 10],
        ['name' => 'Closing', 'days' => 5]
    ];
    
    foreach ($standardPhases as $phaseData) {
        Teamleader::dealPhases()->create([
            'name' => $phaseData['name'],
            'deal_pipeline_id' => $pipelineId,
            'requires_attention_after' => [
                'amount' => $phaseData['days'],
                'unit' => 'days'
            ],
            'follow_up_actions' => ['create_task']
        ]);
    }
}
```

### 2. Analyze Phase Conversion Rates

```php
function analyzePhaseConversions($pipelineId)
{
    $phases = Teamleader::dealPhases()->forPipeline($pipelineId);
    $stats = [];
    
    foreach ($phases['data'] as $key => $phase) {
        $dealsInPhase = Teamleader::deals()->inPhase($phase['id']);
        
        $stats[] = [
            'phase' => $phase['name'],
            'deal_count' => count($dealsInPhase['data']),
            'order' => $phase['order'],
            'attention_days' => $phase['requires_attention_after']['amount']
        ];
    }
    
    return $stats;
}
```

### 3. Clone Pipeline Structure

```php
function clonePipelinePhases($sourcePipelineId, $targetPipelineId)
{
    $sourcePhases = Teamleader::dealPhases()->forPipeline($sourcePipelineId);
    $newPhases = [];
    
    foreach ($sourcePhases['data'] as $phase) {
        $newPhase = Teamleader::dealPhases()->create([
            'name' => $phase['name'],
            'deal_pipeline_id' => $targetPipelineId,
            'requires_attention_after' => $phase['requires_attention_after'],
            'follow_up_actions' => $phase['follow_up_actions'] ?? []
        ]);
        
        $newPhases[] = $newPhase['data'];
    }
    
    return $newPhases;
}
```

### 4. Find Stale Deals by Phase

```php
function findStaleDealsByPhase($pipelineId)
{
    $phases = Teamleader::dealPhases()->forPipeline($pipelineId);
    $staleReport = [];
    
    foreach ($phases['data'] as $phase) {
        $deals = Teamleader::deals()->inPhase($phase['id']);
        $attentionDays = $phase['requires_attention_after']['amount'];
        $unit = $phase['requires_attention_after']['unit'];
        
        $threshold = $unit === 'weeks' ? $attentionDays * 7 : $attentionDays;
        $cutoffDate = date('Y-m-d', strtotime("-{$threshold} days"));
        
        $staleDeals = array_filter($deals['data'], function($deal) use ($cutoffDate) {
            return $deal['updated_at'] < $cutoffDate;
        });
        
        if (count($staleDeals) > 0) {
            $staleReport[] = [
                'phase' => $phase['name'],
                'stale_deal_count' => count($staleDeals),
                'deals' => $staleDeals
            ];
        }
    }
    
    return $staleReport;
}
```

### 5. Bulk Update Phase Settings

```php
function updatePhaseAttentionTimers($pipelineId, $newDays)
{
    $phases = Teamleader::dealPhases()->forPipeline($pipelineId);
    
    foreach ($phases['data'] as $phase) {
        Teamleader::dealPhases()->update($phase['id'], [
            'requires_attention_after' => [
                'amount' => $newDays,
                'unit' => 'days'
            ]
        ]);
    }
}
```

## Best Practices

### 1. Always Use Meaningful Phase Names

```php
// Good: Clear and descriptive
Teamleader::dealPhases()->create([
    'name' => 'Technical Evaluation',
    'deal_pipeline_id' => 'pipeline-uuid',
    'requires_attention_after' => ['amount' => 5, 'unit' => 'days']
]);

// Bad: Vague naming
Teamleader::dealPhases()->create([
    'name' => 'Step 3',
    'deal_pipeline_id' => 'pipeline-uuid'
]);
```

### 2. Set Realistic Attention Timers

```php
// Good: Based on your actual sales cycle
$phases = [
    'Qualification' => 3,      // Quick decision needed
    'Proposal' => 7,           // Time to prepare
    'Negotiation' => 14,       // Back and forth
    'Closing' => 30            // Legal/procurement delays
];

foreach ($phases as $name => $days) {
    Teamleader::dealPhases()->create([
        'name' => $name,
        'deal_pipeline_id' => 'pipeline-uuid',
        'requires_attention_after' => [
            'amount' => $days,
            'unit' => 'days'
        ]
    ]);
}
```

### 3. Use Follow-up Actions Strategically

```php
// Good: Relevant actions for each phase
Teamleader::dealPhases()->create([
    'name' => 'Proposal Sent',
    'deal_pipeline_id' => 'pipeline-uuid',
    'requires_attention_after' => ['amount' => 3, 'unit' => 'days'],
    'follow_up_actions' => ['create_call']  // Follow up call needed
]);

Teamleader::dealPhases()->create([
    'name' => 'Negotiation',
    'deal_pipeline_id' => 'pipeline-uuid',
    'requires_attention_after' => ['amount' => 7, 'unit' => 'days'],
    'follow_up_actions' => ['create_task', 'create_event']  // Tasks and meetings
]);
```

### 4. Handle Phase Deletion Carefully

```php
// Good: Check for deals before deleting
function safelyDeletePhase($phaseId, $targetPhaseId)
{
    $dealsInPhase = Teamleader::deals()->inPhase($phaseId);
    
    if (count($dealsInPhase['data']) > 0) {
        Log::info("Migrating {count($dealsInPhase['data'])} deals from phase");
    }
    
    try {
        return Teamleader::dealPhases()->delete($phaseId, $targetPhaseId);
    } catch (\Exception $e) {
        Log::error('Failed to delete phase', [
            'phase_id' => $phaseId,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

### 5. Validate Phase Order

```php
// Good: Maintain logical phase order
function validatePhaseOrder($pipelineId)
{
    $phases = Teamleader::dealPhases()->forPipeline($pipelineId);
    
    // Sort by order
    usort($phases['data'], function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    // Ensure phases flow logically
    $expectedOrder = ['Lead', 'Qualification', 'Proposal', 'Negotiation', 'Closing'];
    
    foreach ($phases['data'] as $key => $phase) {
        if (isset($expectedOrder[$key]) && $phase['name'] !== $expectedOrder[$key]) {
            Log::warning('Phase order may be incorrect', [
                'expected' => $expectedOrder[$key],
                'actual' => $phase['name'],
                'position' => $key
            ]);
        }
    }
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $phase = Teamleader::dealPhases()->create([
        'name' => 'New Phase',
        'deal_pipeline_id' => 'pipeline-uuid',
        'requires_attention_after' => [
            'amount' => 7,
            'unit' => 'days'
        ]
    ]);
} catch (TeamleaderException $e) {
    Log::error('Error creating phase', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    if ($e->getCode() === 422) {
        // Validation error
        return response()->json([
            'error' => 'Invalid phase data'
        ], 422);
    }
}

// Special handling for phase deletion
try {
    Teamleader::dealPhases()->delete('phase-uuid', 'target-phase-uuid');
} catch (\InvalidArgumentException $e) {
    // Missing target phase for migration
    return response()->json([
        'error' => 'Must provide target phase for deal migration'
    ], 400);
}
```

## Limitations

1. **No Individual Info**: While there is an `info()` method, phases are typically managed through `list()` and filtered by pipeline
2. **Deletion Requires Migration**: You cannot delete a phase without specifying where to move existing deals
3. **Order Management**: Phase ordering is managed through the `move()` method, not a direct order field

## Related Resources

- [Deals](deals.md) - Deals move through phases
- [Deal Pipelines](deal_pipelines.md) - Phases belong to pipelines
- [Users](../general/users.md) - Track phase owners

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
