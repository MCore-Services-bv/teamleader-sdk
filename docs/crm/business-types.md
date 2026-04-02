# Business Types

Read legal structure definitions for companies in Teamleader Focus.

## Overview

The Business Types resource provides read-only access to the legal structures (business types) available for companies
in each country — NV, BV, Ltd, SARL, and so on. Every call requires a country code. Business types are defined by
Teamleader per country and cannot be created, updated, or deleted.

`info()` throws an `InvalidArgumentException`. `list()` throws an `InvalidArgumentException` if no country is provided.
Use `forCountry()` as the primary entry point.

## Endpoint

`businessTypes`

## Capabilities

| Capability  | Supported                              |
|-------------|----------------------------------------|
| Pagination  | ❌ Not supported — all results returned |
| Filtering   | ✅ Country required                     |
| Sorting     | ❌ Not supported                        |
| Sideloading | ❌ Not supported                        |
| Creation    | ❌ Not supported                        |
| Update      | ❌ Not supported                        |
| Deletion    | ❌ Not supported                        |

---

## Methods

### `forCountry(string $countryCode)`

Primary method. Country code is uppercased automatically.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$types = Teamleader::businessTypes()->forCountry('BE');
$types = Teamleader::businessTypes()->forCountry('NL');
$types = Teamleader::businessTypes()->forCountry('DE');
```

---

### `list(array $filters = [], array $options = [])`

Requires `country` in the filters array. Throws `InvalidArgumentException` if absent. Country code is uppercased
automatically.

```php
$types = Teamleader::businessTypes()->list(['country' => 'BE']);
```

---

## Helper Methods

### Country convenience methods

| Method            | Country |
|-------------------|---------|
| `belgium()`       | `BE`    |
| `netherlands()`   | `NL`    |
| `france()`        | `FR`    |
| `germany()`       | `DE`    |
| `unitedKingdom()` | `GB`    |

```php
$types = Teamleader::businessTypes()->belgium();
$types = Teamleader::businessTypes()->germany();
```

### `forCountries(array $countryCodes)`

Fetches business types for multiple countries. Makes one API call per country. Returns an array keyed by uppercased
country code.

```php
$all = Teamleader::businessTypes()->forCountries(['BE', 'NL', 'FR']);
// ['BE' => ['data' => [...]], 'NL' => ['data' => [...]], 'FR' => ['data' => [...]]]
```

### `getSupportedCountries()`

Returns a local map of commonly supported country codes to names — **no API call**.

```php
$countries = Teamleader::businessTypes()->getSupportedCountries();
// ['BE' => 'Belgium', 'NL' => 'Netherlands', 'FR' => 'France', ...]
```

### `isValidCountryCode(string $code)`

Validates that the code is two uppercase letters — **no API call**.

```php
$valid = Teamleader::businessTypes()->isValidCountryCode('BE'); // true
$valid = Teamleader::businessTypes()->isValidCountryCode('belgium'); // false
```

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'type-uuid', 'name' => 'BV/SRL', 'country' => 'BE'],
        ['id' => 'type-uuid', 'name' => 'NV/SA',  'country' => 'BE'],
        ['id' => 'type-uuid', 'name' => 'VZW/ASBL', 'country' => 'BE'],
        // ...
    ],
]
```

---

## Usage Examples

### Build a business type select list

```php
$types = Teamleader::businessTypes()->forCountry('BE');

$options = array_column($types['data'], 'name', 'id');
// ['uuid-1' => 'BV/SRL', 'uuid-2' => 'NV/SA', ...]
```

### Find a type by name

```php
$types  = Teamleader::businessTypes()->forCountry('BE');
$target = null;

foreach ($types['data'] as $type) {
    if (strcasecmp($type['name'], 'BV/SRL') === 0) {
        $target = $type;
        break;
    }
}
```

### Cache business types

Business types change very rarely. Cache them long-term:

```php
$types = Cache::remember('tl_business_types_BE', 86400 * 7, function () {
    return Teamleader::businessTypes()->forCountry('BE');
});
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing country — thrown before the request
try {
    Teamleader::businessTypes()->list(); // no country
} catch (InvalidArgumentException $e) {
    // 'Business types require a country parameter. Use forCountry() ...'
}

// info() not supported
try {
    Teamleader::businessTypes()->info('type-uuid');
} catch (InvalidArgumentException $e) {
    // 'Business types do not support individual info requests.'
}

// API-level errors
try {
    $types = Teamleader::businessTypes()->forCountry('XX');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Companies]] — `business_type_id` is set when creating a company
- [[Addresses]] — Level-two geographical areas for address forms
- [[Filtering]] — General filter reference
