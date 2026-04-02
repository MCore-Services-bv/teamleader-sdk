# Credit Notes

Read credit notes in Teamleader Focus.

## Overview

Credit notes are created indirectly via `Teamleader::invoices()->credit()`
or `Teamleader::invoices()->creditPartially()`. This resource provides read-only access to list, filter, download, and
send credit notes.

Access via `Teamleader::creditnotes()`.

> **SDK key vs endpoint:** The SDK key is `creditnotes` (all lowercase). The internal API endpoint is `creditNotes` (
> capital N) — the SDK handles this transparently.
>
> **`credit_note_date_before` is exclusive**, unlike `invoice_date_before` on Invoices which is inclusive.
>
> **`paid()` and `unpaid()` do not filter the API.** These helpers pass an internal `_paid` flag that `buildFilters()`
> strips before the request — they currently return the same result as `list()`. Use the `paid` field in the response to
> filter manually.

## Endpoint

`creditNotes`

## Capabilities

| Capability  | Supported                                  |
|-------------|--------------------------------------------|
| Pagination  | ✅ Supported                                |
| Filtering   | ✅ Supported                                |
| Sorting     | ❌ Not supported                            |
| Sideloading | ❌ Not supported                            |
| Creation    | ❌ Not supported (use `Invoices::credit()`) |
| Update      | ❌ Not supported                            |
| Deletion    | ❌ Not supported                            |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$creditNotes = Teamleader::creditnotes()->list();

$creditNotes = Teamleader::creditnotes()->list([
    'invoice_id'              => 'invoice-uuid',
    'credit_note_date_after'  => '2025-01-01',
    'credit_note_date_before' => '2025-04-01', // exclusive
], [
    'page_size' => 50, 'page_number' => 1,
]);
```

---

### `info(string $id)`

```php
$creditNote = Teamleader::creditnotes()->info('credit-note-uuid');

$peppolStatus = $creditNote['data']['peppol_status']; // null until sent via Peppol
```

---

### `download(string $id, string $format = 'pdf')`

Returns a temporary download URL. Throws `InvalidArgumentException` for invalid formats.

**Valid formats:** `pdf`, `ubl/e-fff`

```php
$result = Teamleader::creditnotes()->download('credit-note-uuid', 'pdf');
$url    = $result['data']['location'];
```

---

### `sendViaPeppol(string $id)`

Submits a credit note to the Peppol network. Poll `info()` to track `peppol_status`.

```php
Teamleader::creditnotes()->sendViaPeppol('credit-note-uuid');
```

---

## Helper Methods

| Method                                        | Notes                                                                                   |
|-----------------------------------------------|-----------------------------------------------------------------------------------------|
| `booked()`                                    | Alias for `list()` — no extra filter applied                                            |
| `paid()`                                      | ⚠️ `_paid` flag is stripped — equivalent to `list()`. Filter on `paid: true` manually.  |
| `unpaid()`                                    | ⚠️ `_paid` flag is stripped — equivalent to `list()`. Filter on `paid: false` manually. |
| `forInvoice(string $invoiceId)`               | `invoice_id` filter                                                                     |
| `forCustomer(string $type, string $id)`       | `customer` filter — validates type (`contact`, `company`)                               |
| `forProject(string $projectId)`               | `project_id` filter                                                                     |
| `forDepartment(string $departmentId)`         | `department_id` filter                                                                  |
| `betweenDates(string $after, string $before)` | `credit_note_date_after` + `credit_note_date_before` (before is exclusive)              |
| `updatedSince(string $since)`                 | `updated_since` filter                                                                  |

```php
$creditNotes = Teamleader::creditnotes()->forInvoice('invoice-uuid');
$creditNotes = Teamleader::creditnotes()->forCustomer('company', 'company-uuid');
$creditNotes = Teamleader::creditnotes()->betweenDates('2025-01-01', '2025-04-01');
$creditNotes = Teamleader::creditnotes()->updatedSince('2025-01-01T00:00:00+00:00');

// Filter paid/unpaid manually
$unpaid = array_filter($creditNotes['data'], fn($cn) => $cn['paid'] === false);
```

---

## Filters

| Filter                    | Type   | Description                          |
|---------------------------|--------|--------------------------------------|
| `ids`                     | array  | Filter by credit note UUIDs          |
| `department_id`           | string | Department UUID                      |
| `updated_since`           | string | ISO 8601 datetime                    |
| `invoice_id`              | string | Related invoice UUID                 |
| `project_id`              | string | Project UUID                         |
| `customer`                | object | `{type: contact\|company, id: uuid}` |
| `credit_note_date_after`  | string | Date inclusive (YYYY-MM-DD)          |
| `credit_note_date_before` | string | Date **exclusive** (YYYY-MM-DD)      |

---

## Peppol Status Values

Same values as Invoices — see [[Invoices#Peppol-Status-Values]].

---

## Usage Examples

### Get all credit notes for an invoice

```php
$creditNotes = Teamleader::creditnotes()->forInvoice('invoice-uuid');

foreach ($creditNotes['data'] as $cn) {
    $download = Teamleader::creditnotes()->download($cn['id'], 'pdf');
    // $download['data']['location'] — temporary URL
}
```

### Filter unpaid credit notes manually

```php
$creditNotes = Teamleader::creditnotes()->forCustomer('company', 'company-uuid');

$unpaid = array_filter($creditNotes['data'], fn($cn) => $cn['paid'] === false);
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid download format
try {
    Teamleader::creditnotes()->download('uuid', 'ubl/peppol_bis_3');
} catch (InvalidArgumentException $e) {
    // "Invalid format 'ubl/peppol_bis_3'. Must be one of: pdf, ubl/e-fff"
}
```

---

## Related Resources

- [[Invoices]] — `credit()` and `creditPartially()` create credit notes
- [[Companies]] — Filter credit notes by customer
- [[Contacts]] — Filter credit notes by customer
- [[Filtering]] — Filter and pagination reference
