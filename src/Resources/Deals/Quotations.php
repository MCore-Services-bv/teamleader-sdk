<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Quotations extends Resource
{
    use ValidatesWritePayload;

    /**
     * Body fields quotations.update accepts, besides `id`. quotations.create
     * accepts the same set plus `deal_id`. `name` is accepted since
     * specification 1.221.0.
     */
    public const UPDATE_FIELDS = ['name', 'currency', 'grouped_lines', 'text', 'document_template_id', 'discounts', 'expiry'];

    /** Body fields quotations.send accepts */
    public const SEND_FIELDS = ['quotations', 'from', 'recipients', 'subject', 'content', 'language', 'attachments'];

    /** `expiry.action_after_expiry` on quotations.create / quotations.update */
    public const EXPIRY_ACTIONS = ['lock', 'none'];

    /** `discounts[].type` on quotations.create / quotations.update */
    public const DISCOUNT_TYPES = ['percentage'];

    /** `from.sender.type` on quotations.send */
    public const SENDER_TYPES = ['user', 'department'];

    /** `recipients.{to,cc,bcc}[].customer.type` on quotations.send */
    public const RECIPIENT_TYPES = ['contact', 'company'];

    /** `language` on quotations.send */
    public const SEND_LANGUAGES = [
        'en', 'nl', 'fr', 'ch', 'jp', 'de', 'es', 'pt', 'it', 'gr', 'tr', 'cs', 'so', 'sk', 'ru', 'ko', 'ir', 'iq',
        'hu', 'gh', 'bg', 'bs', 'br', 'ar', 'ag', 'al', 'af', 'ro', 'pl', 'ca', 'da', 'uk', 'no', 'fi', 'sv',
    ];

    /** `status` on quotations.info / quotations.list responses */
    public const STATUSES = ['open', 'accepted', 'refused', 'expired'];

    protected string $description = 'Manage quotations in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    /**
     * `expiry` is the one include, on both quotations.list and quotations.info.
     *
     * v2.2.2 removed it, reading that neither endpoint declares an includes
     * request property — which is true. But both responses document the
     * `expiry` field as "returned if user has access to quotation expiry and
     * `includes=expiry` is requested". The specification contradicts itself;
     * the response documentation is the more specific of the two, and an
     * include the API does not know is ignored rather than rejected, so
     * offering it again costs nothing either way. Restored in v2.2.5.
     */
    protected bool $supportsSideloading = true;

    protected array $availableIncludes = ['expiry'];

    /**
     * Filters accepted by quotations.list.
     *
     * Verified against @teamleader/focus-api-specification: `ids` is the only
     * one. `status` was declared here until v2.2.2 and is not a filter — the API
     * ignored it and returned every quotation. Filter client-side on
     * `data[].status` instead.
     */
    protected array $commonFilters = [
        'ids' => 'Array of quotation UUIDs to filter by',
    ];

    // Quotation status values — until v2.2.5 this listed `rejected` and
    // `closed`, which the API does not return, and missed `refused`
    protected array $validStatuses = self::STATUSES;

    // Supported download formats
    protected array $supportedFormats = [
        'pdf',
    ];

    // Usage examples specific to quotations
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all quotations',
            'code' => '$quotations = $teamleader->quotations()->list();',
        ],
        'list_specific' => [
            'description' => 'Get specific quotations by ID',
            'code' => '$quotations = $teamleader->quotations()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'get_single' => [
            'description' => 'Get a single quotation',
            'code' => '$quotation = $teamleader->quotations()->info(\'quotation-uuid\');',
        ],
        'with_expiry' => [
            'description' => 'Get a quotation with its expiry settings',
            'code' => '$quotation = $teamleader->quotations()->withExpiry()->info(\'quotation-uuid\');',
        ],
        'create' => [
            'description' => 'Create a new quotation',
            'code' => '$quotation = $teamleader->quotations()->create([\'deal_id\' => \'deal-uuid\', \'currency\' => [\'code\' => \'EUR\'], \'grouped_lines\' => [...]]);',
        ],
        'update' => [
            'description' => 'Update a quotation',
            'code' => '$teamleader->quotations()->update(\'quotation-uuid\', [\'grouped_lines\' => [...]]);',
        ],
        'accept' => [
            'description' => 'Accept a quotation',
            'code' => '$teamleader->quotations()->accept(\'quotation-uuid\');',
        ],
        'send' => [
            'description' => 'Send a quotation via email',
            'code' => '$teamleader->quotations()->send([\'quotations\' => [\'uuid1\'], \'from\' => [...], \'recipients\' => [...], \'subject\' => \'...\', \'content\' => \'...\', \'language\' => \'en\']);',
        ],
        'download' => [
            'description' => 'Download a quotation as PDF',
            'code' => '$download = $teamleader->quotations()->download(\'quotation-uuid\', \'pdf\');',
        ],
    ];

    /**
     * Get quotation information
     *
     * @param  string  $id  Quotation UUID
     * @param  mixed  $includes  `expiry` — returned when the account has access to quotation expiry
     *
     * @throws InvalidArgumentException When another include is requested
     */
    public function info($id, $includes = null): array
    {
        $pending = $this->getPendingIncludes();
        $this->applyPendingIncludes([]);

        $requested = $this->assertIncludes(
            [...(array) ($includes ?? []), ...$pending],
            $this->availableIncludes,
            'quotations.info'
        );

        return $this->api->request(
            'POST',
            $this->getBasePath().'.info',
            $this->applyIncludes(['id' => $id], $requested)
        );
    }

    /**
     * Include the `expiry` block in the next list() or info() request
     */
    public function withExpiry(): self
    {
        return $this->with('expiry');
    }

    /**
     * Get the base path for the quotations resource
     */
    protected function getBasePath(): string
    {
        return 'quotations';
    }

    /**
     * Create a new quotation
     *
     * quotations.create requires `deal_id` and nothing else. Before v2.2.5 the
     * SDK also required grouped_lines or text, which the specification does
     * not.
     *
     * @param  array  $data  Quotation data
     *
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        if (empty($data['deal_id'])) {
            throw new InvalidArgumentException('deal_id is required to create a quotation');
        }

        $this->validateQuotationData($data, [...self::UPDATE_FIELDS, 'deal_id'], 'quotations.create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update a quotation
     *
     * @param  string  $id  Quotation UUID
     * @param  array  $data  Updated quotation data
     *
     * @throws InvalidArgumentException
     */
    public function update(string $id, array $data): array
    {
        $data['id'] = $id;

        $this->validateQuotationData($data, [...self::UPDATE_FIELDS, 'id'], 'quotations.update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Unknown fields and the enums the specification declares on the body
     *
     * @throws InvalidArgumentException
     */
    protected function validateQuotationData(array $data, array $allowed, string $endpoint): void
    {
        $this->rejectUnknownFields($data, $allowed, $endpoint);

        $this->assertItemEnum($data, 'discounts', 'type', self::DISCOUNT_TYPES, $endpoint);

        if (isset($data['expiry']) && is_array($data['expiry'])) {
            $this->assertEnum($data['expiry']['action_after_expiry'] ?? null, self::EXPIRY_ACTIONS, 'expiry.action_after_expiry', $endpoint);
        }
    }

    /**
     * Delete a quotation
     *
     * @param  string  $id  Quotation UUID
     */
    public function delete(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Mark a quotation as accepted
     *
     * @param  string  $id  Quotation UUID
     */
    public function accept(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.accept', ['id' => $id]);
    }

    /**
     * Send one or more quotations via email
     *
     * @param  array  $data  Send parameters including quotations, sender, recipients, subject, content, and language
     */
    public function send(array $data): array
    {
        $this->validateSendData($data);

        return $this->api->request('POST', $this->getBasePath().'.send', $data);
    }

    /**
     * Validate data for sending quotations
     *
     * quotations.send requires quotations, recipients, subject, content and
     * language. `from` is optional — before v2.2.5 the SDK required it.
     *
     * @throws InvalidArgumentException
     */
    private function validateSendData(array $data): void
    {
        $this->rejectUnknownFields($data, self::SEND_FIELDS, 'quotations.send');

        if (! isset($data['quotations']) || ! is_array($data['quotations']) || empty($data['quotations'])) {
            throw new InvalidArgumentException('quotations array is required and must not be empty');
        }

        if (! isset($data['recipients']) || ! isset($data['recipients']['to']) || empty($data['recipients']['to'])) {
            throw new InvalidArgumentException('recipients.to is required and must not be empty');
        }

        if (! isset($data['subject'])) {
            throw new InvalidArgumentException('subject is required');
        }

        if (! isset($data['content'])) {
            throw new InvalidArgumentException('content is required');
        }

        if (! isset($data['language'])) {
            throw new InvalidArgumentException('language is required');
        }

        $this->assertEnum($data['language'], self::SEND_LANGUAGES, 'language', 'quotations.send');

        if (isset($data['from']['sender'])) {
            $this->assertEnum($data['from']['sender']['type'] ?? null, self::SENDER_TYPES, 'from.sender.type', 'quotations.send');
        }

        foreach (['to', 'cc', 'bcc'] as $list) {
            foreach ($data['recipients'][$list] ?? [] as $index => $recipient) {
                if (isset($recipient['customer'])) {
                    $this->assertEnum(
                        $recipient['customer']['type'] ?? null,
                        self::RECIPIENT_TYPES,
                        "recipients.{$list}[{$index}].customer.type",
                        'quotations.send'
                    );
                }
            }
        }
    }

    /**
     * Download a quotation in a specific format
     *
     * @param  string  $id  Quotation UUID
     * @param  string  $format  Download format (default: 'pdf')
     * @return array Returns location URL and expiration time
     */
    public function download(string $id, string $format = 'pdf'): array
    {
        if (! in_array($format, $this->supportedFormats)) {
            throw new InvalidArgumentException(
                "Invalid format '{$format}'. Supported formats: ".implode(', ', $this->supportedFormats)
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.download', [
            'id' => $id,
            'format' => $format,
        ]);
    }

    /**
     * Get quotations by specific IDs
     *
     * @param  array  $ids  Array of quotation UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * List quotations with enhanced filtering and pagination
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination via page.size / page.number)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination. The SDK-wide convention is page_size / page_number;
        // this resource only understood a nested ['page' => ['size' => ...]]
        // array until v2.2.2, so the standard form was silently ignored and every
        // call returned the API default of 20.
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        } elseif (isset($options['page'])) {
            $params['page'] = $this->buildPagination($options['page']);
        }

        $pending = $this->getPendingIncludes();
        $this->applyPendingIncludes([]);

        $params = $this->applyIncludes($params, $this->assertIncludes(
            [...(array) ($this->resolveIncludesOption($options) ?? []), ...$pending],
            $this->availableIncludes,
            'quotations.list'
        ));

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $supported = array_keys($this->commonFilters);

        $unknown = array_diff(array_keys($filters), $supported);

        if ($unknown !== []) {
            $message = 'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key')
                .' for quotations.list: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', $supported).'.';

            if (in_array('status', $unknown, true)) {
                $message .= ' quotations.list has no status filter — the API ignores it '
                    .'and returns every quotation. Filter client-side on data[].status.';
            }

            throw new InvalidArgumentException($message);
        }

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? $filters['ids'] : [$filters['ids']];
        }

        return $apiFilters;
    }

    /**
     * Build pagination parameters
     */
    private function buildPagination(array $pagination): array
    {
        $page = [];

        if (isset($pagination['size'])) {
            $page['size'] = (int) $pagination['size'];
        }

        if (isset($pagination['number'])) {
            $page['number'] = (int) $pagination['number'];
        }

        return $page;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created quotation ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created quotation',
                    'data.type' => 'Resource type (always "quotation")',
                ],
            ],
            'info' => [
                'description' => 'Complete quotation information',
                'fields' => [
                    'data.id' => 'Quotation UUID',
                    'data.deal' => 'Deal reference object',
                    'data.deal.id' => 'Deal UUID',
                    'data.deal.type' => 'Deal type string',
                    'data.grouped_lines' => 'Array of line item groups',
                    'data.status' => 'Quotation status (open, accepted, refused, expired)',
                    'data.expiry' => 'Expiry settings — only with includes=expiry, and only when the account has access to quotation expiry',
                    'data.name' => 'Quotation name',
                ],
            ],
            'list' => [
                'description' => 'Array of quotations with the same structure as info',
                'fields' => [
                    'data' => 'Array of quotation objects',
                ],
            ],
            'download' => [
                'description' => 'Temporary download URL for quotation',
                'fields' => [
                    'data.location' => 'Temporary URL where the file can be downloaded',
                    'data.expires' => 'Expiration time of the temporary download link',
                ],
            ],
            'send' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
            'accept' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
            'update' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
            'delete' => [
                'description' => 'Empty response on success (204 status)',
                'fields' => [],
            ],
        ];
    }
}
