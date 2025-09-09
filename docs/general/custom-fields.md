# Custom Fields

Manage custom field definitions in Teamleader Focus. This resource provides read-only access to custom field configurations from your Teamleader account.

## Endpoint

`customFields`

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

Get a list of custom fields with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Additional options (not used for custom fields)

**Example:**
```php
$customFields = $teamleader->customFields()->list(['context' => 'contact']);
```

### `info()`

Get detailed information about a specific custom field.

**Parameters:**
- `id` (string): Custom field UUID
- `includes` (array|string): Relations to include (not supported for custom fields)

**Example:**
```php
$customField = $teamleader->customFields()->info('field-uuid-here');
```

### Context-Specific Methods

#### `forContacts()`
Get custom fields for contacts.

**Example:**
```php
$contactFields = $teamleader->customFields()->forContacts();
```

#### `forCompanies()`
Get custom fields for companies.

**Example:**
```php
$companyFields = $teamleader->customFields()->forCompanies();
```

#### `forDeals()`
Get custom fields for deals.

**Example:**
```php
$dealFields = $teamleader->customFields()->forDeals();
```

#### `forProjects()`
Get custom fields for projects.

**Example:**
```php
$projectFields = $teamleader->customFields()->forProjects();
```

#### `forInvoices()`
Get custom fields for invoices.

**Example:**
```php
$invoiceFields = $teamleader->customFields()->forInvoices();
```

#### `forProducts()`
Get custom fields for products.

**Example:**
```php
$productFields = $teamleader->customFields()->forProducts();
```

#### `forMilestones()`
Get custom fields for milestones.

**Example:**
```php
$milestoneFields = $teamleader->customFields()->forMilestones();
```

#### `forTickets()`
Get custom fields for tickets.

**Example:**
```php
$ticketFields = $teamleader->customFields()->forTickets();
```

### Type-Specific Methods

#### `byType()`
Get custom fields by their type.

**Parameters:**
- `type` (string): The field type

**Example:**
```php
$selectFields = $teamleader->customFields()->byType('single_select');
```

#### `byIds()`
Get specific custom fields by their UUIDs.

**Parameters:**
- `ids` (array): Array of custom field UUIDs

**Example:**
```php
$fields = $teamleader->customFields()->byIds(['uuid1', 'uuid2']);
```

## Filtering

### Available Filters

- **`ids`**: Array of custom field UUIDs to filter by
- **`context`**: Filter by context (contact, company, deal, project, invoice, product, milestone, ticket)
- **`type`**: Filter by field type (single_line, multi_line, single_select, multi_select, checkbox, date, number, auto_number)

### Filter Examples

```php
// Filter by context
$contactFields = $teamleader->customFields()->list([
    'context' => 'contact'
]);

// Filter by type
$selectFields = $teamleader->customFields()->list([
    'type' => 'single_select'
]);

// Filter by specific IDs
$specificFields = $teamleader->customFields()->list([
    'ids' => [
        'cf1-uuid-here',
        'cf2-uuid-here'
    ]
]);

// Combine filters
$filteredFields = $teamleader->customFields()->list([
    'context' => 'deal',
    'type' => 'single_select'
]);
```

## Available Contexts

- **`contact`**: Contact custom fields
- **`company`**: Company custom fields
- **`deal`**: Deal custom fields
- **`project`**: Project custom fields
- **`invoice`**: Invoice custom fields
- **`product`**: Product custom fields
- **`milestone`**: Milestone custom fields
- **`ticket`**: Ticket custom fields

## Available Field Types

