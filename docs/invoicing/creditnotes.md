# Credit Notes

The Credit Notes resource allows you to retrieve and manage credit notes in Teamleader Focus. Credit notes are created through invoice credit operations and cannot be directly created, updated, or deleted through the API.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Listing Credit Notes](#listing-credit-notes)
- [Filtering](#filtering)
- [Pagination](#pagination)
- [Getting Credit Note Details](#getting-credit-note-details)
- [Downloading Credit Notes](#downloading-credit-notes)
- [Sending via Peppol](#sending-via-peppol)
- [Convenience Methods](#convenience-methods)
- [Data Fields](#data-fields)

## Basic Usage

```php
// Get the credit notes resource
$creditNotes = $teamleader->creditnotes();

// List all credit notes
$allCreditNotes = $creditNotes->list();

// Get a specific credit note
$creditNote = $creditNotes->info('credit-note-uuid');
```

## Listing Credit Notes

```php
// Get all credit notes
$creditNotes = $teamleader->creditnotes()->list();

// List with pagination
$creditNotes = $teamleader->creditnotes()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);
```

## Filtering

### Available Filters

Credit notes can be filtered using the following parameters:

- **`ids`**: Array of credit note UUIDs
- **`department_id`**: Filter by department (company entity) UUID
- **`invoice_id`**: Filter by related invoice UUID
- **`project_id`**: Filter by project UUID
- **`customer`**: Customer object with `type` (contact/company) and `id`
- **`credit_note_date_after`**: Start date (inclusive, YYYY-MM-DD)
- **`credit_note_date_before`**: End date (exclusive, YYYY-MM-DD)
- **`updated_since`**: ISO 8601 datetime

### Filtering Examples

```php
// Filter by specific IDs
$creditNotes = $teamleader->creditnotes()->list([
    'ids' => ['uuid1', 'uuid2', 'uuid3']
]);

// Filter by invoice
$creditNotes = $teamleader->creditnotes()->forInvoice('invoice-uuid');

// Filter by customer
$creditNotes = $teamleader->creditnotes()->forCustomer('company', 'customer-uuid');

// Filter by project
$creditNotes = $teamleader->creditnotes()->forProject('project-uuid');

// Filter by department
$creditNotes = $teamleader->creditnotes()->forDepartment('department-uuid');

// Filter by date range
$creditNotes = $teamleader->creditnotes()->betweenDates('2023-01-01', '2024-01-01');

// Filter by updated date
$creditNotes = $teamleader->creditnotes()->updatedSince('2024-01-01T00:00:00+00:00');

// Combine multiple filters
$creditNotes = $teamleader->creditnotes()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'customer-uuid'
    ],
    'credit_note_date_after' => '2023-01-01',
    'credit_note_date_before' => '2024-01-01'
]);
```

## Pagination

Credit notes support pagination to handle large result sets efficiently.

```php
// Get first page (20 items per page by default)
$page1 = $teamleader->creditnotes()->list([], [
    'page_size' => 20,
    'page_number' => 1
]);

// Get second page
$page2 = $teamleader->creditnotes()->list([], [
    'page_size' => 20,
    'page_number' => 2
]);

// Get larger page size
$creditNotes = $teamleader->creditnotes()->list([], [
    'page_size' => 100,
    'page_number' => 1
]);
```

## Getting Credit Note Details

Retrieve complete information about a specific credit note.

```php
// Get credit note by ID
$creditNote = $teamleader->creditnotes()->info('credit-note-uuid');

// Access credit note data
$creditNoteNumber = $creditNote['data']['credit_note_number'];
$status = $creditNote['data']['status'];
$isPaid = $creditNote['data']['paid'];
$total = $creditNote['data']['total'];
$invoicee = $creditNote['data']['invoicee'];
$groupedLines = $creditNote['data']['grouped_lines'];
```

## Downloading Credit Notes

Download credit notes in various formats.

### Available Formats

- **`pdf`**: PDF format (default)
- **`ubl/e-fff`**: UBL e-fff format

### Download Examples

```php
// Download as PDF (default)
$download = $teamleader->creditnotes()->download('credit-note-uuid');
$downloadUrl = $download['data']['location'];
$expires = $download['data']['expires'];

// Download as PDF (explicit)
$download = $teamleader->creditnotes()->download('credit-note-uuid', 'pdf');

// Download as UBL e-fff format
$download = $teamleader->creditnotes()->download('credit-note-uuid', 'ubl/e-fff');

// Use the download URL
file_put_contents('creditnote.pdf', file_get_contents($downloadUrl));
```

**Note:** Download URLs are temporary and expire. Check the `expires` field for expiration time.

## Sending via Peppol

Send credit notes through the Peppol network for electronic invoicing.

```php
// Send credit note via Peppol
$result = $teamleader->creditnotes()->sendViaPeppol('credit-note-uuid');
```

**Requirements:**
- Credit note must have valid Peppol recipient information
- Your Teamleader account must be configured for Peppol

## Convenience Methods

The Credit Notes resource provides several convenience methods for common queries:

### Get Booked Credit Notes

```php
// Get all booked credit notes (all credit notes are booked)
$booked = $teamleader->creditnotes()->booked();

// With additional filters
$booked = $teamleader->creditnotes()->booked([
    'department_id' => 'department-uuid'
]);
```

### Get Paid/Unpaid Credit Notes

```php
// Get paid credit notes
$paid = $teamleader->creditnotes()->paid();

// Get unpaid credit notes
$unpaid = $teamleader->creditnotes()->unpaid();

// With pagination
$unpaid = $teamleader->creditnotes()->unpaid([], [
    'page_size' => 50,
    'page_number' => 1
]);
```

### Filter by Related Entities

```php
// Credit notes for a specific invoice
$creditNotes = $teamleader->creditnotes()->forInvoice('invoice-uuid');

// Credit notes for a customer
$creditNotes = $teamleader->creditnotes()->forCustomer('company', 'customer-uuid');

// Credit notes for a project
$creditNotes = $teamleader->creditnotes()->forProject('project-uuid');

// Credit notes for a department
$creditNotes = $teamleader->creditnotes()->forDepartment('department-uuid');
```

### Date Range Queries

```php
// Credit notes between dates
$creditNotes = $teamleader->creditnotes()->betweenDates('2023-01-01', '2024-01-01');

// Updated since a specific date
$creditNotes = $teamleader->creditnotes()->updatedSince('2024-01-01T00:00:00+00:00');
```

## Data Fields

### Credit Note List Fields

When listing credit notes, each item contains:

- **`id`**: Credit note UUID
- **`department`**: Department reference object
    - `id`: Department UUID
    - `type`: Resource type ("department")
- **`credit_note_number`**: Credit note number (e.g., "2017/5") - nullable
- **`credit_note_date`**: Credit note date (YYYY-MM-DD format) - nullable
- **`status`**: Status (always "booked")
- **`invoice`**: Related invoice reference - nullable
    - `id`: Invoice UUID
    - `type`: Resource type ("invoice")
- **`paid`**: Payment status (boolean)
- **`paid_at`**: Payment date (ISO 8601 format) - nullable
- **`invoicee`**: Invoicee information
    - `name`: Invoicee name
    - `vat_number`: VAT number - nullable
    - `customer`: Customer reference
        - `id`: Customer UUID
        - `type`: Customer type
- **`customer`**: Customer reference object
    - `id`: Customer UUID
    - `type`: Customer type ("contact" or "company")
- **`total`**: Total amounts
    - `tax_exclusive`: Amount excluding tax
        - `amount`: Numeric value
        - `currency`: Currency code
    - `tax_inclusive`: Amount including tax
        - `amount`: Numeric value
        - `currency`: Currency code
    - `payable`: Amount to be paid
        - `amount`: Numeric value
        - `currency`: Currency code
    - `taxes`: Array of tax breakdowns
        - `rate`: Tax rate (e.g., 0.21)
        - `taxable`: Taxable amount object
        - `tax`: Tax amount object
- **`created_at`**: Creation timestamp (ISO 8601)
- **`updated_at`**: Last update timestamp (ISO 8601)

### Credit Note Info Fields

The `info` method returns all list fields plus:

- **`customer`**: Extended customer information
    - `email`: Customer email - nullable
    - `national_identification_number`: ID number - nullable
- **`discounts`**: Array of applied discounts
    - `type`: Discount type ("percentage")
    - `value`: Discount value (0-100)
    - `description`: Discount description
- **`grouped_lines`**: Line items grouped by section
    - `section`: Section information
        - `title`: Section title
    - `line_items`: Array of line items
        - `product`: Product reference - nullable
        - `quantity`: Item quantity
        - `description`: Item description
        - `extended_description`: Extended description (Markdown) - nullable
        - `unit`: Unit of measure reference - nullable
        - `unit_price`: Price per unit
        - `tax`: Tax reference
        - `discount`: Line item discount - nullable
        - `total`: Line item totals
        - `product_category`: Product category reference - nullable
- **`currency`**: Currency code (e.g., "USD")
- **`currency_exchange_rate`**: Exchange rate object - nullable
    - `from`: Source currency
    - `to`: Target currency
    - `rate`: Exchange rate
- **`document_template`**: Document template reference
    - `id`: Template UUID
    - `type`: Resource type ("documentTemplate")

### Currency Codes

Supported currency codes:

`BAM`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CZK`, `DKK`, `EUR`, `GBP`, `INR`, `ISK`, `JPY`, `MAD`, `MXN`, `NOK`, `PEN`, `PLN`, `RON`, `SEK`, `TRY`, `USD`, `ZAR`

## Response Examples

### List Response

```php
[
    'data' => [
        [
            'id' => '2b43633b-22d1-41b6-b87b-e1fd742325d4',
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ],
            'credit_note_number' => '2017/5',
            'credit_note_date' => '2016-02-04',
            'status' => 'booked',
            'invoice' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'invoice'
            ],
            'paid' => true,
            'paid_at' => '2016-03-03T16:44:33+00:00',
            'invoicee' => [
                'name' => 'De Rode Duivels',
                'vat_number' => 'BE0899623035',
                'customer' => [
                    'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                    'type' => 'company'
                ]
            ],
            'customer' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'company'
            ],
            'total' => [
                'tax_exclusive' => [
                    'amount' => 123.30,
                    'currency' => 'EUR'
                ],
                'tax_inclusive' => [
                    'amount' => 149.19,
                    'currency' => 'EUR'
                ],
                'payable' => [
                    'amount' => 149.19,
                    'currency' => 'EUR'
                ],
                'taxes' => [
                    [
                        'rate' => 0.21,
                        'taxable' => [
                            'amount' => 123.30,
                            'currency' => 'EUR'
                        ],
                        'tax' => [
                            'amount' => 25.89,
                            'currency' => 'EUR'
                        ]
                    ]
                ]
            ],
            'created_at' => '2016-02-04T16:44:33+00:00',
            'updated_at' => '2016-02-05T16:44:33+00:00'
        ]
    ]
]
```

### Download Response

```php
[
    'data' => [
        'location' => 'https://cdn.teamleader.eu/file',
        'expires' => '2018-02-05T16:44:33+00:00'
    ]
]
```

## Notes

- Credit notes are created through invoice credit operations (`invoices()->credit()` or `invoices()->creditPartially()`), not directly
- All credit notes have status "booked" - there are no draft credit notes
- Download URLs are temporary and expire after the time specified in the `expires` field
- The `credit_note_date_before` filter is exclusive (does not include the specified date)
- The `credit_note_date_after` filter is inclusive (includes the specified date)
- When filtering by customer, both `type` and `id` fields are required in the customer object
