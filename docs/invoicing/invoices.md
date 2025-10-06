# Invoices

Manage invoices in Teamleader Focus, including creating drafts, booking, updating, crediting, sending, and tracking invoice statuses.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Creating Invoices](#creating-invoices)
- [Listing Invoices](#listing-invoices)
- [Getting Invoice Details](#getting-invoice-details)
- [Updating Invoices](#updating-invoices)
- [Credit Operations](#credit-operations)
- [Download Operations](#download-operations)
- [Sending Operations](#sending-operations)
- [Payment Operations](#payment-operations)
- [Special Operations](#special-operations)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Data Fields](#data-fields)

## Basic Usage

```php
// Get the invoices resource
$invoices = $teamleader->invoices();

// List all invoices
$allInvoices = $invoices->list();

// Get specific invoice
$invoice = $invoices->info('invoice-uuid');

// Get draft invoices
$drafts = $invoices->draft();

// Get outstanding invoices
$outstanding = $invoices->outstanding();
```

## Creating Invoices

### Draft a New Invoice

```php
// Create a new draft invoice
$invoice = $teamleader->invoices()->create([
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'for_attention_of' => [
            'name' => 'Finance Department'
        ]
    ],
    'department_id' => 'department-uuid',
    'payment_term' => [
        'type' => 'after_invoice_date',
        'days' => 30
    ],
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Services'
            ],
            'line_items' => [
                [
                    'quantity' => 10,
                    'description' => 'Consulting hours',
                    'unit_price' => [
                        'amount' => 100.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ]
]);
```

### With Optional Fields

```php
// Create invoice with additional options
$invoice = $teamleader->invoices()->create([
    'invoicee' => [...],
    'department_id' => 'department-uuid',
    'payment_term' => [...],
    'grouped_lines' => [...],
    'currency' => [
        'code' => 'EUR',
        'exchange_rate' => 1.0
    ],
    'project_id' => 'project-uuid',
    'purchase_order_number' => 'PO-2024-001',
    'invoice_date' => '2024-01-15',
    'discounts' => [
        [
            'type' => 'percentage',
            'value' => 5,
            'description' => 'Early payment discount'
        ]
    ],
    'note' => 'Thank you for your business',
    'expected_payment_method' => [
        'method' => 'sepa_direct_debit',
        'reference' => 'AB1234'
    ],
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Custom value'
        ]
    ],
    'document_template_id' => 'template-uuid'
]);
```

## Listing Invoices

### Basic Listing

```php
// List all invoices
$invoices = $teamleader->invoices()->list();

// List with pagination
$invoices = $teamleader->invoices()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// List with sorting
$invoices = $teamleader->invoices()->list([], [
    'sort' => [
        [
            'field' => 'invoice_date',
            'order' => 'desc'
        ]
    ]
]);
```

### Filtering by Status

```php
// Draft invoices only
$drafts = $teamleader->invoices()->draft();

// Outstanding invoices only
$outstanding = $teamleader->invoices()->outstanding();

// Matched invoices only
$matched = $teamleader->invoices()->matched();

// Multiple statuses
$invoices = $teamleader->invoices()->list([
    'status' => ['draft', 'outstanding']
]);
```

### Filtering by Customer

```php
// Invoices for a specific company
$invoices = $teamleader->invoices()->forCustomer(
    'company', 
    'company-uuid'
);

// Invoices for a specific contact
$invoices = $teamleader->invoices()->forCustomer(
    'contact', 
    'contact-uuid'
);

// With additional filters
$invoices = $teamleader->invoices()->forCustomer(
    'company',
    'company-uuid',
    ['status' => ['outstanding']]
);
```

### Filtering by Related Records

```php
// Invoices for a specific project
$invoices = $teamleader->invoices()->forProject('project-uuid');

// Invoices for a specific deal
$invoices = $teamleader->invoices()->forDeal('deal-uuid');

// Invoices for a specific department
$invoices = $teamleader->invoices()->forDepartment('department-uuid');
```

### Search and Date Filtering

```php
// Search invoices
$invoices = $teamleader->invoices()->search('Invoice 2024');

// Updated since date
$invoices = $teamleader->invoices()->updatedSince('2024-01-01T00:00:00+00:00');

// Invoice date range
$invoices = $teamleader->invoices()->list([
    'invoice_date_after' => '2024-01-01',
    'invoice_date_before' => '2024-12-31'
]);

// Filter by purchase order number
$invoices = $teamleader->invoices()->list([
    'purchase_order_number' => 'PO-2024-001'
]);

// Filter by payment reference
$invoices = $teamleader->invoices()->list([
    'payment_reference' => '+++084/2613/66074+++'
]);
```

## Getting Invoice Details

### Basic Info

```php
// Get invoice details
$invoice = $teamleader->invoices()->info('invoice-uuid');

// Access invoice data
$invoiceNumber = $invoice['data']['invoice_number'];
$status = $invoice['data']['status'];
$total = $invoice['data']['total']['tax_inclusive']['amount'];
```

### Including Late Fees

```php
// Get invoice with late fee calculations
$invoice = $teamleader->invoices()->info('invoice-uuid', 'late_fees');

// Access late fee data
$dueIncassoInclusive = $invoice['data']['total']['due_incasso_inclusive'];
$fixedLateFee = $invoice['data']['total']['fixed_late_fee'];
$interest = $invoice['data']['total']['interest'];
```

## Updating Invoices

### Update Draft Invoice

```php
// Update a draft invoice
$result = $teamleader->invoices()->update('invoice-uuid', [
    'invoice_date' => '2024-01-15',
    'note' => 'Updated invoice note',
    'purchase_order_number' => 'PO-2024-001',
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Services'
            ],
            'line_items' => [
                [
                    'quantity' => 10,
                    'description' => 'Consulting hours',
                    'unit_price' => [
                        'amount' => 100.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid',
                    'discount' => [
                        'value' => 10,
                        'type' => 'percentage'
                    ]
                ]
            ]
        ]
    ]
]);
```

### Update Booked Invoice

Only available when editing booked invoices is allowed through settings:

```php
// Update a booked invoice
$result = $teamleader->invoices()->updateBooked('invoice-uuid', [
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'payment_term' => [
        'type' => 'after_invoice_date',
        'days' => 30
    ],
    'invoice_date' => '2024-01-15',
    'note' => 'Updated note'
]);
```

### Update Customer Information

```php
$result = $teamleader->invoices()->update('invoice-uuid', [
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'new-company-uuid'
        ],
        'for_attention_of' => [
            'contact_id' => 'contact-uuid'
        ]
    ]
]);
```

### Update Payment Terms

```php
$result = $teamleader->invoices()->update('invoice-uuid', [
    'payment_term' => [
        'type' => 'after_invoice_date', // or 'cash', 'end_of_month'
        'days' => 30 // Not required when type is 'cash'
    ]
]);
```

### Update Currency

```php
$result = $teamleader->invoices()->update('invoice-uuid', [
    'currency' => [
        'code' => 'USD',
        'exchange_rate' => 1.1234
    ]
]);
```

## Credit Operations

### Credit Invoice Completely

```php
// Credit the entire invoice
$creditNote = $teamleader->invoices()->credit(
    'invoice-uuid',
    '2024-02-04' // Credit note date
);

// Get the credit note ID
$creditNoteId = $creditNote['data']['id'];
```

### Credit Invoice Partially

```php
// Credit specific line items
$creditNote = $teamleader->invoices()->creditPartially(
    'invoice-uuid',
    '2024-02-04', // Credit note date
    [
        [
            'section' => [
                'title' => 'Credited Items'
            ],
            'line_items' => [
                [
                    'quantity' => 2,
                    'description' => 'Returned product',
                    'unit_price' => [
                        'amount' => 50.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ],
    [ // Optional discounts
        [
            'type' => 'percentage',
            'value' => 10,
            'description' => 'Adjustment'
        ]
    ]
);
```

## Download Operations

### Download as PDF

```php
// Download invoice as PDF
$download = $teamleader->invoices()->download('invoice-uuid', 'pdf');

// Get download URL and expiration
$downloadUrl = $download['data']['location'];
$expiresAt = $download['data']['expires'];
```

### Download as UBL

```php
// Download as E-FFF format
$download = $teamleader->invoices()->download('invoice-uuid', 'ubl/e-fff');

// Download as Peppol BIS 3 format
$download = $teamleader->invoices()->download('invoice-uuid', 'ubl/peppol_bis_3');
```

## Sending Operations

### Send via Email

```php
// Send invoice via email
$result = $teamleader->invoices()->send(
    'invoice-uuid',
    [
        'subject' => 'Your Invoice',
        'body' => 'Please find your invoice attached.',
        'mail_template_id' => 'template-uuid' // Optional
    ],
    [
        'to' => [
            [
                'customer' => [
                    'type' => 'company',
                    'id' => 'company-uuid'
                ],
                'email' => 'billing@company.com'
            ]
        ],
        'cc' => [
            [
                'email' => 'manager@company.com'
            ]
        ],
        'bcc' => [
            [
                'email' => 'archive@mycompany.com'
            ]
        ]
    ],
    ['file-uuid-1', 'file-uuid-2'] // Optional attachments
);
```

### Send via Peppol

```php
// Send invoice via Peppol network
$result = $teamleader->invoices()->sendViaPeppol('invoice-uuid');
```

## Payment Operations

### Register a Payment

```php
// Register a payment for an invoice
$result = $teamleader->invoices()->registerPayment(
    'invoice-uuid',
    [
        'amount' => 1210.00,
        'currency' => 'EUR'
    ],
    '2024-02-10T10:00:00+00:00', // Payment date
    'payment-method-uuid' // Optional payment method
);
```

### Remove All Payments

```php
// Mark invoice as unpaid and remove all payments
$result = $teamleader->invoices()->removePayments('invoice-uuid');
```

## Special Operations

### Book a Draft Invoice

```php
// Book an invoice on a specific date
$result = $teamleader->invoices()->book(
    'invoice-uuid',
    '2024-01-15' // Booking date (YYYY-MM-DD)
);
```

### Copy an Invoice

```php
// Create a new draft based on existing invoice
$newInvoice = $teamleader->invoices()->copy('invoice-uuid');

// Get the new invoice ID
$newInvoiceId = $newInvoice['data']['id'];
```

### Delete an Invoice

Only possible for draft invoices or the last booked invoice:

```php
// Delete an invoice
$result = $teamleader->invoices()->delete('invoice-uuid');
```

## Filtering

### Available Filters

- **`ids`**: Array of invoice UUIDs
- **`term`**: Search on invoice number, purchase order number, payment reference, and invoicee
- **`invoice_number`**: Full invoice number (fiscal year / number)
- **`department_id`**: Filter on department (company entity)
- **`deal_id`**: Filter on deal UUID
- **`project_id`**: Filter on project UUID
- **`subscription_id`**: Filter on subscription UUID
- **`status`**: Array of statuses (draft, outstanding, matched)
- **`updated_since`**: ISO 8601 datetime
- **`purchase_order_number`**: Purchase order number
- **`payment_reference`**: Payment reference
- **`invoice_date_after`**: Date (inclusive, YYYY-MM-DD)
- **`invoice_date_before`**: Date (inclusive, YYYY-MM-DD)
- **`customer`**: Customer object with type and id

### Filtering Examples

```php
// Filter by multiple criteria
$invoices = $teamleader->invoices()->list([
    'status' => ['outstanding'],
    'department_id' => 'department-uuid',
    'invoice_date_after' => '2024-01-01',
    'invoice_date_before' => '2024-12-31'
]);

// Filter by specific IDs
$invoices = $teamleader->invoices()->list([
    'ids' => ['invoice-uuid-1', 'invoice-uuid-2']
]);

// Complex customer filter
$invoices = $teamleader->invoices()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'status' => ['outstanding', 'matched']
]);
```

## Sorting

### Available Sort Fields

- `invoice_number`
- `invoice_date`

### Sorting Examples

```php
// Sort by invoice number ascending
$invoices = $teamleader->invoices()->list([], [
    'sort' => [
        [
            'field' => 'invoice_number',
            'order' => 'asc'
        ]
    ]
]);

// Sort by invoice date descending (most recent first)
$invoices = $teamleader->invoices()->list([], [
    'sort' => [
        [
            'field' => 'invoice_date',
            'order' => 'desc'
        ]
    ]
]);

// Multiple sort criteria
$invoices = $teamleader->invoices()->list([], [
    'sort' => [
        [
            'field' => 'invoice_date',
            'order' => 'desc'
        ],
        [
            'field' => 'invoice_number',
            'order' => 'asc'
        ]
    ]
]);
```

## Data Fields

### Invoice Statuses

- **`draft`**: Invoice is in draft state
- **`outstanding`**: Invoice is booked and awaiting payment
- **`matched`**: Invoice has been paid

### Payment Term Types

- **`cash`**: Payment due immediately
- **`end_of_month`**: Payment due at end of month + X days
- **`after_invoice_date`**: Payment due X days after invoice date

### Customer Types

- **`contact`**: Individual contact
- **`company`**: Company

### Payment Methods

- **`sepa_direct_debit`**: SEPA Direct Debit
- **`direct_debit`**: Direct Debit
- **`credit_card`**: Credit Card

### Download Formats

- **`pdf`**: PDF format
- **`ubl/e-fff`**: UBL E-FFF format
- **`ubl/peppol_bis_3`**: UBL Peppol BIS 3 format

### Supported Currencies

BAM, CAD, CHF, CLP, CNY, COP, CZK, DKK, EUR, GBP, INR, ISK, JPY, MAD, MXN, NOK, PEN, PLN, RON, SEK, TRY, USD, ZAR

### Invoice Line Item Structure

```php
[
    'quantity' => 10, // Required: numeric
    'description' => 'Item description', // Required: string
    'extended_description' => 'Additional details (Markdown)', // Optional: string
    'unit_of_measure_id' => 'unit-uuid', // Optional: UUID
    'unit_price' => [ // Required: object
        'amount' => 100.00, // Required: numeric
        'tax' => 'excluding' // Required: must be 'excluding'
    ],
    'tax_rate_id' => 'tax-rate-uuid', // Required: UUID
    'discount' => [ // Optional: object
        'value' => 10, // Required: numeric (0-100)
        'type' => 'percentage' // Required: must be 'percentage'
    ],
    'product_id' => 'product-uuid', // Optional: UUID
    'withholding_tax_rate_id' => 'withholding-tax-uuid', // Optional: UUID
    'product_category_id' => 'category-uuid' // Optional: UUID
]
```

### Grouped Lines Structure

```php
'grouped_lines' => [
    [
        'section' => [
            'title' => 'Section Title' // Required: string
        ],
        'line_items' => [ // Required: array of line items
            // ... line items array
        ]
    ]
]
```

### Customer/Invoicee Structure

```php
'invoicee' => [
    'customer' => [ // Required
        'type' => 'company', // Required: 'contact' or 'company'
        'id' => 'customer-uuid' // Required: UUID
    ],
    'for_attention_of' => [ // Optional
        'contact_id' => 'contact-uuid' // When using contact ID
        // OR
        'name' => 'Person Name' // When using name
    ]
]
```

### Payment Term Structure

```php
'payment_term' => [
    'type' => 'after_invoice_date', // Required: 'cash', 'end_of_month', 'after_invoice_date'
    'days' => 30 // Required when type is not 'cash'
]
```

### Currency Structure

```php
'currency' => [
    'code' => 'EUR', // Required: valid currency code
    'exchange_rate' => 1.0 // Optional: numeric
]
```

### Expected Payment Method Structure

```php
'expected_payment_method' => [
    'method' => 'sepa_direct_debit', // Required: valid payment method
    'reference' => 'AB1234' // Optional: reference number
]
```

### Email Content Structure

```php
'content' => [
    'subject' => 'Invoice Subject', // Required: string
    'body' => 'Email body text', // Required: string
    'mail_template_id' => 'template-uuid' // Optional: UUID
]
```

### Email Recipients Structure

```php
'recipients' => [
    'to' => [ // Required: at least one recipient
        [
            'customer' => [ // Optional
                'type' => 'company',
                'id' => 'customer-uuid'
            ],
            'email' => 'recipient@example.com' // Required
        ]
    ],
    'cc' => [ // Optional
        [
            'email' => 'cc@example.com'
        ]
    ],
    'bcc' => [ // Optional
        [
            'email' => 'bcc@example.com'
        ]
    ]
]
```

### Payment Structure

```php
'payment' => [
    'amount' => 123.45, // Required: numeric
    'currency' => 'EUR' // Required: valid currency code
]
```

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'invoice-uuid',
            'invoice_number' => '2024/001',
            'invoice_date' => '2024-01-15',
            'status' => 'outstanding',
            'due_on' => '2024-02-15',
            'paid' => false,
            'sent' => true,
            'invoicee' => [
                'name' => 'Company Name',
                'customer' => [
                    'id' => 'customer-uuid',
                    'type' => 'company'
                ]
            ],
            'total' => [
                'tax_exclusive' => [
                    'amount' => 1000.00,
                    'currency' => 'EUR'
                ],
                'tax_inclusive' => [
                    'amount' => 1210.00,
                    'currency' => 'EUR'
                ],
                'payable' => [
                    'amount' => 1210.00,
                    'currency' => 'EUR'
                ],
                'due' => [
                    'amount' => 1210.00,
                    'currency' => 'EUR'
                ]
            ],
            // ... more fields
        ]
    ]
]
```

### Info Response

```php
[
    'data' => [
        'id' => 'invoice-uuid',
        'department' => [
            'id' => 'department-uuid',
            'type' => 'department'
        ],
        'invoice_number' => '2024/001',
        'invoice_date' => '2024-01-15',
        'status' => 'outstanding',
        'due_on' => '2024-02-15',
        'paid' => false,
        'paid_at' => null,
        'sent' => true,
        'purchase_order_number' => 'PO-2024-001',
        'payment_reference' => '+++084/2613/66074+++',
        'invoicee' => [
            'name' => 'Company Name',
            'vat_number' => 'BE0123456789',
            'customer' => [
                'id' => 'customer-uuid',
                'type' => 'company'
            ],
            'for_attention_of' => [
                'name' => 'Contact Name',
                'contact' => [
                    'id' => 'contact-uuid',
                    'type' => 'contact'
                ]
            ],
            'email' => 'contact@company.com'
        ],
        'discounts' => [
            [
                'type' => 'percentage',
                'value' => 10,
                'description' => 'Early payment discount'
            ]
        ],
        'grouped_lines' => [
            [
                'section' => [
                    'title' => 'Services'
                ],
                'line_items' => [
                    [
                        'product' => [
                            'id' => 'product-uuid',
                            'type' => 'product'
                        ],
                        'quantity' => 10,
                        'description' => 'Consulting hours',
                        'unit_price' => [
                            'amount' => 100.00,
                            'tax' => 'excluding'
                        ],
                        'tax' => [
                            'id' => 'tax-rate-uuid',
                            'type' => 'taxRate'
                        ],
                        'discount' => [
                            'value' => 10,
                            'type' => 'percentage'
                        ],
                        'total' => [
                            'tax_exclusive' => [
                                'amount' => 900.00,
                                'currency' => 'EUR'
                            ],
                            'tax_inclusive' => [
                                'amount' => 1089.00,
                                'currency' => 'EUR'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'total' => [
            'tax_exclusive' => [
                'amount' => 1000.00,
                'currency' => 'EUR'
            ],
            'tax_inclusive' => [
                'amount' => 1210.00,
                'currency' => 'EUR'
            ],
            'payable' => [
                'amount' => 1210.00,
                'currency' => 'EUR'
            ],
            'due' => [
                'amount' => 1210.00,
                'currency' => 'EUR'
            ],
            'taxes' => [
                [
                    'rate' => 0.21,
                    'taxable' => [
                        'amount' => 1000.00,
                        'currency' => 'EUR'
                    ],
                    'tax' => [
                        'amount' => 210.00,
                        'currency' => 'EUR'
                    ]
                ]
            ]
        ],
        'payment_term' => [
            'type' => 'after_invoice_date',
            'days' => 30
        ],
        'payments' => [
            [
                'paid_at' => '2024-02-10T10:00:00+00:00',
                'payment' => [
                    'amount' => 1210.00,
                    'currency' => 'EUR'
                ]
            ]
        ],
        'note' => 'Thank you for your business',
        'currency' => 'EUR',
        'currency_exchange_rate' => [
            'from' => 'EUR',
            'to' => 'EUR',
            'rate' => 1.0
        ],
        'file' => [
            'id' => 'file-uuid',
            'type' => 'file'
        ],
        'deal' => [
            'id' => 'deal-uuid',
            'type' => 'deal'
        ],
        'project' => [
            'id' => 'project-uuid',
            'type' => 'project'
        ],
        'created_at' => '2024-01-10T12:00:00+00:00',
        'updated_at' => '2024-01-15T14:30:00+00:00',
        'web_url' => 'https://focus.teamleader.eu/invoice_detail.php?id=...'
    ]
]
```

### Download Response

```php
[
    'data' => [
        'location' => 'https://cdn.teamleader.eu/file', // Temporary download URL
        'expires' => '2024-02-05T16:44:33+00:00' // URL expiration time
    ]
]
```

### Credit Response

```php
[
    'data' => [
        'id' => 'credit-note-uuid', // UUID of created credit note
        'type' => 'invoice'
    ]
]
```

## Notes

- **Draft Creation**: Use the `create()` method to draft new invoices
- **Draft Deletion**: Only draft invoices or the last booked invoice can be deleted
- **Booked Updates**: Updating booked invoices requires this feature to be enabled in Teamleader settings
- **Late Fees**: Include `late_fees` in the includes parameter to get late fee calculations
- **Credit Notes**: Creating credit notes generates a new invoice with negative amounts
- **Payment Registration**: Registering payments updates the invoice's paid status automatically
- **Email Sending**: Sending via email requires at least one recipient in the "to" field
- **Peppol**: Sending via Peppol requires proper Peppol configuration in Teamleader
- **Downloads**: Download URLs are temporary and expire after the time specified in the response
- **Date Format**: All dates should be in YYYY-MM-DD format
- **DateTime Format**: All datetimes should be in ISO 8601 format
- **Currency**: Prices are always tax-exclusive in the `unit_price` field
- **Discount**: Discounts are percentage-based and must be between 0 and 100
- **Markdown**: The `note` and `extended_description` fields support Markdown formatting
