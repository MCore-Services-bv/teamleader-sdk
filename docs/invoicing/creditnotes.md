# Credit Notes

The `Creditnotes` resource provides read-only access to credit notes in Teamleader Focus. Credit notes are created through invoice credit operations (e.g. `creditPartially()` or `creditFully()` on the Invoices resource) and cannot be created, updated, or deleted directly via this resource.

## Capabilities

| Feature | Supported |
|---------|-----------|
| List | ✅ |
| Info | ✅ |
| Create | ❌ (use Invoices resource) |
| Update | ❌ |
| Delete | ❌ |
| Pagination | ✅ |
| Filtering | ✅ |
| Sorting | ❌ |
| Sideloading | ❌ |

---

## Methods

### `list(array $filters = [], array $options = []): array`

Returns a paginated list of credit notes. Supports filtering by invoice, customer, project, department, and date range.

**Filter parameters:**

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of credit note UUIDs |
| `department_id` | string | Filter by department UUID |
| `updated_since` | string | ISO 8601 datetime |
| `invoice_id` | string | Filter by related invoice UUID |
| `project_id` | string | Filter by project UUID |
| `customer` | array | `{type: contact\|company, id: uuid}` |
| `credit_note_date_after` | string | Start date inclusive (YYYY-MM-DD) |
| `credit_note_date_before` | string | End date exclusive (YYYY-MM-DD) |

**Pagination options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `page_size` | int | 20 | Results per page |
| `page_number` | int | 1 | Page number |

**Example:**

```php
$creditNotes = $teamleader->creditnotes()->list(
    ['department_id' => 'dept-uuid'],
    ['page_size' => 50, 'page_number' => 1]
);
```

**List response fields:**

```json
{
  "data": [
    {
      "id": "27300f09-6250-4a23-8557-d84c52f99ecf",
      "department": { "id": "...", "type": "department" },
      "credit_note_number": "2024/001",        // nullable
      "credit_note_date": "2024-01-15",         // nullable
      "status": "booked",
      "invoice": { "id": "...", "type": "invoice" }, // nullable
      "paid": false,
      "paid_at": null,                           // nullable
      "invoicee": {
        "name": "Acme Corp",
        "vat_number": "BE0899623035"             // nullable
      },
      "total": {
        "tax_exclusive": { "amount": 100.00, "currency": "EUR" },
        "tax_inclusive": { "amount": 121.00, "currency": "EUR" },
        "payable": { "amount": 121.00, "currency": "EUR" }
      },
      "taxes": [],
      "created_at": "2024-01-15T10:00:00+00:00",
      "updated_at": "2024-01-15T10:00:00+00:00"
    }
  ]
}
```

---

### `info(string $id): array`

Returns complete details for a single credit note, including line items, discounts, exchange rate, document template, and Peppol status.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | ✅ | Credit note UUID |

**Example:**

```php
$creditNote = $teamleader->creditnotes()->info('27300f09-6250-4a23-8557-d84c52f99ecf');
```

**Info response fields:**

```json
{
  "data": {
    "id": "27300f09-6250-4a23-8557-d84c52f99ecf",
    "department": { "id": "...", "type": "department" },
    "credit_note_number": "2024/001",           // nullable
    "credit_note_date": "2024-01-15",            // nullable
    "status": "booked",
    "invoice": { "id": "...", "type": "invoice" }, // nullable
    "paid": false,
    "paid_at": null,                              // nullable
    "invoicee": {
      "name": "Acme Corp",
      "vat_number": "BE0899623035",              // nullable
      "customer": {
        "email": "info@acme.com",                // nullable
        "national_identification_number": null   // nullable
      }
    },
    "discounts": [
      { "type": "percentage", "value": 10, "description": "Loyalty discount" }
    ],
    "total": {
      "tax_exclusive": { "amount": 90.00, "currency": "EUR" },
      "tax_inclusive": { "amount": 108.90, "currency": "EUR" },
      "payable": { "amount": 108.90, "currency": "EUR" },
      "taxes": []
    },
    "grouped_lines": [
      {
        "section": { "title": "Services" },
        "line_items": [
          {
            "product": { "id": "...", "type": "product" },  // nullable
            "quantity": 1,
            "description": "Consulting hours",
            "extended_description": "...",
            "unit": { "id": "...", "type": "unit" },         // nullable
            "unit_price": { "amount": 90.00, "currency": "EUR", "tax": "excluding" },
            "tax": { "id": "...", "type": "tax" },
            "discount": null,                                 // nullable
            "total": {
              "tax_exclusive": { "amount": 90.00, "currency": "EUR" },
              "tax_exclusive_before_discount": { "amount": 90.00, "currency": "EUR" },
              "tax_inclusive": { "amount": 108.90, "currency": "EUR" },
              "tax_inclusive_before_discount": { "amount": 108.90, "currency": "EUR" }
            },
            "product_category": null                          // nullable
          }
        ]
      }
    ],
    "currency": "EUR",
    "currency_exchange_rate": {                               // nullable
      "from": "USD",
      "to": "EUR",
      "rate": 1.1234
    },
    "created_at": "2024-01-15T10:00:00+00:00",
    "updated_at": "2024-01-15T10:00:00+00:00",
    "document_template": { "id": "...", "type": "documentTemplate" },
    "peppol_status": null                                     // string|null — populated after sendViaPeppol()
  }
}
```

---

### `download(string $id, string $format = 'pdf'): array`

Returns a temporary download URL for the credit note.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | ✅ | Credit note UUID |
| `format` | string | ❌ | `pdf` (default) or `ubl/e-fff` |

