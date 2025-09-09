# Closing Days

Manage closing days in Teamleader Focus. Closing days are typically used to mark holidays, company closure dates, or other non-working days in your organization.

## Endpoint

`closingDays`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of closing days with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination options

**Example:**
```php
$closingDays = $teamleader->closingDays()->list([
    'date_after' => '2023-12-01',
    'date_before' => '2023-12-31'
]);
```

### `create()` / `add()`

Add a new closing day to the account.

**Parameters:**
- `data` (array): Array containing 'day' field, or
- `day` (string): Date in YYYY-MM-DD format (for `add()` method)

**Example:**
```php
// Using create method
$result = $teamleader->closingDays()->create(['day' => '2024-02-01']);

// Using add method (convenience)
$result = $teamleader->closingDays()->add('2024-02-01');
```

### `delete()`

Remove a closing day from the account.

**Parameters:**
- `id` (string): Closing day UUID

**Example:**
```php
$result = $teamleader->closingDays()->delete('eb264fd0-0e5c-0dbf-ae1e-49e7d6a8e6b8');
```

### Convenience Methods

#### `forMonth()`

Get closing days for a specific month.

**Parameters:**
- `yearMonth` (string): Month in YYYY-MM format

**Example:**
```php
$closingDays = $teamleader->closingDays()->forMonth('2024-02');
```

#### `forYear()`

Get closing days for a specific year.

**Parameters:**
- `year` (int|string): 4-digit year

**Example:**
```php
$closingDays = $teamleader->closingDays()->forYear(2024);
```

#### `forDateRange()`

Get closing days within a specific date range.

**Parameters:**
- `startDate` (string): Start date in YYYY-MM-DD format
- `endDate` (string): End date in YYYY-MM-DD format

**Example:**
```php
$closingDays = $teamleader->closingDays()->forDateRange('2024-01-01', '2024-01-31');
```

#### `upcoming()`

Get upcoming closing days from today forward.

**Parameters:**
- `daysAhead` (int): Number of days to look ahead (default: 30)

**Example:**
```php
$upcomingClosingDays = $teamleader->closingDays()->upcoming(60); // Next 60 days
```

#### `isClosingDay()`

Check if a specific date is a closing day.

**Parameters:**
- `date` (string): Date in YYYY-MM-DD format

**Example:**
```php
$isClosed = $teamleader->closingDays()->isClosingDay('2024-12-25'); // Returns bool
```

#### `bulkAdd()`

Add multiple closing days at once.

**Parameters:**
- `dates` (array): Array of dates in YYYY-MM-DD format

**Example:**
```php
$results = $teamleader->closingDays()->bulkAdd([
    '2024-12-25',
    '2024-12-26',
    '2024-01-01'
]);
```

## Filtering

### Available Filters

- **`date_before`**: End of the period for which to return closing days (inclusive)
- **`date_after`**: Start of the period for which to return closing days (inclusive)

### Filter Examples

```php
// Get closing days in December 2023
$decemberClosingDays = $teamleader->closingDays()->list([
    'date_after' => '2023-12-01',
    'date_before' => '2023-12-31'
]);

// Get closing days from today onwards
$futureClosingDays = $teamleader->closingDays()->list([
    'date_after' => date('Y-m-d')
]);

// Get closing days before a specific date
$pastClosingDays = $teamleader->closingDays()->list([
    'date_before' => '2023-12-31'
]);
```

## Pagination

Closing days support pagination for large datasets:

```php
// Get first page with 10 items
$closingDays = $teamleader->closingDays()->list([], [
    'page_size' => 10,
    'page_number' => 1,
    'include_pagination' => true
]);

// Access pagination metadata
$pagination = $closingDays['meta']['page'] ?? null;
if ($pagination) {
    echo "Page {$pagination['number']} of " . ceil($closingDays['meta']['matches'] / $pagination['size']);
}
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "05676ac4-c61d-42bf-a3ea-a420fc1ec017",
            "date": "2023-12-21"
        },
        {
            "id": "eb264fd0-0e5c-0dbf-ae1e-49e7d6a8e6b8",
            "date": "2023-12-25"
        }
    ],
    "meta": {
        "page": {
            "size": 20,
            "number": 1
        },
        "matches": 2
    }
}
```

### Create Response

```json
{
    "data": {
        "type": "closingDay",
        "id": "eb264fd0-0e5c-0dbf-ae1e-49e7d6a8e6b8"
    }
}
```

### Delete Response

Returns HTTP 204 (No Content) on successful deletion.

## Data Fields

- **`id`**: Closing day UUID (read-only)
- **`date`**: The closing day date in YYYY-MM-DD format
- **`type`**: Resource type, always "closingDay" (in create responses)

## Usage Examples

