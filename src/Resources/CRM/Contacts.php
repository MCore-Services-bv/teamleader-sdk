<?php

namespace McoreServices\TeamleaderSDK\Resources\CRM;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Contacts extends Resource
{
    use ValidatesWritePayload;

    /**
     * Body fields contacts.add accepts. contacts.update accepts the same set
     * plus `id`. From @teamleader/focus-api-specification v1.221.0.
     */
    public const WRITE_FIELDS = [
        'first_name', 'last_name', 'salutation', 'emails', 'telephones', 'website',
        'addresses', 'gender', 'birthdate', 'iban', 'bic', 'national_identification_number',
        'language', 'price_list_id', 'remarks', 'tags', 'custom_fields', 'marketing_mails_consent',
    ];

    /** `emails[].type` on contacts.add / contacts.update — contacts have no invoicing email */
    public const EMAIL_TYPES = ['primary'];

    /** `telephones[].type` on contacts.add / contacts.update */
    public const TELEPHONE_TYPES = ['phone', 'mobile', 'fax'];

    /** `addresses[].type` on contacts.add / contacts.update */
    public const ADDRESS_TYPES = ['primary', 'invoicing', 'delivery', 'visiting'];

    /** `gender` on contacts.add / contacts.update */
    public const GENDERS = ['female', 'male', 'non_binary', 'prefers_not_to_say', 'unknown'];

    /** `filter.email.type` on contacts.list */
    public const FILTER_EMAIL_TYPES = ['primary'];

    /** `filter.status` on contacts.list */
    public const STATUSES = ['active', 'deactivated'];

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

    /**
     * Includes accepted by contacts.list.
     *
     * Verified against @teamleader/focus-api-specification v1.221.0. Only
     * `custom_fields` exists.
     *
     * `price_list` was listed here until v2.1.2 and is not an include: Teamleader
     * returns it automatically on both list and info whenever the account has
     * access to price lists, and null when no price list is set on the contact.
     * Requesting it did nothing — the API ignores include values it does not
     * recognise, and the field came back regardless, which is why the mistake
     * was invisible.
     *
     * Note that contacts.info accepts no includes parameter at all.
     */
    protected array $availableIncludes = [
        'custom_fields',
    ];

    /**
     * contacts.info takes no includes; info() throws when given any. Declared
     * so the generated reference and the spec audit read the info endpoint
     * separately instead of assuming it matches list (added in v2.3.1).
     */
    protected array $infoIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    /**
     * Filters accepted by contacts.list — the complete set the specification
     * declares. Any other key throws; the API would ignore it and return every
     * contact.
     */
    protected array $commonFilters = [
        'ids' => 'Array of contact UUIDs (a single UUID string is wrapped)',
        'email' => 'Email address — a string, or ["type" => "primary", "email" => ...]',
        'company_id' => 'Company UUID, or null for contacts linked to no company',
        'term' => 'Search term (searches first_name, last_name, email and telephone)',
        'updated_since' => 'ISO 8601 datetime',
        'tags' => 'Array of tag names (filters on contacts coupled to all given tags)',
        'status' => 'active or deactivated',
        'marketing_mails_consent' => 'Marketing mails consent (boolean)',
    ];

    /**
     * Sort fields accepted by contacts.list. Order may be asc or desc.
     *
     * This is the map normaliseSort() validates against. Before v2.2.4 no sort
     * field was validated: an unsupported one was ignored by the API and the
     * list came back in default order.
     */
    protected array $availableSortFields = [
        'added_at' => 'Date the contact was added',
        'name' => 'Contact name',
        'updated_at' => 'Date the contact was last updated',
    ];

