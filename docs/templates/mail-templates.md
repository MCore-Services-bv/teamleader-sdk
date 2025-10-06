# Mail Templates

Manage mail templates in Teamleader Focus. This resource provides read-only access to email templates used for invoices, quotations, work orders, and credit notes.

## Endpoint

`mailTemplates`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported (department_id and type are required)
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Important Notes

Mail templates are **read-only** via the API. Both `department_id` and `type` are **required** parameters for all queries.

## Available Filters

- `department_id` (string, required): The UUID of the department
- `type` (string, required): The template type - must be one of:
    - `invoice`
    - `quotation`
    - `work_order`
    - `credit_note`

## Available Methods

### `list()`

Get a list of mail templates for a specific department and type.

**Parameters:**
- `filters` (array): Filters to apply (department_id and type are required)
- `options` (array): Pagination options

**Example:**
```php
$templates = $teamleader->mailTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'type' => 'invoice'
]);
```

### `forDepartment()`

Get mail templates for a specific department and type (helper method).

**Parameters:**
- `departmentId` (string): Department UUID
- `type` (string): Template type (invoice, quotation, work_order, credit_note)
- `options` (array): Optional pagination options

**Example:**
```php
$templates = $teamleader->mailTemplates()->forDepartment(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    'invoice'
);
```

### `byType()`

Alias for `forDepartment()` - get templates by type.

**Parameters:**
- `departmentId` (string): Department UUID
- `type` (string): Template type
- `options` (array): Optional pagination options

**Example:**
```php
$templates = $teamleader->mailTemplates()->byType(
    'dept-uuid',
    'quotation'
);
```

### `allForDepartment()`

Get templates for all types in a department.

**Parameters:**
- `departmentId` (string): Department UUID

**Returns:** Associative array with template types as keys

**Example:**
```php
$allTemplates = $teamleader->mailTemplates()->allForDepartment('dept-uuid');

// Returns:
// [
//     'invoice' => [...invoice templates...],
//     'quotation' => [...quotation templates...],
//     'work_order' => [...work order templates...],
//     'credit_note' => [...credit note templates...]
// ]
```

### `findByName()`

Find a specific mail template by name.

**Parameters:**
- `departmentId` (string): Department UUID
- `type` (string): Template type
- `name` (string): Template name to search for

**Returns:** Template array or null if not found

**Example:**
```php
$template = $teamleader->mailTemplates()->findByName(
    'dept-uuid',
    'invoice',
    'Send link in english'
);
```

### Type-Specific Helper Methods

Convenience methods for each template type:

#### `invoiceTemplates()`
Get invoice templates for a department.

```php
$invoiceTemplates = $teamleader->mailTemplates()->invoiceTemplates('dept-uuid');
```

#### `quotationTemplates()`
Get quotation templates for a department.

```php
$quotationTemplates = $teamleader->mailTemplates()->quotationTemplates('dept-uuid');
```

#### `workOrderTemplates()`
Get work order templates for a department.

```php
$workOrderTemplates = $teamleader->mailTemplates()->workOrderTemplates('dept-uuid');
```

#### `creditNoteTemplates()`
Get credit note templates for a department.

```php
$creditNoteTemplates = $teamleader->mailTemplates()->creditNoteTemplates('dept-uuid');
```

### `getValidTypes()`

Get all available template types.

**Returns:** Array of valid template type strings

**Example:**
```php
$types = $teamleader->mailTemplates()->getValidTypes();
// Returns: ['invoice', 'quotation', 'work_order', 'credit_note']
```

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ],
            'type' => 'invoice',
            'name' => 'Send link in english',
            'content' => [
                'subject' => 'Link for document',
                'body' => '#LINK \n<link> Thank you for using our services'
            ],
            'language' => 'en'
        ]
    ]
]
```

### Field Descriptions

- `id` (string): Unique identifier for the mail template
- `department` (object, nullable): Reference to the department this template belongs to
    - `id` (string): Department UUID
    - `type` (string): Resource type (always "department")
- `type` (string): The document type this template is for (invoice, quotation, work_order, credit_note)
- `name` (string): Display name of the template
- `content` (object): The email content
    - `subject` (string): Email subject line
    - `body` (string): Email body content (may contain placeholders like #LINK)
- `language` (string): Language code for the template (e.g., "en", "nl", "fr")

## Usage Examples

### Basic Usage

```php
// Get all invoice templates for a department
$templates = $teamleader->mailTemplates()->list([
    'department_id' => 'a344c251-2494-0013-b433-ccee8e8435e5',
    'type' => 'invoice'
]);