### Basic Operations

```php
// List all closing days
$allClosingDays = $teamleader->closingDays()->list();

// Add a closing day
$newClosingDay = $teamleader->closingDays()->add('2024-07-21');

// Delete a closing day
$teamleader->closingDays()->delete($newClosingDay['data']['id']);
```

### Date Range Queries

```php
// Get closing days for current month
$currentMonth = date('Y-m');
$thisMonthClosingDays = $teamleader->closingDays()->forMonth($currentMonth);

// Get closing days for next year
$nextYear = date('Y') + 1;
$nextYearClosingDays = $teamleader->closingDays()->forYear($nextYear);

// Get closing days for Q1 2024
$q1ClosingDays = $teamleader->closingDays()->forDateRange('2024-01-01', '2024-03-31');
```

### Practical Applications

```php
// Check if today is a closing day
$isToday ClosingDay = $teamleader->closingDays()->isClosingDay(date('Y-m-d'));
if ($isTodayClosingDay) {
    echo "Office is closed today!";
}

// Get upcoming closing days for planning
$upcomingClosingDays = $teamleader->closingDays()->upcoming(90); // Next 3 months

// Add common holidays for the year
$holidays = [
    '2024-01-01', // New Year's Day
    '2024-04-01', // Easter Monday
    '2024-12-25', // Christmas Day
    '2024-12-26'  // Boxing Day
];

$results = $teamleader->closingDays()->bulkAdd($holidays);

// Check results
foreach ($results as $result) {
    if (isset($result['error'])) {
        echo "Failed to add {$result['date']}: {$result['message']}\n";
    } else {
        echo "Added closing day: {$result['data']['id']}\n";
    }
}
```

### Integration with Business Logic

```php
class WorkingDayCalculator
{
    private $teamleader;
    private $closingDaysCache = [];

    public function __construct(TeamleaderSDK $teamleader)
    {
        $this->teamleader = $teamleader;
    }

    public function getNextWorkingDay(string $date): string
    {
        $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
        
        // Skip weekends
        while (in_array(date('w', strtotime($nextDay)), [0, 6])) {
            $nextDay = date('Y-m-d', strtotime($nextDay . ' +1 day'));
        }
        
        // Skip closing days
        while ($this->isClosingDay($nextDay)) {
            $nextDay = date('Y-m-d', strtotime($nextDay . ' +1 day'));
            
            // Skip weekends again
            while (in_array(date('w', strtotime($nextDay)), [0, 6])) {
                $nextDay = date('Y-m-d', strtotime($nextDay . ' +1 day'));
            }
        }
        
        return $nextDay;
    }

    private function isClosingDay(string $date): bool
    {
        $month = date('Y-m', strtotime($date));
        
        if (!isset($this->closingDaysCache[$month])) {
            $closingDays = $this->teamleader->closingDays()->forMonth($month);
            $this->closingDaysCache[$month] = array_column($closingDays['data'] ?? [], 'date');
        }
        
        return in_array($date, $this->closingDaysCache[$month]);
    }
}
```

## Error Handling

The closing days resource follows the standard SDK error handling patterns:

```php
try {
    $result = $teamleader->closingDays()->add('2024-02-30'); // Invalid date
} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage();
}

// Check for API errors
$result = $teamleader->closingDays()->list();
if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    Log::error("Closing Days API error: {$errorMessage}");
}
```

## Rate Limiting

Closing days API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Create operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Bulk operations**: 1 request per item

Rate limit cost: **1 request per method call**

## Notes

- Closing days are account-wide and affect all departments
- Dates must be in **YYYY-MM-DD** format
- The `date_before` and `date_after` filters are **inclusive**
- Closing days don't support updates - delete and recreate if needed
- Use convenience methods like `forMonth()` and `upcoming()` for common use cases
- Consider caching closing days data for frequently accessed date ranges
- The resource includes validation for date formats and logical date ranges

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ClosingDaysController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $closingDays = $teamleader->closingDays()->upcoming();
        
        return view('closing-days.index', compact('closingDays'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $request->validate([
            'day' => 'required|date|date_format:Y-m-d'
        ]);
        
        $result = $teamleader->closingDays()->add($request->day);
        
        if (isset($result['error'])) {
            return back()->withErrors(['day' => $result['message']]);
        }
        
        return redirect()->route('closing-days.index')
            ->with('success', 'Closing day added successfully');
    }
    
    public function destroy(TeamleaderSDK $teamleader, string $id)
    {
        $result = $teamleader->closingDays()->delete($id);
        
        if (isset($result['error'])) {
            return back()->withErrors(['error' => $result['message']]);
        }
        
        return redirect()->route('closing-days.index')
            ->with('success', 'Closing day deleted successfully');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
