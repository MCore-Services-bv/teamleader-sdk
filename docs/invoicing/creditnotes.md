# Credit Notes

Manage credit notes in Teamleader Focus.

## Overview

The Credit Notes resource provides read-only access to credit notes in your Teamleader account. Credit notes are created automatically when you credit an invoice (fully or partially) and cannot be created directly through this resource.

**Important:** This resource is read-only. To create credit notes, use the `credit()` or `creditPartially()` methods on the Invoices resource.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [download()](#download)
    - [sendViaPeppol()](#sendviapeppol)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`creditnotes`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (created via Invoice credit operations)
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of credit notes with optional filtering and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for pagination

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all credit notes
$creditNotes = Teamleader::creditnotes()->list();

// Get credit notes with filters
$creditNotes = Teamleader::creditnotes()->list([
    'credit_note_date_after' => '2024-01-01',
    'credit_note_date_before' => '2024-12-31'
]);

// With pagination
$creditNotes = Teamleader::creditnotes()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

### `info()`

Get detailed information about a specific credit note.

**Parameters:**
- `id` (string): The credit note UUID

**Example:**
```php
$creditNote = Teamleader::creditnotes()->info('creditnote-uuid');
```

### `download()`

Download a credit note in a specific format.

**Parameters:**
- `id` (string): Credit note UUID
- `format` (string, optional): Format type (default: 'pdf')
    - `pdf` - PDF document
    - `ubl/e-fff` - UBL E-FFF format

**Returns:** Array with `location` (download URL) and `expires` (expiration timestamp)

**Example:**
```php
// Download as PDF
$download = Teamleader::creditnotes()->download('creditnote-uuid');
$pdfUrl = $download['location'];

// Download as UBL
$download = Teamleader::creditnotes()->download('creditnote-uuid', 'ubl/e-fff');
```

### `sendViaPeppol()`

Send a credit note via the Peppol network.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$result = Teamleader::creditnotes()->sendViaPeppol('creditnote-uuid');
```

## Helper Methods

The Credit Notes resource provides convenient helper methods:

### Status-based Methods

```php
// Get booked credit notes (all credit notes are booked)
$booked = Teamleader::creditnotes()->booked();

// Get paid credit notes
$paid = Teamleader::creditnotes()->paid();

// Get unpaid credit notes
$unpaid = Teamleader::creditnotes()->unpaid();
```

### Relationship Methods

```php
// Get credit notes for a specific invoice
$creditNotes = Teamleader::creditnotes()->forInvoice('invoice-uuid');

// Get credit notes for a specific customer
$creditNotes = Teamleader::creditnotes()->forCustomer('company', 'company-uuid');

// Get credit notes for a specific department
$creditNotes = Teamleader::creditnotes()->forDepartment('dept-uuid');

// Get credit notes for a specific project
$creditNotes = Teamleader::creditnotes()->forProject('project-uuid');
```

### Date Range Methods

```php
// Get credit notes within a date range
$creditNotes = Teamleader::creditnotes()->forDateRange('2024-01-01', '2024-12-31');

// Get credit notes for a specific month
$creditNotes = Teamleader::creditnotes()->forMonth('2024-02');

// Get credit notes for a specific year
$creditNotes = Teamleader::creditnotes()->forYear(2024);
```

## Filtering

Available filters for credit notes:

- `ids` - Array of credit note UUIDs
- `department_id` - Filter by department UUID
- `updated_since` - ISO 8601 datetime
- `invoice_id` - Filter by related invoice UUID
- `project_id` - Filter by project UUID
- `customer` - Filter by customer (object with type and id)
- `credit_note_date_after` - Date (inclusive, YYYY-MM-DD)
- `credit_note_date_before` - Date (exclusive, YYYY-MM-DD)

**Example:**
```php
$creditNotes = Teamleader::creditnotes()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'credit_note_date_after' => '2024-01-01',
    'department_id' => 'dept-uuid'
]);
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "uuid",
      "department": {
        "type": "department",
        "id": "uuid"
      },
      "credit_note_number": "CN-2024/001",
      "credit_note_date": "2024-02-15",
      "status": "booked",
      "invoice": {
        "type": "invoice",
        "id": "uuid"
      },
      "paid": false,
      "paid_at": null,
      "invoicee": {
        "name": "Company Name",
        "vat_number": "BE0123456789",
        "customer": {
          "type": "company",
          "id": "uuid"
        }
      },
      "total": {
        "tax_exclusive": {
          "amount": 50.00,
          "currency": "EUR"
        },
        "tax_inclusive": {
          "amount": 60.50,
          "currency": "EUR"
        },
        "payable": {
          "amount": 60.50,
          "currency": "EUR"
        }
      },
      "taxes": [
        {
          "rate": 0.21,
          "taxable": {
            "amount": 50.00,
            "currency": "EUR"
          },
          "tax": {
            "amount": 10.50,
            "currency": "EUR"
          }
        }
      ],
      "created_at": "2024-02-15T10:00:00+00:00",
      "updated_at": "2024-02-15T10:00:00+00:00"
    }
  ]
}
```

### Info Response

Contains complete credit note information including all fields from the list response plus detailed line items and additional metadata.

## Usage Examples

### View All Credit Notes for an Invoice

```php
$invoiceId = 'invoice-uuid';

