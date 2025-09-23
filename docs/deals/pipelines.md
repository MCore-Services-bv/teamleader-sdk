# Deal Pipelines

Manage deal pipelines in Teamleader Focus. Deal pipelines organize your sales process into stages and help track deals through your sales workflow.

## Endpoint

`dealPipelines`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of deal pipelines with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and include options

**Example:**
```php
$pipelines = $teamleader->dealPipelines()->list(['status' => 'open']);
```

### `create()`

Create a new deal pipeline.

**Parameters:**
- `data` (array): Pipeline data with required `name` field

**Example:**
```php
$pipeline = $teamleader->dealPipelines()->create(['name' => 'Sales Pipeline']);
```

### `update()`

Update an existing deal pipeline.

**Parameters:**
- `id` (string): Pipeline UUID
- `data` (array): Update data with required `name` field

**Example:**
```php
$pipeline = $teamleader->dealPipelines()->update('uuid-here', ['name' => 'Updated Pipeline']);
```

### `delete()`

Delete a deal pipeline with optional phase migration.

**Parameters:**
- `id` (string): Pipeline UUID to delete
- `migratePhases` (array): Array of phase migration mappings

**Example:**
```php
$teamleader->dealPipelines()->delete('uuid-here', [
    ['old_phase_id' => 'old-uuid', 'new_phase_id' => 'new-uuid']
]);
```

### `duplicate()`

Create a new deal pipeline by duplicating an existing one.

**Parameters:**
- `id` (string): Source pipeline UUID

**Example:**
```php
$newPipeline = $teamleader->dealPipelines()->duplicate('source-uuid-here');
```

### `markAsDefault()`

Mark a pipeline as the default pipeline.

**Parameters:**
- `id` (string): Pipeline UUID

**Example:**
```php
$teamleader->dealPipelines()->markAsDefault('uuid-here');
```

### `open()`

Get only open pipelines.

**Example:**
```php
$openPipelines = $teamleader->dealPipelines()->open();
```

### `pendingDeletion()`

Get pipelines that are pending deletion.

**Example:**
```php
$pendingPipelines = $teamleader->dealPipelines()->pendingDeletion();
```

### `byIds()`

Get specific pipelines by their UUIDs.

**Parameters:**
- `ids` (array): Array of pipeline UUIDs

**Example:**
```php
$pipelines = $teamleader->dealPipelines()->byIds(['uuid1', 'uuid2']);
```

## Filtering

### Available Filters

- **`ids`**: Array of pipeline UUIDs to filter by
- **`status`**: Filter by pipeline status (open, pending_deletion)

### Filter Examples

```php
// Filter by status
$openPipelines = $teamleader->dealPipelines()->list([
    'status' => 'open'
]);

// Filter by specific IDs
$specificPipelines = $teamleader->dealPipelines()->list([
    'ids' => [
        '92296ad0-2d61-4179-b174-9f354ca2157f',
        '53635682-c382-4fbf-9fd9-9506ca4fbcdd'
    ]
]);

// Combine filters
$filteredPipelines = $teamleader->dealPipelines()->list([
    'status' => 'open',
    'ids' => ['uuid1', 'uuid2']
]);
```

## Pagination

The deal pipelines resource supports pagination:

```php
// Get second page with 10 items per page
$pipelines = $teamleader->dealPipelines()->list([], [
    'page_size' => 10,
    'page_number' => 2,
    'includes' => 'pagination'
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "811a5825-96f4-4318-83c3-2840935c6003",
            "name": "Sales Pipeline"
        }
    ],
    "meta": {
        "page": {
            "size": 10,
            "number": 2
        },
        "matches": 12,
        "default": "f350e48a-fbc3-0a79-e62a-53aa1ae86d44"
    }
}
```

### Create Response

```json
{
    "data": {
        "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
        "type": "dealPipeline"
    }
}
```

### Duplicate Response

```json
{
    "data": {
        "type": "dealPipeline",
        "id": "eb264fd0-0e5c-0dbf-ae1e-49e7d6a8e6b8"
    }
}
```

## Data Fields

