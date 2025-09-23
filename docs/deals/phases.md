# Deal Phases

Manage deal phases in Teamleader Focus. Deal phases represent the individual stages within a deal pipeline that deals progress through during your sales process.

## Endpoint

`dealPhases`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported (phases are sorted by their order in the pipeline)
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of deal phases with filtering options. Phases are automatically sorted by their order in the flow.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination options

**Example:**
```php
$phases = $teamleader->dealPhases()->list(['deal_pipeline_id' => 'pipeline-uuid']);
```

### `create()`

Create a new deal phase within a pipeline.

**Parameters:**
- `data` (array): Phase data with required fields

**Required Fields:**
- `name`: Phase name
- `deal_pipeline_id`: Pipeline UUID
- `requires_attention_after`: Object with `amount` and `unit` properties

**Example:**
```php
$phase = $teamleader->dealPhases()->create([
    'name' => 'Investigation',
    'deal_pipeline_id' => 'f350e48a-fbc3-0a79-e62a-53aa1ae86d44',
    'requires_attention_after' => [
        'amount' => 7,
        'unit' => 'days'
    ],
    'estimated_probability' => 0.5,
    'follow_up_actions' => ['create_event']
]);
```

### `update()`

Update an existing deal phase.

**Parameters:**
- `id` (string): Phase UUID
- `data` (array): Update data

**Example:**
```php
$phase = $teamleader->dealPhases()->update('uuid-here', [
    'name' => 'Preparation',
    'estimated_probability' => 0.8,
    'requires_attention_after' => [
        'amount' => 7,
        'unit' => 'days'
    ]
]);
```

### `delete()`

Delete a deal phase and migrate existing deals to another phase.

**Parameters:**
- `id` (string): Phase UUID to delete
- `newPhaseId` (string): Phase UUID to migrate deals to

**Example:**
```php
$teamleader->dealPhases()->delete('phase-to-delete', 'target-phase-uuid');
```

### `duplicate()`

Create a new deal phase by duplicating an existing one.

**Parameters:**
- `id` (string): Source phase UUID

**Example:**
```php
$newPhase = $teamleader->dealPhases()->duplicate('source-phase-uuid');
```

### `move()`

Move a phase to a new position within its pipeline.

**Parameters:**
- `id` (string): Phase UUID to move
- `afterPhaseId` (string): Phase UUID to place this phase after

**Example:**
```php
$teamleader->dealPhases()->move('phase-to-move', 'after-this-phase');
```

### `forPipeline()`

Get all phases for a specific pipeline.

**Parameters:**
- `pipelineId` (string): Pipeline UUID

**Example:**
```php
$phases = $teamleader->dealPhases()->forPipeline('pipeline-uuid');
```

### `byIds()`

Get specific phases by their UUIDs.

**Parameters:**
- `ids` (array): Array of phase UUIDs

**Example:**
```php
$phases = $teamleader->dealPhases()->byIds(['uuid1', 'uuid2']);
```

## Filtering

### Available Filters

- **`ids`**: Array of phase UUIDs to filter by
- **`deal_pipeline_id`**: Filter phases by specific pipeline UUID

### Filter Examples

```php
// Get phases for a specific pipeline
$phases = $teamleader->dealPhases()->list([
    'deal_pipeline_id' => '53a7e592-a136-4bae-ae15-517befd3d75d'
]);

// Get specific phases by IDs
$specificPhases = $teamleader->dealPhases()->list([
    'ids' => [
        '21efc56e-1ba8-469d-926a-e89502591b47',
        'bb50af79-55cd-4be0-8306-e9626c70a90f'
    ]
]);

// Combine filters
$filteredPhases = $teamleader->dealPhases()->list([
    'deal_pipeline_id' => 'pipeline-uuid',
    'ids' => ['phase1-uuid', 'phase2-uuid']
]);
```

## Pagination

The deal phases resource supports pagination:

```php
// Get second page with 10 items per page
$phases = $teamleader->dealPhases()->list([], [
    'page_size' => 10,
    'page_number' => 2
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "21efc56e-1ba8-469d-926a-e89502591b47",
            "name": "New",
            "actions": ["create_event", "create_call", "create_task"],
            "requires_attention_after": {
                "amount": 7,
                "unit": "days"
            },
            "probability": 0.75
        }
    ]
}
```

### Create Response

```json
{
    "data": {
        "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
        "type": "dealPhase"
    }
}
```

### Duplicate Response

```json
{
    "data": {
        "type": "dealPhase",
        "id": "eb264fd0-0e5c-0dbf-ae1e-49e7d6a8e6b8"
    }
}
```

## Data Fields

### Phase Fields

- **`id`**: Phase UUID (read-only)
- **`name`**: Phase name (required for create/update)
- **`deal_pipeline_id`**: Parent pipeline UUID (required for create)
- **`requires_attention_after`**: Object defining attention timeframe (required)
    - **`amount`**: Number of time units (required)
    - **`unit`**: Time unit - "days" or "weeks" (required)
- **`estimated_probability`**: Probability estimate (0.0 - 1.0)
- **`follow_up_actions`**: Array of follow-up actions
- **`actions`**: Available actions (returned when user has planning access)
- **`probability`**: Win probability (alias for estimated_probability in responses)
- **`type`**: Resource type (always "dealPhase")

### Follow-Up Actions

Available follow-up actions:
- `create_event` - Create a calendar event
- `create_call` - Create a call task
- `create_task` - Create a general task

### Attention After Units

Available time units for `requires_attention_after`:
- `days` - Days
- `weeks` - Weeks

## Usage Examples

### Basic Operations