// Get all credit notes for this invoice
$creditNotes = Teamleader::creditnotes()->forInvoice($invoiceId);

$totalCredited = 0;
foreach ($creditNotes['data'] as $creditNote) {
    $totalCredited += $creditNote['total']['tax_inclusive']['amount'];
}

echo "Total credited: €" . number_format($totalCredited, 2);
```

### Download Credit Note PDFs

```php
$creditNotes = Teamleader::creditnotes()->unpaid();

foreach ($creditNotes['data'] as $creditNote) {
    $download = Teamleader::creditnotes()->download($creditNote['id']);
    
    // Store or process the download URL
    echo "Credit Note {$creditNote['credit_note_number']}: {$download['location']}\n";
}
```

### Track Outstanding Credit Notes

```php
$unpaidCreditNotes = Teamleader::creditnotes()->unpaid();

foreach ($unpaidCreditNotes['data'] as $creditNote) {
    $invoice = Teamleader::invoices()->info($creditNote['invoice']['id']);
    
    echo "Credit Note: {$creditNote['credit_note_number']}\n";
    echo "Original Invoice: {$invoice['data']['invoice_number']}\n";
    echo "Amount: €{$creditNote['total']['tax_inclusive']['amount']}\n\n";
}
```

### Monthly Credit Note Report

```php
$monthlyCreditNotes = Teamleader::creditnotes()->forMonth('2024-02');

$totalCredited = 0;
$byCustomer = [];

foreach ($monthlyCreditNotes['data'] as $creditNote) {
    $amount = $creditNote['total']['tax_inclusive']['amount'];
    $totalCredited += $amount;
    
    $customerName = $creditNote['invoicee']['name'];
    if (!isset($byCustomer[$customerName])) {
        $byCustomer[$customerName] = 0;
    }
    $byCustomer[$customerName] += $amount;
}

echo "Total credited in February: €" . number_format($totalCredited, 2) . "\n";
echo "\nBy customer:\n";
foreach ($byCustomer as $customer => $amount) {
    echo "  {$customer}: €" . number_format($amount, 2) . "\n";
}
```

## Common Use Cases

### 1. Credit Note Tracking

```php
// Get all credit notes with their related invoices
$creditNotes = Teamleader::creditnotes()->list();

foreach ($creditNotes['data'] as $creditNote) {
    if (!$creditNote['paid']) {
        // Get original invoice details
        $invoice = Teamleader::invoices()->info($creditNote['invoice']['id']);
        
        // Track outstanding credit
        $this->trackOutstandingCredit([
            'credit_note' => $creditNote,
            'invoice' => $invoice['data']
        ]);
    }
}
```

### 2. Customer Credit Analysis

```php
$customerId = 'company-uuid';

