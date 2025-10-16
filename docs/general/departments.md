# Departments

Manage departments in Teamleader Focus.

## Overview

The Departments resource provides read-only access to department information in your Teamleader account. Departments are used to organize your team and can be assigned to various resources like deals, invoices, and projects.

**Important:** The Departments resource is read-only. You cannot create, update, or delete departments through the API. Departments must be managed through the Teamleader interface.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`departments`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of departments with optional filtering and sorting.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Sorting and pagination options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all departments
$departments = Teamleader::departments()->list();

// Get active departments only
$activeDepartments = Teamleader::departments()->list([
    'status' => ['active']
]);

// Get departments sorted by name
$departments = Teamleader::departments()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);
```

### `info()`

Get detailed information about a specific department.

**Parameters:**
- `id` (string): Department UUID

**Example:**
```php
// Get department information
$department = Teamleader::departments()->info('department-uuid');

// Access department properties
$name = $department['data']['name'];
$status = $department['data']['status'];
$isDefault = $department['data']['default'];
```

## Helper Methods

The Departments resource provides convenient helper methods for common operations:

### `active()`

Get only active departments.

```php
$activeDepartments = Teamleader::departments()->active();
```

### `archived()`

Get only archived departments.

```php
$archivedDepartments = Teamleader::departments()->archived();
```

### `byIds()`

Get specific departments by their UUIDs.

```php
$departments = Teamleader::departments()->byIds([
    'department-uuid-1',
    'department-uuid-2',
    'department-uuid-3'
]);
```

## Filters

### Available Filters

#### `ids`
Filter by specific department UUIDs.

```php
$departments = Teamleader::departments()->list([
    'ids' => ['department-uuid-1', 'department-uuid-2']
]);
```

#### `status`
Filter by department status. Must be an array.

**Values:** `active`, `archived`

```php
// Active departments only
$departments = Teamleader::departments()->list([
    'status' => ['active']
]);

// Archived departments only
$departments = Teamleader::departments()->list([
    'status' => ['archived']
]);

// Both active and archived
$departments = Teamleader::departments()->list([
    'status' => ['active', 'archived']
]);
```

## Sorting

### Available Sort Fields

- `name` - Sort by department name
- `created_at` - Sort by department creation date
- `default_department` - When sorting ascending, default departments are listed first

### Sort Examples

```php
// Sort by name (ascending)
$departments = Teamleader::departments()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);

// Sort by creation date (descending)
$departments = Teamleader::departments()->list([], [
    'sort' => [['field' => 'created_at', 'order' => 'desc']]
]);

// Sort by default first, then by name
$departments = Teamleader::departments()->list([], [
    'sort' => [
        ['field' => 'default_department', 'order' => 'asc'],
        ['field' => 'name', 'order' => 'asc']
    ]
]);

// Get available sort fields
$sortFields = Teamleader::departments()->getAvailableSortFields();
```

## Response Structure

### Department Object

```php
[
    'id' => 'department-uuid',
    'name' => 'Sales Department',
    'status' => 'active',
    'default' => false,
    'currency' => 'EUR',
    'vat_number' => 'BE0123456789',
    'email_address' => 'sales@company.com',
    'created_at' => '2024-01-01T10:00:00+00:00'
]
```

## Usage Examples

### Get All Active Departments

```php
// Using helper method
$activeDepartments = Teamleader::departments()->active();

// Or with list()
$activeDepartments = Teamleader::departments()->list([
    'status' => ['active']
]);
```

### Build a Department Dropdown

```php
$departments = Teamleader::departments()->active();

$dropdown = [];
foreach ($departments['data'] as $department) {
    $dropdown[$department['id']] = $department['name'];
}

// Use in Blade template
<select name="department_id">
    @foreach($dropdown as $id => $name)
        <option value="{{ $id }}">{{ $name }}</option>
    @endforeach
</select>
```

### Find Default Department

```php
$departments = Teamleader::departments()->active();

$defaultDepartment = null;
foreach ($departments['data'] as $department) {
    if ($department['default'] === true) {
        $defaultDepartment = $department;
        break;
    }
}

if ($defaultDepartment) {
    echo "Default department: " . $defaultDepartment['name'];
}
```

### Get Department by Name

```php
function findDepartmentByName($name)
{
    $departments = Teamleader::departments()->active();
    
    foreach ($departments['data'] as $department) {
        if (strcasecmp($department['name'], $name) === 0) {
            return $department;
        }
    }
    
    return null;
}

$salesDept = findDepartmentByName('Sales');
```

### Paginate Through Departments

```php
$allDepartments = [];
$page = 1;
$pageSize = 100;

do {
    $response = Teamleader::departments()->list([], [
        'page_size' => $pageSize,
        'page_number' => $page
    ]);
    
    $allDepartments = array_merge($allDepartments, $response['data']);
    $hasMore = count($response['data']) === $pageSize;
    $page++;
    
} while ($hasMore);
```

## Common Use Cases

### Department Selection in Forms

```php
class DealController extends Controller
{
    public function create()
    {
        $departments = Teamleader::departments()
            ->active()
            ->list([], [
                'sort' => [['field' => 'name', 'order' => 'asc']]
            ]);
        
        return view('deals.create', [
            'departments' => $departments['data']
        ]);
    }
}
```

### Cache Departments

```php
use Illuminate\Support\Facades\Cache;

class DepartmentService
{
    public function getActiveDepartments()
    {
        return Cache::remember('active_departments', 3600, function() {
            return Teamleader::departments()->active();
        });
    }
    
