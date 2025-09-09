# Document Templates

Manage document templates in Teamleader Focus. This resource provides read-only access to document template information for specific departments and document types.

## Endpoint

`documentTemplates`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get document templates with required filtering by department and document type.

**Parameters:**
- `filters` (array): Array of filters to apply (department_id and document_type are **required**)
- `options` (array): Additional options (not used for this endpoint)

**Example:**
```php
$templates = $teamleader->documentTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'document_type' => 'invoice'
]);
```

### `byType()`

Get document templates by document type for a specific department.

**Parameters:**
- `departmentId` (string): Department UUID
- `documentType` (string): Document type
- `additionalFilters` (array): Additional filters like status

**Example:**
```php
$invoiceTemplates = $teamleader->documentTemplates()->byType(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    'invoice'
);
```

### `activeForDepartment()`

Get only active document templates for a specific department and type.

**Parameters:**
- `departmentId` (string): Department UUID
- `documentType` (string): Document type

**Example:**
```php
$activeTemplates = $teamleader->documentTemplates()->activeForDepartment(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    'quotation'
);
```

### `archivedForDepartment()`

Get only archived document templates for a specific department and type.

**Parameters:**
- `departmentId` (string): Department UUID
- `documentType` (string): Document type

**Example:**
```php
$archivedTemplates = $teamleader->documentTemplates()->archivedForDepartment(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    'invoice'
);
```

### `allForDepartment()`

Get all document templates for a department across multiple document types. **Note:** This makes multiple API calls.

**Parameters:**
- `departmentId` (string): Department UUID
- `documentTypes` (array): Array of document types to fetch (optional, defaults to all types)

**Example:**
```php
$allTemplates = $teamleader->documentTemplates()->allForDepartment(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    ['invoice', 'quotation', 'order']
);
```

## Required Parameters

⚠️ **Important**: The Teamleader API requires both `department_id` and `document_type` for all document template requests.

- **`department_id`**: UUID of the department (required)
- **`document_type`**: Type of document template (required)

## Filtering

### Available Filters

- **`department_id`**: Department UUID (**required**)
- **`document_type`**: Type of document template (**required**)
- **`status`**: Filter by template status (active, archived)

### Filter Examples

```php
// Basic required filters
$templates = $teamleader->documentTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'document_type' => 'invoice'
]);

// Filter by status (active only)
$activeTemplates = $teamleader->documentTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'document_type' => 'invoice',
    'status' => ['active']
]);

// Filter by multiple statuses
$allTemplates = $teamleader->documentTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'document_type' => 'quotation',
    'status' => ['active', 'archived']
]);
```

## Document Types

### Available Document Types

- **`delivery_note`**: Delivery Note
- **`invoice`**: Invoice
- **`order`**: Order
- **`order_confirmation`**: Order Confirmation
- **`quotation`**: Quotation
- **`timetracking_report`**: Time Tracking Report
- **`workorder`**: Work Order

### Getting Available Types

```php
$documentTypes = $teamleader->documentTemplates()->getAvailableDocumentTypes();
// Returns: ['delivery_note', 'invoice', 'order', ...]

$displayNames = $teamleader->documentTemplates()->getDocumentTypeDisplayNames();
// Returns: ['delivery_note' => 'Delivery Note', 'invoice' => 'Invoice', ...]
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "a344c251-2494-0013-b433-ccee8e8435e5",
            "department": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "department"
            },
            "document_type": "invoice",
            "is_default": true,
            "name": "new logo",
            "status": "active"
        }
    ]
}
```

## Data Fields

### Template Fields

- **`id`**: Template UUID
- **`department`**: Department reference object with id and type
- **`document_type`**: Type of document (delivery_note, invoice, order, etc.)
- **`is_default`**: Whether this template is the default for this document type
- **`name`**: Template name/description
- **`status`**: Template status ("active" or "archived")

## Usage Examples

### Get Invoice Templates for Department