// Get all credit notes for customer
$creditNotes = Teamleader::creditnotes()->forCustomer('company', $customerId);

$totalCredit = 0;
$creditByMonth = [];

foreach ($creditNotes['data'] as $creditNote) {
    $amount = $creditNote['total']['tax_inclusive']['amount'];
    $totalCredit += $amount;
    
    $month = date('Y-m', strtotime($creditNote['credit_note_date']));
    if (!isset($creditByMonth[$month])) {
        $creditByMonth[$month] = 0;
    }
    $creditByMonth[$month] += $amount;
}
```

### 3. Generate Credit Note Reports

```php
// Get credit notes for reporting period
$creditNotes = Teamleader::creditnotes()->forDateRange(
    '2024-01-01',
    '2024-12-31'
);

$report = [
    'total_credit_notes' => count($creditNotes['data']),
    'total_amount' => 0,
    'paid' => 0,
    'unpaid' => 0,
    'by_department' => []
];

foreach ($creditNotes['data'] as $creditNote) {
    $amount = $creditNote['total']['tax_inclusive']['amount'];
    $report['total_amount'] += $amount;
    
    if ($creditNote['paid']) {
        $report['paid'] += $amount;
    } else {
        $report['unpaid'] += $amount;
    }
    
    $deptId = $creditNote['department']['id'];
    if (!isset($report['by_department'][$deptId])) {
        $report['by_department'][$deptId] = 0;
    }
    $report['by_department'][$deptId] += $amount;
}
```

## Best Practices

### 1. Always Check Related Invoice

```php
$creditNote = Teamleader::creditnotes()->info('creditnote-uuid');

if (isset($creditNote['data']['invoice'])) {
    $invoice = Teamleader::invoices()->info($creditNote['data']['invoice']['id']);
    
    // Verify relationship
    $this->verifyCreditNoteInvoiceRelation($creditNote['data'], $invoice['data']);
}
```

### 2. Track Payment Status

```php
// Regularly check unpaid credit notes
$unpaidCreditNotes = Teamleader::creditnotes()->unpaid();

foreach ($unpaidCreditNotes['data'] as $creditNote) {
    $daysOutstanding = (new DateTime())->diff(
        new DateTime($creditNote['credit_note_date'])
    )->days;
    
    if ($daysOutstanding > 30) {
        // Follow up on old credit notes
        $this->followUpOnCreditNote($creditNote);
    }
}
```

### 3. Monitor Credit Note Patterns

```php
// Analyze credit note patterns to identify issues
$recentCreditNotes = Teamleader::creditnotes()->forMonth(date('Y-m'));

$reasons = [];
foreach ($recentCreditNotes['data'] as $creditNote) {
    // Track which products/services are being credited most
    $this->analyzeCreditNoteLines($creditNote['id']);
}
```

### 4. Download for Record Keeping

```php
$creditNotes = Teamleader::creditnotes()->forYear(2024);

foreach ($creditNotes['data'] as $creditNote) {
    $download = Teamleader::creditnotes()->download($creditNote['id']);
    
    // Archive for compliance
    $this->archiveCreditNotePdf(
        $creditNote['credit_note_number'],
        $download['location']
    );
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $creditNote = Teamleader::creditnotes()->info('creditnote-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        // Credit note not found
        Log::error('Credit note does not exist');
    } else {
        // Other error
        Log::error('Failed to retrieve credit note', [
            'error' => $e->getMessage()
        ]);
    }
}

// Handle download errors
try {
    $download = Teamleader::creditnotes()->download('creditnote-uuid');
} catch (TeamleaderException $e) {
    Log::error('Failed to download credit note', [
        'error' => $e->getMessage()
    ]);
}
```

## Related Resources

- [Invoices](invoices.md) - Invoice management (create credit notes)
- [Payment Methods](payment-methods.md) - Payment method information
- [Payment Terms](payment-terms.md) - Payment term configuration
- [Tax Rates](tax-rates.md) - Tax rate information
- [Companies](../crm/companies.md) - Customer management
- [Projects](../projects/projects.md) - Project management
