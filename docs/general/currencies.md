# Currencies

Retrieve exchange rates and perform currency conversions in Teamleader Focus.

## Overview

The Currencies resource is structurally different from all other resources in the SDK. It has
no `list()`, `info()`, `create()`, `update()`, or `delete()` methods — calling any of those throws
a `BadMethodCallException`. The primary entry point is `exchangeRates()`, which fetches all supported rates relative to
a base currency.

`convert()` and `getRate()` are **local SDK helpers** — they call `exchangeRates()` internally and compute the result
themselves. They do not make separate API calls.

## Endpoint

`currencies`

## Capabilities

| Capability  | Supported        |
|-------------|------------------|
| Pagination  | ❌ Not applicable |
| Filtering   | ❌ Not applicable |
| Sorting     | ❌ Not applicable |
| Sideloading | ❌ Not applicable |
| Creation    | ❌ Not supported  |
| Update      | ❌ Not supported  |
| Deletion    | ❌ Not supported  |

> **Note:** `list()` and `info()` both throw `BadMethodCallException`. Use `exchangeRates()` instead.

---

## Methods

### `exchangeRates(string $baseCurrency)`

Fetches all supported exchange rates relative to a base currency. The currency code is validated against the supported
list before the request is sent — an `InvalidArgumentException` is thrown for unknown codes.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$rates = Teamleader::currencies()->exchangeRates('EUR');

foreach ($rates['data'] as $currency) {
    echo "{$currency['code']}: {$currency['exchange_rate']}\n";
}
```

---

### `convert(float $amount, string $fromCurrency, string $toCurrency)`

Converts an amount between two currencies. Calls `exchangeRates($fromCurrency)` internally — **one API call**.

**Same-currency conversion** returns immediately with `exchange_rate: 1.0`, no API call made.

**Currency not found** returns an error array rather than throwing:

```php
['error' => true, 'message' => 'Exchange rate not found for EUR to XYZ']
```

`converted_amount` is rounded to **4 decimal places**.

```php
$result = Teamleader::currencies()->convert(1000.00, 'EUR', 'USD');

// [
//     'amount'           => 1000.0,
//     'converted_amount' => 1095.0000,  // rounded to 4dp
//     'exchange_rate'    => 1.095,
//     'from_currency'    => 'EUR',
//     'to_currency'      => 'USD',
// ]
```

---

### `getRate(string $baseCurrency, string $targetCurrency)`

Returns the exchange rate and metadata for a specific currency pair. Calls `exchangeRates($baseCurrency)` internally — *
*one API call**.

**Same-currency pair** returns `rate: 1.0` immediately without an API call.

**Currency not found** returns an error array.

```php
$rate = Teamleader::currencies()->getRate('EUR', 'USD');

// [
//     'base'   => 'EUR',
//     'target' => 'USD',
//     'rate'   => 1.095,
//     'symbol' => '$',
//     'name'   => 'US Dollar',
// ]
```

---

## Helper Methods

### Base currency shortcuts

```php
$rates = Teamleader::currencies()->eurRates(); // exchangeRates('EUR')
$rates = Teamleader::currencies()->usdRates(); // exchangeRates('USD')
$rates = Teamleader::currencies()->gbpRates(); // exchangeRates('GBP')
```

### Validation and introspection

```php
// Validate a code before using it
$valid = Teamleader::currencies()->isValidCurrencyCode('EUR'); // true
$valid = Teamleader::currencies()->isValidCurrencyCode('XYZ'); // false

// All supported currencies as code => name map
$currencies = Teamleader::currencies()->getSupportedCurrencies();
// ['BAM' => 'Bosnian Mark', 'CAD' => 'Canadian Dollar', ...]

// Just the codes as a flat array
$codes = Teamleader::currencies()->getSupportedCurrencyCodes();
// ['BAM', 'CAD', 'CHF', ...]

