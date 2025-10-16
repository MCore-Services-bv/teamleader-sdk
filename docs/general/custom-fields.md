# Custom Fields

Manage custom field definitions in Teamleader Focus.

## Overview

The Custom Fields resource provides read-only access to custom field definitions in your Teamleader account. Custom fields allow you to extend standard Teamleader objects (contacts, companies, deals, etc.) with your own data fields.

**Important:** The Custom Fields resource is read-only. You cannot create, update, or delete custom field definitions through the API. Custom fields must be managed through the Teamleader interface.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Available Contexts](#available-contexts)
- [Field Types](#field-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`customFieldDefinitions`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get all custom field definitions with optional filtering.

**Parameters:**
- `filters` (array): Filters to apply

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all custom fields
$customFields = Teamleader::customFields()->list();

// Get custom fields for contacts
$contactFields = Teamleader::customFields()->list([
    'context' => 'contact'
]);

// Get custom fields by type
$selectFields = Teamleader::customFields()->list([
    'type' => 'single_select'
]);
```

### `info()`

Get detailed information about a specific custom field definition.

**Parameters:**
- `id` (string): Custom field UUID

**Example:**
```php
// Get custom field definition
$field = Teamleader::customFields()->info('field-uuid');

// Access field properties
$fieldName = $field['data']['label'];
$fieldType = $field['data']['type'];
$fieldContext = $field['data']['configuration']['context'];
```

## Helper Methods

The Custom Fields resource provides convenient helper methods for common operations:

### Context-Specific Methods

```php
// Get custom fields for contacts
$fields = Teamleader::customFields()->forContacts();

// Get custom fields for companies
$fields = Teamleader::customFields()->forCompanies();

// Get custom fields for deals
$fields = Teamleader::customFields()->forDeals();

// Get custom fields for projects
$fields = Teamleader::customFields()->forProjects();

// Get custom fields for invoices
$fields = Teamleader::customFields()->forInvoices();

// Get custom fields for products
$fields = Teamleader::customFields()->forProducts();

// Get custom fields for milestones
$fields = Teamleader::customFields()->forMilestones();

// Get custom fields for tickets
$fields = Teamleader::customFields()->forTickets();
```

### Type-Specific Methods

```php
// Get custom fields by type
$selectFields = Teamleader::customFields()->byType('single_select');
$textFields = Teamleader::customFields()->byType('single_line');
$dateFields = Teamleader::customFields()->byType('date');
```

### ID-Based Methods

```php
// Get specific custom fields by their UUIDs
$fields = Teamleader::customFields()->byIds([
    'field-uuid-1',
    'field-uuid-2'
]);
```

## Filters

### Available Filters

#### `ids`
Filter by specific custom field UUIDs.

```php
$fields = Teamleader::customFields()->list([
    'ids' => ['field-uuid-1', 'field-uuid-2']
]);
```

#### `context`
Filter by the resource context where the custom field is used.

**Values:** `contact`, `company`, `deal`, `project`, `invoice`, `product`, `milestone`, `ticket`

```php
// Get contact custom fields
$fields = Teamleader::customFields()->list([
    'context' => 'contact'
]);

// Get deal custom fields
$fields = Teamleader::customFields()->list([
    'context' => 'deal'
]);
```

#### `type`
Filter by custom field type.

**Values:** `single_line`, `multi_line`, `single_select`, `multi_select`, `checkbox`, `date`, `number`, `auto_number`

```php
// Get all dropdown fields
$fields = Teamleader::customFields()->list([
    'type' => 'single_select'
]);

// Get all date fields
$fields = Teamleader::customFields()->list([
    'type' => 'date'
]);
```

## Available Contexts

The SDK provides helper methods to get the list of available contexts:

```php
$contexts = Teamleader::customFields()->getAvailableContexts();

// Returns:
// [
//     'contact' => 'Contact custom fields',
//     'company' => 'Company custom fields',
//     'deal' => 'Deal custom fields',
//     'project' => 'Project custom fields',
//     'invoice' => 'Invoice custom fields',
//     'product' => 'Product custom fields',
//     'milestone' => 'Milestone custom fields',
//     'ticket' => 'Ticket custom fields'
// ]
```

## Field Types

Get available field types:

```php
$types = Teamleader::customFields()->getAvailableTypes();

// Returns:
// [
//     'single_line' => 'Single line text field',
//     'multi_line' => 'Multi-line text field',
//     'single_select' => 'Single selection dropdown',
//     'multi_select' => 'Multiple selection field',
//     'checkbox' => 'Checkbox field',
//     'date' => 'Date field',
//     'number' => 'Number field',
//     'auto_number' => 'Auto-incrementing number field'
// ]
```

### Check Field Type Capabilities

```php
// Check if a field type has options (for dropdowns)
$hasOptions = Teamleader::customFields()->typeHasOptions('single_select'); // true
$hasOptions = Teamleader::customFields()->typeHasOptions('single_line'); // false
```

## Response Structure

### Custom Field Object

```php
[
    'id' => 'field-uuid',
    'label' => 'Industry',
    'type' => 'single_select',
    'configuration' => [
        'context' => 'company',
        'required' => false,
        'options' => [
            ['value' => 'tech', 'label' => 'Technology'],
            ['value' => 'retail', 'label' => 'Retail'],
            ['value' => 'finance', 'label' => 'Finance']
        ]
    ],
    'group' => [
        'id' => 'group-uuid',
        'label' => 'Company Details'
    ]
]
```

## Usage Examples

### Get All Custom Fields

```php
$allFields = Teamleader::customFields()->list();

foreach ($allFields['data'] as $field) {
    echo $field['label'] . ' (' . $field['type'] . ')' . PHP_EOL;
}
```

### Get Custom Fields for a Specific Context

```php
// Using filter
$contactFields = Teamleader::customFields()->list([
    'context' => 'contact'
]);

// Using helper method (recommended)
$contactFields = Teamleader::customFields()->forContacts();
```

### Build Dynamic Forms

```php
class DynamicFormBuilder
{
    public function buildContactForm()
    {
        $customFields = Teamleader::customFields()->forContacts();
        
        $formFields = [];
        foreach ($customFields['data'] as $field) {
            $formFields[] = [
                'name' => 'custom_' . $field['id'],
                'label' => $field['label'],
                'type' => $this->mapFieldType($field['type']),
                'required' => $field['configuration']['required'] ?? false,
                'options' => $field['configuration']['options'] ?? null
            ];
        }
        
        return $formFields;
    }
    
    private function mapFieldType($teamleaderType)
    {
        return match($teamleaderType) {
            'single_line' => 'text',
            'multi_line' => 'textarea',
            'single_select' => 'select',
            'multi_select' => 'multiselect',
            'checkbox' => 'checkbox',
            'date' => 'date',
            'number' => 'number',
            default => 'text'
        };
    }
}
```

### Get Dropdown Options

```php
$fields = Teamleader::customFields()->forCompanies();

foreach ($fields['data'] as $field) {
    if ($field['type'] === 'single_select') {
        echo "Field: " . $field['label'] . PHP_EOL;
        echo "Options:" . PHP_EOL;
        
        foreach ($field['configuration']['options'] as $option) {
            echo "  - " . $option['label'] . PHP_EOL;
        }
    }
}
```

### Filter by Multiple Criteria

```php
// Get all dropdown fields for deals
$dealDropdowns = Teamleader::customFields()->list([
    'context' => 'deal',
    'type' => 'single_select'
]);
```

## Common Use Cases

### Validate Custom Field Values

```php
class CustomFieldValidator
{
    public function validate($fieldId, $value)
    {
        $field = Teamleader::customFields()->info($fieldId);
        $fieldData = $field['data'];
        
        // Check required
        if ($fieldData['configuration']['required'] && empty($value)) {
            throw new \Exception("Field {$fieldData['label']} is required");
        }
        
        // Validate type
        switch ($fieldData['type']) {
            case 'number':
                if (!is_numeric($value)) {
                    throw new \Exception("Field {$fieldData['label']} must be numeric");
                }
                break;
                
            case 'single_select':
                $validOptions = array_column($fieldData['configuration']['options'], 'value');
                if (!in_array($value, $validOptions)) {
                    throw new \Exception("Invalid option for {$fieldData['label']}");
                }
                break;
                
            case 'date':
                if (!strtotime($value)) {
                    throw new \Exception("Field {$fieldData['label']} must be a valid date");
                }
                break;
        }
        
        return true;
    }
}
```

### Cache Custom Fields

```php
use Illuminate\Support\Facades\Cache;

class CustomFieldService
{
    public function getFieldsForContext(string $context)
    {
        $cacheKey = "custom_fields.{$context}";
        
        return Cache::remember($cacheKey, 7200, function() use ($context) {
            return Teamleader::customFields()->forContext($context);
        });
    }
    
    public function getFieldById(string $fieldId)
    {
        $cacheKey = "custom_field.{$fieldId}";
        
        return Cache::remember($cacheKey, 7200, function() use ($fieldId) {
            return Teamleader::customFields()->info($fieldId);
        });
    }
}
```

### Generate Form HTML

```php
class CustomFieldRenderer
{
    public function renderField($field, $value = null)
    {
        $html = '<div class="form-group">';
        $html .= '<label>' . htmlspecialchars($field['label']);
        
        if ($field['configuration']['required']) {
            $html .= ' <span class="required">*</span>';
        }
        
        $html .= '</label>';
        
        switch ($field['type']) {
            case 'single_line':
                $html .= '<input type="text" name="custom_' . $field['id'] . '" 
                         value="' . htmlspecialchars($value ?? '') . '" class="form-control">';
                break;
                
            case 'multi_line':
                $html .= '<textarea name="custom_' . $field['id'] . '" 
                         class="form-control">' . htmlspecialchars($value ?? '') . '</textarea>';
                break;
                
            case 'single_select':
                $html .= '<select name="custom_' . $field['id'] . '" class="form-control">';
                $html .= '<option value="">-- Select --</option>';
                foreach ($field['configuration']['options'] as $option) {
                    $selected = ($value === $option['value']) ? 'selected' : '';
                    $html .= '<option value="' . $option['value'] . '" ' . $selected . '>' 
                           . htmlspecialchars($option['label']) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'checkbox':
                $checked = $value ? 'checked' : '';
                $html .= '<input type="checkbox" name="custom_' . $field['id'] . '" ' . $checked . '>';
                break;
                
            case 'date':
                $html .= '<input type="date" name="custom_' . $field['id'] . '" 
                         value="' . htmlspecialchars($value ?? '') . '" class="form-control">';
                break;
                
            case 'number':
                $html .= '<input type="number" name="custom_' . $field['id'] . '" 
                         value="' . htmlspecialchars($value ?? '') . '" class="form-control">';
                break;
        }
        
        $html .= '</div>';
        return $html;
    }
}
```

### Sync Custom Fields to Local Database

```php
use Illuminate\Console\Command;

class SyncCustomFieldsCommand extends Command
{
    protected $signature = 'teamleader:sync-custom-fields';
    
    public function handle()
    {
        $contexts = ['contact', 'company', 'deal', 'project', 'invoice'];
        
        foreach ($contexts as $context) {
            $this->info("Syncing {$context} custom fields...");
            
            $fields = Teamleader::customFields()->forContext($context);
            
            foreach ($fields['data'] as $fieldData) {
                \App\Models\CustomField::updateOrCreate(
                    ['teamleader_id' => $fieldData['id']],
                    [
                        'label' => $fieldData['label'],
                        'type' => $fieldData['type'],
                        'context' => $context,
                        'required' => $fieldData['configuration']['required'] ?? false,
                        'options' => json_encode($fieldData['configuration']['options'] ?? []),
                        'group_label' => $fieldData['group']['label'] ?? null,
                    ]
                );
            }
        }
        
        $this->info('Custom fields synced successfully!');
    }
}
```

## Best Practices

### 1. Cache Custom Field Definitions

Custom fields rarely change, so cache them aggressively:

```php
// Good: Cache for 2 hours
$fields = Cache::remember('custom_fields.contact', 7200, function() {
    return Teamleader::customFields()->forContacts();
});

// Bad: Fetching on every request
$fields = Teamleader::customFields()->forContacts();
```

### 2. Use Helper Methods

```php
// Good: Clear and readable
$contactFields = Teamleader::customFields()->forContacts();

// Less ideal: Manual filtering
$contactFields = Teamleader::customFields()->list(['context' => 'contact']);
```

### 3. Validate Against Field Definitions

Always validate user input against custom field definitions:

```php
$field = Teamleader::customFields()->info($fieldId);

if ($field['data']['type'] === 'single_select') {
    $validOptions = array_column($field['data']['configuration']['options'], 'value');
    if (!in_array($userInput, $validOptions)) {
        // Invalid value
    }
}
```

### 4. Group Fields by Context

When displaying forms, group fields by their context:

```php
$fieldsByContext = [];

$allFields = Teamleader::customFields()->list();

foreach ($allFields['data'] as $field) {
    $context = $field['configuration']['context'];
    $fieldsByContext[$context][] = $field;
}
```

### 5. Handle Missing Field Gracefully

```php
try {
    $field = Teamleader::customFields()->info($fieldId);
} catch (TeamleaderException $e) {
    // Field might have been deleted
    Log::warning("Custom field not found: {$fieldId}");
    return null;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $fields = Teamleader::customFields()->forContacts();
} catch (TeamleaderException $e) {
    Log::error('Error fetching custom fields', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    $fields = ['data' => []];
}
```

## Working with Custom Field Values

While this resource manages field _definitions_, you'll set custom field values when creating or updating resources:

```php
// Get custom field definition
$customFields = Teamleader::customFields()->forContacts();
$industryField = $customFields['data'][0]; // Assume first field is "Industry"

// Create contact with custom field value
$contact = Teamleader::contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'custom_fields' => [
        [
            'id' => $industryField['id'],
            'value' => 'technology'
        ]
    ]
]);
```

## Related Resources

- [Contacts](../crm/contacts.md) - Use custom fields with contacts
- [Companies](../crm/companies.md) - Use custom fields with companies
- [Deals](../deals/deals.md) - Use custom fields with deals
- [Projects](../projects/projects.md) - Use custom fields with projects
- [Invoices](../invoicing/invoices.md) - Use custom fields with invoices

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
