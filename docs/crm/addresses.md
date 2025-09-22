# Addresses (Level Two Areas)

Get geographical level two area information for addresses. Level two areas correspond to provinces, states, departments, or similar administrative divisions in different countries.

## Endpoint

`levelTwoAreas`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported (country and language)
- **Supports Sorting**: ❌ Not Supported (results are sorted alphabetically)
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported (read-only)
- **Supports Update**: ❌ Not Supported (read-only)
- **Supports Deletion**: ❌ Not Supported (read-only)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `levelTwoAreas()`

Get level two areas for a specific country.

**Parameters:**
- `countryCode` (string): ISO country code (e.g., "BE", "NL", "FR")
- `language` (string, optional): Language code for area names (e.g., "nl", "fr", "en")

**Example:**
```php
$areas = $teamleader->addresses()->levelTwoAreas('BE', 'nl');
```

### `list()`

Get level two areas with filters (alternative to `levelTwoAreas()`).

**Parameters:**
- `filters` (array): Must contain 'country' key, optionally 'language'
- `options` (array): Not used for level two areas

**Example:**
```php
$areas = $teamleader->addresses()->list([
    'country' => 'BE',
    'language' => 'nl'
]);
```

### Country-Specific Convenience Methods

Quick access methods for common countries:

```php
// Belgian provinces in Dutch
$belgianProvinces = $teamleader->addresses()->belgianProvinces('nl');

// Dutch provinces
$dutchProvinces = $teamleader->addresses()->dutchProvinces();

// French departments
$frenchDepartments = $teamleader->addresses()->frenchDepartments('fr');

// German states (Bundesländer)
$germanStates = $teamleader->addresses()->germanStates('de');

// UK regions
$ukRegions = $teamleader->addresses()->ukRegions('en');

// US states
$usStates = $teamleader->addresses()->usStates();

// Canadian provinces
$canadianProvinces = $teamleader->addresses()->canadianProvinces();
```

### `forCountries()`

Get level two areas for multiple countries.

**Parameters:**
- `countries` (array): Array of country codes or country/language pairs
- `defaultLanguage` (string, optional): Default language if not specified per country

**Examples:**
```php
// Simple array with default language
$areas = $teamleader->addresses()->forCountries(['BE', 'NL', 'FR'], 'en');

// Associative array with specific languages per country
$areas = $teamleader->addresses()->forCountries([
    'BE' => 'nl',
    'FR' => 'fr',
    'DE' => 'de'
]);
```

### `search()`

Search level two areas by name within a country (client-side search).

**Parameters:**
- `countryCode` (string): Country to search in
- `query` (string): Search query
- `language` (string, optional): Language for results
- `exactMatch` (bool): Whether to match exactly (default: false)

**Example:**
```php
// Search Belgian provinces for "Antwerp"
$result = $teamleader->addresses()->search('BE', 'Antwerp', 'en');

// Exact match search
$exact = $teamleader->addresses()->search('BE', 'Antwerpen', 'nl', true);
```

### `findById()`

Find a specific area by ID within a country.

**Parameters:**
- `countryCode` (string): Country code
- `areaId` (string): Area ID to find
- `language` (string, optional): Language for result

**Example:**
```php
$area = $teamleader->addresses()->findById('BE', 'fd48d4a3-b9dc-4eac-8071-5889c9f21e5d');
```

## Filtering

### Required Filter

- **`country`**: ISO country code (required for all requests)

### Optional Filter

