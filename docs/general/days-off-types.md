# Day Off Types

Manage day off types in Teamleader Focus. This resource provides full CRUD operations for day off types, allowing you to create, read, update, and delete day off type definitions used in time tracking and employee management.

## Endpoint

`dayOffTypes`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get all day off types for the account.

**Parameters:**
- None (filters and options not supported)

**Example:**
```php
$dayOffTypes = $teamleader->dayOffTypes()->list();
```

### `create()`

Create a new day off type.

**Parameters:**
- `data` (array): Day off type data

**Example:**
```php
$dayOffType = $teamleader->dayOffTypes()->create([
    'name' => 'Vacation',
    'color' => '#00B2B2'
]);
```

### `update()`

Update an existing day off type.

**Parameters:**
- `id` (string): Day off type UUID
- `data` (array): Data to update

**Example:**
```php
$result = $teamleader->dayOffTypes()->update('uuid-here', [
    'name' => 'Updated Name',
    'color' => '#FF0000'
]);
```

### `delete()`

Delete a day off type.

**Parameters:**
- `id` (string): Day off type UUID

**Example:**
```php
$result = $teamleader->dayOffTypes()->delete('uuid-here');
```

### `createWithValidity()`

Create a day off type with date validity period.

**Parameters:**
- `name` (string): Name of the day off type
- `color` (string, optional): Hex color code
- `fromDate` (string, optional): Start date (YYYY-MM-DD)
- `untilDate` (string, optional): End date (YYYY-MM-DD)

**Example:**
```php
$dayOffType = $teamleader->dayOffTypes()->createWithValidity(
    'Summer Leave',
    '#FFB600',
    '2024-06-01',
    '2024-08-31'
);
```

### `updateColor()`

Update only the color of a day off type.

**Parameters:**
- `id` (string): Day off type UUID
- `color` (string): Hex color code

**Example:**
```php
$result = $teamleader->dayOffTypes()->updateColor('uuid-here', '#FF6B6B');
```

### `updateValidity()`

Update the validity dates of a day off type.

**Parameters:**
- `id` (string): Day off type UUID
- `fromDate` (string): Start date (YYYY-MM-DD)
- `untilDate` (string, optional): End date (YYYY-MM-DD)

**Example:**
```php
$result = $teamleader->dayOffTypes()->updateValidity('uuid-here', '2024-01-01', '2024-12-31');
```

### `bulkCreate()`

Create multiple day off types in one operation.

**Parameters:**
- `dayOffTypes` (array): Array of day off type data

**Example:**
```php
$results = $teamleader->dayOffTypes()->bulkCreate([
    ['name' => 'Vacation', 'color' => '#00B2B2'],
    ['name' => 'Sick Leave', 'color' => '#FF6B6B'],
    ['name' => 'Personal Day', 'color' => '#4ECDC4']
]);
```

## Data Fields

### Required Fields

- **`name`** (string): Name of the day off type (max 255 characters)

### Optional Fields

- **`color`** (string): Hex color code (e.g., "#00B2B2")
- **`date_validity`** (object): Date validity period
    - **`from`** (string, required if date_validity provided): Start date (YYYY-MM-DD format)
    - **`until`** (string, optional): End date (YYYY-MM-DD format)

### Response Fields

- **`id`** (string): Day off type UUID
- **`name`** (string): Name of the day off type
- **`type`** (string): Resource type identifier

## Request/Response Examples

### List Day Off Types

**Request:**
```php
$dayOffTypes = $teamleader->dayOffTypes()->list();
```

**Response:**
```json
{
    "data": [
        {
            "id": "811a5825-96f4-4318-83c3-2840935c6003",
            "name": "Vacation"
        },
        {
            "id": "922b6936-07g5-5429-94d4-3951046d7114",
            "name": "Sick Leave"
        }
    ]
}
```

### Create Day Off Type

**Request:**
```php
$dayOffType = $teamleader->dayOffTypes()->create([
    'name' => 'Personal Day',
    'color' => '#4ECDC4',
    'date_validity' => [
        'from' => '2024-01-01',
        'until' => '2024-12-31'
    ]
]);
```

