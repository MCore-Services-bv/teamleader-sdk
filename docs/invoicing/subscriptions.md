# Subscriptions

Manage recurring subscriptions in Teamleader Focus. Subscriptions automatically generate invoices on a billing cycle and can be sent via email, Peppol, or postal service.

## Capabilities

| Feature | Supported |
|---------|-----------|
| List | ✅ |
| Info | ✅ |
| Create | ✅ |
| Update | ✅ |
| Delete | ❌ (use `deactivate()`) |
| Pagination | ✅ |
| Filtering | ✅ |
| Sorting | ✅ (`title`, `created_at`, `status`) |
| Sideloading | ❌ |

---

## Methods

### `list(array $filters = [], array $options = []): array`

Returns a paginated, filterable, sortable list of subscriptions.

**Filter parameters:**

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of subscription UUIDs |
| `invoice_id` | string | Find subscriptions that generated a specific invoice |
| `deal_id` | string | Subscriptions created from a specific deal |
| `department_id` | string | Filter by department UUID |
| `customer` | array | `{type: contact\|company, id: uuid}` |
| `status` | array | `['active']`, `['deactivated']`, or both |

**Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `page_size` | int | 20 | Results per page |
| `page_number` | int | 1 | Page number |
| `sort` | array | — | `{field: title\|created_at\|status, order: asc\|desc}` |

**Example:**

```php
// All active subscriptions sorted by creation date
$subscriptions = $teamleader->subscriptions()->list(
    ['status' => ['active']],
    ['sort' => ['field' => 'created_at', 'order' => 'desc']]
);
```

**List response fields (per item):**

```json
{
  "data": [
    {
      "id": "e2314517-3cab-4aa9-8471-450e73449041",
      "title": "Monthly support",
      "note": null,                                   // nullable
      "status": "active",
      "department": { "id": "...", "type": "department" },
      "invoicee": {
        "customer": { "type": "company", "id": "..." },
        "for_attention_of": null                      // nullable
      },
      "project": null,                                // nullable
      "starts_on": "2024-01-01",
      "ends_on": null,                                // nullable
      "next_renewal_date": "2024-02-01",              // nullable
      "billing_cycle": {
        "periodicity": { "unit": "month", "period": 1 },
        "days_in_advance": 7
      },
      "total": {
        "tax_exclusive": { "amount": 500.00, "currency": "EUR" },
        "tax_inclusive": { "amount": 605.00, "currency": "EUR" },
        "taxes": []
      },
      "web_url": "https://focus.teamleader.eu/subscription_detail.php?id=...",
      "created_at": "2024-01-01T09:00:00+00:00"      // nullable
    }
  ]
}
```

---

### `info(string $id): array`

Returns complete details for a single subscription, including line items, invoice generation settings, custom fields, and document template.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | ✅ | Subscription UUID |

**Example:**

```php
$subscription = $teamleader->subscriptions()->info('e2314517-3cab-4aa9-8471-450e73449041');
```

**Info response fields** (superset of list, adds):

```json
{
  "data": {
    "billing_cycle": {
      "periodicity": { "unit": "month", "period": 1 },
      "days_in_advance": 7,
      "payment_term": {
        "type": "after_invoice_date",
        "days": 30
      }
    },
    "grouped_lines": [
      {
        "section": { "title": "Support" },
        "line_items": [
          {
            "product": null,
            "quantity": 1,
            "description": "Monthly support fee",
            "extended_description": null,
            "unit": null,
            "unit_price": { "amount": 500.00, "tax": "excluding" },
            "tax": { "id": "...", "type": "taxRate" },
            "discount": null,
            "total": {
              "tax_exclusive": { "amount": 500.00, "currency": "EUR" },
              "tax_exclusive_before_discount": { "amount": 500.00, "currency": "EUR" },
              "tax_inclusive": { "amount": 605.00, "currency": "EUR" },
              "tax_inclusive_before_discount": { "amount": 605.00, "currency": "EUR" }
            },
            "product_category": null,
            "withheld_tax": null
          }
        ]
      }
    ],
    "invoice_generation": {
      "action": "book_and_send",
      "sending_methods": [
        { "method": "peppol" }
      ]
    },
    "payment_method": null,
    "custom_fields": [],
    "document_template": { "id": "...", "type": "documentTemplate" },
    "currency": "EUR",
    "created_at": "2024-01-01T09:00:00+00:00"        // nullable
  }
}
```