```php
$invoiceTemplates = $teamleader->documentTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'document_type' => 'invoice'
]);

foreach ($invoiceTemplates['data'] as $template) {
    echo "Template: {$template['name']} ({$template['status']})\n";
    if ($template['is_default']) {
        echo "This is the default template\n";
    }
}
```

### Get Active Templates Only

```php
$activeQuotationTemplates = $teamleader->documentTemplates()->activeForDepartment(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    'quotation'
);
```

### Get All Templates for a Department

```php
// Get templates for specific document types
$selectedTypes = ['invoice', 'quotation', 'order'];
$allTemplates = $teamleader->documentTemplates()->allForDepartment(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    $selectedTypes
);

echo "Found {$allTemplates['meta']['total_templates']} templates\n";
```

### Find Default Template

```php
$templates = $teamleader->documentTemplates()->byType(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    'invoice'
);

$defaultTemplate = collect($templates['data'])
    ->firstWhere('is_default', true);

if ($defaultTemplate) {
    echo "Default invoice template: {$defaultTemplate['name']}\n";
}
```

### Working with Multiple Departments

```php
$departments = ['dept-uuid-1', 'dept-uuid-2', 'dept-uuid-3'];
$documentType = 'invoice';

foreach ($departments as $deptId) {
    try {
        $templates = $teamleader->documentTemplates()->activeForDepartment($deptId, $documentType);
        echo "Department {$deptId} has " . count($templates['data']) . " active invoice templates\n";
    } catch (Exception $e) {
        echo "Error fetching templates for department {$deptId}: {$e->getMessage()}\n";
    }
}
```

## Error Handling

Document templates require both `department_id` and `document_type` parameters:

```php
try {
    // This will throw an InvalidArgumentException
    $templates = $teamleader->documentTemplates()->list([
        'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5'
        // Missing required document_type
    ]);
} catch (InvalidArgumentException $e) {
    echo "Missing required parameter: {$e->getMessage()}\n";
}

// Proper usage
$templates = $teamleader->documentTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'document_type' => 'invoice'
]);

if (isset($templates['error']) && $templates['error']) {
    $errorMessage = $templates['message'] ?? 'Unknown error';
    Log::error("Document Templates API error: {$errorMessage}");
}
```

## Rate Limiting

Document template API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Convenience methods**: 1 request per call
- **`allForDepartment()`**: Multiple requests (1 per document type)

Rate limit cost: **1 request per method call** (except `allForDepartment()`)

## Helper Methods

### Get Available Options

```php
// Get all supported document types
$types = $teamleader->documentTemplates()->getAvailableDocumentTypes();

// Get display names for UI
$displayNames = $teamleader->documentTemplates()->getDocumentTypeDisplayNames();

// Get available statuses
$statuses = $teamleader->documentTemplates()->getAvailableStatuses();
```

## Notes

- Document templates are **read-only** in the Teamleader API
- No create, update, or delete operations are supported
- Both `department_id` and `document_type` are **required** for all requests
- Templates don't support sideloading/includes
- No pagination is available for this endpoint
- Use the `is_default` field to identify the default template for each document type
- The `allForDepartment()` method makes multiple API calls and should be used carefully with rate limits

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DocumentTemplateController extends Controller
{
    public function index(TeamleaderSDK $teamleader, Request $request)
    {
        $departmentId = $request->get('department_id');
        $documentType = $request->get('document_type', 'invoice');
        
        if (!$departmentId) {
            return redirect()->back()
                ->with('error', 'Please select a department first.');
        }
        
        $templates = $teamleader->documentTemplates()->byType(
            $departmentId,
            $documentType
        );
        
        return view('document-templates.index', compact('templates', 'departmentId', 'documentType'));
    }
    
    public function getByType(TeamleaderSDK $teamleader, string $departmentId, string $type)
    {
        $templates = $teamleader->documentTemplates()->activeForDepartment($departmentId, $type);
        
        return response()->json($templates);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
