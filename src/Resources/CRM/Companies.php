<?php

namespace McoreServices\TeamleaderSDK\Resources\CRM;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Companies extends Resource
{
    use ValidatesWritePayload;

    /**
     * Body fields companies.add accepts. companies.update accepts the same
     * set plus `id`. From @teamleader/focus-api-specification v1.221.0.
     */
    public const WRITE_FIELDS = [
        'name', 'business_type_id', 'vat_number', 'national_identification_number',
        'emails', 'telephones', 'website', 'addresses', 'iban', 'bic', 'language',
        'preferred_currency', 'price_list_id', 'responsible_user_id', 'remarks',
        'tags', 'custom_fields', 'marketing_mails_consent',
    ];

    /** `emails[].type` on companies.add / companies.update */
    public const EMAIL_TYPES = ['primary', 'invoicing'];

    /** `telephones[].type` on companies.add / companies.update */
    public const TELEPHONE_TYPES = ['phone', 'fax'];

    /** `addresses[].type` on companies.add / companies.update */
    public const ADDRESS_TYPES = ['primary', 'invoicing', 'delivery', 'visiting'];

    /** `preferred_currency` on companies.add / companies.update */
    public const CURRENCIES = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP', 'INR', 'ISK',
        'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    /** `filter.email.type` on companies.list — only primary addresses are searchable */
    public const FILTER_EMAIL_TYPES = ['primary'];

    /** `filter.status` on companies.list */
    public const STATUSES = ['active', 'deactivated'];

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
     * Verified against @teamleader/focus-api-specification v1.221.0. Only
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

    /**
     * Filters accepted by companies.list — the complete set the specification
     * declares. Any other key throws; the API would ignore it and return every
     * company.
     */
    protected array $commonFilters = [
        'ids' => 'Array of company UUIDs (a single UUID string is wrapped)',
        'email' => 'Email address — a string, or ["type" => "primary", "email" => ...]. Only primary is searchable',
        'vat_number' => 'VAT number',
        'national_identification_number' => 'National identification number',
        'term' => 'Search term (searches name, VAT number, emails and telephones)',
        'tags' => 'Array of tag names — companies coupled to all given tags',
        'updated_since' => 'ISO 8601 datetime',
        'status' => 'active or deactivated (a single value, not an array)',
        'marketing_mails_consent' => 'Marketing mails consent (boolean)',
    ];

    /**
     * Sort fields accepted by companies.list. Order may be asc or desc.
     *
     * Declared here (rather than only returned by getAvailableSortFields())
     * because this is the map normaliseSort() validates against. Before
     * v2.2.4 no sort field was validated: an unsupported one was sent, ignored
     * by the API, and the list came back in default order.
     */
    protected array $availableSortFields = [
        'name' => 'Company name',
        'added_at' => 'Date the company was added',
        'updated_at' => 'Date the company was last updated',
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
     * List companies
     *
     * @param  array  $filters  See $commonFilters; unknown keys throw
     * @param  array  $options  page_size, page_number, sort, sort_order, include
     *
     * @throws InvalidArgumentException On an unknown filter, sort field or include
     */
    public function list(array $filters = [], array $options = []): array
    {
        // Fluent includes are consumed before validating, so a rejected call
        // cannot leak them into the next one.
        $pending = $this->getPendingIncludes();
        $this->applyPendingIncludes([]);

        $includes = $this->assertIncludes(
            [...(array) ($this->resolveIncludesOption($options) ?? []), ...$pending],
            $this->availableIncludes,
            'companies.list'
        );

        $params = $this->applyFilters([], $filters);

        if (! empty($options['sort'])) {
            $params['sort'] = $this->normaliseSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        $params = $this->applyPagination($params, $options['page_size'] ?? 20, $options['page_number'] ?? 1);
        $params = $this->applyIncludes($params, $includes);

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
        try {
            $this->assertIncludes($includes, $this->infoIncludes, 'companies.info');
        } catch (InvalidArgumentException $e) {
            $requested = is_array($includes) ? $includes : explode(',', (string) $includes);

            throw new InvalidArgumentException(
                $e->getMessage().(in_array('custom_fields', array_map('trim', $requested), true)
                    ? ' custom_fields is a companies.list include; companies.info returns custom fields automatically.'
                    : '')
            );
        }
    }

    /**
     * Create a new company
     *
     * A select custom field takes the option **label** as its value (a string,
     * or a list of strings for multi select), not the option id: Teamleader
     * refuses the id with "has an invalid single selection value". Resolve
     * either form with customFields()->selectValue($fieldId, $labelOrId).
     */
    public function create(array $data)
    {
        $validatedData = $this->validateCompanyData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.add', $validatedData);
    }

    /**
     * Validate company data before sending to API
     *
     * Checked against companies.add / companies.update in the specification:
     * `name` is required on create; unknown top-level fields throw (the API
     * would drop them and report success); enum values are checked for email,
     * telephone and address types and the preferred currency.
     *
     * Null is preserved — it tells the API to clear the field, e.g.
     * `price_list_id => null`. Empty strings and empty arrays are stripped.
     *
     * @throws InvalidArgumentException
     */
    protected function validateCompanyData(array $data, string $operation = 'create'): array
    {
        $endpoint = $operation === 'create' ? 'companies.add' : 'companies.update';

        if ($operation === 'create' && empty($data['name'])) {
            throw new InvalidArgumentException('Company name is required');
        }

        $this->rejectUnknownFields(
            $data,
            $operation === 'create' ? self::WRITE_FIELDS : [...self::WRITE_FIELDS, 'id'],
            $endpoint
        );

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

        $this->assertItemEnum($data, 'emails', 'type', self::EMAIL_TYPES, $endpoint);
        $this->assertItemEnum($data, 'telephones', 'type', self::TELEPHONE_TYPES, $endpoint);
        $this->assertItemEnum($data, 'addresses', 'type', self::ADDRESS_TYPES, $endpoint);
        $this->assertEnum($data['preferred_currency'] ?? null, self::CURRENCIES, 'preferred_currency', $endpoint);

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
        return $this->availableSortFields;
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
                    $apiFilters['ids'] = is_array($value) ? array_values($value) : [$value];
                    break;

                case 'email':
                    $email = is_array($value) ? $value : ['email' => $value];

                    if (! isset($email['email']) || ! is_string($email['email'])) {
                        throw new InvalidArgumentException(
                            'The email filter takes an address string, or ["type" => "primary", "email" => "..."].'
                        );
                    }

                    $type = $email['type'] ?? 'primary';
                    $this->assertEnum($type, self::FILTER_EMAIL_TYPES, 'filter.email.type', 'companies.list');

                    $apiFilters['email'] = [
                        'type' => $type,
                        'email' => $email['email'],
                    ];
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
                        $apiFilters['tags'] = array_values($value);
                    } elseif (is_string($value)) {
                        $apiFilters['tags'] = array_map('trim', explode(',', $value));
                    }
                    break;

                case 'updated_since':
                    $apiFilters['updated_since'] = $value;
                    break;

                case 'status':
                    // The API declares a single string. Before v2.2.4 an array was
                    // silently reduced to its first element, so ['active',
                    // 'deactivated'] quietly returned active companies only.
                    if (is_array($value)) {
                        throw new InvalidArgumentException(
                            'companies.list filters on one status at a time: pass "active" or "deactivated", '
                            .'not an array. Omit the filter to get both.'
                        );
                    }

                    $this->assertEnum($value, self::STATUSES, 'filter.status', 'companies.list');
                    $apiFilters['status'] = $value;
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
