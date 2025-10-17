# Mail Templates

Manage mail templates in Teamleader Focus.

## Overview

The Mail Templates resource provides read-only access to email templates configured in Teamleader Focus. These templates are used for sending invoices, quotations, work orders, and credit notes. Templates are configured in the Teamleader interface and can be filtered by department and document type.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Template Types](#template-types)
- [Filters](#filters)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`mailTemplates`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported (required)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (read-only)
- **Update**: ❌ Not Supported (read-only)
- **Deletion**: ❌ Not Supported (read-only)

## Available Methods

### `list()`

Get mail templates with required filtering by type.

**Required Filters:**
- `type` (string): Template type (invoice, quotation, work_order, credit_note)

**Optional Filters:**
- `department_id` (string): Filter by department UUID

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get invoice templates
$templates = Teamleader::mailTemplates()->list([
    'type' => 'invoice'
]);

// Get templates for specific department
$templates = Teamleader::mailTemplates()->list([
    'type' => 'quotation',
    'department_id' => 'department-uuid'
]);
```

## Helper Methods

### Type-based Methods

```php
// Get templates by type
$invoiceTemplates = Teamleader::mailTemplates()->forType('invoice');

$quotationTemplates = Teamleader::mailTemplates()->forType('quotation');

// With department filter
$templates = Teamleader::mailTemplates()->forType('invoice', 'department-uuid');
```

### Find by Name

```php
// Find template by name
$template = Teamleader::mailTemplates()->findByName(
    'Send link in english',
    'invoice'
);
```

### Dropdown Options

```php
// Get templates as key-value pairs for dropdowns
$options = Teamleader::mailTemplates()->asOptions('invoice');

// Returns: ['uuid1' => 'Default Invoice', 'uuid2' => 'Custom Invoice', ...]
```

### Specific Type Helpers

```php
// Get templates for specific types
$invoiceTemplates = Teamleader::mailTemplates()->invoiceTemplates();
$quotationTemplates = Teamleader::mailTemplates()->quotationTemplates();
$workOrderTemplates = Teamleader::mailTemplates()->workOrderTemplates();
$creditNoteTemplates = Teamleader::mailTemplates()->creditNoteTemplates();

// With department
$templates = Teamleader::mailTemplates()->invoiceTemplates('department-uuid');
```

## Template Types

Valid mail template types:

| Type | Description |
|------|-------------|
| `invoice` | Invoice email templates |
| `quotation` | Quotation email templates |
| `work_order` | Work order email templates |
| `credit_note` | Credit note email templates |

## Filters

Available filters for the `list()` method:

| Filter | Type | Required | Description |
|--------|------|----------|-------------|
| `type` | string | Yes | Template type |
| `department_id` | string | No | Filter by department UUID |

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'template-uuid-1',
            'name' => 'Default Invoice Template',
            'language' => 'en',
            'department' => [
                'type' => 'department',
                'id' => 'department-uuid'
            ]
        ],
        [
            'id' => 'template-uuid-2',
            'name' => 'Invoice Template - Dutch',
            'language' => 'nl',
            'department' => [
                'type' => 'department',
                'id' => 'department-uuid'
            ]
        ]
    ]
]
```

## Usage Examples

### Get All Invoice Templates

```php
// Get all invoice templates
$templates = Teamleader::mailTemplates()->forType('invoice');

foreach ($templates['data'] as $template) {
    echo "{$template['name']} ({$template['language']})<br>";
}
```

### Get Templates for Department

```php
// Get quotation templates for specific department
$templates = Teamleader::mailTemplates()->forType(
    'quotation',
    'department-uuid'
);
```

### Find Template by Name

```php
// Find specific template
$template = Teamleader::mailTemplates()->findByName(
    'Default Invoice',
    'invoice'
);

if ($template) {
    $templateId = $template['id'];
    // Use template ID for sending emails
}
```

### Build Template Selector

```php
// Create dropdown for template selection
$options = Teamleader::mailTemplates()->asOptions('invoice');

echo '<select name="mail_template">';
echo '<option value="">Select template...</option>';

foreach ($options as $id => $name) {
    echo "<option value='{$id}'>{$name}</option>";
}

echo '</select>';
```

### Get Templates by Language

```php
// Get templates filtered by language
$allTemplates = Teamleader::mailTemplates()->forType('invoice');

$englishTemplates = array_filter($allTemplates['data'], function($t) {
    return $t['language'] === 'en';
});

$dutchTemplates = array_filter($allTemplates['data'], function($t) {
    return $t['language'] === 'nl';
});
```

### Multi-language Template Selection

```php
function getTemplateForLanguage(string $type, string $language): ?array
{
    $templates = Teamleader::mailTemplates()->forType($type);
    
    foreach ($templates['data'] as $template) {
        if ($template['language'] === $language) {
            return $template;
        }
    }
    
    // Fallback to first template if language not found
    return $templates['data'][0] ?? null;
}

// Usage
$template = getTemplateForLanguage('invoice', 'en');
```

## Common Use Cases

### 1. Template Selector Component

```php
class MailTemplateSelector
{
    private $templates;
    
    public function __construct(string $type, ?string $departmentId = null)
    {
        if ($departmentId) {
            $this->templates = Teamleader::mailTemplates()->forType($type, $departmentId);
        } else {
            $this->templates = Teamleader::mailTemplates()->forType($type);
        }
    }
    
    public function getOptions(): array
    {
        $options = [];
        
        foreach ($this->templates['data'] as $template) {
            $options[$template['id']] = $template['name'];
        }
        
        return $options;
    }
    
    public function getTemplateById(string $id): ?array
    {
        foreach ($this->templates['data'] as $template) {
            if ($template['id'] === $id) {
                return $template;
            }
        }
        
        return null;
    }
    
    public function getDefaultTemplate(): ?array
    {
        return $this->templates['data'][0] ?? null;
    }
}

// Usage
$selector = new MailTemplateSelector('invoice', 'department-uuid');
$options = $selector->getOptions();
```

### 2. Automatic Template Selection

```php
class TemplateResolver
{
    public function resolveTemplate(
        string $type,
        string $language,
        ?string $departmentId = null
    ): ?string {
        $templates = $departmentId
            ? Teamleader::mailTemplates()->forType($type, $departmentId)
            : Teamleader::mailTemplates()->forType($type);
        
        // First, try exact language match
        foreach ($templates['data'] as $template) {
            if ($template['language'] === $language) {
                return $template['id'];
            }
        }
        
        // Fallback to English
        foreach ($templates['data'] as $template) {
            if ($template['language'] === 'en') {
                return $template['id'];
            }
        }
        
        // Last resort: return first available
        return $templates['data'][0]['id'] ?? null;
    }
}

// Usage
$resolver = new TemplateResolver();
$templateId = $resolver->resolveTemplate('invoice', 'nl', 'department-uuid');
```

### 3. Template Cache

```php
class TemplateCache
{
    private static $cache = [];
    
    public static function getTemplates(string $type, ?string $departmentId = null): array
    {
        $key = $type . '_' . ($departmentId ?? 'all');
        
        if (!isset(self::$cache[$key])) {
            $templates = $departmentId
                ? Teamleader::mailTemplates()->forType($type, $departmentId)
                : Teamleader::mailTemplates()->forType($type);
                
            self::$cache[$key] = $templates['data'];
        }
        
        return self::$cache[$key];
    }
    
    public static function findTemplate(string $type, string $name): ?array
    {
        $templates = self::getTemplates($type);
        
        foreach ($templates as $template) {
            if ($template['name'] === $name) {
                return $template;
            }
        }
        
        return null;
    }
}

// Usage
$templates = TemplateCache::getTemplates('invoice');
$template = TemplateCache::findTemplate('invoice', 'Default Invoice');
```

### 4. Email Sending with Template Selection

```php
class InvoiceMailer
{
    public function sendInvoice(
        string $invoiceId,
        string $recipientEmail,
        ?string $templateId = null
    ): bool {
        // If no template specified, get default
        if (!$templateId) {
            $templates = Teamleader::mailTemplates()->invoiceTemplates();
            $templateId = $templates['data'][0]['id'] ?? null;
        }
        
        if (!$templateId) {
            throw new Exception('No mail template available');
        }
        
        // Use template ID to send invoice email
        // (Implementation depends on your email sending logic)
        
        return true;
    }
}
```

## Best Practices

### 1. Cache Template Data

```php
// Templates rarely change, cache them
class TemplateManager
{
    private static $templates = [];
    
    public static function getTemplates(string $type): array
    {
        if (!isset(self::$templates[$type])) {
            $result = Teamleader::mailTemplates()->forType($type);
            self::$templates[$type] = $result['data'];
        }
        
        return self::$templates[$type];
    }
}
```

### 2. Provide Fallback Templates

```php
function getTemplateWithFallback(string $type, string $preferredName): string
{
    $templates = Teamleader::mailTemplates()->forType($type);
    
    // Try to find preferred template
    foreach ($templates['data'] as $template) {
        if ($template['name'] === $preferredName) {
            return $template['id'];
        }
    }
    
    // Fallback to first available
    if (!empty($templates['data'])) {
        return $templates['data'][0]['id'];
    }
    
    throw new Exception("No {$type} templates available");
}
```

### 3. Validate Template Type

```php
function isValidTemplateType(string $type): bool
{
    $validTypes = ['invoice', 'quotation', 'work_order', 'credit_note'];
    return in_array($type, $validTypes);
}

// Usage
if (isValidTemplateType($requestedType)) {
    $templates = Teamleader::mailTemplates()->forType($requestedType);
}
```

### 4. Group Templates by Department

```php
function getTemplatesByDepartment(string $type): array
{
    $allTemplates = Teamleader::mailTemplates()->forType($type);
    $byDepartment = [];
    
    foreach ($allTemplates['data'] as $template) {
        $deptId = $template['department']['id'];
        
        if (!isset($byDepartment[$deptId])) {
            $byDepartment[$deptId] = [];
        }
        
        $byDepartment[$deptId][] = $template;
    }
    
    return $byDepartment;
}
```

### 5. Handle Missing Templates

```php
function getTemplateOrNull(string $type, ?string $departmentId = null): ?string
{
    try {
        $templates = $departmentId
            ? Teamleader::mailTemplates()->forType($type, $departmentId)
            : Teamleader::mailTemplates()->forType($type);
        
        return $templates['data'][0]['id'] ?? null;
        
    } catch (Exception $e) {
        Log::warning('Failed to fetch templates', [
            'type' => $type,
            'department' => $departmentId
        ]);
        
        return null;
    }
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Fetching templates
try {
    $templates = Teamleader::mailTemplates()->forType('invoice');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Invalid type parameter
        Log::error('Invalid template type');
    }
    
    // Provide default templates or handle gracefully
    $templates = ['data' => []];
}

// Finding template by name
try {
    $template = Teamleader::mailTemplates()->findByName('Custom Template', 'invoice');
    
    if (!$template) {
        // Template not found
        Log::warning('Template not found', ['name' => 'Custom Template']);
    }
} catch (TeamleaderException $e) {
    Log::error('Error fetching template', ['error' => $e->getMessage()]);
}

// Department-specific templates
try {
    $templates = Teamleader::mailTemplates()->forType('invoice', 'invalid-department-id');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        // Department not found
        Log::error('Department not found');
    }
}
```

## Important Notes

### 1. Read-Only Resource

Mail templates cannot be created, updated, or deleted via the API. They must be configured in the Teamleader Focus interface.

### 2. Type is Required

You must always specify a template type when listing templates. There is no way to get all templates across all types in a single call.

### 3. Department Filtering

Department filtering is optional but recommended if your organization uses multiple departments with different templates.

### 4. Language Support

Templates can have different language variants. Consider language when selecting templates for international customers.

## Template Type Details

### Invoice Templates

Used when sending invoices to customers.

```php
$templates = Teamleader::mailTemplates()->invoiceTemplates();
```

### Quotation Templates

Used when sending quotations/proposals to prospects.

```php
$templates = Teamleader::mailTemplates()->quotationTemplates();
```

### Work Order Templates

Used when sending work orders to customers or suppliers.

```php
$templates = Teamleader::mailTemplates()->workOrderTemplates();
```

### Credit Note Templates

Used when sending credit notes to customers.

```php
$templates = Teamleader::mailTemplates()->creditNoteTemplates();
```

## Related Resources

- [Invoices](../invoicing/invoices.md) - Send invoices using templates
- [Quotations](../deals/quotations.md) - Send quotations using templates
- [Credit Notes](../invoicing/creditnotes.md) - Send credit notes using templates
- [Departments](../general/departments.md) - Department information

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
