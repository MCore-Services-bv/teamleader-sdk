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

    /**
     * Includes accepted by companies.list.
     *
     * Verified against @teamleader/focus-api-specification v1.197.0. Only
     * `custom_fields` exists for list.
     *
     * Until v2.1.2 this declared seven values — addresses, business_type,
     * responsible_user, added_by, tags, custom_fields and price_list — of which
     * six were not includes at all. Those fields are returned by default, so
     * requesting them appeared to work: the API ignores unrecognised include
     * values and the data arrived regardless.
     *
     * `price_list` in particular is returned automatically whenever the account
     * has access to price lists, and is null when no price list is set on the
     * company.
     *
     * @see $infoIncludes For the different set companies.info accepts
     */
    protected array $availableIncludes = [
        'custom_fields',
    ];

    /**
     * Includes accepted by companies.info.
     *
     * A different set from companies.list — the info endpoint offers related
     * records rather than custom fields.
     */
    protected array $infoIncludes = [
        'related_companies',
        'related_contacts',
    ];

    /**
     * Default includes.
     *
     * Empty. Until v2.1.2 this was ['responsible_user', 'addresses'], so every
     * companies request carried two include values the API does not recognise.
     */
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of company UUIDs',
        'email' => 'Email address (requires type and email fields)',
        'vat_number' => 'VAT number',
        'national_identification_number' => 'National identification number',
        'term' => 'Search term (searches name, VAT, emails, phones)',
        'tags' => 'Array of tag names',
        'updated_since' => 'ISO 8601 datetime',
        'status' => 'Company status (active, deactivated)',
        'marketing_mails_consent' => 'Marketing mails consent (boolean)',
    ];

    /**
     * Enhanced search method with better field handling
     */
    public function search(string $term, array $options = []): array
    {
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

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
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
                    'email' => $email,
                ],
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Search by VAT number
     */
    public function byVatNumber(string $vatNumber, array $options = []): array
    {
        return $this->list(
            array_merge(['vat_number' => $vatNumber], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Search by national identification number
     */
    public function byNationalIdentificationNumber(string $number, array $options = []): array
    {
        return $this->list(
            array_merge(['national_identification_number' => $number], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Fuzzy search by company name
     *
     * @deprecated since v2.2.1 — companies.list has no `name` filter. The API
     * ignored it and returned every company, unfiltered, with HTTP 200. Use
     * search() / the `term` filter, which searches name as well as VAT number,
     * emails and telephones. This method will be removed in v3.0.
     *
     * @throws InvalidArgumentException Always
     */
    public function byName(string $name, array $options = []): array
    {
        throw new InvalidArgumentException(
            'companies.list has no `name` filter — this method silently returned every '
            ."company. Use search('{$name}') instead, which filters on name, VAT number, "
            .'emails and telephones via the `term` filter.'
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
     * Get company information
     *
     * `companies.info` accepts `related_companies` and `related_contacts` as
     * includes — not `custom_fields`, which is a companies.list include. Custom
     * fields and the price list are returned automatically; `price_list` is
     * present whenever the account has access to price lists, and null when no
     * price list is set on the company.
     *
     * @param  string  $id  Company UUID
     * @param  mixed  $includes  related_companies and/or related_contacts
     *
     * @throws InvalidArgumentException When an include is not valid for this endpoint
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        $requested = $includes ?? $this->getPendingIncludes();

        if (! empty($requested)) {
            $this->validateInfoIncludes($requested);

            $params = $this->applyIncludes($params, $requested);
        }

        // Consume any pending includes so they do not leak into a later call
        $this->applyPendingIncludes([]);

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Reject includes companies.info does not accept
     *
     * @param  array|string  $includes
     *
     * @throws InvalidArgumentException
     */
    protected function validateInfoIncludes($includes): void
    {
        $requested = is_array($includes)
            ? $includes
            : array_map('trim', explode(',', (string) $includes));

        foreach ($requested as $include) {
            if (in_array($include, $this->infoIncludes, true)) {
                continue;
            }

            $message = "Invalid include for companies.info: {$include}. Accepts: "
                .implode(', ', $this->infoIncludes).'.';

            if ($include === 'custom_fields') {
                $message .= ' custom_fields is a companies.list include; companies.info '
                    .'returns custom fields automatically.';
            }

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Create a new company
     */
    public function create(array $data)
    {
        $validatedData = $this->validateCompanyData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.add', $validatedData);
    }

    /**
     * Validate company data before sending to API
     */
    protected function validateCompanyData(array $data, string $operation = 'create'): array
    {
        if ($operation === 'create') {
            if (empty($data['name'])) {
                throw new InvalidArgumentException('Company name is required');
            }
        }

        // Strip empty strings and empty arrays, but preserve null — null signals a field clear to the API
        $data = array_filter($data, function ($value, $key) {
            if ($key === 'id') {
                return true;
            }
            if ($value === null) {
                return true;
            }

            return $value !== '' && $value !== [];
        }, ARRAY_FILTER_USE_BOTH);

        if (isset($data['emails']) && is_array($data['emails'])) {
            foreach ($data['emails'] as $email) {
                if (isset($email['email']) && ! filter_var($email['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Invalid email format: '.$email['email']);
                }
            }
        }

        if (isset($data['website']) && ! empty($data['website'])) {
            if (! filter_var($data['website'], FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Invalid website URL format: '.$data['website']);
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

        return $this->api->request('POST', $this->getBasePath().'.update', $validatedData);
    }

    /**
     * Delete a company
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Upload or remove a company logo.
     * Pass a base64 data URI string to set the logo, or null to remove it.
     *
     * @param  string  $id  Company UUID
     * @param  string|null  $image  Base64 data URI (e.g. data:image/png;base64,...) or null to remove
     */
    public function uploadLogo(string $id, ?string $image): array
    {
        if ($image !== null && ! str_starts_with($image, 'data:image/')) {
            throw new InvalidArgumentException(
                'Image must be a base64 data URI (e.g. data:image/png;base64,...) or null to remove the logo'
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.uploadLogo', [
            'id' => $id,
            'image' => $image,
        ]);
    }

    /**
     * Manage tags (add/remove)
     */
    public function manageTags(string $id, array $tagsToAdd = [], array $tagsToRemove = []): array
    {
        $results = [];

        if (! empty($tagsToAdd)) {
            $results['tagged'] = $this->tag($id, $tagsToAdd);
        }

        if (! empty($tagsToRemove)) {
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

        return $this->api->request('POST', $this->getBasePath().'.tag', [
            'id' => $id,
            'tags' => $tags,
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

        return $this->api->request('POST', $this->getBasePath().'.untag', [
            'id' => $id,
            'tags' => $tags,
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
     * Include custom fields in the next list() request
     *
     * The only include companies.list accepts.
     */
    public function withCustomFields()
    {
        return $this->with('custom_fields');
    }

    /**
     * Include related companies in the next info() request
     *
     * companies.info only — not accepted by companies.list.
     */
    public function withRelatedCompanies()
    {
        return $this->with('related_companies');
    }

    /**
     * Include related contacts in the next info() request
     *
     * companies.info only — not accepted by companies.list.
     */
    public function withRelatedContacts()
    {
        return $this->with('related_contacts');
    }

    /**
     * Get available sort fields for companies
     */
    public function getAvailableSortFields(): array
    {
        return [
            'added_at' => 'Date company was added',
            'updated_at' => 'Date company was last updated',
            'name' => 'Company name',
        ];
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
                    if (is_string($value)) {
                        $apiFilters['email'] = [
                            'type' => 'primary',
                            'email' => $value,
                        ];
                    } elseif (is_array($value) && isset($value['email'])) {
                        $apiFilters['email'] = [
                            'type' => $value['type'] ?? 'primary',
                            'email' => $value['email'],
                        ];
                    }
                    break;

                case 'vat_number':
                    $apiFilters['vat_number'] = $value;
                    break;

                case 'national_identification_number':
                    $apiFilters['national_identification_number'] = $value;
                    break;

                case 'term':
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
                        $apiFilters['status'] = $value[0];
                    } else {
                        $apiFilters['status'] = $value;
                    }
                    break;

                case 'marketing_mails_consent':
                    $apiFilters['marketing_mails_consent'] = (bool) $value;
                    break;

                case 'search':
                case 'general_search':
                    $apiFilters['term'] = $value;
                    break;

                default:
                    throw new InvalidArgumentException(
                        "Invalid filter key '{$key}' for companies.list. Supported filters: "
                        .implode(', ', array_keys($this->commonFilters))
                        .". 'search' and 'general_search' are accepted as aliases for 'term'."
                        .($key === 'name'
                            ? " companies.list has no name filter — use 'term', which searches name, VAT, emails and telephones."
                            : '')
                    );
            }
        }

        if (! empty($apiFilters)) {
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
