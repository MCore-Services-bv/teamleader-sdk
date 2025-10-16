# Deal Pipelines

Manage deal pipelines in Teamleader Focus.

## Overview

The Deal Pipelines resource allows you to manage sales pipelines in Teamleader. A pipeline represents your complete sales process and contains multiple phases that deals move through from initial contact to close. Organizations can have multiple pipelines for different types of sales processes.

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
    - [markAsDefault()](#markasdefault)
- [Helper Methods](#helper-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`dealPipelines`

## Capabilities

- **Pagination**: ❌ Not Supported (all pipelines returned)
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported (requires phase migration)

## Available Methods

### `list()`

Get all deal pipelines.

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all pipelines
$pipelines = Teamleader::dealPipelines()->list();
```

### `info()`

Get detailed information about a specific pipeline.

**Parameters:**
- `id` (string): Pipeline UUID

**Example:**
```php
$pipeline = Teamleader::dealPipelines()->info('pipeline-uuid');
```

### `create()`

Create a new deal pipeline.

**Parameters:**
- `data` (array): Pipeline data

**Example:**
```php
$pipeline = Teamleader::dealPipelines()->create([
    'name' => 'Enterprise Sales Pipeline'
]);
```

### `update()`

Update an existing deal pipeline.

**Parameters:**
- `id` (string): Pipeline UUID
- `data` (array): Updated pipeline data

**Example:**
```php
$pipeline = Teamleader::dealPipelines()->update('pipeline-uuid', [
    'name' => 'Updated Enterprise Sales Pipeline'
]);
```

### `delete()`

Delete a deal pipeline. Requires migrating phases from this pipeline to phases in other pipelines.

**Parameters:**
- `id` (string): Pipeline UUID to delete
- `migratePhases` (array): Array mapping old phase IDs to new phase IDs

**Example:**
```php
// Map each phase to a phase in another pipeline
$migratePhases = [
    ['old_phase_id' => 'phase-uuid-1', 'new_phase_id' => 'target-phase-uuid-1'],
    ['old_phase_id' => 'phase-uuid-2', 'new_phase_id' => 'target-phase-uuid-2'],
    ['old_phase_id' => 'phase-uuid-3', 'new_phase_id' => 'target-phase-uuid-3']
];

Teamleader::dealPipelines()->delete('pipeline-uuid', $migratePhases);
```

**Important:** You must provide migration instructions for all phases in the pipeline. Any deals in those phases will be moved to the corresponding new phases.

### `duplicate()`

Duplicate an existing pipeline with all its phases.

**Parameters:**
- `id` (string): Source pipeline UUID

**Example:**
```php
$newPipeline = Teamleader::dealPipelines()->duplicate('source-pipeline-uuid');
```

### `markAsDefault()`

Mark a pipeline as the default pipeline for new deals.

**Parameters:**
- `id` (string): Pipeline UUID

**Example:**
```php
Teamleader::dealPipelines()->markAsDefault('pipeline-uuid');
```

## Helper Methods

### `all()`

Get all pipelines (alias for `list()`).

```php
$pipelines = Teamleader::dealPipelines()->all();
```

## Response Structure

### Pipeline Object

```php
[
    'id' => 'pipeline-uuid',
    'name' => 'Enterprise Sales Pipeline',
    'default' => true
]
```

## Usage Examples

### Create a New Pipeline

```php
// Create pipeline
$pipeline = Teamleader::dealPipelines()->create([
    'name' => 'SMB Sales Pipeline'
]);

// Set as default
Teamleader::dealPipelines()->markAsDefault($pipeline['data']['id']);
```

### Duplicate Pipeline for Different Market

```php
// Duplicate existing pipeline
$newPipeline = Teamleader::dealPipelines()->duplicate('existing-pipeline-uuid');

// Rename it
Teamleader::dealPipelines()->update($newPipeline['data']['id'], [
    'name' => 'International Sales Pipeline'
]);
```

### Delete Pipeline Safely

```php
// Get source pipeline phases
$sourcePipeline = 'pipeline-to-delete-uuid';
$targetPipeline = 'target-pipeline-uuid';

$sourcePhases = Teamleader::dealPhases()->forPipeline($sourcePipeline);
$targetPhases = Teamleader::dealPhases()->forPipeline($targetPipeline);

// Map phases (match by name or order)
$phaseMap = [];
foreach ($sourcePhases['data'] as $key => $sourcePhase) {
    if (isset($targetPhases['data'][$key])) {
        $phaseMap[] = [
            'old_phase_id' => $sourcePhase['id'],
            'new_phase_id' => $targetPhases['data'][$key]['id']
        ];
    }
}

// Delete pipeline
Teamleader::dealPipelines()->delete($sourcePipeline, $phaseMap);
```

### Get Default Pipeline

```php
$pipelines = Teamleader::dealPipelines()->list();

$defaultPipeline = null;
foreach ($pipelines['data'] as $pipeline) {
    if ($pipeline['default'] === true) {
        $defaultPipeline = $pipeline;
        break;
    }
}
```

## Common Use Cases

### 1. Setup Multiple Sales Processes

```php
function setupSalesPipelines()
{
    // Create enterprise pipeline
    $enterprise = Teamleader::dealPipelines()->create([
        'name' => 'Enterprise Sales'
    ]);
    
    // Create SMB pipeline
    $smb = Teamleader::dealPipelines()->create([
        'name' => 'SMB Sales'
    ]);
    
    // Create partner pipeline
    $partner = Teamleader::dealPipelines()->create([
        'name' => 'Partner Sales'
    ]);
    
    // Set enterprise as default
    Teamleader::dealPipelines()->markAsDefault($enterprise['data']['id']);
    
    return [
        'enterprise' => $enterprise['data'],
        'smb' => $smb['data'],
        'partner' => $partner['data']
    ];
}
```

### 2. Clone Pipeline for New Region

```php
function clonePipelineForRegion($sourcePipelineId, $regionName)
{
    // Duplicate pipeline
    $newPipeline = Teamleader::dealPipelines()->duplicate($sourcePipelineId);
    
    // Rename for region
    Teamleader::dealPipelines()->update($newPipeline['data']['id'], [
        'name' => "{$regionName} Sales Pipeline"
    ]);
    
    // Get phases and adjust timing for region
    $phases = Teamleader::dealPhases()->forPipeline($newPipeline['data']['id']);
    
    foreach ($phases['data'] as $phase) {
        // Adjust attention timers based on region
        $adjustedDays = $phase['requires_attention_after']['amount'] * 1.5; // 50% longer
        
        Teamleader::dealPhases()->update($phase['id'], [
            'requires_attention_after' => [
                'amount' => ceil($adjustedDays),
                'unit' => 'days'
            ]
        ]);
    }
    
    return $newPipeline['data'];
}
```

### 3. Pipeline Health Dashboard

```php
function getPipelineHealthMetrics()
{
    $pipelines = Teamleader::dealPipelines()->list();
    $metrics = [];
    
    foreach ($pipelines['data'] as $pipeline) {
        $phases = Teamleader::dealPhases()->forPipeline($pipeline['id']);
        $totalDeals = 0;
        $totalValue = 0;
        
        foreach ($phases['data'] as $phase) {
            $deals = Teamleader::deals()->inPhase($phase['id']);
            $totalDeals += count($deals['data']);
            
            foreach ($deals['data'] as $deal) {
                $totalValue += $deal['estimated_value']['amount'];
            }
        }
        
        $metrics[] = [
            'pipeline' => $pipeline['name'],
            'is_default' => $pipeline['default'],
            'phase_count' => count($phases['data']),
            'total_deals' => $totalDeals,
            'total_value' => $totalValue
        ];
    }
    
    return $metrics;
}
```

### 4. Merge Pipelines

```php
function mergePipelines($sourcePipelineId, $targetPipelineId)
{
    // Get phases from both pipelines
    $sourcePhases = Teamleader::dealPhases()->forPipeline($sourcePipelineId);
    $targetPhases = Teamleader::dealPhases()->forPipeline($targetPipelineId);
    
    // Build migration map (map all source phases to closest target phase)
    $phaseMap = [];
    
    foreach ($sourcePhases['data'] as $sourcePhase) {
        // Find best matching target phase by name
        $bestMatch = null;
        $highestSimilarity = 0;
        
        foreach ($targetPhases['data'] as $targetPhase) {
            $similarity = similar_text(
                strtolower($sourcePhase['name']),
                strtolower($targetPhase['name'])
            );
            
            if ($similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $bestMatch = $targetPhase['id'];
            }
        }
        
        $phaseMap[] = [
            'old_phase_id' => $sourcePhase['id'],
            'new_phase_id' => $bestMatch ?? $targetPhases['data'][0]['id']
        ];
    }
    
    // Delete source pipeline
    return Teamleader::dealPipelines()->delete($sourcePipelineId, $phaseMap);
}
```

### 5. Pipeline Performance Comparison

```php
function comparePipelinePerformance($startDate, $endDate)
{
    $pipelines = Teamleader::dealPipelines()->list();
    $comparison = [];
    
    foreach ($pipelines['data'] as $pipeline) {
        $phases = Teamleader::dealPhases()->forPipeline($pipeline['id']);
        $phaseIds = array_column($phases['data'], 'id');
        
        $wonDeals = [];
        $lostDeals = [];
        
        foreach ($phaseIds as $phaseId) {
            $won = Teamleader::deals()->won([
                'phase_id' => $phaseId,
                'estimated_closing_date_from' => $startDate,
                'estimated_closing_date_to' => $endDate
            ]);
            $wonDeals = array_merge($wonDeals, $won['data']);
            
            $lost = Teamleader::deals()->lost([
                'phase_id' => $phaseId,
                'estimated_closing_date_from' => $startDate,
                'estimated_closing_date_to' => $endDate
            ]);
            $lostDeals = array_merge($lostDeals, $lost['data']);
        }
        
        $totalClosed = count($wonDeals) + count($lostDeals);
        
        $comparison[] = [
            'pipeline' => $pipeline['name'],
            'won' => count($wonDeals),
            'lost' => count($lostDeals),
            'win_rate' => $totalClosed > 0 
                ? (count($wonDeals) / $totalClosed) * 100 
                : 0
        ];
    }
    
    return $comparison;
}
```

## Best Practices

### 1. Use Descriptive Pipeline Names

```php
// Good: Clearly describes the sales process
Teamleader::dealPipelines()->create([
    'name' => 'Enterprise B2B Sales'
]);

// Bad: Vague or unclear
Teamleader::dealPipelines()->create([
    'name' => 'Pipeline 2'
]);
```

### 2. Always Have a Default Pipeline

```php
// Good: Ensure there's always a default
$pipelines = Teamleader::dealPipelines()->list();

$hasDefault = false;
foreach ($pipelines['data'] as $pipeline) {
    if ($pipeline['default'] === true) {
        $hasDefault = true;
        break;
    }
}

if (!$hasDefault && !empty($pipelines['data'])) {
    Teamleader::dealPipelines()->markAsDefault($pipelines['data'][0]['id']);
}
```

### 3. Validate Phase Mapping Before Deletion

```php
// Good: Ensure all phases are mapped
function safelyDeletePipeline($pipelineId, $phaseMap)
{
    $phases = Teamleader::dealPhases()->forPipeline($pipelineId);
    $phaseIds = array_column($phases['data'], 'id');
    $mappedIds = array_column($phaseMap, 'old_phase_id');
    
    $unmappedPhases = array_diff($phaseIds, $mappedIds);
    
    if (!empty($unmappedPhases)) {
        throw new \Exception(
            'All phases must be mapped before deletion. Missing: ' . 
            implode(', ', $unmappedPhases)
        );
    }
    
    return Teamleader::dealPipelines()->delete($pipelineId, $phaseMap);
}
```

### 4. Use Duplication for Consistency

```php
// Good: Start from proven template
$templatePipeline = 'proven-pipeline-uuid';

$newPipeline = Teamleader::dealPipelines()->duplicate($templatePipeline);

Teamleader::dealPipelines()->update($newPipeline['data']['id'], [
    'name' => 'New Market Pipeline'
]);
```

### 5. Document Pipeline Purpose

```php
// Good: Maintain external documentation
function createPipelineWithDocs($name, $description, $targetMarket)
{
    $pipeline = Teamleader::dealPipelines()->create([
        'name' => $name
    ]);
    
    // Store metadata in your system
    DB::table('pipeline_metadata')->insert([
        'teamleader_id' => $pipeline['data']['id'],
        'description' => $description,
        'target_market' => $targetMarket,
        'created_by' => auth()->user()->id,
        'created_at' => now()
    ]);
    
    return $pipeline;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $pipeline = Teamleader::dealPipelines()->create([
        'name' => 'New Pipeline'
    ]);
} catch (TeamleaderException $e) {
    Log::error('Error creating pipeline', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}

// Special handling for pipeline deletion
try {
    Teamleader::dealPipelines()->delete('pipeline-uuid', $phaseMap);
} catch (\InvalidArgumentException $e) {
    // Missing or invalid phase migration map
    return response()->json([
        'error' => 'Must provide valid phase migration mapping'
    ], 400);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 400) {
        // Invalid phase mappings
        Log::error('Invalid phase migration map', [
            'pipeline_id' => 'pipeline-uuid',
            'map' => $phaseMap
        ]);
    }
}
```

## Limitations

1. **No Filtering**: You cannot filter pipelines; `list()` always returns all pipelines
2. **No Pagination**: All pipelines are returned in a single request
3. **Deletion Requires Full Phase Mapping**: Every phase must be mapped to a new phase
4. **Name Only**: Pipelines only have a name field; no description or additional metadata

## Related Resources

- [Deals](deals.md) - Deals belong to pipelines
- [Deal Phases](deal_phases.md) - Phases belong to pipelines
- [Users](../general/users.md) - Pipeline owners

## See Also

- [Usage Guide](../usage.md) - General SDK usage
