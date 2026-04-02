# Withholding Tax Rates

Read withholding tax rate definitions in Teamleader Focus.

## Overview

Withholding tax rates apply to invoices where tax is withheld at source — common in certain professional services
invoicing contexts. The rates are configured in Teamleader Focus settings and cannot be modified through the API.

Access via `Teamleader::withholdingTaxRates()`.

> **No filters, pagination, or sorting.** `list()` passes an empty filter object to the API and returns all rates in one
> response. All helpers are **client-side**.
>
> **Float comparison uses tolerance of `0.0001`** in `findByRate()` to avoid IEEE 754 floating-point precision issues.

## Endpoint

`withholdingTaxRates`

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

### `list(array $filters = [], array $options = [])`

Returns all withholding tax rates in a single response.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$rates = Teamleader::withholdingTaxRates()->list();
```

---

## Helper Methods

All helpers are **client-side** — they call `list()` then filter in PHP.

### `find(string $id)`

Returns the rate object with that UUID, or `null`.

```php
$rate = Teamleader::withholdingTaxRates()->find('rate-uuid');
```

### `findByRate(float $rate)`

Matches using float tolerance (`0.0001`). Returns the first match, or `null`.

```php
$rate = Teamleader::withholdingTaxRates()->findByRate(0.15); // 15%
$rate = Teamleader::withholdingTaxRates()->findByRate(0.10); // 10%
```

### `findByDescription(string $description, bool $exactMatch = true)`

Case-insensitive search. Default is exact match; pass `false` for partial.

```php
$rate = Teamleader::withholdingTaxRates()->findByDescription('15%');
$rate = Teamleader::withholdingTaxRates()->findByDescription('15', false); // partial
```

### `findByRateRange(float $minRate, float $maxRate)`

Returns all rates between two values (inclusive). Returns an empty array if none found.

```php
$rates = Teamleader::withholdingTaxRates()->findByRateRange(0.05, 0.15);
```

### `asOptions()`

Returns flat `[id => description]` map.

```php
$options = Teamleader::withholdingTaxRates()->asOptions();
// ['uuid-1' => '15%', 'uuid-2' => '10%', 'uuid-3' => '0%']
```

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'uuid', 'description' => '15%', 'rate' => 0.15],
        ['id' => 'uuid', 'description' => '10%', 'rate' => 0.10],
        ['id' => 'uuid', 'description' => '0%',  'rate' => 0.0],
    ],
]
```

---

## Usage Examples

```php
// Use on invoice creation
$rate = Teamleader::withholdingTaxRates()->findByRate(0.15);

Teamleader::invoices()->create([
    ...,
    'withholding_tax_rate_id' => $rate['id'],
]);

// Cache rates
$rates = Cache::remember('tl_withholding_tax_rates', 86400, fn() =>
    Teamleader::withholdingTaxRates()->list()
);
```

---

## Related Resources

- [[Invoices]] — `withholding_tax_rate_id` is an optional field on invoice creation
- [[Tax-Rates]] — Standard VAT/sales tax rates applied to line items
