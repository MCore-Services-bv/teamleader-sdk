# Business Types

Get business types (legal structures) that companies can have within specific countries. Business types are country-specific and sorted alphabetically by default.

## Endpoint

`businessTypes`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported (country only)
- **Supports Sorting**: ❌ Not Supported (always alphabetical)
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported (read-only)
- **Supports Update**: ❌ Not Supported (read-only)
- **Supports Deletion**: ❌ Not Supported (read-only)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `forCountry()`

Get business types for a specific country.

**Parameters:**
- `countryCode` (string): ISO country code (e.g., "BE", "NL", "FR")

**Example:**
```php
$businessTypes = $teamleader->businessTypes()->forCountry('BE');
```

### `list()`

Get business types with country filter (alternative to `forCountry()`).

**Parameters:**
- `filters` (array): Must contain 'country' key
- `options` (array): Not used for business types

**Example:**
```php
$businessTypes = $teamleader->businessTypes()->list(['country' => 'BE']);
```

### Country-Specific Convenience Methods

Quick access methods for common countries:

```php
// Get business types for Belgium
$beTypes = $teamleader->businessTypes()->belgium();

// Get business types for Netherlands
$nlTypes = $teamleader->businessTypes()->netherlands();

// Get business types for France
$frTypes = $teamleader->businessTypes()->france();

// Get business types for Germany
$deTypes = $teamleader->businessTypes()->germany();

// Get business types for United Kingdom
$gbTypes = $teamleader->businessTypes()->unitedKingdom();
```

### `forCountries()`

Get business types for multiple countries at once.

**Parameters:**
- `countryCodes` (array): Array of ISO country codes

**Example:**
```php
$multipleTypes = $teamleader->businessTypes()->forCountries(['BE', 'NL', 'FR']);
```

## Filtering

### Required Filter

- **`country`**: ISO country code (required for all requests)

### Filter Examples

```php
// Get business types for Belgium
$beTypes = $teamleader->businessTypes()->forCountry('BE');

// Get business types using list method
$beTypes = $teamleader->businessTypes()->list(['country' => 'BE']);

// Get business types for multiple countries
$types = $teamleader->businessTypes()->forCountries(['BE', 'NL', 'DE']);
```

## Response Format

### Single Country Response

```json
{
    "data": [
        {
            "id": "fd48d4a3-b9dc-4eac-8071-5889c9f21e5d",
            "name": "VZW/ASBL",
            "country": "BE"
        },
        {
            "id": "abc123def456-789-012-345",
            "name": "BV/SRL",
            "country": "BE"
        },
        {
            "id": "xyz789abc123-456-789-012",
            "name": "NV/SA",
            "country": "BE"
        }
    ]
}
```

### Multiple Countries Response

```json
{
    "BE": {
        "data": [
            {
                "id": "fd48d4a3-b9dc-4eac-8071-5889c9f21e5d",
                "name": "VZW/ASBL",
                "country": "BE"
            }
        ]
    },
    "NL": {
        "data": [
            {
                "id": "def456ghi789-012-345-678",
                "name": "BV",
                "country": "NL"
            }
        ]
    }
}
```

## Data Fields

- **`id`**: Business type UUID
- **`name`**: Business type name/legal structure (e.g., "VZW/ASBL", "BV/SRL", "NV/SA")
- **`country`**: ISO country code (e.g., "BE", "NL")

## Usage Examples

### Basic Usage

Get business types for a specific country:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CompanyController extends Controller
{
    public function getBusinessTypes(TeamleaderSDK $teamleader, string $country)
    {
        $businessTypes = $teamleader->businessTypes()->forCountry($country);
        
        return response()->json($businessTypes);
    }
}
```

### Multiple Countries

Get business types for multiple countries:

```php
$countries = ['BE', 'NL', 'FR', 'DE'];
$allBusinessTypes = $teamleader->businessTypes()->forCountries($countries);

foreach ($allBusinessTypes as $country => $types) {
    echo "Business types for {$country}:\n";
    foreach ($types['data'] as $type) {
        echo "- {$type['name']}\n";
    }
}
```

### Form Population

Use in Laravel forms for company registration:

```php
// Controller
public function create(TeamleaderSDK $teamleader)
{
    $businessTypes = $teamleader->businessTypes()->forCountry('BE');
    
    return view('companies.create', compact('businessTypes'));
}
```

```blade
{{-- Blade template --}}
<select name="business_type_id" class="form-control">
    <option value="">Select Business Type</option>
    @foreach($businessTypes['data'] as $type)
        <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
    @endforeach
