# Document Templates

Manage document template definitions in Teamleader Focus.

## Overview

The Document Templates resource provides read-only access to document template information in your Teamleader account. Document templates are used for generating formatted documents like invoices, quotations, and other business documents.

**Important:** This resource is read-only and requires both `department_id` and `document_type` filters. You cannot create, update, or delete document templates through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Document Types](#document-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`documentTemplates`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Required (department_id and document_type)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get document templates for a specific department and document type.

**Parameters:**
- `filters` (array): Required filters
    - `department_id` (string, required): Department UUID
    - `document_type` (string, required): Type of document
    - `status` (string, optional): Filter by status ('active', 'archived')

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get invoice templates for a department
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'invoice'
]);

// Get only active templates
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'invoice',
    'status' => 'active'
]);
```

## Helper Methods

### `forDepartmentAndType()`

Get templates for a specific department and document type.

```php
$templates = Teamleader::documentTemplates()->forDepartmentAndType(
    'department-uuid',
    'invoice'
);
```

### `forDepartment()`

Get templates for a specific department (must specify document_type separately).

```php
$invoiceTemplates = Teamleader::documentTemplates()->forDepartment(
    'department-uuid',
    'invoice'
);
```

### `forInvoices()`

Get invoice templates for a department.

```php
$templates = Teamleader::documentTemplates()->forInvoices('department-uuid');
```

### `forQuotations()`

Get quotation templates for a department.

```php
$templates = Teamleader::documentTemplates()->forQuotations('department-uuid');
```

### `forCreditNotes()`

Get credit note templates for a department.

```php
$templates = Teamleader::documentTemplates()->forCreditNotes('department-uuid');
```

## Document Types

Available document types:

- `invoice` - Invoice templates
- `quotation` - Quotation templates
- `credit_note` - Credit note templates

Get the list programmatically:

```php
$types = Teamleader::documentTemplates()->getAvailableDocumentTypes();
// Returns: ['invoice', 'quotation', 'credit_note']
```

## Response Structure

### Document Template Object

```php
[
    'id' => 'template-uuid',
    'name' => 'Standard Invoice Template',
    'type' => 'invoice',
    'department' => [
        'type' => 'department',
        'id' => 'department-uuid'
    ],
    'status' => 'active', // or 'archived'
    'language' => 'en',
    'default' => true // Indicates if this is the default template
]
```

## Usage Examples

### Get Invoice Templates

```php
$departmentId = 'department-uuid';

$templates = Teamleader::documentTemplates()->forInvoices($departmentId);

foreach ($templates['data'] as $template) {
    echo $template['name'] . ' (' . $template['language'] . ')' . PHP_EOL;
}
```

### Get Default Template

```php
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'department-uuid',
    'document_type' => 'invoice',
    'status' => 'active'
]);

// Find the default template
$defaultTemplate = null;
foreach ($templates['data'] as $template) {
    if ($template['default'] === true) {
        $defaultTemplate = $template;
        break;
    }
}
```

### Get Templates for All Document Types

```php
$departmentId = 'department-uuid';
$documentTypes = ['invoice', 'quotation', 'credit_note'];

$allTemplates = [];
foreach ($documentTypes as $type) {
    $allTemplates[$type] = Teamleader::documentTemplates()->list([
        'department_id' => $departmentId,
        'document_type' => $type
    ]);
}
```

### Get Templates by Language

```php
function getTemplatesByLanguage($departmentId, $documentType, $language)
{
    $templates = Teamleader::documentTemplates()->list([
        'department_id' => $departmentId,
        'document_type' => $documentType
    ]);
    
    $filtered = [];
    foreach ($templates['data'] as $template) {
        if ($template['language'] === $language) {
            $filtered[] = $template;
        }
    }
    
    return $filtered;
}

$dutchInvoiceTemplates = getTemplatesByLanguage('dept-uuid', 'invoice', 'nl');
```

## Common Use Cases

### Template Selector for Invoices

```php
class TemplateSelector
{
    public function getInvoiceTemplatesForDropdown($departmentId)
    {
        $templates = Teamleader::documentTemplates()->forInvoices($departmentId);
        
        $options = [];
        foreach ($templates['data'] as $template) {
            if ($template['status'] === 'active') {
                $options[$template['id']] = $template['name'] . ' (' . strtoupper($template['language']) . ')';
            }
        }
        
        return $options;
    }
    
    public function getDefaultTemplate($departmentId, $documentType)
    {
        $templates = Teamleader::documentTemplates()->list([
            'department_id' => $departmentId,
            'document_type' => $documentType
        ]);
        
        foreach ($templates['data'] as $template) {
            if ($template['default'] === true && $template['status'] === 'active') {
                return $template;
            }
        }
        
        return $templates['data'][0] ?? null;
    }
}
```

### Cache Document Templates

```php
use Illuminate\Support\Facades\Cache;

