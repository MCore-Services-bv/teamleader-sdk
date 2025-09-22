<?php

namespace McoreServices\TeamleaderSDK\Resources\CRM;

use McoreServices\TeamleaderSDK\Resources\Resource;

class Addresses extends Resource
{
    protected string $description = 'Get geographical area information for addresses (level two areas like provinces, states, departments)';

    // Resource capabilities - Addresses (level two areas) is read-only
    protected bool $supportsCreation = false;
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = true; // Only country filtering
    protected bool $supportsSideloading = false;

    // Available includes (none for level two areas)
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'country' => 'ISO country code (required) - e.g., BE, NL, FR',
        'language' => 'Language code for area names (optional) - e.g., nl, fr, en',
    ];

    // Usage examples specific to level two areas
    protected array $usageExamples = [
        'list_provinces' => [
            'description' => 'Get provinces for Belgium in Dutch',
            'code' => '$areas = $teamleader->addresses()->levelTwoAreas("BE", "nl");'
        ],
        'list_states' => [
            'description' => 'Get states for Germany',
            'code' => '$areas = $teamleader->addresses()->levelTwoAreas("DE");'
        ],
        'list_departments' => [
            'description' => 'Get departments for France in French',
            'code' => '$areas = $teamleader->addresses()->levelTwoAreas("FR", "fr");'
        ],
        'multiple_countries' => [
            'description' => 'Get level two areas for multiple countries',
            'code' => '
$belgianProvinces = $teamleader->addresses()->levelTwoAreas("BE");
$dutchProvinces = $teamleader->addresses()->levelTwoAreas("NL");'
        ]
    ];

    /**
     * Get the base path for the addresses resource
     */
    protected function getBasePath(): string
    {
        return 'levelTwoAreas';
    }

    /**
     * Get level two areas (provinces, states, departments) for a country
     *
     * @param string $countryCode ISO country code (e.g., "BE", "NL", "FR")
     * @param string|null $language Language code for area names (e.g., "nl", "fr", "en")
     * @return array
     */
    public function levelTwoAreas(string $countryCode, string $language = null): array
    {
        $params = ['country' => strtoupper($countryCode)];

        if ($language) {
            $params['language'] = strtolower($language);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Override list method to work with level two areas
     *
     * @param array $filters Must contain 'country' key, optionally 'language'
     * @param array $options Not used for level two areas
     * @return array
     * @throws \InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
        if (empty($filters['country'])) {
            throw new \InvalidArgumentException(
                'Level two areas require a country parameter. Use levelTwoAreas() method or provide country in filters.'
            );
        }

        $params = ['country' => strtoupper($filters['country'])];

        if (!empty($filters['language'])) {
            $params['language'] = strtolower($filters['language']);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get Belgian provinces
     *
     * @param string $language Language code (nl, fr, de, en)
     * @return array
     */
    public function belgianProvinces(string $language = 'nl'): array
    {
        return $this->levelTwoAreas('BE', $language);
    }

    /**
     * Get Dutch provinces
     *
     * @param string $language Language code
     * @return array
     */
    public function dutchProvinces(string $language = 'nl'): array
    {
        return $this->levelTwoAreas('NL', $language);
    }

    /**
     * Get French departments
     *
     * @param string $language Language code
     * @return array
     */
    public function frenchDepartments(string $language = 'fr'): array
    {
        return $this->levelTwoAreas('FR', $language);
    }

    /**
     * Get German states (Bundesländer)
     *
     * @param string $language Language code
     * @return array
     */
    public function germanStates(string $language = 'de'): array
    {
        return $this->levelTwoAreas('DE', $language);
    }

    /**
     * Get UK countries/regions
     *
     * @param string $language Language code
     * @return array
     */
    public function ukRegions(string $language = 'en'): array
    {
        return $this->levelTwoAreas('GB', $language);
    }

    /**
     * Get US states
     *
     * @param string $language Language code
     * @return array
     */
    public function usStates(string $language = 'en'): array
    {
        return $this->levelTwoAreas('US', $language);
    }

    /**
     * Get Canadian provinces
     *
     * @param string $language Language code
     * @return array
     */
    public function canadianProvinces(string $language = 'en'): array
    {
        return $this->levelTwoAreas('CA', $language);
    }

    /**
     * Get level two areas for multiple countries
     *
     * @param array $countries Array of country codes or country/language pairs
     * @param string|null $defaultLanguage Default language if not specified per country
     * @return array Array with country codes as keys
     */
    public function forCountries(array $countries, string $defaultLanguage = null): array
    {
        $results = [];

        foreach ($countries as $key => $value) {
            if (is_numeric($key)) {
                // Simple array: ['BE', 'NL', 'FR']
                $countryCode = $value;
                $language = $defaultLanguage;
            } else {
                // Associative array: ['BE' => 'nl', 'FR' => 'fr']
                $countryCode = $key;
                $language = $value;
            }

            $results[strtoupper($countryCode)] = $this->levelTwoAreas($countryCode, $language);
        }

        return $results;
    }

    /**
     * Search level two areas by name (client-side search)
     *
     * @param string $countryCode Country to search in
     * @param string $query Search query
     * @param string|null $language Language for results
     * @param bool $exactMatch Whether to match exactly or use partial matching
     * @return array
     */
    public function search(string $countryCode, string $query, string $language = null, bool $exactMatch = false): array
    {
        $areas = $this->levelTwoAreas($countryCode, $language);

        if (empty($query) || !isset($areas['data'])) {
            return $areas;
        }

        $filteredAreas = array_filter($areas['data'], function($area) use ($query, $exactMatch) {
            if (!isset($area['name'])) {
                return false;
            }

            if ($exactMatch) {
                return strcasecmp($area['name'], $query) === 0;
            } else {
                return stripos($area['name'], $query) !== false;
            }
        });

        return [
            'data' => array_values($filteredAreas),
            'country' => strtoupper($countryCode),
            'query' => $query,
            'exact_match' => $exactMatch,
            'language' => $language,
            'total_found' => count($filteredAreas),
            'total_available' => count($areas['data'])
        ];
    }

    /**
     * Get area by ID within a country
     *
     * @param string $countryCode Country code
     * @param string $areaId Area ID to find
     * @param string|null $language Language for result
     * @return array|null
     */
    public function findById(string $countryCode, string $areaId, string $language = null): ?array
    {
        $areas = $this->levelTwoAreas($countryCode, $language);

        if (!isset($areas['data'])) {
            return null;
        }

        foreach ($areas['data'] as $area) {
            if (isset($area['id']) && $area['id'] === $areaId) {
                return $area;
            }
        }

        return null;
    }

    /**
     * Get supported countries for level two areas
     * (This is a helper method - actual supported countries depend on Teamleader)
     *
     * @return array
     */
    public function getSupportedCountries(): array
    {
        return [
            'BE' => 'Belgium',
            'NL' => 'Netherlands',
            'FR' => 'France',
            'DE' => 'Germany',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'CA' => 'Canada',
            'LU' => 'Luxembourg',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'PT' => 'Portugal'
        ];
    }

    /**
     * Get supported languages
     *
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        return [
            'nl' => 'Dutch',
            'fr' => 'French',
            'de' => 'German',
            'en' => 'English',
            'es' => 'Spanish',
            'it' => 'Italian',
            'pt' => 'Portuguese'
        ];
    }

    /**
     * Validate country code format
     *
     * @param string $countryCode
     * @return bool
     */
    public function isValidCountryCode(string $countryCode): bool
    {
        return preg_match('/^[A-Z]{2}$/', strtoupper($countryCode));
    }

    /**
     * Validate language code format
     *
     * @param string $languageCode
     * @return bool
     */
    public function isValidLanguageCode(string $languageCode): bool
    {
        return preg_match('/^[a-z]{2}$/', strtolower($languageCode));
    }

    /**
     * Override info method as it's not supported for level two areas
     *
     * @param string $id
     * @param mixed $includes
     * @return array
     * @throws \InvalidArgumentException
     */
    public function info($id, $includes = null): array
    {
        throw new \InvalidArgumentException(
            'Level two areas do not support individual info requests. Use levelTwoAreas() to get areas for a country, or findById() to find a specific area.'
        );
    }

    /**
     * Override getSuggestedIncludes as level two areas don't have includes
     *
     * @return array
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Level two areas don't have sideloadable relationships
    }

    /**
     * Get response structure documentation
     *
     * @return array
     */
    public function getResponseStructure(): array
    {
        return [
            'data' => [
                'description' => 'Array of level two areas (provinces, states, departments)',
                'type' => 'array',
                'items' => [
                    'id' => 'Area UUID (e.g., "fd48d4a3-b9dc-4eac-8071-5889c9f21e5d")',
                    'name' => 'Area name in requested language (e.g., "Antwerpen", "Vlaams-Brabant")',
                    'country' => 'ISO country code (e.g., "BE")'
                ]
            ]
        ];
    }

    /**
     * Override validation since level two areas don't support create/update
     *
     * @param array $data
     * @param string $operation
     * @return array
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Level two areas are read-only, no validation needed
        return $data;
    }
}