    public function getDepartmentById($departmentId)
    {
        $cacheKey = "department.{$departmentId}";
        
        return Cache::remember($cacheKey, 3600, function() use ($departmentId) {
            return Teamleader::departments()->info($departmentId);
        });
    }
    
    public function clearCache()
    {
        Cache::forget('active_departments');
    }
}
```

### Sync Departments to Local Database

```php
use App\Models\Department;
use Illuminate\Console\Command;

class SyncDepartmentsCommand extends Command
{
    protected $signature = 'teamleader:sync-departments';
    
    public function handle()
    {
        $this->info('Syncing departments...');
        
        $departments = Teamleader::departments()->list([
            'status' => ['active']
        ]);
        
        foreach ($departments['data'] as $deptData) {
            Department::updateOrCreate(
                ['teamleader_id' => $deptData['id']],
                [
                    'name' => $deptData['name'],
                    'status' => $deptData['status'],
                    'is_default' => $deptData['default'],
                    'currency' => $deptData['currency'],
                    'vat_number' => $deptData['vat_number'] ?? null,
                    'email' => $deptData['email_address'] ?? null,
                ]
            );
        }
        
        $this->info('Departments synced successfully!');
    }
}
```

### Department-Based Authorization

```php
class DepartmentPolicy
{
    public function viewDepartmentData(User $user, $departmentId)
    {
        // Get user's assigned department
        $userDepartment = $user->teamleader_department_id;
        
        // Admins can view all departments
        if ($user->is_admin) {
            return true;
        }
        
        // Users can only view their own department
        return $userDepartment === $departmentId;
    }
}
```

### Department Statistics Dashboard

```php
class DepartmentDashboard
{
    public function getStatistics()
    {
        $departments = Teamleader::departments()->active();
        
        $stats = [];
        foreach ($departments['data'] as $department) {
            $stats[$department['id']] = [
                'name' => $department['name'],
                'deals_count' => $this->getDealsCount($department['id']),
                'total_revenue' => $this->getTotalRevenue($department['id']),
                'active_projects' => $this->getActiveProjects($department['id'])
            ];
        }
        
        return $stats;
    }
    
    private function getDealsCount($departmentId)
    {
        // Implementation using deals resource
        $deals = Teamleader::deals()->list([
            'department_id' => $departmentId
        ]);
        
        return count($deals['data']);
    }
}
```

### Validate Department Access

```php
class DepartmentValidator
{
    public function isValidDepartment($departmentId): bool
    {
        try {
            $department = Teamleader::departments()->info($departmentId);
            return $department['data']['status'] === 'active';
        } catch (TeamleaderException $e) {
            return false;
        }
    }
    
    public function getUserAllowedDepartments(User $user): array
    {
        $allDepartments = Teamleader::departments()->active();
        
        if ($user->is_admin) {
            return $allDepartments['data'];
        }
        
        // Filter to user's assigned departments
        return array_filter($allDepartments['data'], function($dept) use ($user) {
            return in_array($dept['id'], $user->allowed_department_ids ?? []);
        });
    }
}
```

## Best Practices

### 1. Cache Department Data

Departments change infrequently, so cache them:

```php
// Good: Cache for 1 hour
$departments = Cache::remember('active_departments', 3600, function() {
    return Teamleader::departments()->active();
});

// Bad: Fetching on every request
$departments = Teamleader::departments()->active();
```

### 2. Use Helper Methods

```php
// Good: Clear and readable
$activeDepartments = Teamleader::departments()->active();

// Less ideal: Manual filtering
$activeDepartments = Teamleader::departments()->list([
    'status' => ['active']
]);
```

### 3. Sort Results for User-Facing Lists

```php
// Good: Sorted alphabetically
$departments = Teamleader::departments()->list(
    ['status' => ['active']],
    ['sort' => [['field' => 'name', 'order' => 'asc']]]
);

// Bad: Unsorted (inconsistent order)
$departments = Teamleader::departments()->active();
```

### 4. Handle Missing Departments

```php
try {
    $department = Teamleader::departments()->info($departmentId);
} catch (TeamleaderException $e) {
    // Department might have been archived or deleted
    Log::warning("Department not found: {$departmentId}");
    return null;
}
```

### 5. Include Default Department First

```php
// When building dropdowns, show default department first
$departments = Teamleader::departments()->list(
    ['status' => ['active']],
    ['sort' => [
        ['field' => 'default_department', 'order' => 'asc'],
        ['field' => 'name', 'order' => 'asc']
    ]]
);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $department = Teamleader::departments()->info($departmentId);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        // Department not found
        return response()->json(['error' => 'Department not found'], 404);
    }
    
    // Other error
    Log::error('Error fetching department', [
        'department_id' => $departmentId,
        'error' => $e->getMessage()
    ]);
    
    throw $e;
}
```

## Department Assignment

When working with other resources, you can assign departments:

```php
// Create a deal with a department
$deal = Teamleader::deals()->create([
    'title' => 'New Sales Opportunity',
    'department' => [
        'type' => 'department',
        'id' => 'department-uuid'
    ],
    // ... other fields
]);

// Create an invoice with a department
$invoice = Teamleader::invoices()->create([
    'department' => [
        'type' => 'department',
        'id' => 'department-uuid'
    ],
    // ... other fields
]);
```

## Related Resources

- [Users](users.md) - Users are often associated with departments
- [Deals](../deals/deals.md) - Deals can be assigned to departments
- [Invoices](../invoicing/invoices.md) - Invoices are created under departments
- [Projects](../projects/projects.md) - Projects can be assigned to departments

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
- [Teams](teams.md) - Related organizational structure