**Example:**

```php
// Download as PDF
$download = $teamleader->creditnotes()->download('credit-note-uuid', 'pdf');
$url = $download['data']['location'];

// Download as UBL
$download = $teamleader->creditnotes()->download('credit-note-uuid', 'ubl/e-fff');
```

**Response:**

```json
{
  "data": {
    "location": "https://...",
    "expires": "2024-01-15T11:00:00+00:00"
  }
}
```

---

### `sendViaPeppol(string $id): array`

Submits a credit note to the recipient via the Peppol network. After calling this method, poll `info()` to track the `peppol_status` field until it reaches a final state.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | ✅ | Credit note UUID |

**Example:**

```php
$teamleader->creditnotes()->sendViaPeppol('credit-note-uuid');

// Check submission status
$creditNote = $teamleader->creditnotes()->info('credit-note-uuid');
$status = $creditNote['data']['peppol_status']; // e.g. 'sent', 'sending_failed'
```

---

## Convenience Methods

These methods wrap `list()` with pre-built filters for common use cases.

### `booked(array $additionalFilters = [], array $options = []): array`

Returns all booked credit notes.

```php
$creditNotes = $teamleader->creditnotes()->booked();
```

### `paid(array $additionalFilters = [], array $options = []): array`

Returns paid credit notes.

```php
$creditNotes = $teamleader->creditnotes()->paid();
```

### `unpaid(array $additionalFilters = [], array $options = []): array`

Returns unpaid credit notes.

```php
$creditNotes = $teamleader->creditnotes()->unpaid();
```

### `forInvoice(string $invoiceId, ...): array`

Returns credit notes linked to a specific invoice.

```php
$creditNotes = $teamleader->creditnotes()->forInvoice('invoice-uuid');
```

### `forCustomer(string $customerType, string $customerId, ...): array`

Returns credit notes for a specific customer (contact or company).

```php
$creditNotes = $teamleader->creditnotes()->forCustomer('company', 'company-uuid');
```

### `forProject(string $projectId, ...): array`

Returns credit notes linked to a specific project.

```php
$creditNotes = $teamleader->creditnotes()->forProject('project-uuid');
```

### `forDepartment(string $departmentId, ...): array`

Returns credit notes for a specific department.

```php
$creditNotes = $teamleader->creditnotes()->forDepartment('dept-uuid');
```

### `betweenDates(string $dateAfter, string $dateBefore, ...): array`

Returns credit notes within a date range. `$dateAfter` is inclusive, `$dateBefore` is exclusive.

```php
$creditNotes = $teamleader->creditnotes()->betweenDates('2024-01-01', '2025-01-01');
```

### `updatedSince(string $since, ...): array`

Returns credit notes updated after a given datetime (ISO 8601).

```php
$creditNotes = $teamleader->creditnotes()->updatedSince('2024-06-01T00:00:00+00:00');
```

---

## Peppol Status Values

The `peppol_status` field on the `info()` response is `null` until `sendViaPeppol()` has been called. It then transitions through the following states:

| Status | Description |
|--------|-------------|
| `sending` | Submission in progress |
| `sending_failed` | Submission failed before reaching the network |
| `sent` | Successfully sent to the Peppol network |
| `application_acknowledged` | Acknowledged by the receiving application |
| `application_accepted` | Accepted by the receiving application |
| `application_rejected` | Rejected by the receiving application |
| `receiver_acknowledged` | Acknowledged by the receiver |
| `receiver_accepted` | Accepted by the receiver |
| `receiver_rejected` | Rejected by the receiver |
| `receiver_is_processing` | Receiver is currently processing |
| `receiver_awaits_feedback` | Awaiting feedback from the receiver |
| `receiver_conditionally_accepted` | Conditionally accepted by the receiver |
| `receiver_paid` | Receiver has marked as paid |

---

## Common Use Cases

### Check if a Credit Note Was Sent via Peppol

```php
$creditNote = $teamleader->creditnotes()->info('credit-note-uuid');
$peppolStatus = $creditNote['data']['peppol_status'];

if ($peppolStatus === null) {
    // Not yet submitted via Peppol
} elseif ($peppolStatus === 'sending_failed') {
    // Handle failure
} elseif (in_array($peppolStatus, ['receiver_accepted', 'receiver_paid'])) {
    // Successfully delivered and accepted
}
```

### Get All Credit Notes for an Invoice and Download Them

```php
$creditNotes = $teamleader->creditnotes()->forInvoice('invoice-uuid');

foreach ($creditNotes['data'] as $creditNote) {
    $download = $teamleader->creditnotes()->download($creditNote['id'], 'pdf');
    // Store or serve $download['data']['location']
}
```

### Get Unpaid Credit Notes for a Customer

```php
$creditNotes = $teamleader->creditnotes()->forCustomer('company', 'company-uuid');

$unpaid = array_filter($creditNotes['data'], fn($cn) => $cn['paid'] === false);
```

### Sync Credit Notes Updated Since Last Run

```php
$lastSync = '2024-06-01T00:00:00+00:00';

$creditNotes = $teamleader->creditnotes()->updatedSince($lastSync);

foreach ($creditNotes['data'] as $creditNote) {
    // Process each updated credit note
}
```

---

## Related Resources

- **Invoices** — Credit notes are created via `creditPartially()` or `creditFully()` on the Invoices resource
- **Departments** — Filter credit notes by department
- **Companies / Contacts** — Filter credit notes by customer
- **Projects** — Filter credit notes by project
