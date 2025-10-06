# Call Outcomes

Manage call outcomes in Teamleader Focus Calendar. This resource provides read-only access to configured call outcomes that can be used when logging calls in the system.

## Endpoint

`callOutcomes`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported (managed in Teamleader UI)
- **Supports Update**: ❌ Not Supported (managed in Teamleader UI)
- **Supports Deletion**: ❌ Not Supported (managed in Teamleader UI)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of call outcomes with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and filtering options

**Example:**
```php
$callOutcomes = $teamleader->callOutcomes()->list();
```

### `byIds()`

Get specific call outcomes by their UUIDs.

**Parameters:**
- `ids` (array): Array of call outcome UUIDs
- `options` (array): Additional options

**Example:**
```php
$callOutcomes = $teamleader->callOutcomes()->byIds(['uuid1', 'uuid2']);
```

### `findByName()`

Find a call outcome by its name.

**Parameters:**
- `name` (string): Name of the call outcome
- `options` (array): Additional options

**Example:**
```php
$outcome = $teamleader->callOutcomes()->findByName('Succesvol gesprek');
```

## Available Filters

- `ids` (array): Filter by specific call outcome UUIDs

## Call Outcome Fields

### Response Fields

- `id` (string): Call outcome UUID
- `name` (string): Name of the call outcome (e.g., "Succesvol gesprek", "Niet geïnteresseerd", "Voicemail", "Andere")

**Note**: The API only returns basic information (`id` and `name`). Additional configuration details like call type (incoming/outgoing/both) and follow-up actions are managed in the Teamleader Focus interface but are not exposed via the API.

## Usage Examples

### Basic Call Outcome Management

```php
// Get all call outcomes
$allOutcomes = $teamleader->callOutcomes()->list();

// Find a specific outcome by name
$successOutcome = $teamleader->callOutcomes()->findByName('Succesvol gesprek');

// Get specific outcomes by IDs
$specificOutcomes = $teamleader->callOutcomes()->byIds([
    'a776ee14-dff0-0bc7-9c56-eb1cb7b6d000',
    '14430beb-c236-0910-b754-7d0b8a26d001'
]);
```

### Working with Call Outcome Data

```php
$outcomes = $teamleader->callOutcomes()->list();

foreach ($outcomes['data'] as $outcome) {
    echo "Outcome: " . $outcome['name'] . "\n";
    echo "ID: " . $outcome['id'] . "\n";
    echo "---\n";
}

// Expected output based on your Teamleader instance:
// Outcome: Succesvol gesprek
// ID: a776ee14-dff0-0bc7-9c56-eb1cb7b6d000
// ---
// Outcome: Niet geïnteresseerd  
// ID: 14430beb-c236-0910-b754-7d0b8a26d001
// ---
// Outcome: Voicemail
// ID: 24fc5020-f36f-0aa7-a951-b8dd1856d002
// ---
// Outcome: Andere
// ID: 722b0cf2-2a4a-0fbb-9e5d-4d0ff1e6d003
// ---
```

## Error Handling

The call outcomes resource follows standard SDK error handling:

```php
$result = $teamleader->callOutcomes()->list();

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Call Outcomes API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Call outcome API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CallOutcomeController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $outcomes = $teamleader->callOutcomes()->list();
        return view('calendar.call-outcomes.index', compact('outcomes'));
    }
    
    public function getForType(Request $request, TeamleaderSDK $teamleader)
    {
        $type = $request->get('type', 'outgoing');
        $outcomes = $teamleader->callOutcomes()->byType($type);
        return response()->json($outcomes);
    }
}
```

## Configuration

Call outcomes are managed through the Teamleader Focus interface:

1. Navigate to **Settings** > **Calendar** > **Call outcomes**
2. Click the **+** symbol to add new outcomes
3. Configure:
    - **Name**: Display name for the outcome
    - **Type**: Whether it applies to incoming, outgoing, or both call types
    - **Follow-up action**: Optional automated follow-up (task, meeting, call, ticket)

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