    // Usage examples specific to contacts
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all contacts',
            'code' => '$contacts = $teamleader->contacts()->list();',
        ],
        'search_by_term' => [
            'description' => 'Search contacts by term',
            'code' => '$contacts = $teamleader->contacts()->search("John");',
        ],
        'filter_by_company' => [
            'description' => 'Get contacts for specific company',
            'code' => '$contacts = $teamleader->contacts()->forCompany("company-uuid");',
        ],
        'without_company' => [
            'description' => 'Get contacts linked to no company',
            'code' => '$contacts = $teamleader->contacts()->withoutCompany();',
        ],
        'filter_by_email' => [
            'description' => 'Find contact by email',
            'code' => '$contacts = $teamleader->contacts()->byEmail("john@example.com");',
        ],
        'with_custom_fields' => [
            'description' => 'Get contacts with custom fields',
            'code' => '$contacts = $teamleader->contacts()->withCustomFields()->list();',
        ],
        'create_contact' => [
            'description' => 'Create a new contact',
            'code' => '$contact = $teamleader->contacts()->create(["first_name" => "John", "last_name" => "Doe"]);',
        ],
        'link_price_list' => [
            'description' => 'Link a contact to a price list',
            'code' => '$teamleader->contacts()->update("contact-uuid", ["price_list_id" => "price-list-uuid"]);',
        ],
        'clear_price_list' => [
            'description' => 'Remove the price list from a contact',
            'code' => '$teamleader->contacts()->update("contact-uuid", ["price_list_id" => null]);',
        ],
    ];

    /**
     * Get contact information
     *
     * `contacts.info` accepts no includes parameter — the spec declares none.
     * Custom fields and the price list are returned automatically; `price_list`
     * is present whenever the account has access to price lists, and null when
     * no price list is set on the contact.
     *
     * @param  string  $id  Contact UUID
     * @param  mixed  $includes  Not supported by this endpoint
     *
     * @throws InvalidArgumentException When includes are requested
     */
    public function info($id, $includes = null): array
    {
        if (! empty($includes) || ! empty($this->getPendingIncludes())) {
            throw new InvalidArgumentException(
                'contacts.info accepts no includes parameter. Custom fields and '
                .'price_list are returned automatically. Use list() if you need '
                .'includes=custom_fields.'
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Get the base path for the contacts resource
     */
    protected function getBasePath(): string
    {
        return 'contacts';
    }

    /**
     * Create a new contact
     */
    public function create(array $data): array
    {
        $validatedData = $this->validateContactData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.add', $validatedData);
    }

    /**
     * Validate contact data before sending to API
     *
     * Checked against contacts.add / contacts.update in the specification:
     * `last_name` is required on create; unknown top-level fields throw (the
     * API would drop them and report success); enum values are checked for
     * email, telephone and address types and gender.
     *
     * Null is preserved — it tells the API to clear the field, e.g.
     * `price_list_id => null`. Empty strings and empty arrays are stripped.
     *
     * @throws InvalidArgumentException
     */
    protected function validateContactData(array $data, string $operation = 'create'): array
    {
        $endpoint = $operation === 'create' ? 'contacts.add' : 'contacts.update';

        // contacts.add requires last_name. Before v2.2.4 a first_name alone
        // passed this check and was then rejected by the API.
        if ($operation === 'create' && empty($data['last_name'])) {
            throw new InvalidArgumentException(
                'contacts.add requires last_name. first_name is optional.'
            );
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
        $this->assertEnum($data['gender'] ?? null, self::GENDERS, 'gender', $endpoint);

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
     * Update a contact
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $validatedData = $this->validateContactData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $validatedData);
    }

    /**
     * Delete a contact
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Upload or remove a contact avatar.
     * Pass a base64 data URI string to set the avatar, or null to remove it.
     *
     * @param  string  $id  Contact UUID
     * @param  string|null  $image  Base64 data URI (e.g. data:image/png;base64,...) or null to remove
     */
    public function uploadAvatar(string $id, ?string $image): array
    {
        if ($image !== null && ! str_starts_with($image, 'data:image/')) {
            throw new InvalidArgumentException(
                'Image must be a base64 data URI (e.g. data:image/png;base64,...) or null to remove the avatar'
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.uploadAvatar', [
            'id' => $id,
            'image' => $image,
        ]);
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
     * List contacts
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
            'contacts.list'
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
     * Find contacts by email
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
     * Get contacts linked to no company at all
     *
     * Sends `filter.company_id: null`, which the API has accepted since
     * specification 1.221.0. A contact whose only linked company has been
     * deleted is part of this set.
     */
    public function withoutCompany(array $options = []): array
    {
        return $this->list(
            array_merge(['company_id' => null], $options['filters'] ?? []),
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
     * Tag a contact
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
     * Untag a contact
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
     * Link a contact to a company
     */
    public function linkToCompany(string $id, string $companyId, array $data = []): array
    {
        $params = [
            'id' => $id,
            'company_id' => $companyId,
        ];

        if (isset($data['position'])) {
            $params['position'] = $data['position'];
        }

        if (isset($data['decision_maker'])) {
            $params['decision_maker'] = $data['decision_maker'];
        }

        return $this->api->request('POST', $this->getBasePath().'.linkToCompany', $params);
    }

    /**
     * Unlink a contact from a company
     */
    public function unlinkFromCompany(string $id, string $companyId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.unlinkFromCompany', [
            'id' => $id,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Update contact to company link
     */
    public function updateCompanyLink(string $id, string $companyId, array $data = []): array
    {
        $params = [
            'id' => $id,
            'company_id' => $companyId,
        ];

        if (isset($data['position'])) {
            $params['position'] = $data['position'];
        }

        if (isset($data['decision_maker'])) {
            $params['decision_maker'] = $data['decision_maker'];
        }

        return $this->api->request('POST', $this->getBasePath().'.updateCompanyLink', $params);
    }

    /**
     * Include custom fields in the next list() request
     *
     * The only include contacts.list accepts. Note that contacts.info takes no
     * includes at all, so this must not be chained into info().
     */
    public function withCustomFields(): self
    {
        return $this->with('custom_fields');
    }

    /**
     * Get available sort fields for contacts
     */
    public function getAvailableSortFields(): array
    {
        return $this->availableSortFields;
    }

    /**
     * Build the `filter` object for contacts.list
     *
     * Empty values are skipped, except `company_id => null`, which is a real
     * filter: contacts linked to no company. Unknown keys throw — before
     * v2.2.4 they were dropped here without a word, so the call returned
     * every contact.
     *
     * @throws InvalidArgumentException
     */
    protected function applyFilters(array $params = [], array $filters = [])
    {
        if (empty($filters)) {
            return $params;
        }

        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($key === 'company_id' && $value === null) {
                $apiFilters['company_id'] = null;

                continue;
            }

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
                    $this->assertEnum($type, self::FILTER_EMAIL_TYPES, 'filter.email.type', 'contacts.list');

                    $apiFilters['email'] = [
                        'type' => $type,
                        'email' => $email['email'],
                    ];
                    break;

                case 'company_id':
                    $apiFilters['company_id'] = $value;
                    break;

                case 'term':
                    $apiFilters['term'] = $value;
                    break;

                case 'updated_since':
                    $apiFilters['updated_since'] = $value;
                    break;

                case 'tags':
                    if (is_array($value)) {
                        $apiFilters['tags'] = array_values($value);
                    } elseif (is_string($value)) {
                        $apiFilters['tags'] = array_map('trim', explode(',', $value));
                    }
                    break;

                case 'status':
                    $this->assertEnum($value, self::STATUSES, 'filter.status', 'contacts.list');
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
                        "Invalid filter key '{$key}' for contacts.list. Supported filters: "
                        .implode(', ', array_keys($this->commonFilters))
                        .". 'search' and 'general_search' are accepted as aliases for 'term'."
                    );
            }
        }

        if ($apiFilters !== []) {
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
