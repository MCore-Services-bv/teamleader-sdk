# Business Types

Get business types (legal structures) for companies in specific countries.

## Overview

The Business Types resource provides read-only access to the available business types (legal structures) for companies in different countries. Business types represent the legal form of a company, such as "NV" (Naamloze Vennootschap) in Belgium, "BV" (Besloten Vennootschap) in the Netherlands, or "Ltd" (Limited Company) in the UK.

**Important:** The Business Types resource is read-only. Business types are pre-defined by Teamleader for each country and cannot be created, updated, or deleted through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [forCountry()](#forcountry)
- [Filters](#filters)
- [Response Structure](#response-structure)
- [Supported Countries](#supported-countries)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`businessTypes`

## Capabilities

- **Pagination**: ❌ Not Supported (all results returned at once)
- **Filtering**: ✅ Supported (country only)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get business types for a specific country. Requires a country parameter.

**Parameters:**
- `filters` (array): Must contain 'country' key with ISO country code

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get business types for Belgium
$businessTypes = Teamleader::businessTypes()->list([
    'country' => 'BE'
]);

// Get business types for Netherlands
$businessTypes = Teamleader::businessTypes()->list([
    'country' => 'NL'
]);
```

**Note:** The `list()` method will throw an `InvalidArgumentException` if the country parameter is not provided.

### `forCountry()`

Helper method to get business types for a specific country. This is the recommended way to use this resource.

**Parameters:**
- `countryCode` (string): ISO country code (e.g., "BE", "NL", "FR")

**Example:**
```php
// Get business types for Belgium
$businessTypes = Teamleader::businessTypes()->forCountry('BE');

// Get business types for Germany
$businessTypes = Teamleader::businessTypes()->forCountry('DE');

// Get business types for France
$businessTypes = Teamleader::businessTypes()->forCountry('FR');
```

## Filters

### Available Filters

#### `country` (Required)
ISO country code to get business types for.

**Values:** ISO 3166-1 alpha-2 country codes (e.g., BE, NL, FR, DE, UK, US)

```php
// Using list() method
$businessTypes = Teamleader::businessTypes()->list([
    'country' => 'BE'
]);

// Using helper method (recommended)
$businessTypes = Teamleader::businessTypes()->forCountry('BE');
```

## Response Structure

### Business Type Object

```php
[
    'id' => 'business-type-uuid',
    'name' => 'NV'
]
```

### List Response

```php
[
    'data' => [
        [
            'id' => 'business-type-uuid-1',
            'name' => 'BVBA'
        ],
        [
            'id' => 'business-type-uuid-2',
            'name' => 'NV'
        ],
        [
            'id' => 'business-type-uuid-3',
            'name' => 'Eenmanszaak'
        ],
        [
            'id' => 'business-type-uuid-4',
            'name' => 'VZW'
        ]
    ]
]
```

## Supported Countries

Business types are available for many countries. Common examples include:

### Belgium (BE)
- NV (Naamloze Vennootschap)
- BVBA (Besloten Vennootschap met Beperkte Aansprakelijkheid)
- Eenmanszaak
- VZW (Vereniging Zonder Winstoogmerk)

### Netherlands (NL)
- BV (Besloten Vennootschap)
- NV (Naamloze Vennootschap)
- Eenmanszaak
- VOF (Vennootschap Onder Firma)

### France (FR)
- SARL (Société à Responsabilité Limitée)
- SAS (Société par Actions Simplifiée)
- SA (Société Anonyme)
- EURL (Entreprise Unipersonnelle à Responsabilité Limitée)

### Germany (DE)
- GmbH (Gesellschaft mit beschränkter Haftung)
- AG (Aktiengesellschaft)
- UG (Unternehmergesellschaft)
- GbR (Gesellschaft bürgerlichen Rechts)

### United Kingdom (GB/UK)
- Ltd (Limited Company)
- PLC (Public Limited Company)
- LLP (Limited Liability Partnership)
- Sole Trader

**Note:** The exact list of business types for each country is maintained by Teamleader and may vary.

## Usage Examples

### Get Business Types for a Country

```php
// Get Belgian business types
$beTypes = Teamleader::businessTypes()->forCountry('BE');

foreach ($beTypes['data'] as $type) {
    echo "{$type['name']} (ID: {$type['id']})\n";
}
```

### Get Business Types for Multiple Countries

```php
$countries = ['BE', 'NL', 'FR', 'DE'];
$allBusinessTypes = [];

foreach ($countries as $country) {
    $types = Teamleader::businessTypes()->forCountry($country);
    $allBusinessTypes[$country] = $types['data'];
}

// Now you have business types organized by country
```

### Create a Dropdown for Company Creation

```php
function getBusinessTypeDropdown($countryCode)
{
    $businessTypes = Teamleader::businessTypes()->forCountry($countryCode);
    $dropdown = [];
    
    foreach ($businessTypes['data'] as $type) {
        $dropdown[$type['id']] = $type['name'];
    }
    
    return $dropdown;
}

// Usage in a form
$beBusinessTypes = getBusinessTypeDropdown('BE');
// Returns: ['uuid-1' => 'BVBA', 'uuid-2' => 'NV', ...]
```

### Find Business Type by Name

```php
function findBusinessTypeByName($countryCode, $name)
{
    $businessTypes = Teamleader::businessTypes()->forCountry($countryCode);
    
    foreach ($businessTypes['data'] as $type) {
        if (strcasecmp($type['name'], $name) === 0) {
            return $type;
        }
    }
    
    return null;
}

// Usage
$bvba = findBusinessTypeByName('BE', 'BVBA');
if ($bvba) {
    echo "Found BVBA with ID: {$bvba['id']}\n";
}
```

## Common Use Cases

### 1. Dynamic Company Form

```php
class CompanyFormController
{
    public function create()
    {
        $countries = ['BE', 'NL', 'FR', 'DE'];
        $businessTypesByCountry = [];
        
        foreach ($countries as $country) {
            $types = Teamleader::businessTypes()->forCountry($country);
            $businessTypesByCountry[$country] = $types['data'];
        }
        
        return view('companies.create', [
            'businessTypes' => $businessTypesByCountry
        ]);
    }
    
    public function getBusinessTypesForCountry(Request $request)
    {
        $country = $request->input('country');
        
        if (!$country) {
            return response()->json(['error' => 'Country is required'], 400);
        }
        
        try {
            $businessTypes = Teamleader::businessTypes()->forCountry($country);
            return response()->json($businessTypes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid country code'], 400);
        }
    }
}
```

### 2. Cache Business Types

```php
use Illuminate\Support\Facades\Cache;

class BusinessTypeService
{
    public function getForCountry($countryCode)
    {
        $cacheKey = "business_types_{$countryCode}";
        
        return Cache::remember($cacheKey, 86400, function() use ($countryCode) {
            return Teamleader::businessTypes()->forCountry($countryCode);
        });
    }
    
    public function getAllForCountries(array $countryCodes)
    {
        $result = [];
        
        foreach ($countryCodes as $code) {
            $result[$code] = $this->getForCountry($code);
        }
        
        return $result;
    }
}
```

### 3. Validate Business Type

```php
function validateBusinessType($businessTypeId, $countryCode)
{
    $businessTypes = Teamleader::businessTypes()->forCountry($countryCode);
    $validIds = array_column($businessTypes['data'], 'id');
    
    if (!in_array($businessTypeId, $validIds)) {
        throw new \InvalidArgumentException(
            "Invalid business type ID for country {$countryCode}"
        );
    }
    
    return true;
}

// Usage
try {
    validateBusinessType('some-uuid', 'BE');
    // Proceed with company creation
} catch (\InvalidArgumentException $e) {
    // Handle invalid business type
}
```

### 4. Sync Business Types to Local Database

```php
class SyncBusinessTypesCommand extends Command
{
    protected $signature = 'teamleader:sync-business-types {countries?*}';
    protected $description = 'Sync business types from Teamleader';
    
    public function handle()
    {
        $countries = $this->argument('countries') ?: ['BE', 'NL', 'FR', 'DE'];
        
        foreach ($countries as $country) {
            $this->info("Syncing business types for {$country}...");
            
            try {
                $businessTypes = Teamleader::businessTypes()->forCountry($country);
                
                foreach ($businessTypes['data'] as $type) {
                    DB::table('business_types')->updateOrInsert(
                        [
                            'teamleader_id' => $type['id'],
                            'country' => $country
                        ],
                        [
                            'name' => $type['name'],
                            'updated_at' => now()
                        ]
                    );
                }
                
                $this->info("Synced " . count($businessTypes['data']) . " business types");
            } catch (\Exception $e) {
                $this->error("Failed to sync {$country}: {$e->getMessage()}");
            }
        }
        
        $this->info('Sync complete!');
    }
}
```

### 5. API Endpoint for AJAX Requests

```php
// routes/api.php
Route::get('/business-types/{country}', function($country) {
    try {
        $businessTypes = Teamleader::businessTypes()->forCountry(strtoupper($country));
        return response()->json($businessTypes);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Invalid country code or unable to fetch business types'
        ], 400);
    }
});

// JavaScript usage
// fetch('/api/business-types/BE')
//     .then(response => response.json())
//     .then(data => {
//         // Populate dropdown with data.data
//     });
```

## Best Practices

### 1. Cache Business Types

Business types rarely change, so caching them improves performance:

```php
// Good: Cache for 24 hours
$businessTypes = Cache::remember("business_types_BE", 86400, function() {
    return Teamleader::businessTypes()->forCountry('BE');
});

// Bad: Fetch on every request
$businessTypes = Teamleader::businessTypes()->forCountry('BE');
```

### 2. Use the Helper Method

```php
// Good: Use forCountry() helper
$types = Teamleader::businessTypes()->forCountry('BE');

// Less readable: Use list() with filters
$types = Teamleader::businessTypes()->list(['country' => 'BE']);
```

### 3. Handle Country Code Case

```php
// Good: Normalize country code
$country = strtoupper($request->input('country'));
$types = Teamleader::businessTypes()->forCountry($country);

// Risky: Use input directly
$types = Teamleader::businessTypes()->forCountry($request->input('country'));
```

### 4. Validate Before Using

```php
// Good: Validate the business type exists
function validateAndCreateCompany($data)
{
    $businessTypes = Teamleader::businessTypes()->forCountry($data['country']);
    $validIds = array_column($businessTypes['data'], 'id');
    
    if (!in_array($data['business_type_id'], $validIds)) {
        throw new \InvalidArgumentException('Invalid business type for this country');
    }
    
    return Teamleader::companies()->create($data);
}
```

### 5. Provide Fallback

```php
// Good: Provide fallback if API fails
try {
    $businessTypes = Teamleader::businessTypes()->forCountry('BE');
} catch (\Exception $e) {
    Log::error('Failed to fetch business types', ['error' => $e->getMessage()]);
    
    // Use cached version or default list
    $businessTypes = Cache::get('business_types_BE_backup', ['data' => []]);
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $businessTypes = Teamleader::businessTypes()->forCountry('BE');
} catch (\InvalidArgumentException $e) {
    // Country parameter missing or invalid
    Log::error('Invalid country code', [
        'error' => $e->getMessage()
    ]);
} catch (TeamleaderException $e) {
    // API error
    Log::error('Error fetching business types', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    return ['data' => []];
}
```

## Limitations

1. **Country Required**: You must always provide a country code
2. **No Filtering**: Cannot filter by business type name or other criteria
3. **No Pagination**: All business types for a country are returned at once
4. **Read-Only**: Cannot create, update, or delete business types
5. **No Individual Info**: Cannot fetch a single business type by ID

```php
// Cannot do this:
// Teamleader::businessTypes()->list(); // ❌ Missing country
// Teamleader::businessTypes()->info('uuid'); // ❌ No info method
// Teamleader::businessTypes()->search('NV'); // ❌ No search method

// Must do this:
Teamleader::businessTypes()->forCountry('BE'); // ✅ Requires country
```

## Related Resources

- [Companies](companies.md) - Use business types when creating companies
- [Addresses](addresses.md) - Get geographical areas for addresses

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Resources](../general/resources.md) - Understanding resource architecture
