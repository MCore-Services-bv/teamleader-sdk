# Currencies

Get currency exchange rates from Teamleader Focus. This resource provides access to current exchange rates for supported currencies.

## Endpoint

`currencies.exchangeRates`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `exchangeRates(string $baseCurrency)`

Get exchange rates for a specific base currency.

**Parameters:**
- `baseCurrency` (string): The base currency code (e.g., 'EUR', 'USD')

**Example:**
```php
$rates = $teamleader->currencies()->exchangeRates('EUR');
```

### `eurRates()`

Get exchange rates with EUR as the base currency (convenience method).

**Example:**
```php
$eurRates = $teamleader->currencies()->eurRates();
```

### `usdRates()`

Get exchange rates with USD as the base currency (convenience method).

**Example:**
```php
$usdRates = $teamleader->currencies()->usdRates();
```

### `gbpRates()`

Get exchange rates with GBP as the base currency (convenience method).

**Example:**
```php
$gbpRates = $teamleader->currencies()->gbpRates();
```

### `convert(float $amount, string $fromCurrency, string $toCurrency)`

Convert an amount from one currency to another using current exchange rates.

**Parameters:**
- `amount` (float): The amount to convert
- `fromCurrency` (string): Source currency code
- `toCurrency` (string): Target currency code

**Example:**
```php
$conversion = $teamleader->currencies()->convert(100.00, 'EUR', 'USD');
```

### `getRate(string $baseCurrency, string $targetCurrency)`

Get the exchange rate for a specific currency pair.

**Parameters:**
- `baseCurrency` (string): Base currency code
- `targetCurrency` (string): Target currency code

**Example:**
```php
$rate = $teamleader->currencies()->getRate('EUR', 'USD');
```

### Utility Methods

#### `getSupportedCurrencies()`
Returns an array of all supported currency codes with their names.

#### `getSupportedCurrencyCodes()`
Returns an array of supported currency codes only.

#### `isValidCurrencyCode(string $currencyCode)`
Check if a currency code is supported.

#### `getCurrencyName(string $currencyCode)`
Get the full name of a currency by its code.

#### `getCommonPairs()`
Returns common currency pairs for quick reference.

## Supported Currencies

The following currency codes are supported:

| Code | Name |
|------|------|
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

## Response Format

### Exchange Rates Response

```json
{
    "data": [
        {
            "code": "USD",
            "symbol": "$",
            "name": "US Dollar",
            "exchange_rate": 1.1238
        },
        {
            "code": "GBP",
            "symbol": "£",
            "name": "British Pound",
            "exchange_rate": 0.8642
        }
    ]
}
```

### Currency Conversion Response

```json
{
    "amount": 100.0,
    "converted_amount": 112.38,
    "exchange_rate": 1.1238,
    "from_currency": "EUR",
    "to_currency": "USD"
}
```

### Single Rate Response

```json
{
    "base": "EUR",
    "target": "USD",
    "rate": 1.1238,
    "symbol": "$",
    "name": "US Dollar"
}
```

## Usage Examples

### Basic Exchange Rates

Get exchange rates for EUR:

```php
$rates = $teamleader->currencies()->exchangeRates('EUR');

// Access specific currency rates
foreach ($rates['data'] as $currency) {
    echo "{$currency['name']} ({$currency['code']}): {$currency['exchange_rate']}\n";
}
```

### Convenience Methods

```php
// Get EUR rates (same as exchangeRates('EUR'))
$eurRates = $teamleader->currencies()->eurRates();

// Get USD rates
$usdRates = $teamleader->currencies()->usdRates();

// Get GBP rates
$gbpRates = $teamleader->currencies()->gbpRates();
```

### Currency Conversion

```php
// Convert 100 EUR to USD
$conversion = $teamleader->currencies()->convert(100.00, 'EUR', 'USD');

if (!isset($conversion['error'])) {
    echo "€{$conversion['amount']} = \${$conversion['converted_amount']}";
    echo " (Rate: {$conversion['exchange_rate']})";
}
```

### Get Specific Exchange Rate

```php
// Get EUR to USD rate
$rate = $teamleader->currencies()->getRate('EUR', 'USD');

if (!isset($rate['error'])) {
    echo "1 {$rate['base']} = {$rate['rate']} {$rate['target']}";
}
```

### Working with Supported Currencies

```php
// Get all supported currencies
$currencies = $teamleader->currencies()->getSupportedCurrencies();

foreach ($currencies as $code => $name) {
    echo "{$code}: {$name}\n";
}

// Check if currency is supported
if ($teamleader->currencies()->isValidCurrencyCode('EUR')) {
    echo "EUR is supported";
}

// Get currency name
$name = $teamleader->currencies()->getCurrencyName('USD');
echo $name; // "US Dollar"
```

### Common Currency Pairs

```php
$pairs = $teamleader->currencies()->getCommonPairs();

foreach ($pairs as $pairName => $pair) {
    $rate = $teamleader->currencies()->getRate($pair['base'], $pair['target']);
    if (!isset($rate['error'])) {
        echo "{$pairName}: {$rate['rate']}\n";
    }
}
```

### Laravel Integration Example

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CurrencyController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $baseCurrency = request('base', 'EUR');
        
        // Validate currency
        if (!$teamleader->currencies()->isValidCurrencyCode($baseCurrency)) {
            return back()->withErrors(['base' => 'Invalid currency code']);
        }
        
        $rates = $teamleader->currencies()->exchangeRates($baseCurrency);
        $supportedCurrencies = $teamleader->currencies()->getSupportedCurrencies();
        
        return view('currencies.index', compact('rates', 'baseCurrency', 'supportedCurrencies'));
    }
    
    public function convert(Request $request, TeamleaderSDK $teamleader)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3'
        ]);
        
        $conversion = $teamleader->currencies()->convert(
            $request->amount,
            $request->from,
            $request->to
        );
        
        return response()->json($conversion);
    }
}
```

## Error Handling

Exchange rate requests follow standard SDK error handling:

```php
$rates = $teamleader->currencies()->exchangeRates('EUR');

if (isset($rates['error']) && $rates['error']) {
    $errorMessage = $rates['message'] ?? 'Unknown error';
    $statusCode = $rates['status_code'] ?? 0;
    
    Log::error("Currency API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

For invalid currency codes:

```php
try {
    $rates = $teamleader->currencies()->exchangeRates('INVALID');
} catch (InvalidArgumentException $e) {
    Log::error("Invalid currency code: " . $e->getMessage());
}
```

## Rate Limiting

Currency exchange rate calls count towards your Teamleader API rate limit:

- **Exchange rates**: 1 request per call
- **Convert**: 1 request per call (uses exchangeRates internally)
- **Get rate**: 1 request per call (uses exchangeRates internally)

Rate limit cost: **1 request per method call**

## Notes

- Exchange rates are provided by Teamleader and may not be real-time
- The `convert()` method uses the current exchange rates from the API
- Currency conversion includes rounding to 4 decimal places for accuracy
- The API returns exchange rates relative to the specified base currency
- All currency codes must be in the supported currencies list
- Currency codes are automatically converted to uppercase
- The `list()` and `info()` methods are not available for currencies - use `exchangeRates()` instead

## JavaScript/Frontend Integration

You can use the currency conversion functionality in AJAX requests:

```javascript
// Convert currency via AJAX
fetch('/api/currency/convert', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        amount: 100,
        from: 'EUR',
        to: 'USD'
    })
})
.then(response => response.json())
.then(data => {
    if (!data.error) {
        console.log(`${data.amount} ${data.from_currency} = ${data.converted_amount} ${data.to_currency}`);
    }
});
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
