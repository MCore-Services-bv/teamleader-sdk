<?php

namespace McoreServices\TeamleaderSDK\Resources\CRM;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Companies extends Resource
{
    protected string $description = 'Manage companies in Teamleader Focus CRM';

    // Resource capabilities - Companies support full CRUD operations
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;
    protected bool $supportsSorting = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = true;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'addresses',
        'business_type',
        'responsible_user',
        'added_by',
        'tags'
    ];

    // Default includes
    protected array $defaultIncludes = [
        'responsible_user',
        'addresses'
    ];

    // Common filters based on API documentation - UPDATED WITH CORRECT STRUCTURE
    protected array $commonFilters = [
        'ids' => 'Array of company UUIDs',
        'email' => 'Email address (requires type and email fields)',
        'name' => 'Company name (fuzzy search)',
        'vat_number' => 'VAT number',
        'term' => 'Search term (searches name, VAT, emails, phones)',
        'tags' => 'Array of tag names',
        'updated_since' => 'ISO 8601 datetime',
        'status' => 'Company status (active, deactivated)'
    ];

    /**
     * Enhanced search method with better field handling
     */
    public function search(string $term, array $options = []): array
    {
        // Use 'term' filter which searches across multiple fields
        return $this->list(
            array_merge(['term' => $term], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * List companies with enhanced filtering and sorting
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = $this->buildQueryParams(
            [],
            $filters,
            $options['sort'] ?? null,
            $options['sort_order'] ?? 'asc',
            $options['page_size'] ?? 20,
            $options['page_number'] ?? 1,
            $options['include'] ?? null
        );

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get the base path for the companies resource
     */
    protected function getBasePath(): string
    {
        return 'companies';
    }

    /**
     * Search by email with proper structure
     */
    public function byEmail(string $email, array $options = []): array
    {
        return $this->list(
            array_merge([
                'email' => [
                    'type' => 'primary',
                    'email' => $email
                ]
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Search by VAT number (fixed)
     */
    public function byVatNumber(string $vatNumber, array $options = []): array
    {
        return $this->list(
            array_merge(['vat_number' => $vatNumber], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Fuzzy search by company name
     */
    public function byName(string $name, array $options = []): array
    {
        return $this->list(
            array_merge(['name' => $name], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * General search across multiple fields (name, VAT, email, phone)
     */
    public function searchAll(string $query, array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $query], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get company information with enhanced include handling
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    // ... (keep all your existing methods like create, update, delete, tag, etc.)

    /**
     * Create a new company
     */
    public function create(array $data)
    {
        $validatedData = $this->validateCompanyData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.add', $validatedData);
    }

    /**
     * Validate company data before sending to API
     */
    protected function validateCompanyData(array $data, string $operation = 'create'): array
    {
        // Required fields for creation
        if ($operation === 'create') {
            if (empty($data['name'])) {
                throw new InvalidArgumentException('Company name is required');
            }
        }

        // Clean up empty values
        $data = array_filter($data, function ($value) {
            return $value !== '' && $value !== null && $value !== [];
        });

        // Validate email format if provided
        if (isset($data['emails']) && is_array($data['emails'])) {
            foreach ($data['emails'] as $email) {
                if (isset($email['email']) && !filter_var($email['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Invalid email format: ' . $email['email']);
                }
            }
        }

        // Validate website URL if provided
        if (isset($data['website']) && !empty($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Invalid website URL format: ' . $data['website']);
            }
        }

        return $data;
    }

    /**
     * Update a company
     */
    public function update($id, array $data)
    {
        $data['id'] = $id;
        $validatedData = $this->validateCompanyData($data, 'update');
        return $this->api->request('POST', $this->getBasePath() . '.update', $validatedData);
    }

    /**
     * Delete a company
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Manage tags (add/remove)
     */
    public function manageTags(string $id, array $tagsToAdd = [], array $tagsToRemove = []): array
    {
        $results = [];

        if (!empty($tagsToAdd)) {
            $results['tagged'] = $this->tag($id, $tagsToAdd);
        }

        if (!empty($tagsToRemove)) {
            $results['untagged'] = $this->untag($id, $tagsToRemove);
        }

        return $results;
    }

    /**
     * Tag a company
     */
    public function tag(string $id, $tags): array
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        return $this->api->request('POST', $this->getBasePath() . '.tag', [
            'id' => $id,
            'tags' => $tags
        ]);
    }

    /**
     * Untag a company
     */
    public function untag(string $id, $tags): array
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        return $this->api->request('POST', $this->getBasePath() . '.untag', [
            'id' => $id,
            'tags' => $tags
        ]);
    }

    /**
     * Get companies with specific tags
     */
    public function withTags($tags, array $options = []): array
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        return $this->list(
            array_merge(['tags' => $tags], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get companies updated since a specific date
     */
    public function updatedSince(string $date, array $options = []): array
    {
        return $this->list(
            array_merge(['updated_since' => $date], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Include methods for fluent interface
     */
    public function withAddresses()
    {
        return $this->with('addresses');
    }

    public function withBusinessType()
    {
        return $this->with('business_type');
    }

    public function withResponsibleUser()
    {
        return $this->with('responsible_user');
    }

    public function withAddedBy()
    {
        return $this->with('added_by');
    }

    public function withCommonRelationships()
    {
        return $this->with([
            'addresses',
            'responsible_user',
            'business_type',
            'tags'
        ]);
    }

    /**
     * Get available sort fields for companies
     */
    public function getAvailableSortFields(): array
    {
        return [
            'added_at' => 'Date company was added',
            'updated_at' => 'Date company was last updated',
            'name' => 'Company name'
        ];
    }

    /**
     * FIXED: Build filters array for the API request with correct structure
     */
    protected function applyFilters(array $params = [], array $filters = [])
    {
        if (empty($filters)) {
            return $params;
        }

        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }

            switch ($key) {
                case 'ids':
                    if (is_array($value)) {
                        $apiFilters['ids'] = $value;
                    }
                    break;

                case 'email':
                    // FIXED: Email filter requires nested structure
                    if (is_string($value)) {
                        $apiFilters['email'] = [
                            'type' => 'primary', // API only accepts 'primary'
                            'email' => $value
                        ];
                    } elseif (is_array($value) && isset($value['email'])) {
                        $apiFilters['email'] = [
                            'type' => $value['type'] ?? 'primary',
                            'email' => $value['email']
                        ];
                    }
                    break;

                case 'name':
                    // Fuzzy search by company name
                    $apiFilters['name'] = $value;
                    break;

                case 'vat_number':
                    // FIXED: Direct mapping for VAT number
                    $apiFilters['vat_number'] = $value;
                    break;

                case 'term':
                    // Search across name, VAT, emails, phones
                    $apiFilters['term'] = $value;
                    break;

                case 'tags':
                    if (is_array($value)) {
                        $apiFilters['tags'] = $value;
                    } elseif (is_string($value)) {
                        $apiFilters['tags'] = array_map('trim', explode(',', $value));
                    }
                    break;

                case 'updated_since':
                    $apiFilters['updated_since'] = $value;
                    break;

                case 'status':
                    if (is_array($value)) {
                        $apiFilters['status'] = $value[0]; // API expects single value, not array
                    } else {
                        $apiFilters['status'] = $value;
                    }
                    break;

                // Handle legacy/alternative field names
                case 'search':
                case 'general_search':
                    // Map general search to 'term' which searches multiple fields
                    $apiFilters['term'] = $value;
                    break;

                case 'company_number':
                    // This might not be supported by the API based on docs
                    // but we'll pass it through in case it works
                    $apiFilters['company_number'] = $value;
                    break;
            }
        }

        if (!empty($apiFilters)) {
            $params['filter'] = $apiFilters;
        }

        return $params;
    }

    /**
     * Get suggested includes
     */
    protected function getSuggestedIncludes(): array
    {
        return $this->defaultIncludes;
    }
}