- **`language`**: Language code for area names (if not provided, uses country's primary language)

### Filter Examples

```php
// Get Belgian provinces in Dutch
$areas = $teamleader->addresses()->levelTwoAreas('BE', 'nl');

// Get French departments in French
$areas = $teamleader->addresses()->levelTwoAreas('FR', 'fr');

// Get German states in English (if available)
$areas = $teamleader->addresses()->levelTwoAreas('DE', 'en');

// Using list method with filters
$areas = $teamleader->addresses()->list([
    'country' => 'BE',
    'language' => 'fr'  // Belgian provinces in French
]);
```

## Response Format

### Single Country Response

```json
{
    "data": [
        {
            "id": "fd48d4a3-b9dc-4eac-8071-5889c9f21e5d",
            "name": "Antwerpen",
            "country": "BE"
        },
        {
            "id": "abc123def456-789-012-345",
            "name": "Vlaams-Brabant",
            "country": "BE"
        },
        {
            "id": "xyz789abc123-456-789-012",
            "name": "Oost-Vlaanderen",
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
                "name": "Antwerpen",
                "country": "BE"
            }
        ]
    },
    "NL": {
        "data": [
            {
                "id": "def456ghi789-012-345-678",
                "name": "Noord-Holland",
                "country": "NL"
            }
        ]
    }
}
```

### Search Response

```json
{
    "data": [
        {
            "id": "fd48d4a3-b9dc-4eac-8071-5889c9f21e5d",
            "name": "Antwerpen",
            "country": "BE"
        }
    ],
    "country": "BE",
    "query": "Antwerp",
    "exact_match": false,
    "language": "nl",
    "total_found": 1,
    "total_available": 10
}
```

## Data Fields

- **`id`**: Area UUID (e.g., "fd48d4a3-b9dc-4eac-8071-5889c9f21e5d")
- **`name`**: Area name in requested language (e.g., "Antwerpen", "Noord-Holland")
- **`country`**: ISO country code (e.g., "BE", "NL")

## Usage Examples

### Basic Area Retrieval

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class AddressController extends Controller
{
    public function getAreas(Request $request, TeamleaderSDK $teamleader)
    {
        $country = $request->get('country', 'BE');
        $language = $request->get('language', 'nl');
        
        $areas = $teamleader->addresses()->levelTwoAreas($country, $language);
        
        return response()->json($areas);
    }
}
```

### Form Population

Use in address forms:

```php
// Controller
public function create(TeamleaderSDK $teamleader)
{
    $provinces = $teamleader->addresses()->belgianProvinces('nl');
    
    return view('addresses.create', compact('provinces'));
}
```

```blade
{{-- Blade template --}}
<select name="province_id" class="form-control">
    <option value="">Select Province</option>
    @foreach($provinces['data'] as $province)
        <option value="{{ $province['id'] }}">{{ $province['name'] }}</option>
    @endforeach
</select>
```

### Multi-Country Address System

```php
public function getAreasForCountry(Request $request, TeamleaderSDK $teamleader)
{
    $country = $request->get('country');
    $language = $request->get('language', 'en');
    
    if (!$teamleader->addresses()->isValidCountryCode($country)) {
        return response()->json(['error' => 'Invalid country code'], 400);
    }
    
    $areas = $teamleader->addresses()->levelTwoAreas($country, $language);
    
    return response()->json([
        'country' => $country,
        'language' => $language,
        'areas' => $areas['data']
    ]);
}
```

### Address Validation Service

Create a service to validate addresses:

```php
use Illuminate\Support\Facades\Cache;

class AddressValidationService
{
    public function __construct(private TeamleaderSDK $teamleader) {}
    
    public function validateArea(string $country, string $areaId): bool
    {
        $cacheKey = "areas_{$country}";
        
        $areas = Cache::remember($cacheKey, 3600, function() use ($country) {
            return $this->teamleader->addresses()->levelTwoAreas($country);
        });
        
        $validIds = collect($areas['data'])->pluck('id')->toArray();
        
        return in_array($areaId, $validIds);
    }
    
    public function getAreaName(string $country, string $areaId, string $language = 'en'): ?string
    {
        $area = $this->teamleader->addresses()->findById($country, $areaId, $language);
        
        return $area['name'] ?? null;
    }
}
```

### Dynamic Country/Area Selection

JavaScript-enhanced form with dynamic loading:

```php
// API endpoint for AJAX calls
public function getAreasApi(Request $request, TeamleaderSDK $teamleader)
{
    $country = $request->get('country');
    
    if (!$country) {
        return response()->json(['error' => 'Country is required'], 400);
    }
    
    $areas = $teamleader->addresses()->levelTwoAreas($country);
    
    return response()->json($areas['data']);
}
```

```blade
{{-- Blade template with JavaScript --}}
<select name="country" id="country" class="form-control" onchange="loadAreas()">
    <option value="">Select Country</option>
    <option value="BE">Belgium</option>
    <option value="NL">Netherlands</option>
    <option value="FR">France</option>
    <option value="DE">Germany</option>
</select>

<select name="area_id" id="area" class="form-control">
    <option value="">Select Province/State</option>
</select>

<script>
function loadAreas() {
    const country = document.getElementById('country').value;
    const areaSelect = document.getElementById('area');
    
    // Clear current options
    areaSelect.innerHTML = '<option value="">Select Province/State</option>';
    
    if (!country) return;
    
    fetch(`/api/areas?country=${country}`)
        .then(response => response.json())
        .then(areas => {
            areas.forEach(area => {
                const option = document.createElement('option');
                option.value = area.id;
                option.textContent = area.name;
                areaSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading areas:', error));
}
</script>
```

### Search and Autocomplete

```php
public function searchAreas(Request $request, TeamleaderSDK $teamleader)
{
    $country = $request->get('country');
    $query = $request->get('q');
    
    if (!$country || !$query) {
        return response()->json([]);
    }
    
    $results = $teamleader->addresses()->search($country, $query);
    
    $suggestions = collect($results['data'])->map(function($area) {
        return [
            'id' => $area['id'],
            'value' => $area['name'],
            'label' => $area['name'],
            'country' => $area['country']
        ];
    })->take(10);
    
    return response()->json($suggestions);
}
```

### Cached Service with Multiple Languages

```php
class MultiLanguageAddressService
{
    public function __construct(private TeamleaderSDK $teamleader) {}
    
    public function getAreas(string $country, string $language = 'en'): array
    {
        $cacheKey = "areas_{$country}_{$language}";
        
        return Cache::remember($cacheKey, 3600, function() use ($country, $language) {
            return $this->teamleader->addresses()->levelTwoAreas($country, $language);
        });
    }
    
    public function getAreasForAllLanguages(string $country): array
    {
        $supportedLanguages = $this->teamleader->addresses()->getSupportedLanguages();
        $results = [];
        
        foreach (array_keys($supportedLanguages) as $langCode) {
            $results[$langCode] = $this->getAreas($country, $langCode);
        }
        
        return $results;
    }
}
```

## Common Areas by Country

### Belgium (BE)
- **Antwerpen**: Antwerp Province
- **Vlaams-Brabant**: Flemish Brabant
- **West-Vlaanderen**: West Flanders
- **Oost-Vlaanderen**: East Flanders
- **Limburg**: Limburg Province
- **Waals-Brabant**: Walloon Brabant
- **Henegouwen**: Hainaut
- **Luik**: Liège
- **Namen**: Namur
- **Luxemburg**: Luxembourg Province

### Netherlands (NL)
- **Noord-Holland**: North Holland
- **Zuid-Holland**: South Holland
- **Utrecht**: Utrecht
- **Noord-Brabant**: North Brabant
- **Gelderland**: Gelderland
- **Overijssel**: Overijssel

### France (FR)
- **Ain**: Ain Department
- **Aisne**: Aisne Department
- **Allier**: Allier Department
- **Paris**: Paris Department
- **Nord**: North Department

## Supported Countries and Languages

### Countries

The SDK provides helper methods for common countries:

| Country | Code | Helper Method | Common Languages |
|---------|------|---------------|------------------|
| Belgium | BE | `belgianProvinces()` | nl, fr, de |
| Netherlands | NL | `dutchProvinces()` | nl, en |
| France | FR | `frenchDepartments()` | fr, en |
| Germany | DE | `germanStates()` | de, en |
| United Kingdom | GB | `ukRegions()` | en |
| United States | US | `usStates()` | en |
| Canada | CA | `canadianProvinces()` | en, fr |

Get all supported countries:

```php
$supportedCountries = $teamleader->addresses()->getSupportedCountries();
```

### Languages

Common language codes supported:

| Language | Code |
|----------|------|
| Dutch | nl |
| French | fr |
| German | de |
| English | en |
| Spanish | es |
| Italian | it |
| Portuguese | pt |

Get all supported languages:

```php
$supportedLanguages = $teamleader->addresses()->getSupportedLanguages();
```

## Validation Helpers

### Validate Input

```php
$countryCode = 'BE';
$languageCode = 'nl';

// Validate country code
if (!$teamleader->addresses()->isValidCountryCode($countryCode)) {
    throw new InvalidArgumentException('Invalid country code');
}

// Validate language code
if (!$teamleader->addresses()->isValidLanguageCode($languageCode)) {
    throw new InvalidArgumentException('Invalid language code');
}

$areas = $teamleader->addresses()->levelTwoAreas($countryCode, $languageCode);
```

### Form Validation Rules

Laravel form request validation:

```php
class AddressRequest extends FormRequest
{
    public function rules()
    {
        return [
            'country' => 'required|string|size:2',
            'area_id' => 'required|string',
            'language' => 'nullable|string|size:2'
        ];
    }
    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $teamleader = app(TeamleaderSDK::class);
            
            // Validate area exists for country
            $country = $this->input('country');
            $areaId = $this->input('area_id');
            
            if ($country && $areaId) {
                $area = $teamleader->addresses()->findById($country, $areaId);
                
                if (!$area) {
                    $validator->errors()->add('area_id', 'Invalid area for selected country');
                }
            }
        });
    }
}
```

## Error Handling

Handle missing country parameter:

```php
try {
    $areas = $teamleader->addresses()->list([]);
} catch (\InvalidArgumentException $e) {
    return response()->json([
        'error' => 'Country parameter is required',
        'message' => $e->getMessage()
    ], 400);
}
```

Handle API errors:

```php
$result = $teamleader->addresses()->levelTwoAreas('BE');

if (isset($result['error']) && $result['error']) {
    Log::error("Address Areas API error: {$result['message']}", [
        'country' => 'BE',
        'status_code' => $result['status_code'] ?? null
    ]);
    
    return response()->json([
        'error' => 'Failed to fetch areas',
        'message' => 'Please try again later'
    ], 500);
}
```

Handle search with no results:

```php
$searchResult = $teamleader->addresses()->search('BE', 'NonExistent');

if (empty($searchResult['data'])) {
    return response()->json([
        'message' => 'No areas found matching your search',
        'country' => 'BE',
        'query' => 'NonExistent',
        'suggestions' => $teamleader->addresses()->levelTwoAreas('BE')['data']
    ]);
}
```

## Rate Limiting

Address areas API calls count towards your overall Teamleader API rate limit:

- **Single country request**: 1 request per call
- **Multiple countries**: 1 request per country (not batched)
- **Search operations**: 1 request (uses client-side filtering)

Rate limit cost: **1 request per country**

## Best Practices

1. **Cache Results**: Level two areas rarely change, cache them for better performance
2. **Validate Inputs**: Always validate country and language codes before API calls
3. **Use Convenience Methods**: Use country-specific methods for better readability
4. **Handle Missing Languages**: Fallback to default language if requested language not available
5. **Batch Countries**: Use `forCountries()` when you need multiple countries
6. **Client-Side Search**: Use the search functionality to provide better user experience

## Performance Optimization

### Caching Strategy

```php
// Cache areas for 1 hour (they rarely change)
$cacheKey = "level_two_areas_{$country}_{$language}";

$areas = Cache::remember($cacheKey, 3600, function() use ($teamleader, $country, $language) {
    return $teamleader->addresses()->levelTwoAreas($country, $language);
});
```

### Preload Common Areas

```php
// Artisan command to preload common areas
class PreloadAreasCommand extends Command
{
    protected $signature = 'addresses:preload';
    
    public function handle(TeamleaderSDK $teamleader)
    {
        $commonCountries = ['BE', 'NL', 'FR', 'DE', 'GB'];
        
        foreach ($commonCountries as $country) {
            $areas = $teamleader->addresses()->levelTwoAreas($country);
            
            Cache::put("areas_{$country}", $areas, 3600);
            
            $this->info("Preloaded areas for {$country}");
        }
    }
}
```

## Notes

- Level two areas are **read-only** in the Teamleader API
- Area names depend on the requested language (falls back to country default if not available)
- Individual area `info()` method is not supported - use `findById()` instead
- Country and language codes are case-insensitive in SDK but normalized internally
- Results are automatically sorted alphabetically by area name
- The same area ID may have different names in different languages

## Laravel Integration Example

Complete Laravel integration for address management:

```php
// Service class
class AddressService
{
    public function __construct(
        private TeamleaderSDK $teamleader,
        private CacheManager $cache
    ) {}
    
    public function getAreasForSelect(string $country, string $language = 'en'): array
    {
        $areas = $this->getAreas($country, $language);
        $options = [];
        
        foreach ($areas['data'] as $area) {
            $options[$area['id']] = $area['name'];
        }
        
        return $options;
    }
    
    public function getAreas(string $country, string $language = 'en'): array
    {
        $cacheKey = "areas_{$country}_{$language}";
        
        return $this->cache->remember($cacheKey, 3600, function() use ($country, $language) {
            return $this->teamleader->addresses()->levelTwoAreas($country, $language);
        });
    }
    
    public function validateArea(string $country, string $areaId): bool
    {
        $areas = $this->getAreas($country);
        
        return collect($areas['data'])->contains('id', $areaId);
    }
}

// Controller
class AddressController extends Controller
{
    public function areas(Request $request, AddressService $service)
    {
        $country = $request->get('country');
        $language = $request->get('language', 'en');
        
        $areas = $service->getAreas($country, $language);
        
        return response()->json($areas);
    }
}

// Routes
Route::get('/api/addresses/areas', [AddressController::class, 'areas']);
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