// Or using the helper method
$templates = $teamleader->mailTemplates()->forDepartment(
    'a344c251-2494-0013-b433-ccee8e8435e5',
    'invoice'
);
```

### With Pagination

```php
$templates = $teamleader->mailTemplates()->list([
    'department_id' => 'dept-uuid',
    'type' => 'quotation'
], [
    'page_size' => 50,
    'page_number' => 1
]);
```

### Find Specific Template

```php
$template = $teamleader->mailTemplates()->findByName(
    'dept-uuid',
    'invoice',
    'Send link in english'
);

if ($template) {
    echo "Template subject: " . $template['content']['subject'];
    echo "Template body: " . $template['content']['body'];
}
```

### Get All Templates for a Department

```php
$allTemplates = $teamleader->mailTemplates()->allForDepartment('dept-uuid');

foreach ($allTemplates as $type => $templates) {
    if (!isset($templates['error'])) {
        echo "Found " . count($templates['data']) . " {$type} templates\n";
    }
}
```

### Using Type-Specific Helpers

```php
// Get invoice templates
$invoiceTemplates = $teamleader->mailTemplates()->invoiceTemplates('dept-uuid');

// Get quotation templates
$quotationTemplates = $teamleader->mailTemplates()->quotationTemplates('dept-uuid');

// Get work order templates
$workOrderTemplates = $teamleader->mailTemplates()->workOrderTemplates('dept-uuid');

// Get credit note templates
$creditNoteTemplates = $teamleader->mailTemplates()->creditNoteTemplates('dept-uuid');
```

## Error Handling

```php
use InvalidArgumentException;

try {
    // Missing required parameter
    $templates = $teamleader->mailTemplates()->list([
        'type' => 'invoice'
        // Missing department_id!
    ]);
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
    // "department_id is required for mail templates"
}

try {
    // Invalid type
    $templates = $teamleader->mailTemplates()->forDepartment(
        'dept-uuid',
        'invalid_type'
    );
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
    // "Invalid template type: invalid_type. Must be one of: invoice, quotation, work_order, credit_note"
}
```

## Template Placeholders

Mail template bodies may contain placeholders that are replaced by Teamleader when sending emails. Common placeholders include:

- `#LINK` - Link to the document
- Other placeholders specific to document types (refer to Teamleader documentation)

Example template body:
```
#LINK 
<link> Thank you for using our services
```

## Common Use Cases

### Building a Template Selector

```php
// Get all templates for dropdown/selector
$departmentId = 'dept-uuid';
$type = 'invoice';

$templates = $teamleader->mailTemplates()->forDepartment($departmentId, $type);

if (isset($templates['data'])) {
    foreach ($templates['data'] as $template) {
        echo sprintf(
            '<option value="%s">%s (%s)</option>',
            $template['id'],
            $template['name'],
            $template['language']
        );
    }
}
```

### Validating Template Availability

```php
// Check if a specific template exists
$departmentId = 'dept-uuid';
$templateName = 'Default Invoice Email';

$template = $teamleader->mailTemplates()->findByName(
    $departmentId,
    'invoice',
    $templateName
);

if (!$template) {
    // Template doesn't exist - create fallback or notify admin
    echo "Template '{$templateName}' not found";
}
```

## Notes

- Mail templates are configured in the Teamleader Focus UI
- The API provides read-only access to these templates
- Templates are specific to departments and document types
- You cannot create, update, or delete templates via the API
- Both `department_id` and `type` parameters are always required