---

### `create(array $data): array`

Creates a new subscription. Returns HTTP 201 with `data.{id, type}`.

**Required fields:**

| Field | Type | Description |
|-------|------|-------------|
| `invoicee` | object | Customer reference — see structure below |
| `department_id` | string | Department UUID |
| `starts_on` | string | YYYY-MM-DD |
| `billing_cycle` | object | Periodicity + days_in_advance — see structure below |
| `title` | string | Subscription title |
| `grouped_lines` | array | Line items — see structure below |
| `payment_term` | object | `{type: cash\|end_of_month\|after_invoice_date, days?}` |
| `invoice_generation` | object | `{action: draft\|book\|book_and_send, ...}` |

**Optional fields:**

| Field | Type | Description |
|-------|------|-------------|
| `ends_on` | string\|null | YYYY-MM-DD end date |
| `deal_id` | string\|null | Associated deal UUID |
| `project_id` | string\|null | Associated project UUID |
| `note` | string\|null | Markdown-formatted note |
| `payment_method` | string | `direct_debit` |
| `custom_fields` | array | `[{id, value}]` |
| `document_template_id` | string | Template UUID |

**`invoice_generation` structure:**

When `action` is `book_and_send`, you must supply `sending_methods`:

```php
'invoice_generation' => [
    'action' => 'book_and_send',
    'sending_methods' => [
        ['method' => 'email'],          // Send by email
        ['method' => 'peppol'],         // Send via Peppol network
        ['method' => 'postal_service'], // Send by post
    ],
],
```

Valid methods: `email`, `peppol`, `postal_service`. Multiple methods can be combined.

**Billing cycle periodicity options:**

| Unit | Valid periods | Example |
|------|--------------|---------|
| `week` | 1, 2 | Every 1 or 2 weeks |
| `month` | 1–12 | Every N months |
| `year` | 1+ | Every N years |

**Example — monthly subscription via Peppol:**

```php
$result = $teamleader->subscriptions()->create([
    'invoicee' => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid'],
    ],
    'department_id' => 'dept-uuid',
    'starts_on' => '2024-01-01',
    'billing_cycle' => [
        'periodicity' => ['unit' => 'month', 'period' => 1],
        'days_in_advance' => 7,
    ],
    'title' => 'Monthly support',
    'grouped_lines' => [
        [
            'section' => ['title' => 'Support'],
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Monthly support fee',
                    'unit_price' => ['amount' => 500.00, 'tax' => 'excluding'],
                    'tax_rate_id' => 'tax-rate-uuid',
                ],
            ],
        ],
    ],
    'payment_term' => ['type' => 'after_invoice_date', 'days' => 30],
    'invoice_generation' => [
        'action' => 'book_and_send',
        'sending_methods' => [
            ['method' => 'peppol'],
        ],
    ],
]);

$subscriptionId = $result['data']['id'];
```

---

### `update(string $id, array $data): array`

Updates an existing subscription. Returns HTTP 204 (no body).

**Important constraints:**
- `starts_on` and `billing_cycle` can only be updated if **no invoices have been generated yet**
- All fields are optional

Updatable fields mirror the `create()` fields. `invoice_generation` follows the same structure:

```php
$teamleader->subscriptions()->update('subscription-uuid', [
    'title' => 'Updated title',
    'invoice_generation' => [
        'action' => 'book_and_send',
        'sending_methods' => [
            ['method' => 'email'],
            ['method' => 'peppol'],
        ],
    ],
]);
```

---

### `deactivate(string $id): array`

Deactivates a subscription. The subscription will no longer generate invoices. Returns HTTP 204.

```php
$teamleader->subscriptions()->deactivate('subscription-uuid');
```

---

## Convenience Methods

### `active(array $additionalFilters = [], array $options = []): array`

Returns active subscriptions (`status = ['active']`).

```php
$active = $teamleader->subscriptions()->active();
```

### `deactivated(array $additionalFilters = [], array $options = []): array`

Returns deactivated subscriptions.

```php
$inactive = $teamleader->subscriptions()->deactivated();
```

### `forCustomer(string $type, string $id, array $options = []): array`

Returns subscriptions for a specific customer.

```php
$subscriptions = $teamleader->subscriptions()->forCustomer('company', 'company-uuid');
```

