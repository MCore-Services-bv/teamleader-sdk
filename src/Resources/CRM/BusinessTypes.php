<?php

namespace McoreServices\TeamleaderSDK\Resources\CRM;

use McoreServices\TeamleaderSDK\Resources\Resource;

class BusinessTypes extends Resource
{
    protected string $description = 'Get business types (legal structures) for companies in specific countries';

    // Resource capabilities - BusinessTypes is read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true; // Only country filtering

    protected bool $supportsSideloading = false;

    // Available includes (none for business types)
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'country' => 'ISO country code (required) - e.g., BE, NL, FR',
    ];

    // Usage examples specific to business types
    protected array $usageExamples = [
        'list_for_country' => [
            'description' => 'Get business types for Belgium',
            'code' => '$businessTypes = $teamleader->businessTypes()->forCountry("BE");',
        ],
        'list_for_netherlands' => [
            'description' => 'Get business types for Netherlands',
            'code' => '$businessTypes = $teamleader->businessTypes()->forCountry("NL");',
        ],
        'list_multiple_countries' => [
            'description' => 'Get business types for multiple countries',
            'code' => '
$beTypes = $teamleader->businessTypes()->forCountry("BE");
$nlTypes = $teamleader->businessTypes()->forCountry("NL");',
        ],
    ];

    /**
     * Get the base path for the business types resource
     */
    protected function getBasePath(): string
    {
        return 'businessTypes';
    }

    /**
     * Get business types for a specific country
     *
     * @param  string  $countryCode  ISO country code (e.g., "BE", "NL")
     */
    public function forCountry(string $countryCode): array
    {
        $params = ['country' => strtoupper($countryCode)];

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Override list method to require country parameter
     *
     * @param  array  $filters  Must contain 'country' key
     * @param  array  $options  Not used for business types
     *
     * @throws \InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
        if (empty($filters['country'])) {
            throw new \InvalidArgumentException(
                'Business types require a country parameter. Use forCountry() method or provide country in filters.'
            );
        }

        $params = ['country' => strtoupper($filters['country'])];

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get business types for Belgium (convenience method)
     */
    public function belgium(): array
    {
        return $this->forCountry('BE');
    }

    /**
     * Get business types for Netherlands (convenience method)
     */
    public function netherlands(): array
    {
        return $this->forCountry('NL');
    }

    /**
     * Get business types for France (convenience method)
     */
    public function france(): array
    {
        return $this->forCountry('FR');
    }

    /**
     * Get business types for Germany (convenience method)
     */
    public function germany(): array
    {
        return $this->forCountry('DE');
    }

    /**
     * Get business types for United Kingdom (convenience method)
     */
    public function unitedKingdom(): array
    {
        return $this->forCountry('GB');
    }

    /**
     * Get business types for multiple countries at once
     *
     * @param  array  $countryCodes  Array of ISO country codes
     * @return array Array with country codes as keys
     */
    public function forCountries(array $countryCodes): array
    {
        $results = [];

        foreach ($countryCodes as $countryCode) {
            $results[strtoupper($countryCode)] = $this->forCountry($countryCode);
        }

        return $results;
    }

    /**
     * Get available countries that support business types
     * (This is a helper method - actual supported countries depend on Teamleader)
     */
    public function getSupportedCountries(): array
    {
        return [
            'BE' => 'Belgium',
            'NL' => 'Netherlands',
            'FR' => 'France',
            'DE' => 'Germany',
            'GB' => 'United Kingdom',
            'LU' => 'Luxembourg',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'ES' => 'Spain',
            'IT' => 'Italy',
        ];
    }

    /**
     * Validate country code format
     */
    public function isValidCountryCode(string $countryCode): bool
    {
        return preg_match('/^[A-Z]{2}$/', strtoupper($countryCode));
    }

    /**
     * Override info method as it's not supported for business types
     *
     * @param  string  $id
     * @param  mixed  $includes
     *
     * @throws \InvalidArgumentException
     */
    public function info($id, $includes = null): array
    {
        throw new \InvalidArgumentException(
            'Business types do not support individual info requests. Use forCountry() to get all business types for a country.'
        );
    }

    /**
     * Override getSuggestedIncludes as business types don't have includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Business types don't have sideloadable relationships
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'data' => [
                'description' => 'Array of business types',
                'type' => 'array',
                'items' => [
                    'id' => 'Business type UUID',
                    'name' => 'Business type name (e.g., "VZW/ASBL", "BV/SRL")',
                    'country' => 'Country code (e.g., "BE")',
                ],
            ],
        ];
    }

    /**
     * Override validation since business types don't support create/update
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Business types are read-only, no validation needed
        return $data;
    }
}