```php
// List all phases
$allPhases = $teamleader->dealPhases()->list();

// Get phases for a specific pipeline
$pipelinePhases = $teamleader->dealPhases()->forPipeline('pipeline-uuid');

// Create a new phase
$newPhase = $teamleader->dealPhases()->create([
    'name' => 'Investigation',
    'deal_pipeline_id' => 'f350e48a-fbc3-0a79-e62a-53aa1ae86d44',
    'requires_attention_after' => [
        'amount' => 7,
        'unit' => 'days'
    ],
    'estimated_probability' => 0.5,
    'follow_up_actions' => ['create_event']
]);

// Update phase
$updatedPhase = $teamleader->dealPhases()->update('phase-uuid', [
    'name' => 'Updated Phase Name',
    'estimated_probability' => 0.8
]);
```

### Advanced Operations

```php
// Duplicate an existing phase
$duplicatedPhase = $teamleader->dealPhases()->duplicate('source-phase-uuid');

// Move a phase within the pipeline
$teamleader->dealPhases()->move('phase-to-move', 'after-this-phase');

// Delete a phase (migrate deals to another phase)
$teamleader->dealPhases()->delete('phase-to-delete', 'target-phase-uuid');
```

### Complex Phase Creation

```php
// Create a phase with all optional fields
$comprehensivePhase = $teamleader->dealPhases()->create([
    'name' => 'Negotiation',
    'deal_pipeline_id' => 'f350e48a-fbc3-0a79-e62a-53aa1ae86d44',
    'requires_attention_after' => [
        'amount' => 2,
        'unit' => 'weeks'
    ],
    'estimated_probability' => 0.85,
    'follow_up_actions' => [
        'create_event',
        'create_call',
        'create_task'
    ]
]);
```

### Working with Pipeline Phases

```php
// Get all phases for a pipeline and organize them
$pipeline = $teamleader->dealPipelines()->info('pipeline-uuid');
$phases = $teamleader->dealPhases()->forPipeline('pipeline-uuid');

// Create a new phase at the end of the pipeline
$lastPhase = end($phases['data']);
$newPhase = $teamleader->dealPhases()->create([
    'name' => 'Final Review',
    'deal_pipeline_id' => 'pipeline-uuid',
    'requires_attention_after' => [
        'amount' => 3,
        'unit' => 'days'
    ],
    'estimated_probability' => 0.95
]);

// Then move it to the desired position
$teamleader->dealPhases()->move(
    $newPhase['data']['id'], 
    $lastPhase['id']
);
```

## Phase Ordering and Movement

Deal phases within a pipeline have a specific order that affects the sales flow:

```php
// Get current phases in order
$phases = $teamleader->dealPhases()->forPipeline('pipeline-uuid');

// Move a phase to be after another specific phase
$teamleader->dealPhases()->move(
    'phase-to-move-uuid',
    'place-after-this-phase-uuid'
);

// To move a phase to the beginning, you'd need to:
// 1. Find the current first phase
// 2. Move your phase to be after it
// 3. Then move the original first phase to be after your phase
```

## Phase Deletion and Migration

When deleting a phase, you must specify where to migrate existing deals:

```php
// Before deleting, you might want to check for existing deals
$phasesToMigrateTo = $teamleader->dealPhases()->forPipeline('same-pipeline-uuid');

// Delete phase and migrate deals
$teamleader->dealPhases()->delete(
    'phase-to-delete-uuid',
    'migration-target-phase-uuid'
);
```

## Validation and Business Rules

The SDK includes validation for phase data:

- Phase names must be provided
- Pipeline ID is required for creation
- `requires_attention_after` must have both `amount` and `unit`
- Unit must be either "days" or "weeks"
- Follow-up actions must be from the allowed list
- Probability should be between 0.0 and 1.0

```php
// This will throw an InvalidArgumentException
try {
    $teamleader->dealPhases()->create([
        'name' => 'Test Phase',
        // Missing deal_pipeline_id - will throw exception
    ]);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Deal pipeline ID is required"
}
```

## Error Handling

The deal phases resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->dealPhases()->create($phaseData);

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Deal Phases API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'phase_data' => $phaseData
    ]);
}
```

## Rate Limiting

Deal phases API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Duplicate operations**: 1 request per call
- **Move operations**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Phases are automatically sorted by their order in the pipeline flow
- Phase names should be unique within a pipeline
- When deleting a phase, all deals in that phase will be moved to the specified target phase
- The `duplicate()` method creates an exact copy of the source phase within the same pipeline
- Moving phases only affects their position within the same pipeline
- The `actions` field is only returned when the user has access to planning and deal automation
- Probability values are typically expressed as decimals (0.5 = 50%)

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DealPhaseController extends Controller
{
    public function index(TeamleaderSDK $teamleader, Request $request)
    {
        $pipelineId = $request->get('pipeline_id');
        
        $phases = $pipelineId 
            ? $teamleader->dealPhases()->forPipeline($pipelineId)
            : $teamleader->dealPhases()->list();
            
        return view('deals.phases.index', compact('phases'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $phase = $teamleader->dealPhases()->create($request->validated());
        
        return redirect()->route('deals.phases.index')
            ->with('success', 'Phase created successfully');
    }
    
    public function duplicate(TeamleaderSDK $teamleader, string $id)
    {
        $duplicatedPhase = $teamleader->dealPhases()->duplicate($id);
        
        return response()->json([
            'success' => true,
            'phase' => $duplicatedPhase
        ]);
    }
    
    public function move(Request $request, TeamleaderSDK $teamleader, string $id)
    {
        $teamleader->dealPhases()->move(
            $id,
            $request->input('after_phase_id')
        );
        
        return response()->json(['success' => true]);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
