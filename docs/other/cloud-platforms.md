# Cloud Platforms

Fetch cloud platform URLs for Teamleader documents.

## Overview

The Cloud Platforms resource generates direct deep-link URLs to specific documents in the Teamleader Focus cloud
interface. Useful for embedding links in your own app that take users straight to an invoice, quotation, or ticket in
Teamleader.

Access via `Teamleader::cloudPlatforms()`.

> **Valid types:** `invoice`, `quotation`, `ticket` only. Any other value throws `InvalidArgumentException` before the
> request.
>
> **`batchUrls()` makes one API call per ID** — it loops internally. There is no batch endpoint.

## Endpoint

`cloudPlatforms`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ❌ Not supported |
| Filtering   | ❌ Not supported |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ❌ Not supported |
| Update      | ❌ Not supported |
| Deletion    | ❌ Not supported |

---

## Methods

### `url(string $type, string $id)`

Core method. Validates `$type` and `$id` (UUID format) before the request.

**Valid types:** `invoice`, `quotation`, `ticket`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$result = Teamleader::cloudPlatforms()->url('invoice', 'invoice-uuid');
$url    = $result['data']['url'];
```

---

## Helper Methods

### Type-specific wrappers (return full response array)

```php
$result = Teamleader::cloudPlatforms()->invoiceUrl('invoice-uuid');
$result = Teamleader::cloudPlatforms()->quotationUrl('quotation-uuid');
$result = Teamleader::cloudPlatforms()->ticketUrl('ticket-uuid');
```

### String unwrapping wrappers (return URL string directly)

```php
$url = Teamleader::cloudPlatforms()->getUrl('invoice', 'invoice-uuid');
$url = Teamleader::cloudPlatforms()->getInvoiceUrl('invoice-uuid');
$url = Teamleader::cloudPlatforms()->getQuotationUrl('quotation-uuid');
$url = Teamleader::cloudPlatforms()->getTicketUrl('ticket-uuid');
```

### `batchUrls(string $type, array $ids)`

Makes one API call per ID. Returns `[id => url, ...]`. Throws if `$type` is invalid or `$ids` is empty.

```php
$urls = Teamleader::cloudPlatforms()->batchUrls('invoice', [
    'invoice-uuid-1',
    'invoice-uuid-2',
    'invoice-uuid-3',
]);
// ['invoice-uuid-1' => 'https://...', 'invoice-uuid-2' => 'https://...']
```

---

## Response Structure

```php
[
    'data' => [
        'url' => 'https://app.teamleader.eu/invoices/view/invoice-uuid',
    ],
]
```

---

## Usage Examples

### Embed an invoice link

```php
$url = Teamleader::cloudPlatforms()->getInvoiceUrl('invoice-uuid');

// In a Blade view:
// <a href="{{ $url }}" target="_blank">View in Teamleader</a>
```

### Redirect to a quotation

```php
$url = Teamleader::cloudPlatforms()->getQuotationUrl($quotationId);
return redirect($url);
```

### Add links to a list of invoices

```php
$invoices = Teamleader::invoices()->list(['status' => ['outstanding']]);
$ids      = array_column($invoices['data'], 'id');

$urls = Teamleader::cloudPlatforms()->batchUrls('invoice', $ids);

foreach ($invoices['data'] as $invoice) {
    $invoice['cloud_url'] = $urls[$invoice['id']] ?? null;
}
```

---

## Error Handling

```php
use InvalidArgumentException;

// Invalid type
try {
    Teamleader::cloudPlatforms()->url('deal', 'deal-uuid');
} catch (InvalidArgumentException $e) {
    // 'Invalid type: deal. Must be one of: invoice, quotation, ticket'
}

// Empty ID
try {
    Teamleader::cloudPlatforms()->url('invoice', '');
} catch (InvalidArgumentException $e) {
    // 'Resource ID is required'
}

// Invalid UUID format
try {
    Teamleader::cloudPlatforms()->url('invoice', 'not-a-uuid');
} catch (InvalidArgumentException $e) {
    // 'Invalid ID format: not-a-uuid. Must be a valid UUID.'
}
```

---

## Related Resources

- [[Invoices]] — Cloud links for invoices
- [[Quotations]] — Cloud links for quotations
