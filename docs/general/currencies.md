# Currencies

Manage currency exchange rates in Teamleader Focus.

## Overview

The Currencies resource provides access to exchange rates for various currencies supported by Teamleader. Unlike most resources, this one uses a specialized approach focused on exchange rate retrieval and currency conversion rather than standard CRUD operations.

**Important:** The Currencies resource is read-only and uses the `exchangeRates()` method instead of standard `list()` or `info()` methods.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [exchangeRates()](#exchangerates)
    - [convert()](#convert)
    - [getRate()](#getrate)
- [Helper Methods](#helper-methods)
- [Supported Currencies](#supported-currencies)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`currencies`

## Capabilities

- **Exchange Rates**: ✅ Supported
- **Currency Conversion**: ✅ Supported
- **Pagination**: ❌ Not Applicable
- **Filtering**: ❌ Not Applicable
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `exchangeRates()`

Get exchange rates for all supported currencies relative to a base currency.

**Parameters:**
- `baseCurrency` (string): The base currency code (e.g., 'EUR', 'USD')

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get exchange rates with EUR as base
$rates = Teamleader::currencies()->exchangeRates('EUR');

// Get exchange rates with USD as base
$rates = Teamleader::currencies()->exchangeRates('USD');
```

### `convert()`

Convert an amount from one currency to another using current exchange rates.

**Parameters:**
- `amount` (float): Amount to convert
- `fromCurrency` (string): Source currency code
- `toCurrency` (string): Target currency code

**Example:**
```php
// Convert 100 EUR to USD
$result = Teamleader::currencies()->convert(100, 'EUR', 'USD');

// Returns:
[
    'amount' => 100,
    'converted_amount' => 109.50,
    'exchange_rate' => 1.095,
    'from_currency' => 'EUR',
    'to_currency' => 'USD'
]
```

### `getRate()`

Get the exchange rate for a specific currency pair.

**Parameters:**
- `baseCurrency` (string): Base currency code
- `targetCurrency` (string): Target currency code

**Example:**
```php
// Get EUR to USD exchange rate
$rate = Teamleader::currencies()->getRate('EUR', 'USD');

// Returns:
[
    'base' => 'EUR',
    'target' => 'USD',
    'rate' => 1.095,
    'symbol' => '$',
    'name' => 'US Dollar'
]
```

## Helper Methods

### Currency Shortcuts

```php
// Get EUR exchange rates
$rates = Teamleader::currencies()->eurRates();

// Get USD exchange rates
$rates = Teamleader::currencies()->usdRates();

// Get GBP exchange rates
$rates = Teamleader::currencies()->gbpRates();
```

### Validation Methods

```php
// Check if currency code is valid
if (Teamleader::currencies()->isValidCurrencyCode('EUR')) {
    echo "EUR is supported";
}

// Get list of supported currencies
$currencies = Teamleader::currencies()->getSupportedCurrencies();

// Get list of currency codes only
$codes = Teamleader::currencies()->getSupportedCurrencyCodes();
```

### Common Currency Pairs

```php
// Get common trading pairs
$pairs = Teamleader::currencies()->getCommonPairs();

// Returns:
[
    'EUR/USD' => ['base' => 'EUR', 'target' => 'USD'],
    'USD/EUR' => ['base' => 'USD', 'target' => 'EUR'],
    'GBP/EUR' => ['base' => 'GBP', 'target' => 'EUR'],
    // ... more pairs
]
```

## Supported Currencies

The following currencies are supported:

| Code | Currency Name |
|------|---------------|
| BAM | Bosnian Mark |
| CAD | Canadian Dollar |
| CHF | Swiss Franc |
| CLP | Chilean Peso |
| CNY | Chinese Yuan |
| COP | Colombian Peso |
| CZK | Czech Koruna |
| DKK | Danish Krone |
| EUR | Euro |
| GBP | British Pound |
| INR | Indian Rupee |
| ISK | Icelandic Krona |
| JPY | Japanese Yen |
| MAD | Moroccan Dirham |
| MXN | Mexican Peso |
| NOK | Norwegian Krone |
| PEN | Peruvian Sol |
| PLN | Polish Zloty |
| RON | Romanian Leu |
| SEK | Swedish Krona |
| TRY | Turkish Lira |
| USD | US Dollar |
| ZAR | South African Rand |

Get the list programmatically:

```php
$currencies = Teamleader::currencies()->getSupportedCurrencies();
$codes = Teamleader::currencies()->getSupportedCurrencyCodes();
```

## Response Structure

### Exchange Rates Response

```php
[
    'data' => [
        [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.095
        ],
        [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'exchange_rate' => 0.857
        ],
        // ... more currencies
    ]
]
```

## Usage Examples

### Get Current Exchange Rates

```php
// Get all exchange rates relative to EUR
$rates = Teamleader::currencies()->exchangeRates('EUR');

foreach ($rates['data'] as $currency) {
    echo "{$currency['code']}: {$currency['exchange_rate']}\n";
}
```

### Convert Currency Amounts

```php
// Convert invoice amount
$invoiceAmount = 1000; // EUR
$result = Teamleader::currencies()->convert($invoiceAmount, 'EUR', 'USD');

echo "€{$invoiceAmount} = \${$result['converted_amount']}";
```

### Build a Currency Converter

```php
class CurrencyConverter
{
    public function convert($amount, $from, $to)
    {
        // Validate currencies
        $validator = Teamleader::currencies();
        
        if (!$validator->isValidCurrencyCode($from)) {
            throw new \InvalidArgumentException("Invalid source currency: {$from}");
        }
        
        if (!$validator->isValidCurrencyCode($to)) {
            throw new \InvalidArgumentException("Invalid target currency: {$to}");
        }
        
        // Perform conversion
        return Teamleader::currencies()->convert($amount, $from, $to);
    }
}
```

### Display Exchange Rate Widget

```php
class ExchangeRateWidget
{
    public function getRates($baseCurrency = 'EUR')
    {
        $rates = Teamleader::currencies()->exchangeRates($baseCurrency);
        
        $formatted = [];
        foreach ($rates['data'] as $currency) {
            $formatted[] = [
                'code' => $currency['code'],
                'name' => $currency['name'],
                'symbol' => $currency['symbol'],
                'rate' => number_format($currency['exchange_rate'], 4),
                'flag' => $this->getCurrencyFlag($currency['code'])
            ];
        }
        
        return $formatted;
    }
    
    private function getCurrencyFlag($code)
    {
        // Map currency codes to country flags
        $flags = [
            'EUR' => '🇪🇺',
            'USD' => '🇺🇸',
            'GBP' => '🇬🇧',
            'JPY' => '🇯🇵',
            'CHF' => '🇨🇭',
            // ... more mappings
        ];
        
        return $flags[$code] ?? '🌍';
    }
}
```

## Common Use Cases

### Multi-Currency Pricing

```php
class PricingService
{
    public function getPriceInCurrency($basePrice, $baseCurrency, $targetCurrency)
    {
        if ($baseCurrency === $targetCurrency) {
            return $basePrice;
        }
        
        $result = Teamleader::currencies()->convert(
            $basePrice,
            $baseCurrency,
            $targetCurrency
        );
        
        return round($result['converted_amount'], 2);
    }
    
    public function getAllPrices($basePrice, $baseCurrency)
    {
        $rates = Teamleader::currencies()->exchangeRates($baseCurrency);
        $prices = [];
        
        foreach ($rates['data'] as $currency) {
            $prices[$currency['code']] = [
                'amount' => round($basePrice * $currency['exchange_rate'], 2),
                'symbol' => $currency['symbol'],
                'formatted' => $currency['symbol'] . number_format($basePrice * $currency['exchange_rate'], 2)
            ];
        }
        
        return $prices;
    }
}
```

### Invoice Currency Conversion

```php
class InvoiceService
{
    public function convertInvoiceAmount($invoiceAmount, $invoiceCurrency, $targetCurrency)
    {
        $result = Teamleader::currencies()->convert(
            $invoiceAmount,
            $invoiceCurrency,
            $targetCurrency
        );
        
        return [
            'original_amount' => $invoiceAmount,
            'original_currency' => $invoiceCurrency,
            'converted_amount' => $result['converted_amount'],
            'target_currency' => $targetCurrency,
            'exchange_rate' => $result['exchange_rate'],
            'conversion_date' => now()->toDateString()
        ];
    }
}
```

### Cache Exchange Rates

```php
use Illuminate\Support\Facades\Cache;

class CachedCurrencyService
{
    public function getRates($baseCurrency)
    {
        $cacheKey = "exchange_rates.{$baseCurrency}";
        
        // Cache for 1 hour
        return Cache::remember($cacheKey, 3600, function() use ($baseCurrency) {
            return Teamleader::currencies()->exchangeRates($baseCurrency);
        });
    }
    
    public function convert($amount, $from, $to)
    {
        // Get cached rates
        $rates = $this->getRates($from);
        
        // Find target currency rate
        foreach ($rates['data'] as $currency) {
            if ($currency['code'] === $to) {
                return [
                    'amount' => $amount,
                    'converted_amount' => round($amount * $currency['exchange_rate'], 2),
                    'rate' => $currency['exchange_rate'],
                    'from' => $from,
                    'to' => $to
                ];
            }
        }
        
        throw new \Exception("Currency {$to} not found");
    }
}
```

### Currency Dropdown Builder

```php
class CurrencyDropdownBuilder
{
    public function build()
    {
        $currencies = Teamleader::currencies()->getSupportedCurrencies();
        
        $options = [];
        foreach ($currencies as $code => $name) {
            $options[] = [
                'value' => $code,
                'label' => "{$code} - {$name}"
            ];
        }
        
        // Sort alphabetically by code
        usort($options, function($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        
        return $options;
    }
}
```

### Real-time Exchange Rate Display

```php
class ExchangeRateDashboard
{
    public function getCurrentRates($baseCurrency = 'EUR', $targetCurrencies = ['USD', 'GBP', 'CHF'])
    {
        $rates = Teamleader::currencies()->exchangeRates($baseCurrency);
        
        $display = [];
        foreach ($rates['data'] as $currency) {
            if (in_array($currency['code'], $targetCurrencies)) {
                $display[] = [
                    'pair' => "{$baseCurrency}/{$currency['code']}",
                    'rate' => $currency['exchange_rate'],
                    'formatted' => "1 {$baseCurrency} = {$currency['exchange_rate']} {$currency['code']}",
                    'timestamp' => now()->toDateTimeString()
                ];
            }
        }
        
        return $display;
    }
}
```

## Best Practices

### 1. Cache Exchange Rates

Exchange rates don't change frequently, so cache them:

```php
// Good: Cache for 1 hour
$rates = Cache::remember('exchange_rates_eur', 3600, function() {
    return Teamleader::currencies()->exchangeRates('EUR');
});

// Bad: Fetch every time
$rates = Teamleader::currencies()->exchangeRates('EUR');
```

### 2. Validate Currency Codes

```php
// Good: Validate before converting
if (Teamleader::currencies()->isValidCurrencyCode($currency)) {
    $result = Teamleader::currencies()->convert($amount, 'EUR', $currency);
}

// Bad: No validation
$result = Teamleader::currencies()->convert($amount, 'EUR', $currency);
```

### 3. Handle Same Currency Conversions

```php
// Good: Check if same currency
if ($fromCurrency === $toCurrency) {
    return $amount;
}

$result = Teamleader::currencies()->convert($amount, $fromCurrency, $toCurrency);

// Bad: Unnecessary API call
$result = Teamleader::currencies()->convert($amount, $fromCurrency, $toCurrency);
```

### 4. Round Converted Amounts

```php
// Good: Round to appropriate decimal places
$converted = round($result['converted_amount'], 2);

// Bad: Using raw conversion
$converted = $result['converted_amount']; // Could be 123.456789
```

### 5. Store Historical Rates

```php
// Good: Store rates for audit trail
class RateHistory
{
    public function storeRate($from, $to, $rate)
    {
        \App\Models\ExchangeRate::create([
            'from_currency' => $from,
            'to_currency' => $to,
            'rate' => $rate,
            'date' => now()
        ]);
    }
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $rates = Teamleader::currencies()->exchangeRates('EUR');
} catch (TeamleaderException $e) {
    Log::error('Error fetching exchange rates', [
        'error' => $e->getMessage()
    ]);
    
    // Use fallback rates or cached data
    $rates = Cache::get('exchange_rates_eur_fallback');
}
```

## Method Restrictions

The following standard methods are **not available** for the Currencies resource:

```php
// These will throw BadMethodCallException
Teamleader::currencies()->list();      // ❌ Not available
Teamleader::currencies()->info($id);   // ❌ Not available
Teamleader::currencies()->create([]);  // ❌ Not available
Teamleader::currencies()->update();    // ❌ Not available
Teamleader::currencies()->delete();    // ❌ Not available
```

Use `exchangeRates()`, `convert()`, or `getRate()` instead.

## Related Resources

- [Departments](departments.md) - Departments may have different currencies
- [Invoices](../invoicing/invoices.md) - Invoices support multiple currencies
- [Deals](../deals/deals.md) - Deals can be in different currencies

## See Also

- [Usage Guide](../usage.md) - General SDK usage
