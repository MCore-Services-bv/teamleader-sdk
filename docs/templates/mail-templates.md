# Mail Templates

Read mail template definitions in Teamleader Focus.

## Overview

Mail templates are the email layouts used when sending invoices, quotations, work orders, and credit notes to customers. This resource is read-only — templates are configured in the Teamleader Focus web interface.

Access via `Teamleader::mailTemplates()`.

> **`list()` throws without `type`** — it is required, not optional. Passing a missing or invalid type throws `InvalidArgumentException` before the request.
>
> **No pagination.** `list()` returns all matching templates in a single response.

## Endpoint

`mailTemplates`

## Capabilities

| Capability | Supported |
|---|---|
| Pagination | ❌ Not supported |
| Filtering | ✅ Supported (`type` required, `department_id` optional) |
| Sorting | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation | ❌ Not supported |
| Update | ❌ Not supported |
| Deletion | ❌ Not supported |

---

## Methods

### `list(array $filters = [], array $options = [])`

`type` is required — throws `InvalidArgumentException` if absent or invalid.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All invoice templates
$templates = Teamleader::mailTemplates()->list(['type' => 'invoice']);

// Quotation templates for a specific department
$templates = Teamleader::mailTemplates()->list([
    'type'          => 'quotation',
    'department_id' => 'dept-uuid',
]);
```

---

## Helper Methods

### Type shortcuts

```php
Teamleader::mailTemplates()->forInvoices();
Teamleader::mailTemplates()->forQuotations();
Teamleader::mailTemplates()->forWorkOrders();
Teamleader::mailTemplates()->forCreditNotes();

// With department filter
Teamleader::mailTemplates()->forInvoices('dept-uuid');
```

### `forType(string $type, ?string $departmentId = null)`

Type validated before the request.

```php
$templates = Teamleader::mailTemplates()->forType('invoice');
$templates = Teamleader::mailTemplates()->forType('credit_note', 'dept-uuid');
```

### `findByName(string $name, string $type)`

Client-side — calls `list()` then searches in PHP. Returns the first match or `null`.

```php
$template = Teamleader::mailTemplates()->findByName('Send link in English', 'invoice');
```

### `asOptions(string $type)`

Returns `[id => name]` map for dropdowns.

```php
$options = Teamleader::mailTemplates()->asOptions('invoice');
// ['uuid-1' => 'Default Invoice', 'uuid-2' => 'Invoice – Dutch']
```

### `groupByLanguage(string $type)`

Returns templates grouped by language code.

```php
$grouped = Teamleader::mailTemplates()->groupByLanguage('invoice');
// ['en' => [...], 'nl' => [...]]
```

---

## Valid Types

| Type | Description |
|---|---|
| `invoice` | Invoice email templates |
| `quotation` | Quotation email templates |
| `work_order` | Work order email templates |
| `credit_note` | Credit note email templates |

---

## Response Structure

```php
[
    'data' => [
        [
            'id'         => 'template-uuid',
            'type'       => 'invoice',
            'name'       => 'Send link in English',
            'language'   => 'en',
            'department' => ['type' => 'department', 'id' => 'dept-uuid'],  // nullable
            'content'    => [
                'subject' => 'Your invoice #INVOICE_NUMBER',
                'body'    => '<p>Dear #CUSTOMER_NAME, ...</p>',
            ],
        ],
    ],
]
```

---

## Usage Example

```php
// Pick a template for sending an invoice
$template = Teamleader::mailTemplates()->findByName('Standard Invoice', 'invoice');

if (!$template) {
    $templates = Teamleader::mailTemplates()->forInvoices();
    $template  = $templates['data'][0]; // fall back to first available
}

$templateId = $template['id'];
```

---

## Error Handling

```php
use InvalidArgumentException;

// Missing type
try {
    Teamleader::mailTemplates()->list(['department_id' => 'dept-uuid']);
} catch (InvalidArgumentException $e) {
    // 'type is required for mail templates. Must be one of: invoice, quotation, work_order, credit_note'
}

// Invalid type
try {
    Teamleader::mailTemplates()->list(['type' => 'purchase_order']);
} catch (InvalidArgumentException $e) {
    // 'Invalid template type: purchase_order. Must be one of: ...'
}
```

---

## Related Resources

- [[Invoices]] — `mail_template_id` used when sending invoices
- [[Quotations]] — `mail_template_id` used when sending quotations
- [[Credit-Notes]] — `mail_template_id` used when sending credit notes