**Response (201):**
```json
{
    "data": {
        "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
        "type": "dayOffType"
    }
}
```

### Update Day Off Type

**Request:**
```php
$result = $teamleader->dayOffTypes()->update('uuid-here', [
    'name' => 'Updated Personal Day',
    'color' => '#FF6B6B'
]);
```

**Response (204):**
No content returned for successful updates.

### Delete Day Off Type

**Request:**
```php
$result = $teamleader->dayOffTypes()->delete('uuid-here');
```

**Response (204):**
No content returned for successful deletions.

## Usage Examples

### Basic Operations

```php
// List all day off types
$dayOffTypes = $teamleader->dayOffTypes()->list();

// Create a simple day off type
$newType = $teamleader->dayOffTypes()->create([
    'name' => 'Mental Health Day'
]);

// Create with color
$coloredType = $teamleader->dayOffTypes()->create([
    'name' => 'Company Holiday',
    'color' => '#00B2B2'
]);

// Update an existing type
$updated = $teamleader->dayOffTypes()->update('uuid-here', [
    'name' => 'Updated Name',
    'color' => '#FF0000'
]);

// Delete a type
$deleted = $teamleader->dayOffTypes()->delete('uuid-here');
```

### Working with Date Validity

```php
// Create day off type valid for summer months
$summerLeave = $teamleader->dayOffTypes()->create([
    'name' => 'Summer Leave',
    'color' => '#FFB600',
    'date_validity' => [
        'from' => '2024-06-01',
        'until' => '2024-08-31'
    ]
]);

// Using the convenience method
$winterBreak = $teamleader->dayOffTypes()->createWithValidity(
    'Winter Break',
    '#45B7D1',
    '2024-12-15',
    '2025-01-15'
);

// Update validity dates
$updated = $teamleader->dayOffTypes()->updateValidity(
    'uuid-here',
    '2024-01-01',
    '2024-12-31'
);
```

### Bulk Operations

```php
// Create multiple day off types
$types = [
    ['name' => 'Vacation', 'color' => '#00B2B2'],
    ['name' => 'Sick Leave', 'color' => '#FF6B6B'],
    ['name' => 'Personal Day', 'color' => '#4ECDC4'],
    ['name' => 'Bereavement', 'color' => '#96CEB4']
];

$results = $teamleader->dayOffTypes()->bulkCreate($types);

// Check results
foreach ($results as $result) {
    if ($result['success']) {
        echo "Created: " . $result['data']['data']['id'] . "\n";
    } else {
        echo "Failed: " . $result['error'] . "\n";
    }
}
```

### Using Common Colors

```php
// Get predefined color options
$colors = $teamleader->dayOffTypes()->getCommonColors();

// Create types using common colors
foreach (['Vacation', 'Sick Leave', 'Personal'] as $index => $name) {
    $colorKey = array_keys($colors)[$index];
    
    $teamleader->dayOffTypes()->create([
        'name' => $name,
        'color' => $colorKey
    ]);
}
```

## Validation

The SDK provides built-in validation for day off type data:

### Validation Rules