### `forDepartment(string $departmentId, array $options = []): array`

Returns subscriptions for a specific department.

```php
$subscriptions = $teamleader->subscriptions()->forDepartment('dept-uuid');
```

### `forDeal(string $dealId, array $options = []): array`

Returns subscriptions created from a specific deal.

```php
$subscriptions = $teamleader->subscriptions()->forDeal('deal-uuid');
```

### `forInvoice(string $invoiceId, array $options = []): array`

Returns subscriptions that generated a specific invoice. Useful for tracing which subscription produced an invoice.

```php
$subscriptions = $teamleader->subscriptions()->forInvoice('invoice-uuid');
```

### `byIds(array $ids, array $options = []): array`

Returns subscriptions matching an array of UUIDs.

```php
$subscriptions = $teamleader->subscriptions()->byIds([
    'uuid-1',
    'uuid-2',
]);
```

---

## Sending Methods

The `invoice_generation.sending_methods` array controls how invoices are delivered when `action` is `book_and_send`. Multiple methods can be combined.

| Method | Description |
|--------|-------------|
| `email` | Deliver invoice by email |
| `peppol` | Deliver invoice via the Peppol e-invoicing network |
| `postal_service` | Deliver invoice by post |

**Combining methods:**

```php
'sending_methods' => [
    ['method' => 'email'],
    ['method' => 'peppol'],
],
```

---

## Common Use Cases

### Audit All Active Subscriptions

```php
$active = $teamleader->subscriptions()->active(
    [],
    ['sort' => ['field' => 'created_at', 'order' => 'asc']]
);

foreach ($active['data'] as $sub) {
    echo "{$sub['title']} — next renewal: {$sub['next_renewal_date']}\n";
}
```

### Find Which Subscription Generated an Invoice

```php
// When you receive an invoice with a subscription reference
$invoice = $teamleader->invoices()->info('invoice-uuid');

if (isset($invoice['data']['subscription'])) {
    $sub = $teamleader->subscriptions()->info(
        $invoice['data']['subscription']['id']
    );
    echo "Generated by: {$sub['data']['title']}";
}

// Or look it up from the subscription side
$subscriptions = $teamleader->subscriptions()->forInvoice('invoice-uuid');
```

### Migrate a Subscription to Peppol Delivery

```php
// Get current settings
$sub = $teamleader->subscriptions()->info('subscription-uuid');

// Switch delivery to Peppol
$teamleader->subscriptions()->update('subscription-uuid', [
    'invoice_generation' => [
        'action' => 'book_and_send',
        'sending_methods' => [
            ['method' => 'peppol'],
        ],
    ],
]);
```

### List Subscriptions Created This Year

```php
$all = $teamleader->subscriptions()->active();

$thisYear = array_filter($all['data'], function ($sub) {
    return $sub['created_at'] !== null &&
        str_starts_with($sub['created_at'], date('Y'));
});
```

### Deactivate Expired Subscriptions

```php
$active = $teamleader->subscriptions()->active();

foreach ($active['data'] as $sub) {
    if ($sub['ends_on'] !== null && $sub['ends_on'] < date('Y-m-d')) {
        $teamleader->subscriptions()->deactivate($sub['id']);
    }
}
```

---

## Validation

The SDK validates the following before sending requests:

| Field | Validation |
|-------|-----------|
| `invoicee.customer.type` | Must be `contact` or `company` |
| `billing_cycle.periodicity.unit` | Must be `week`, `month`, or `year` |
| `payment_term.type` | Must be `cash`, `end_of_month`, or `after_invoice_date` |
| `invoice_generation.action` | Must be `draft`, `book`, or `book_and_send` |
| `invoice_generation.sending_methods[].method` | Must be `email`, `peppol`, or `postal_service` |
| `grouped_lines` | Must be non-empty; each group must have non-empty `line_items` |
| Line items | Must have `quantity`, `description`, `unit_price.amount`, `unit_price.tax`, `tax_rate_id` |
| `filter.status` | Values must be `active` or `deactivated` |

---

## Related Resources

- **Invoices** — Invoices generated by subscriptions carry a `subscription` reference
- **Deals** — Subscriptions can be created from a deal (`deal_id`)
- **Projects** — Subscriptions can be linked to a project (`project_id`)
- **Departments** — Each subscription belongs to a department
- **Companies / Contacts** — Subscription invoicee is a CRM entity
