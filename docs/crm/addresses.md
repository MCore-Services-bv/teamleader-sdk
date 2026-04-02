# Addresses

Read level-two geographical areas (provinces, states, departments) in Teamleader Focus.

## Overview

The Addresses resource provides read-only access to level-two geographical areas for address forms and validation. This
is **not** a resource for managing physical addresses on companies or contacts — those are fields on the company/contact
record itself. This resource returns the list of valid provinces, states, or departments for a given country.

Every call requires a country code. `list()` throws an `InvalidArgumentException` if the country is missing.
Use `levelTwoAreas()` as the primary entry point.

## Endpoint

`levelTwoAreas`

## Capabilities

| Capability  | Supported                              |
|-------------|----------------------------------------|
| Pagination  | ❌ Not supported — all results returned |
| Filtering   | ✅ Country required, language optional  |
| Sorting     | ❌ Not supported                        |
| Sideloading | ❌ Not supported                        |
| Creation    | ❌ Not supported                        |
| Update      | ❌ Not supported                        |
| Deletion    | ❌ Not supported                        |

---

## Methods

### `levelTwoAreas(string $countryCode, ?string $language = null)`

Primary method. Country code is uppercased automatically; language code is lowercased automatically.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Belgian provinces in Dutch
$areas = Teamleader::addresses()->levelTwoAreas('BE', 'nl');

// Belgian provinces in French
$areas = Teamleader::addresses()->levelTwoAreas('BE', 'fr');

// German states
$areas = Teamleader::addresses()->levelTwoAreas('DE');

// US states
$areas = Teamleader::addresses()->levelTwoAreas('US');
```

---

### `list(array $filters = [], array $options = [])`

Requires `country` in the filters array. Throws `InvalidArgumentException` if absent. Also accepts an
optional `language` filter key.

```php
$areas = Teamleader::addresses()->list(['country' => 'BE', 'language' => 'nl']);
```

---

## Helper Methods

### Country convenience methods

| Method                                       | Country | Default language |
|----------------------------------------------|---------|------------------|
| `belgianProvinces(string $language = 'nl')`  | `BE`    | Dutch            |
| `dutchProvinces(string $language = 'nl')`    | `NL`    | Dutch            |
| `frenchDepartments(string $language = 'fr')` | `FR`    | French           |
| `germanStates(string $language = 'de')`      | `DE`    | German           |
| `ukRegions(string $language = 'en')`         | `GB`    | English          |
| `usStates(string $language = 'en')`          | `US`    | English          |
| `canadianProvinces(string $language = 'en')` | `CA`    | English          |

```php
$areas = Teamleader::addresses()->belgianProvinces();        // Dutch
$areas = Teamleader::addresses()->belgianProvinces('fr');    // French
$areas = Teamleader::addresses()->usStates();
```

### `forCountries(array $countries, ?string $defaultLanguage = null)`

Fetches areas for multiple countries. Makes one API call per country. Returns an array keyed by uppercased country code.

```php
$all = Teamleader::addresses()->forCountries(['BE', 'NL', 'FR']);
// ['BE' => ['data' => [...]], 'NL' => [...], 'FR' => [...]]
```

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'area-uuid', 'name' => 'Antwerpen',       'country' => 'BE'],
        ['id' => 'area-uuid', 'name' => 'Oost-Vlaanderen', 'country' => 'BE'],
        ['id' => 'area-uuid', 'name' => 'West-Vlaanderen', 'country' => 'BE'],
        // ...
    ],
]
```

---

## Usage Examples

### Build a province dropdown

```php
$areas = Teamleader::addresses()->belgianProvinces('nl');

$options = array_column($areas['data'], 'name', 'id');
// ['uuid-1' => 'Antwerpen', 'uuid-2' => 'Oost-Vlaanderen', ...]
```

### Validate a province

```php
$areas          = Teamleader::addresses()->levelTwoAreas('BE', 'nl');
$validProvinces = array_column($areas['data'], 'name');

if (!in_array($request->input('province'), $validProvinces)) {
    throw new InvalidArgumentException('Invalid province for Belgium');
}
```

### Serve areas dynamically on country change

```php
// In a controller
public function getAreas(Request $request): JsonResponse
{
    $country  = strtoupper($request->input('country'));
    $language = $request->input('language', 'nl');

    $areas = Teamleader::addresses()->levelTwoAreas($country, $language);

    return response()->json($areas['data']);
}
```

### Cache geographical areas

Areas change very rarely. Cache them long-term:

```php
$areas = Cache::remember("tl_areas_{$country}_{$language}", 86400 * 7, function () use ($country, $language) {
    return Teamleader::addresses()->levelTwoAreas($country, $language);
});
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing country — thrown before the request
try {
    Teamleader::addresses()->list(); // no country
} catch (InvalidArgumentException $e) {
    // 'Level two areas require a country parameter. Use levelTwoAreas() ...'
}

// API-level errors
try {
    $areas = Teamleader::addresses()->levelTwoAreas('BE', 'nl');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Companies]] — Physical addresses are fields on company records
- [[Contacts]] — Physical addresses are fields on contact records
- [[Business-Types]] — Legal structures per country
- [[Filtering]] — General filter reference
