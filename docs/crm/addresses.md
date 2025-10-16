# Addresses

Get geographical area information for addresses (level two areas).

## Overview

The Addresses resource provides read-only access to level two geographical areas (provinces, states, departments, etc.) for different countries. This resource helps you get the correct province or state names for address forms and validation.

**Important:** This resource is about geographical area definitions, NOT about physical addresses of companies or contacts. Physical addresses are stored directly on company and contact entities. This resource provides the list of valid provinces/states for use in address forms.

**Also Important:** The Addresses resource is read-only. Geographical areas are pre-defined by Teamleader for each country and cannot be created, updated, or deleted through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [levelTwoAreas()](#leveltwoareas)
- [Filters](#filters)
- [Response Structure](#response-structure)
- [Supported Countries](#supported-countries)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`levelTwoAreas`

## Capabilities

- **Pagination**: ❌ Not Supported (all results returned at once)
- **Filtering**: ✅ Supported (country and language only)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get level two areas for a specific country. This method is not typically used directly - use `levelTwoAreas()` instead.

**Note:** The `list()` method on this resource is an alias for `levelTwoAreas()` and maintains consistency with other resources.

### `levelTwoAreas()`

Get level two areas (provinces, states, departments) for a specific country, optionally in a specific language.

**Parameters:**
- `countryCode` (string): ISO country code (e.g., "BE", "NL", "FR")
- `language` (string, optional): Language code for area names (e.g., "nl", "fr", "en")

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get provinces for Belgium in Dutch
$areas = Teamleader::addresses()->levelTwoAreas('BE', 'nl');

// Get states for Germany
$areas = Teamleader::addresses()->levelTwoAreas('DE');

// Get departments for France in French
$areas = Teamleader::addresses()->levelTwoAreas('FR', 'fr');

// Get provinces for Belgium in French
$areas = Teamleader::addresses()->levelTwoAreas('BE', 'fr');
```

## Filters

### Available Filters

#### `country` (Required)
ISO country code to get level two areas for.

**Values:** ISO 3166-1 alpha-2 country codes (e.g., BE, NL, FR, DE, US, GB)

```php
$areas = Teamleader::addresses()->levelTwoAreas('BE');
```

#### `language` (Optional)
Language code for area names. Useful for countries with multiple official languages.

**Values:** ISO 639-1 language codes (e.g., nl, fr, en, de)

```php
// Belgian provinces in Dutch
$areasNL = Teamleader::addresses()->levelTwoAreas('BE', 'nl');

// Belgian provinces in French
$areasFR = Teamleader::addresses()->levelTwoAreas('BE', 'fr');
```

## Response Structure

### Level Two Area Object

```php
[
    'name' => 'Oost-Vlaanderen'
]
```

**Note:** Areas are returned as simple objects with just the name. There is no UUID or other metadata.

### List Response Examples

#### Belgium (Dutch)
```php
[
    'data' => [
        ['name' => 'Antwerpen'],
        ['name' => 'Limburg'],
        ['name' => 'Oost-Vlaanderen'],
        ['name' => 'Vlaams-Brabant'],
        ['name' => 'West-Vlaanderen'],
        ['name' => 'Brabant Wallon'],
        ['name' => 'Hainaut'],
        ['name' => 'Liège'],
        ['name' => 'Luxembourg'],
        ['name' => 'Namur'],
        ['name' => 'Bruxelles-Capitale']
    ]
]
```

#### Netherlands
```php
[
    'data' => [
        ['name' => 'Drenthe'],
        ['name' => 'Flevoland'],
        ['name' => 'Friesland'],
        ['name' => 'Gelderland'],
        ['name' => 'Groningen'],
        ['name' => 'Limburg'],
        ['name' => 'Noord-Brabant'],
        ['name' => 'Noord-Holland'],
        ['name' => 'Overijssel'],
        ['name' => 'Utrecht'],
        ['name' => 'Zeeland'],
        ['name' => 'Zuid-Holland']
    ]
]
```

#### United States
```php
[
    'data' => [
        ['name' => 'Alabama'],
        ['name' => 'Alaska'],
        ['name' => 'Arizona'],
        ['name' => 'Arkansas'],
        ['name' => 'California'],
        // ... all 50 states
    ]
]
```

## Supported Countries

Level two areas are available for many countries. Common examples include:

### Belgium (BE)
- Language support: Dutch (nl), French (fr)
- 11 provinces + Brussels Capital Region

### Netherlands (NL)
- 12 provinces

### France (FR)
- Language support: French (fr)
- 101 departments

### Germany (DE)
- Language support: German (de)
- 16 federal states (Bundesländer)

### United States (US)
- 50 states + territories

### United Kingdom (GB)
- Counties and regions

**Note:** The exact list of areas for each country is maintained by Teamleader and may vary.

## Usage Examples

### Get Areas for a Country

```php
// Get Belgian provinces
$beProvinces = Teamleader::addresses()->levelTwoAreas('BE');

foreach ($beProvinces['data'] as $province) {
    echo $province['name'] . "\n";
}
```

### Get Areas in Multiple Languages

```php
// Get Belgian provinces in both Dutch and French
$dutchProvinces = Teamleader::addresses()->levelTwoAreas('BE', 'nl');
$frenchProvinces = Teamleader::addresses()->levelTwoAreas('BE', 'fr');

echo "Dutch: " . $dutchProvinces['data'][0]['name'] . "\n";
echo "French: " . $frenchProvinces['data'][0]['name'] . "\n";
```

### Create Province Dropdown

```php
function getProvinceDropdown($countryCode, $language = null)
{
    $areas = Teamleader::addresses()->levelTwoAreas($countryCode, $language);
    $dropdown = [];
    
    foreach ($areas['data'] as $area) {
        $dropdown[$area['name']] = $area['name'];
    }
    
    return $dropdown;
}

// Usage
$belgianProvinces = getProvinceDropdown('BE', 'nl');
// Returns: ['Antwerpen' => 'Antwerpen', 'Limburg' => 'Limburg', ...]
```

### Validate Province Name

```php
function validateProvince($province, $country, $language = null)
{
    $areas = Teamleader::addresses()->levelTwoAreas($country, $language);
    $validProvinces = array_column($areas['data'], 'name');
    
    return in_array($province, $validProvinces);
}

// Usage
if (!validateProvince('Antwerpen', 'BE', 'nl')) {
    throw new \InvalidArgumentException('Invalid province for Belgium');
}
```

## Common Use Cases

### 1. Dynamic Address Form

```php
class AddressFormController
{
    public function getAreasForCountry(Request $request)
    {
        $country = $request->input('country');
        $language = $request->input('language', 'nl');
        
        if (!$country) {
            return response()->json(['error' => 'Country is required'], 400);
        }
        
        try {
            $areas = Teamleader::addresses()->levelTwoAreas($country, $language);
            return response()->json($areas);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid country code'], 400);
        }
    }
}

// JavaScript usage
// When country dropdown changes, fetch provinces:
// fetch('/api/areas?country=BE&language=nl')
//     .then(response => response.json())
//     .then(data => {
//         // Populate province dropdown with data.data
//     });
```

### 2. Cache Geographical Areas

```php
use Illuminate\Support\Facades\Cache;

class GeographicalAreaService
{
    public function getAreas($countryCode, $language = null)
    {
        $cacheKey = $language 
            ? "areas_{$countryCode}_{$language}"
            : "areas_{$countryCode}";
        
        return Cache::remember($cacheKey, 86400 * 7, function() use ($countryCode, $language) {
            return Teamleader::addresses()->levelTwoAreas($countryCode, $language);
        });
    }
    
    public function getAreasForMultipleCountries(array $countries)
    {
        $result = [];
        
        foreach ($countries as $country) {
            $result[$country] = $this->getAreas($country);
        }
        
        return $result;
    }
}
```

### 3. Address Validation Helper

```php
class AddressValidator
{
    protected $areasCache = [];
    
    public function validateAddress($address)
    {
        $errors = [];
        
        // Validate required fields
        if (empty($address['country'])) {
            $errors[] = 'Country is required';
            return $errors;
        }
        
        // Validate province/state if provided
        if (!empty($address['province'])) {
            if (!$this->isValidProvince($address['province'], $address['country'])) {
                $errors[] = 'Invalid province for the selected country';
            }
        }
        
        return $errors;
    }
    
    protected function isValidProvince($province, $country)
    {
        if (!isset($this->areasCache[$country])) {
            $areas = Teamleader::addresses()->levelTwoAreas($country);
            $this->areasCache[$country] = array_column($areas['data'], 'name');
        }
        
        return in_array($province, $this->areasCache[$country]);
    }
}
```

### 4. Sync Areas to Local Database

```php
class SyncGeographicalAreasCommand extends Command
{
    protected $signature = 'teamleader:sync-areas {countries?*}';
    protected $description = 'Sync geographical areas from Teamleader';
    
    public function handle()
    {
        $countries = $this->argument('countries') ?: ['BE', 'NL', 'FR', 'DE'];
        
        foreach ($countries as $country) {
            $this->info("Syncing areas for {$country}...");
            
            try {
                $areas = Teamleader::addresses()->levelTwoAreas($country);
                
                foreach ($areas['data'] as $area) {
                    DB::table('geographical_areas')->updateOrInsert(
                        [
                            'country' => $country,
                            'name' => $area['name']
                        ],
                        [
                            'updated_at' => now()
                        ]
                    );
                }
                
                $this->info("Synced " . count($areas['data']) . " areas");
            } catch (\Exception $e) {
                $this->error("Failed to sync {$country}: {$e->getMessage()}");
            }
        }
        
        $this->info('Sync complete!');
    }
}
```

### 5. Multi-Language Address Form

```php
class MultiLanguageAddressForm
{
    public function getFormData($countryCode, $locale)
    {
        // Map locale to language code
        $languageMap = [
            'nl_BE' => 'nl',
            'fr_BE' => 'fr',
            'en_GB' => 'en'
        ];
        
        $language = $languageMap[$locale] ?? null;
        
        // Get areas in the appropriate language
        $areas = Teamleader::addresses()->levelTwoAreas($countryCode, $language);
        
        return [
            'country' => $countryCode,
            'language' => $language,
            'areas' => $areas['data'],
            'labels' => $this->getLabels($locale)
        ];
    }
    
    protected function getLabels($locale)
    {
        return [
            'nl_BE' => [
                'province' => 'Provincie',
                'select' => 'Selecteer provincie'
            ],
            'fr_BE' => [
                'province' => 'Province',
                'select' => 'Sélectionner province'
            ]
        ][$locale] ?? [];
    }
}
```

## Best Practices

### 1. Cache Geographical Areas

Areas rarely change, so caching them improves performance:

```php
// Good: Cache for 7 days
$areas = Cache::remember("areas_BE_nl", 86400 * 7, function() {
    return Teamleader::addresses()->levelTwoAreas('BE', 'nl');
});

// Bad: Fetch on every request
$areas = Teamleader::addresses()->levelTwoAreas('BE', 'nl');
```

### 2. Specify Language for Multi-Language Countries

```php
// Good: Specify language for clarity
$dutchProvinces = Teamleader::addresses()->levelTwoAreas('BE', 'nl');
$frenchProvinces = Teamleader::addresses()->levelTwoAreas('BE', 'fr');

// Less clear: No language specified
$provinces = Teamleader::addresses()->levelTwoAreas('BE');
```

### 3. Normalize Country Codes

```php
// Good: Normalize to uppercase
$country = strtoupper($request->input('country'));
$areas = Teamleader::addresses()->levelTwoAreas($country);

// Risky: Use input directly
$areas = Teamleader::addresses()->levelTwoAreas($request->input('country'));
```

### 4. Validate User Input

```php
// Good: Validate province against available areas
function createCompany($data)
{
    if (!empty($data['province'])) {
        $areas = Teamleader::addresses()->levelTwoAreas($data['country']);
        $validProvinces = array_column($areas['data'], 'name');
        
        if (!in_array($data['province'], $validProvinces)) {
            throw new \InvalidArgumentException('Invalid province for this country');
        }
    }
    
    return Teamleader::companies()->create($data);
}
```

### 5. Handle Missing Data Gracefully

```php
// Good: Provide fallback
try {
    $areas = Teamleader::addresses()->levelTwoAreas('BE');
} catch (\Exception $e) {
    Log::error('Failed to fetch areas', ['error' => $e->getMessage()]);
    
    // Use cached version or empty array
    $areas = Cache::get('areas_BE_backup', ['data' => []]);
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $areas = Teamleader::addresses()->levelTwoAreas('BE', 'nl');
} catch (TeamleaderException $e) {
    Log::error('Error fetching geographical areas', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'country' => 'BE',
        'language' => 'nl'
    ]);
    
    // Provide fallback
    return ['data' => []];
}
```

## What This Resource Is NOT

This resource is commonly misunderstood. Here's what it's NOT for:

### ❌ NOT for Company/Contact Addresses

This resource does NOT manage physical addresses of companies or contacts. Those are stored directly on the entities:

```php
// This is NOT how you get a company's address
$areas = Teamleader::addresses()->levelTwoAreas('BE'); // ❌ Wrong

// This is how you get a company's address
$company = Teamleader::companies()
    ->with('addresses')
    ->info('company-uuid'); // ✅ Correct

$companyAddress = $company['data']['addresses'][0];
```

### ❌ NOT for Creating Addresses

You cannot create addresses through this resource:

```php
// This does NOT work
Teamleader::addresses()->create([...]); // ❌ Not supported

// Create addresses through companies/contacts
Teamleader::companies()->create([
    'name' => 'Company',
    'addresses' => [
        [
            'type' => 'primary',
            'address' => [
                'line_1' => '123 Street',
                'city' => 'Brussels',
                'country' => 'BE'
            ]
        ]
    ]
]); // ✅ Correct
```

## Limitations

1. **Country Required**: You must always provide a country code
2. **Read-Only**: Cannot create, update, or delete geographical areas
3. **No Pagination**: All areas for a country are returned at once
4. **Limited Filtering**: Can only filter by country and language
5. **No Search**: Cannot search for specific area names

```php
// Cannot do this:
// Teamleader::addresses()->create([...]); // ❌ Not supported
// Teamleader::addresses()->search('Antwerp'); // ❌ Not supported
// Teamleader::addresses()->info('uuid'); // ❌ Not supported

// Can only do this:
Teamleader::addresses()->levelTwoAreas('BE'); // ✅ Supported
Teamleader::addresses()->levelTwoAreas('BE', 'nl'); // ✅ Supported
```

## Related Resources

- [Companies](companies.md) - Companies have physical addresses
- [Contacts](contacts.md) - Contacts have physical addresses
- [Business Types](business_types.md) - Another country-specific resource

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Resources](../general/resources.md) - Understanding resource architecture