</select>
```

### Validation Helper

Validate if business type exists for a country:

```php
public function validateBusinessType(TeamleaderSDK $teamleader, string $country, string $businessTypeId): bool
{
    $businessTypes = $teamleader->businessTypes()->forCountry($country);
    
    $validIds = collect($businessTypes['data'])->pluck('id')->toArray();
    
    return in_array($businessTypeId, $validIds);
}
```

### Caching for Performance

Cache business types to reduce API calls:

```php
use Illuminate\Support\Facades\Cache;

public function getCachedBusinessTypes(TeamleaderSDK $teamleader, string $country): array
{
    $cacheKey = "business_types_{$country}";
    
    return Cache::remember($cacheKey, 3600, function() use ($teamleader, $country) {
        return $teamleader->businessTypes()->forCountry($country);
    });
}
```

## Common Business Types by Country

### Belgium (BE)
- **VZW/ASBL**: Non-profit organization
- **BV/SRL**: Private limited liability company
- **NV/SA**: Public limited liability company
- **VOF/SNC**: General partnership
- **BVBA/SPRL**: (Legacy) Private limited liability company
- **CV/SCS**: Limited partnership

### Netherlands (NL)
- **BV**: Private limited liability company
- **NV**: Public limited liability company
- **VOF**: General partnership
- **CV**: Limited partnership
- **Eenmanszaak**: Sole proprietorship
- **Stichting**: Foundation

### France (FR)
- **SARL**: Limited liability company
- **SA**: Public limited company
- **SAS**: Simplified joint stock company
- **SNC**: General partnership
- **EURL**: Single-member limited liability company

## Supported Countries

The SDK provides helper methods for common countries:

| Country | Code | Helper Method |
|---------|------|---------------|
| Belgium | BE | `belgium()` |
| Netherlands | NL | `netherlands()` |
| France | FR | `france()` |
| Germany | DE | `germany()` |
| United Kingdom | GB | `unitedKingdom()` |

Additional countries may be supported by the API. Use `getSupportedCountries()` to get the full list:

```php
$supportedCountries = $teamleader->businessTypes()->getSupportedCountries();
```

## Error Handling

Handle missing country parameter:

```php
try {
    $businessTypes = $teamleader->businessTypes()->list([]);
} catch (\InvalidArgumentException $e) {
    // Handle missing country parameter
    return response()->json([
        'error' => 'Country parameter is required',
        'message' => $e->getMessage()
    ], 400);
}
```

Handle API errors:

```php
$result = $teamleader->businessTypes()->forCountry('BE');

if (isset($result['error']) && $result['error']) {
    Log::error("Business Types API error: {$result['message']}", [
        'status_code' => $result['status_code'] ?? null
    ]);
    
    return response()->json([
        'error' => 'Failed to fetch business types',
        'details' => $result['message']
    ], 500);
}
```

## Rate Limiting

Business types API calls count towards your overall Teamleader API rate limit:

- **Single country request**: 1 request per call
- **Multiple countries**: 1 request per country (not batched)

Rate limit cost: **1 request per country**

## Best Practices

1. **Cache Results**: Business types rarely change, cache them for better performance
2. **Validate Country Codes**: Use `isValidCountryCode()` to validate input
3. **Handle Missing Countries**: Some countries may not have business types available
4. **Use Convenience Methods**: Use country-specific methods for better code readability
5. **Batch Countries**: Use `forCountries()` when you need multiple countries

## Notes

- Business types are **read-only** in the Teamleader API
- Results are automatically sorted alphabetically by name
- No individual `info()` method is available - use country listing instead
- Country codes are case-insensitive in the SDK but returned as uppercase
- Business type names may include multiple languages separated by "/"

## Laravel Integration Example

Complete Laravel integration example:

```php
// Service class
class BusinessTypeService
{
    public function __construct(private TeamleaderSDK $teamleader) {}
    
    public function getForCountry(string $country): array
    {
        $cacheKey = "business_types_" . strtolower($country);
        
        return Cache::remember($cacheKey, 3600, function() use ($country) {
            return $this->teamleader->businessTypes()->forCountry($country);
        });
    }
    
    public function getSelectOptions(string $country): array
    {
        $businessTypes = $this->getForCountry($country);
        $options = [];
        
        foreach ($businessTypes['data'] as $type) {
            $options[$type['id']] = $type['name'];
        }
        
        return $options;
    }
}

// Controller
class BusinessTypeController extends Controller
{
    public function index(Request $request, BusinessTypeService $service)
    {
        $country = $request->get('country', 'BE');
        $businessTypes = $service->getForCountry($country);
        
        return response()->json($businessTypes);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