// Name for a specific code
$name = Teamleader::currencies()->getCurrencyName('EUR'); // 'Euro'
```

### Common pairs

Returns a predefined map of frequently used currency pairs — no API call.

```php
$pairs = Teamleader::currencies()->getCommonPairs();
// [
//     'EUR/USD' => ['base' => 'EUR', 'target' => 'USD'],
//     'USD/EUR' => ['base' => 'USD', 'target' => 'EUR'],
//     'GBP/EUR' => ['base' => 'GBP', 'target' => 'EUR'],
//     'EUR/GBP' => ['base' => 'EUR', 'target' => 'GBP'],
//     'USD/GBP' => ['base' => 'USD', 'target' => 'GBP'],
//     'GBP/USD' => ['base' => 'GBP', 'target' => 'USD'],
//     'EUR/CHF' => ['base' => 'EUR', 'target' => 'CHF'],
//     'USD/JPY' => ['base' => 'USD', 'target' => 'JPY'],
// ]
```

---

## Supported Currencies

| Code | Name               |
|------|--------------------|
| BAM  | Bosnian Mark       |
| CAD  | Canadian Dollar    |
| CHF  | Swiss Franc        |
| CLP  | Chilean Peso       |
| CNY  | Chinese Yuan       |
| COP  | Colombian Peso     |
| CZK  | Czech Koruna       |
| DKK  | Danish Krone       |
| EUR  | Euro               |
| GBP  | British Pound      |
| INR  | Indian Rupee       |
| ISK  | Icelandic Krona    |
| JPY  | Japanese Yen       |
| MAD  | Moroccan Dirham    |
| MXN  | Mexican Peso       |
| NOK  | Norwegian Krone    |
| PEN  | Peruvian Sol       |
| PLN  | Polish Zloty       |
| RON  | Romanian Leu       |
| SEK  | Swedish Krona      |
| TRY  | Turkish Lira       |
| USD  | US Dollar          |
| ZAR  | South African Rand |

---

## Response Structure

### `exchangeRates()` response

```php
[
    'data' => [
        [
            'code'          => 'USD',
            'name'          => 'US Dollar',
            'symbol'        => '$',
            'exchange_rate' => 1.095,
        ],
        [
            'code'          => 'GBP',
            'name'          => 'British Pound',
            'symbol'        => '£',
            'exchange_rate' => 0.857,
        ],
        // one entry per supported currency
    ],
]
```

### `convert()` response

```php
// Success
[
    'amount'           => 1000.0,
    'converted_amount' => 1095.0000, // 4 decimal places
    'exchange_rate'    => 1.095,
    'from_currency'    => 'EUR',
    'to_currency'      => 'USD',
]

// Currency not found (no exception thrown)
[
    'error'   => true,
    'message' => 'Exchange rate not found for EUR to XYZ',
]
```

### `getRate()` response

```php
// Success
[
    'base'   => 'EUR',
    'target' => 'USD',
    'rate'   => 1.095,
    'symbol' => '$',
    'name'   => 'US Dollar',
]

// Currency not found (no exception thrown)
[
    'error'   => true,
    'message' => 'Exchange rate not found for EUR/XYZ',
]
```

---

## Usage Examples

### Get all rates and find a specific one

```php
$rates = Teamleader::currencies()->exchangeRates('EUR');

$usdRate = null;
foreach ($rates['data'] as $currency) {
    if ($currency['code'] === 'USD') {
        $usdRate = $currency['exchange_rate'];
        break;
    }
}
```

### Convert an invoice amount

```php
$result = Teamleader::currencies()->convert(2500.00, 'EUR', 'USD');

if (!isset($result['error'])) {
    $converted = $result['converted_amount']; // 4dp
    $rounded   = round($converted, 2);        // round for display
}
```

### Build a currency select list

```php
$currencies = Teamleader::currencies()->getSupportedCurrencies();

$options = [];
foreach ($currencies as $code => $name) {
    $options[$code] = "{$code} — {$name}";
}
// ['BAM' => 'BAM — Bosnian Mark', 'CAD' => 'CAD — Canadian Dollar', ...]
```

### Cache exchange rates

Exchange rates change infrequently during business hours. Cache them to avoid repeated API calls:

```php
$rates = Cache::remember('tl_exchange_rates_eur', 3600, function () {
    return Teamleader::currencies()->exchangeRates('EUR');
});
```

---

## Error Handling

```php
use BadMethodCallException;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid currency code — thrown before the request
try {
    $rates = Teamleader::currencies()->exchangeRates('XYZ');
} catch (InvalidArgumentException $e) {
    // 'Invalid currency code: XYZ'
    Log::error($e->getMessage());
}

// convert() / getRate() — check for error array, not exception
$result = Teamleader::currencies()->convert(100, 'EUR', 'XYZ');
if (isset($result['error']) && $result['error']) {
    Log::warning($result['message']);
}

// list() / info() — always throw
try {
    Teamleader::currencies()->list();
} catch (BadMethodCallException $e) {
    // Use exchangeRates() instead
}

// API-level errors
try {
    $rates = Teamleader::currencies()->exchangeRates('EUR');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Invoices]] — Invoices support multiple currencies
- [[Deals]] — Deals can carry a currency and exchange rate
- [[Filtering]] — General filter reference