- **Name**: Required, string, maximum 255 characters
- **Color**: Optional, must be valid hex color format (#RRGGBB)
- **Date Validity**: Optional object
    - **From**: Required if date_validity provided, YYYY-MM-DD format
    - **Until**: Optional, YYYY-MM-DD format, must be after 'from' date

### Laravel Validation

```php
// Get Laravel validation rules
$rules = $teamleader->dayOffTypes()->getValidationRules();

// Use in Laravel validator
$validator = validator($request->all(), $rules);
```

### Manual Validation Examples

```php
// Valid data
$validData = [
    'name' => 'Vacation',
    'color' => '#00B2B2',
    'date_validity' => [
        'from' => '2024-01-01',
        'until' => '2024-12-31'
    ]
];

// Invalid color format - will throw exception
try {
    $teamleader->dayOffTypes()->create([
        'name' => 'Test',
        'color' => 'invalid-color' // ❌ Should be #RRGGBB
    ]);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}

// Invalid date format - will throw exception
try {
    $teamleader->dayOffTypes()->create([
        'name' => 'Test',
        'date_validity' => [
            'from' => '01/01/2024' // ❌ Should be YYYY-MM-DD
        ]
    ]);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

## Error Handling

The day off types resource follows standard SDK error handling:

```php
try {
    $result = $teamleader->dayOffTypes()->create([
        'name' => 'Test Type'
    ]);
    
    if (isset($result['error']) && $result['error']) {
        // Handle API error
        $errorMessage = $result['message'] ?? 'Unknown error';
        Log::error("Day Off Type creation failed: {$errorMessage}");
    }
    
} catch (InvalidArgumentException $e) {
    // Handle validation error
    Log::error("Validation error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle other errors
    Log::error("Unexpected error: " . $e->getMessage());
}
```

## Rate Limiting

Day off types API calls count towards your Teamleader API rate limit:

- **List operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Bulk operations**: 1 request per item (not atomic)

**Rate limit cost**: 1 request per method call

## Best Practices

### Naming Conventions

Use clear, descriptive names for day off types:

```php
// Good naming
$teamleader->dayOffTypes()->create(['name' => 'Annual Vacation']);
$teamleader->dayOffTypes()->create(['name' => 'Sick Leave']);
$teamleader->dayOffTypes()->create(['name' => 'Bereavement Leave']);

// Avoid generic names
$teamleader->dayOffTypes()->create(['name' => 'Time Off']); // Too generic
```

### Color Management

Use consistent, meaningful colors:

```php
// Use semantic colors
$colors = [
    'vacation' => '#00B2B2',      // Teal for vacation
    'sick' => '#FF6B6B',          // Red for sick leave
    'personal' => '#4ECDC4',      // Light blue for personal
    'bereavement' => '#96CEB4',   // Muted green for bereavement
    'training' => '#FFEAA7'       // Yellow for training
];
```

### Date Validity

Use date validity for seasonal or temporary day off types:

```php
// Seasonal leave types
$teamleader->dayOffTypes()->create([
    'name' => 'Summer Vacation',
    'color' => '#FFB600',
    'date_validity' => [
        'from' => date('Y') . '-06-01',
        'until' => date('Y') . '-08-31'
    ]
]);

// Temporary company policies
$teamleader->dayOffTypes()->create([
    'name' => 'COVID Recovery Time',
    'color' => '#FF6B6B',
    'date_validity' => [
        'from' => '2024-01-01',
        'until' => '2024-12-31'
    ]
]);
```

## Laravel Integration

### Controller Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DayOffTypesController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $dayOffTypes = $teamleader->dayOffTypes()->list();
        return view('day-off-types.index', compact('dayOffTypes'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $rules = $teamleader->dayOffTypes()->getValidationRules();
        $request->validate($rules);
        
        $result = $teamleader->dayOffTypes()->create($request->all());
        
        return redirect()->back()->with('success', 'Day off type created successfully');
    }
    
    public function update(Request $request, TeamleaderSDK $teamleader, string $id)
    {
        $result = $teamleader->dayOffTypes()->update($id, $request->all());
        
        return response()->json(['success' => true]);
    }
    
    public function destroy(TeamleaderSDK $teamleader, string $id)
    {
        $result = $teamleader->dayOffTypes()->delete($id);
        
        return response()->json(['success' => true]);
    }
}
```

### Blade Template Helpers

```php
// In a service provider or helper
View::composer('*', function ($view) {
    $view->with('dayOffTypeColors', app(TeamleaderSDK::class)->dayOffTypes()->getCommonColors());
});
```

## Notes

- Day off types are account-wide and affect all users
- Deleting a day off type may affect existing time entries
- Color codes must be in 6-digit hex format (#RRGGBB)
- Date validity allows for seasonal or time-limited day off types
- Names should be unique and descriptive
- The API doesn't support filtering, sorting, or pagination for day off types
- Updates and deletions return 204 No Content on success
- Always validate data before sending to avoid API errors

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