class DocumentTemplateService
{
    public function getTemplates($departmentId, $documentType)
    {
        $cacheKey = "doc_templates.{$departmentId}.{$documentType}";
        
        return Cache::remember($cacheKey, 7200, function() use ($departmentId, $documentType) {
            return Teamleader::documentTemplates()->list([
                'department_id' => $departmentId,
                'document_type' => $documentType,
                'status' => 'active'
            ]);
        });
    }
}
```

### Multi-Language Template Selection

```php
class MultiLanguageTemplateSelector
{
    public function getTemplateForCustomer($departmentId, $documentType, $customerLanguage)
    {
        $templates = Teamleader::documentTemplates()->list([
            'department_id' => $departmentId,
            'document_type' => $documentType,
            'status' => 'active'
        ]);
        
        // Try to find template in customer's language
        foreach ($templates['data'] as $template) {
            if ($template['language'] === $customerLanguage) {
                return $template;
            }
        }
        
        // Fallback to default template
        foreach ($templates['data'] as $template) {
            if ($template['default'] === true) {
                return $template;
            }
        }
        
        // Last resort: first active template
        return $templates['data'][0] ?? null;
    }
}
```

### Template Availability Check

```php
class TemplateValidator
{
    public function hasActiveTemplates($departmentId, $documentType)
    {
        $templates = Teamleader::documentTemplates()->list([
            'department_id' => $departmentId,
            'document_type' => $documentType,
            'status' => 'active'
        ]);
        
        return !empty($templates['data']);
    }
    
    public function validateTemplateId($templateId, $departmentId, $documentType)
    {
        $templates = Teamleader::documentTemplates()->list([
            'department_id' => $departmentId,
            'document_type' => $documentType
        ]);
        
        foreach ($templates['data'] as $template) {
            if ($template['id'] === $templateId && $template['status'] === 'active') {
                return true;
            }
        }
        
        return false;
    }
}
```

### Sync Templates to Local Database

```php
use App\Models\DocumentTemplate;
use Illuminate\Console\Command;

class SyncDocumentTemplatesCommand extends Command
{
    protected $signature = 'teamleader:sync-templates {department?}';
    
    public function handle()
    {
        $departments = $this->getDepartments();
        $documentTypes = ['invoice', 'quotation', 'credit_note'];
        
        foreach ($departments as $dept) {
            foreach ($documentTypes as $type) {
                $this->info("Syncing {$type} templates for {$dept['name']}...");
                
                $templates = Teamleader::documentTemplates()->list([
                    'department_id' => $dept['id'],
                    'document_type' => $type
                ]);
                
                foreach ($templates['data'] as $template) {
                    DocumentTemplate::updateOrCreate(
                        ['teamleader_id' => $template['id']],
                        [
                            'name' => $template['name'],
                            'type' => $template['type'],
                            'department_id' => $dept['id'],
                            'language' => $template['language'],
                            'status' => $template['status'],
                            'is_default' => $template['default']
                        ]
                    );
                }
            }
        }
        
        $this->info('Document templates synced successfully!');
    }
    
    private function getDepartments()
    {
        // Get departments to sync
        return Teamleader::departments()->active()['data'];
    }
}
```

## Best Practices

### 1. Always Cache Templates

```php
// Good: Cache templates
$templates = Cache::remember('templates_dept_invoice', 7200, function() {
    return Teamleader::documentTemplates()->forInvoices($deptId);
});

// Bad: Fetch every time
$templates = Teamleader::documentTemplates()->forInvoices($deptId);
```

### 2. Use Helper Methods

```php
// Good: Clear and readable
$templates = Teamleader::documentTemplates()->forInvoices($deptId);

// Less ideal: Manual filtering
$templates = Teamleader::documentTemplates()->list([
    'department_id' => $deptId,
    'document_type' => 'invoice'
]);
```

### 3. Handle Missing Templates

```php
// Good: Check for templates
$templates = Teamleader::documentTemplates()->forInvoices($deptId);

if (empty($templates['data'])) {
    throw new \Exception('No invoice templates configured for this department');
}

// Bad: Assume templates exist
$template = $templates['data'][0];
```

### 4. Filter by Status

```php
// Good: Only get active templates
$templates = Teamleader::documentTemplates()->list([
    'department_id' => $deptId,
    'document_type' => 'invoice',
    'status' => 'active'
]);

// Bad: Include archived templates
$templates = Teamleader::documentTemplates()->forInvoices($deptId);
```

### 5. Respect Default Templates

```php
// Good: Use default when available
$templates = Teamleader::documentTemplates()->forInvoices($deptId);

foreach ($templates['data'] as $template) {
    if ($template['default'] && $template['status'] === 'active') {
        return $template['id'];
    }
}

// Fallback to first active
foreach ($templates['data'] as $template) {
    if ($template['status'] === 'active') {
        return $template['id'];
    }
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $templates = Teamleader::documentTemplates()->list([
        'department_id' => $departmentId,
        'document_type' => 'invoice'
    ]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 400) {
        // Missing required filters
        Log::error('Missing required filters', [
            'department_id' => $departmentId ?? 'missing'
        ]);
    } else {
        Log::error('Error fetching templates', [
            'error' => $e->getMessage()
        ]);
    }
    
    // Provide fallback
    $templates = ['data' => []];
}
```

## Required Filters

Both `department_id` and `document_type` are **required**:

```php
// ✅ Correct
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'dept-uuid',
    'document_type' => 'invoice'
]);

// ❌ Will fail - missing required filters
$templates = Teamleader::documentTemplates()->list([
    'document_type' => 'invoice'
]);

// ❌ Will fail - missing required filters
$templates = Teamleader::documentTemplates()->list([
    'department_id' => 'dept-uuid'
]);
```

## Related Resources

- [Departments](departments.md) - Templates are department-specific
- [Invoices](../invoicing/invoices.md) - Use invoice templates
- [Quotations](../deals/quotations.md) - Use quotation templates

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