### Pipeline Fields

- **`id`**: Pipeline UUID (read-only)
- **`name`**: Pipeline name (required for create/update)
- **`type`**: Resource type (always "dealPipeline")

### Meta Fields (in list response)

- **`default`**: UUID of the default pipeline
- **`matches`**: Total number of pipelines matching filters
- **`page`**: Pagination information

## Usage Examples

### Basic Operations

```php
// List all pipelines
$allPipelines = $teamleader->dealPipelines()->list();

// Create a new pipeline
$newPipeline = $teamleader->dealPipelines()->create([
    'name' => 'New Sales Process'
]);

// Update pipeline name
$updatedPipeline = $teamleader->dealPipelines()->update('uuid-here', [
    'name' => 'Updated Sales Process'
]);

// Mark as default
$teamleader->dealPipelines()->markAsDefault('uuid-here');

// Delete pipeline (without phase migration)
$teamleader->dealPipelines()->delete('uuid-here');
```

### Advanced Operations

```php
// Duplicate an existing pipeline
$duplicatedPipeline = $teamleader->dealPipelines()->duplicate('source-uuid');

// Delete with phase migration
$migrationMap = [
    [
        'old_phase_id' => '57785244-0d45-0f01-9c18-5ce1cb68e4c1',
        'new_phase_id' => '29648aea-52f9-09f7-8e1e-cc5c08b4c742'
    ]
];
$teamleader->dealPipelines()->delete('pipeline-uuid', $migrationMap);
```

### Filtered Listings

```php
// Get only open pipelines
$openPipelines = $teamleader->dealPipelines()->open();

// Get pipelines pending deletion
$pendingPipelines = $teamleader->dealPipelines()->pendingDeletion();

// Get specific pipelines by IDs
$specificPipelines = $teamleader->dealPipelines()->byIds([
    'uuid1',
    'uuid2'
]);
```

### Paginated Listings

```php
// Get paginated results with metadata
$paginatedPipelines = $teamleader->dealPipelines()->list([], [
    'page_size' => 20,
    'page_number' => 1,
    'includes' => 'pagination'
]);

// Access pagination info
$totalMatches = $paginatedPipelines['meta']['matches'];
$currentPage = $paginatedPipelines['meta']['page']['number'];
$defaultPipeline = $paginatedPipelines['meta']['default'];
```

## Pipeline Deletion and Migration

When deleting a pipeline, you must specify how to migrate deals from the phases of the deleted pipeline to phases in other pipelines:

```php
// Example: Moving deals from deleted pipeline phases to new locations
$migrationMap = [
    [
        'old_phase_id' => 'phase-from-deleted-pipeline',
        'new_phase_id' => 'target-phase-in-another-pipeline'
    ],
    [
        'old_phase_id' => 'another-old-phase',
        'new_phase_id' => 'another-target-phase'
    ]
];

$teamleader->dealPipelines()->delete('pipeline-to-delete', $migrationMap);
```

## Error Handling

The deal pipelines resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->dealPipelines()->create(['name' => 'Test Pipeline']);

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Deal Pipelines API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Deal pipelines API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Duplicate operations**: 1 request per call
- **Mark as default operations**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Pipeline names must be unique within your Teamleader account
- When deleting a pipeline, you must provide migration mappings for all existing deals
- The `duplicate()` method creates an exact copy including all phases of the source pipeline
- Only one pipeline can be marked as default at a time
- Pipelines with status `pending_deletion` cannot be used for new deals
- The default pipeline cannot be deleted until another pipeline is marked as default

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DealPipelineController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $pipelines = $teamleader->dealPipelines()->open();
        
        return view('deals.pipelines.index', compact('pipelines'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $pipeline = $teamleader->dealPipelines()->create([
            'name' => $request->name
        ]);
        
        return redirect()->route('deals.pipelines.index')
            ->with('success', 'Pipeline created successfully');
    }
    
    public function markAsDefault(TeamleaderSDK $teamleader, string $id)
    {
        $teamleader->dealPipelines()->markAsDefault($id);
        
        return response()->json(['success' => true]);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
