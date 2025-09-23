<?php

namespace McoreServices\TeamleaderSDK\Resources\CRM;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Contacts extends Resource
{
    protected string $description = 'Manage contacts in Teamleader Focus CRM';

    // Resource capabilities - Contacts support full CRUD operations
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;
    protected bool $supportsSorting = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = true;

    // Available includes for sideloading (based on API docs)
    protected array $availableIncludes = [
        'custom_fields'
    ];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of contact UUIDs',
        'email' => 'Email address (requires type and email fields)',
        'company_id' => 'Filter by company UUID',
        'term' => 'Search term (searches first_name, last_name, email and telephone)',
        'updated_since' => 'ISO 8601 datetime',
        'tags' => 'Array of tag names (filters on contacts coupled to all given tags)',
        'status' => 'Contact status (active, deactivated)'
    ];

    // Usage examples specific to contacts
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all contacts',
            'code' => '$contacts = $teamleader->contacts()->list();'
        ],
        'search_by_term' => [
            'description' => 'Search contacts by term',
            'code' => '$contacts = $teamleader->contacts()->search("John");'
        ],
        'filter_by_company' => [
            'description' => 'Get contacts for specific company',
            'code' => '$contacts = $teamleader->contacts()->forCompany("company-uuid");'
        ],
        'filter_by_email' => [
            'description' => 'Find contact by email',
            'code' => '$contacts = $teamleader->contacts()->byEmail("john@example.com");'
        ],
        'with_custom_fields' => [
            'description' => 'Get contacts with custom fields',
            'code' => '$contacts = $teamleader->contacts()->withCustomFields()->list();'
        ],
        'create_contact' => [
            'description' => 'Create a new contact',
            'code' => '$contact = $teamleader->contacts()->create(["first_name" => "John", "last_name" => "Doe"]);'
        ]
    ];

    /**
     * Get the base path for the contacts resource
     */
    protected function getBasePath(): string
    {
        return 'contacts';
    }

    /**
     * List contacts with enhanced filtering and sorting
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
     * Get contact information with enhanced include handling
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

    /**
     * Create a new contact
     */
    public function create(array $data): array
    {
        $validatedData = $this->validateContactData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.add', $validatedData);
    }

    /**
     * Update a contact
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $validatedData = $this->validateContactData($data, 'update');
        return $this->api->request('POST', $this->getBasePath() . '.update', $validatedData);
    }

    /**
     * Delete a contact
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Search contacts by term (searches first_name, last_name, email and telephone)
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $term], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Find contacts by email
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
     * Get contacts for a specific company
     */
    public function forCompany(string $companyId, array $options = []): array
    {
        return $this->list(
            array_merge(['company_id' => $companyId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get contacts with specific tags
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
     * Get contacts updated since a specific date
     */
    public function updatedSince(string $date, array $options = []): array
    {
        return $this->list(
            array_merge(['updated_since' => $date], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get active contacts only
     */
    public function active(array $options = []): array
    {
        return $this->list(
            array_merge(['status' => 'active'], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get deactivated contacts only
     */
    public function deactivated(array $options = []): array
    {
        return $this->list(
            array_merge(['status' => 'deactivated'], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Tag a contact
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
     * Untag a contact
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
     * Link a contact to a company
     */
    public function linkToCompany(string $id, string $companyId, array $data = []): array
    {
        $params = [
            'id' => $id,
            'company_id' => $companyId
        ];

        // Optional fields
        if (isset($data['position'])) {
            $params['position'] = $data['position'];
        }

        if (isset($data['decision_maker'])) {
            $params['decision_maker'] = $data['decision_maker'];
        }

        return $this->api->request('POST', $this->getBasePath() . '.linkToCompany', $params);
    }

    /**
     * Unlink a contact from a company
     */
    public function unlinkFromCompany(string $id, string $companyId): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.unlinkFromCompany', [
            'id' => $id,
            'company_id' => $companyId
        ]);
    }

    /**
     * Update contact to company link
     */
    public function updateCompanyLink(string $id, string $companyId, array $data = []): array
    {
        $params = [
            'id' => $id,
            'company_id' => $companyId
        ];

        // Optional fields
        if (isset($data['position'])) {
            $params['position'] = $data['position'];
        }

        if (isset($data['decision_maker'])) {
            $params['decision_maker'] = $data['decision_maker'];
        }

        return $this->api->request('POST', $this->getBasePath() . '.updateCompanyLink', $params);
    }

    /**
     * Include custom fields in the next request
     */
    public function withCustomFields(): self
    {
        return $this->with('custom_fields');
    }

    /**
     * Validate contact data before sending to API
     */
    protected function validateContactData(array $data, string $operation = 'create'): array
    {
        // Required fields for creation
        if ($operation === 'create') {
            if (empty($data['first_name']) && empty($data['last_name'])) {
                throw new InvalidArgumentException('Contact must have at least a first_name or last_name');
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

        // Validate gender if provided
        if (isset($data['gender'])) {
            $validGenders = ['female', 'male', 'non_binary', 'prefers_not_to_say', 'unknown'];
            if (!in_array($data['gender'], $validGenders)) {
                throw new InvalidArgumentException('Invalid gender. Must be one of: ' . implode(', ', $validGenders));
            }
        }

        return $data;
    }

    /**
     * Build filters array for the API request with correct structure
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
                    // Email filter requires nested structure
                    if (is_string($value)) {
                        $apiFilters['email'] = [
                            'type' => 'primary',
                            'email' => $value
                        ];
                    } elseif (is_array($value) && isset($value['email'])) {
                        $apiFilters['email'] = [
                            'type' => $value['type'] ?? 'primary',
                            'email' => $value['email']
                        ];
                    }
                    break;

                case 'company_id':
                    $apiFilters['company_id'] = $value;
                    break;

                case 'term':
                    // Search across first_name, last_name, email and telephone
                    $apiFilters['term'] = $value;
                    break;

                case 'updated_since':
                    $apiFilters['updated_since'] = $value;
                    break;

                case 'tags':
                    if (is_array($value)) {
                        $apiFilters['tags'] = $value;
                    } elseif (is_string($value)) {
                        $apiFilters['tags'] = array_map('trim', explode(',', $value));
                    }
                    break;

                case 'status':
                    $apiFilters['status'] = $value;
                    break;

                // Handle legacy/alternative field names
                case 'search':
                case 'general_search':
                    // Map general search to 'term'
                    $apiFilters['term'] = $value;
                    break;
            }
        }

        if (!empty($apiFilters)) {
            $params['filter'] = $apiFilters;
        }

        return $params;
    }

    /**
     * Get available sort fields for contacts
     */
    public function getAvailableSortFields(): array
    {
        return [
            'added_at' => 'Date contact was added',
            'name' => 'Contact name (first_name + last_name)',
            'updated_at' => 'Date contact was last updated'
        ];
    }

    /**
     * Get suggested includes
     */
    protected function getSuggestedIncludes(): array
    {
        return $this->defaultIncludes;
    }
}
