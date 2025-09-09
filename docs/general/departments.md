# Departments

Manage departments in Teamleader Focus. This resource provides read-only access to department information from your Teamleader account.

## Endpoint

`departments`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of departments with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting options

**Example:**
```php
$departments = $teamleader->departments()->list(['status' => ['active']]);
```

### `info()`

Get detailed information about a specific department.

**Parameters:**
- `id` (string): Department UUID
- `includes` (array|string): Relations to include (not supported for departments)

**Example:**
```php
$department = $teamleader->departments()->info('department-uuid-here');
```

### `active()`

Get only active departments.

**Example:**
```php
$activeDepartments = $teamleader->departments()->active();
```

### `archived()`

Get only archived departments.

**Example:**
```php
$archivedDepartments = $teamleader->departments()->archived();
```

### `byIds()`

Get specific departments by their UUIDs.

**Parameters:**
- `ids` (array): Array of department UUIDs

**Example:**
```php
$departments = $teamleader->departments()->byIds(['uuid1', 'uuid2']);
```

## Filtering

### Available Filters

- **`ids`**: Array of department UUIDs to filter by
- **`status`**: Filter by department status (active, archived)

### Filter Examples

```php
// Filter by status
$activeDepartments = $teamleader->departments()->list([
    'status' => ['active']
]);

// Filter by multiple statuses
$departments = $teamleader->departments()->list([
    'status' => ['active', 'archived']
]);

// Filter by specific IDs
$specificDepartments = $teamleader->departments()->list([
    'ids' => [
        '92296ad0-2d61-4179-b174-9f354ca2157f',
        '53635682-c382-4fbf-9fd9-9506ca4fbcdd'
    ]
]);

// Combine filters
$filteredDepartments = $teamleader->departments()->list([
    'status' => ['active'],
    'ids' => ['uuid1', 'uuid2']
]);
```

## Sorting

### Available Sort Fields

- **`name`**: Sorts by department name
- **`created_at`**: Sorts by department creation date
- **`default_department`**: When sorting ascending, default departments are listed first (sorting behavior only)

### Sorting Examples

```php
// Sort by name (ascending)
$departments = $teamleader->departments()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);

// Sort by creation date (descending)
$departments = $teamleader->departments()->list([], [
    'sort' => [
        [
            'field' => 'created_at',
            'order' => 'desc'
        ]
    ]
]);

// Multiple sort criteria
$departments = $teamleader->departments()->list([], [
    'sort' => [
        [
            'field' => 'default_department',
            'order' => 'asc'
        ],
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "67c576e7-7e6f-465d-b6ab-a864f6e5e95b",
            "name": "Human Resources",
            "vat_number": "BE0899623035",
            "currency": "EUR",
            "emails": [
                {
                    "type": "primary",
                    "email": "hr@company.com"
                }
            ],
            "status": "active"
        }
    ]
}
```

### Single Department Response

```json
{
    "data": {
        "id": "67c576e7-7e6f-465d-b6ab-a864f6e5e95b",
        "name": "Human Resources",
        "vat_number": "BE0899623035",
        "address": {
            "line_1": "Dok Noord 3A 101",
            "postal_code": "9000",
            "city": "Ghent",
            "country": "BE",
            "area_level_two": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "area_level_two"
            }
        },
        "emails": [
            {
                "type": "primary",
                "email": "info@piedpiper.eu"
            },
            {
                "type": "invoicing",
                "email": "invoicing@piedpiper.eu"
            }
        ],
        "telephones": [
            {
                "type": "phone",
                "number": "092980615"
            }
        ],
        "website": "https://piedpiper.com",
        "currency": "EUR",
        "iban": "BE12123412341234",
        "bic": "BICBANK",
        "fiscal_regime": "RF01",
        "status": "active"
    }
}
```

## Data Fields

### Common Fields (Available in list and info)

- **`id`**: Department UUID
- **`name`**: Department name
- **`vat_number`**: VAT registration number
- **`currency`**: Default currency (e.g., "EUR")
- **`emails`**: Array of email addresses with types
- **`status`**: Department status ("active" or "archived")

### Additional Fields (Available in info only)

- **`address`**: Complete address information including line_1, postal_code, city, country, and area_level_two
- **`telephones`**: Array of telephone numbers with types (phone, mobile, fax)
- **`website`**: Department website URL
- **`iban`**: Bank account IBAN
- **`bic`**: Bank identifier code
- **`fiscal_regime`**: Fiscal regime identifier

## Usage Examples

### Basic List

Get all departments with default settings:

```php
$departments = $teamleader->departments()->list();
```

### Filtered List

Get only active departments:

```php
$activeDepartments = $teamleader->departments()->list([
    'status' => ['active']
]);
```

### Sorted List

Get departments sorted by name:

```php
$sortedDepartments = $teamleader->departments()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);
```

### Complex Query

Get specific active departments sorted by name:

```php
$departments = $teamleader->departments()->list([
    'status' => ['active'],
    'ids' => [
        '92296ad0-2d61-4179-b174-9f354ca2157f',
        '53635682-c382-4fbf-9fd9-9506ca4fbcdd'
    ]
], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);
```

### Get Single Department

Retrieve detailed information for a specific department:

```php
$department = $teamleader->departments()->info('67c576e7-7e6f-465d-b6ab-a864f6e5e95b');

// Access specific fields
$name = $department['data']['name'];
$emails = $department['data']['emails'];
$address = $department['data']['address'];
```

### Convenience Methods

Use the built-in convenience methods for common operations:

```php
// Get all active departments
$activeDepartments = $teamleader->departments()->active();

// Get all archived departments
$archivedDepartments = $teamleader->departments()->archived();

// Get specific departments by ID
$specificDepartments = $teamleader->departments()->byIds([
    'uuid1',
    'uuid2'
]);
```

## Error Handling

The departments resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->departments()->list();

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Departments API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Departments API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **Convenience methods**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Departments are **read-only** in the Teamleader API
- No create, update, or delete operations are supported
- Departments don't support sideloading/includes
- The `default_department` field is only available for sorting, not as response data
- Always check the `status` field to distinguish between active and archived departments
- Email arrays may contain multiple emails with different types (`primary`, `invoicing`)
- Telephone arrays may contain different types (`phone`, `mobile`, `fax`)

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DepartmentController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $departments = $teamleader->departments()->active();
        
        return view('departments.index', compact('departments'));
    }
    
    public function show(TeamleaderSDK $teamleader, string $id)
    {
        $department = $teamleader->departments()->info($id);
        
        return view('departments.show', compact('department'));
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