- **`single_line`**: Single line text field
- **`multi_line`**: Multi-line text field
- **`single_select`**: Single selection dropdown
- **`multi_select`**: Multiple selection field
- **`checkbox`**: Checkbox field
- **`date`**: Date field
- **`number`**: Number field
- **`auto_number`**: Auto-incrementing number field

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "cf1-uuid-here",
            "context": "contact",
            "label": "Industry",
            "key": "industry",
            "type": "single_select",
            "group": "Additional Information",
            "required": false,
            "configuration": {
                "options": [
                    {
                        "value": "technology",
                        "label": "Technology"
                    },
                    {
                        "value": "finance",
                        "label": "Finance"
                    }
                ]
            }
        }
    ]
}
```

### Single Custom Field Response

```json
{
    "data": {
        "id": "cf1-uuid-here",
        "context": "contact",
        "label": "Industry",
        "key": "industry",
        "type": "single_select",
        "group": "Additional Information",
        "required": false,
        "configuration": {
            "options": [
                {
                    "value": "technology",
                    "label": "Technology"
                },
                {
                    "value": "finance",
                    "label": "Finance"
                },
                {
                    "value": "healthcare",
                    "label": "Healthcare"
                }
            ]
        }
    }
}
```

## Data Fields

### Common Fields

- **`id`**: Custom field UUID
- **`context`**: The context where this field applies (contact, company, deal, etc.)
- **`label`**: Display label for the field
- **`key`**: Unique key identifier for the field
- **`type`**: Field type (single_line, multi_line, single_select, etc.)
- **`group`**: The group this field belongs to in the UI
- **`required`**: Boolean indicating if the field is required
- **`configuration`**: Field-specific configuration object

### Configuration Object

The configuration object varies by field type:

#### For Select Fields (single_select, multi_select)
```json
{
    "options": [
        {
            "value": "option_key",
            "label": "Display Label"
        }
    ]
}
```

#### For Number Fields
```json
{
    "decimal_places": 2,
    "minimum": 0,
    "maximum": 1000000
}
```

#### For Auto Number Fields
```json
{
    "prefix": "INV-",
    "next_number": 1001
}
```

## Usage Examples

### Basic List

Get all custom fields:

```php
$customFields = $teamleader->customFields()->list();
```

### Context-Specific Fields

Get custom fields for different contexts:

```php
// Contact fields
$contactFields = $teamleader->customFields()->forContacts();

// Deal fields
$dealFields = $teamleader->customFields()->forDeals();

// Company fields
$companyFields = $teamleader->customFields()->forCompanies();
```

### Type-Specific Fields

Get fields by their type:

```php
// All dropdown fields
$selectFields = $teamleader->customFields()->byType('single_select');

// All text fields
$textFields = $teamleader->customFields()->byType('single_line');

// All date fields
$dateFields = $teamleader->customFields()->byType('date');
```

### Complex Filtering

Combine multiple filters:

```php
$filteredFields = $teamleader->customFields()->list([
    'context' => 'contact',
    'type' => 'single_select'
]);
```

### Get Single Field

Retrieve detailed information for a specific field:

```php
$field = $teamleader->customFields()->info('cf1-uuid-here');

// Access field properties
$label = $field['data']['label'];
$type = $field['data']['type'];
$options = $field['data']['configuration']['options'] ?? [];
```

### Working with Field Options

For select fields, work with the available options:

```php
$selectFields = $teamleader->customFields()->byType('single_select');

foreach ($selectFields['data'] as $field) {
    if (isset($field['configuration']['options'])) {
        echo "Field: " . $field['label'] . "\n";
        foreach ($field['configuration']['options'] as $option) {
            echo "  - " . $option['label'] . " (" . $option['value'] . ")\n";
        }
    }
}
```

## Error Handling

The custom fields resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->customFields()->list();

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Custom Fields API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Custom fields API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **Convenience methods**: 1 request per call

Rate limit cost: **1 request per method call**

## Important Notes

- **Context Naming**: The API uses `'sale'` for deal custom fields, not `'deal'` as might be expected
- **Money Fields**: The `'money'` field type is supported and returns currency values
- **Select Field Configuration**: Select fields include detailed configuration with options, each having an `id` and `value`
- **External Costs**: The `'pro_external_cost'` context relates to order/external cost functionality
- **Option Structure**: Single select fields have an `options` array where each option has both `id` and `value` properties
- **Groups**: Custom fields can be organized into groups (like "Webfresh" or "MM Lease" in examples)
- **Configuration Object**: The configuration structure varies by field type:
    - Select fields: `options`, `extra_option_allowed`, `default_value`
    - Other field types may have different configuration properties

## Data Structure Details

### Select Field Configuration

```json
{
    "configuration": {
        "options": [
            {
                "id": "7362150a-dc64-0150-b760-1b38ab1a9ab0",
                "value": "Belgie"
            }
        ],
        "extra_option_allowed": false,
        "default_value": null
    }
}
```

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CustomFieldController extends Controller
{
    public function index(TeamleaderSDK $teamleader, Request $request)
    {
        $context = $request->get('context');
        
        if ($context) {
            $customFields = $teamleader->customFields()->forContext($context);
        } else {
            $customFields = $teamleader->customFields()->list();
        }
        
        return view('custom-fields.index', compact('customFields', 'context'));
    }
    
    public function show(TeamleaderSDK $teamleader, string $id)
    {
        $customField = $teamleader->customFields()->info($id);
        
        return view('custom-fields.show', compact('customField'));
    }
    
    public function forContext(TeamleaderSDK $teamleader, string $context)
    {
        // Validate context
        $availableContexts = $teamleader->customFields()->getAvailableContexts();
        
        if (!array_key_exists($context, $availableContexts)) {
            abort(404, 'Invalid context');
        }
        
        $customFields = $teamleader->customFields()->forContext($context);
        
        return response()->json($customFields);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
