<?php

namespace McoreServices\TeamleaderSDK\Resources\Tickets;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Tickets extends Resource
{
    use ValidatesWritePayload;

    /** Body fields tickets.create accepts */
    public const CREATE_FIELDS = [
        'subject', 'customer', 'ticket_status_id', 'assignee', 'custom_fields', 'description',
        'participant', 'initial_reply', 'milestone_id', 'project_id',
    ];

    /** Body fields tickets.update accepts, besides `id` — no initial_reply */
    public const UPDATE_FIELDS = [
        'subject', 'description', 'ticket_status_id', 'customer', 'assignee', 'participant',
        'custom_fields', 'milestone_id', 'project_id',
    ];

    public const REQUIRED_ON_CREATE = ['subject', 'customer', 'ticket_status_id'];

    public const CUSTOMER_TYPES = ['contact', 'company'];

    /** `initial_reply` on tickets.create */
    public const INITIAL_REPLY_OPTIONS = ['automatic', 'disabled'];

    /** `filter.type` on tickets.listMessages */
    public const MESSAGE_TYPES = ['customer', 'internal', 'thirdParty'];

    /** `sent_by.type` on tickets.importMessage */
    public const SENT_BY_TYPES = ['company', 'contact', 'user'];

    /** Filter keys tickets.listMessages accepts */
    public const MESSAGE_FILTERS = ['type', 'created_before', 'created_after'];

    protected string $description = 'Manage tickets (support cases) in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = false; // Not mentioned in API docs

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    /**
     * Filters tickets.list accepts. `relates_to` and `exclude` may be passed
     * nested (['relates_to' => ['type' => ..., 'id' => ...]]) or in the dotted
     * form listed here; until v2.2.13 the dotted form was sent as-is, which
     * the API ignored.
     */
    protected array $commonFilters = [
        'ids' => 'Array of ticket UUIDs',
        'relates_to.type' => 'Related entity type (contact, company)',
        'relates_to.id' => 'Related entity UUID',
        'project_ids' => 'Array of project UUIDs',
        'assignee_ids' => 'Array of user UUIDs; a null entry matches unassigned tickets',
        'exclude.status_ids' => 'Array of status UUIDs to exclude',
    ];

    // Valid customer types
    protected array $customerTypes = self::CUSTOMER_TYPES;

    // Valid initial reply options
    protected array $initialReplyOptions = self::INITIAL_REPLY_OPTIONS;

    // Valid message types
    protected array $messageTypes = self::MESSAGE_TYPES;

    // Valid sent_by types for importing messages
    protected array $sentByTypes = self::SENT_BY_TYPES;

    // Usage examples specific to tickets
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all tickets',
            'code' => '$tickets = $teamleader->tickets()->list();',
        ],
        'filter_by_customer' => [
            'description' => 'Get tickets for a specific customer',
            'code' => '$tickets = $teamleader->tickets()->forCustomer("company", "company-uuid");',
        ],
        'filter_by_project' => [
            'description' => 'Get tickets for specific projects',
            'code' => '$tickets = $teamleader->tickets()->forProjects(["project-uuid-1", "project-uuid-2"]);',
        ],
        'get_ticket_info' => [
            'description' => 'Get detailed ticket information',
            'code' => '$ticket = $teamleader->tickets()->info("ticket-uuid");',
        ],
        'create_ticket' => [
            'description' => 'Create a new ticket',
            'code' => '$ticket = $teamleader->tickets()->create([
    "subject" => "Customer issue",
    "customer" => ["type" => "company", "id" => "company-uuid"],
    "ticket_status_id" => "status-uuid",
    "assignee" => ["type" => "user", "id" => "user-uuid"]
]);',
        ],
        'update_ticket' => [
            'description' => 'Update an existing ticket',
            'code' => '$result = $teamleader->tickets()->update("ticket-uuid", [
    "subject" => "Updated subject",
    "ticket_status_id" => "new-status-uuid"
]);',
        ],
        'add_reply' => [
            'description' => 'Add a customer-facing reply to a ticket',
            'code' => '$result = $teamleader->tickets()->addReply(
    "ticket-uuid",
    "<p>Thank you for your inquiry...</p>",
    "status-uuid"
);',
        ],
        'add_internal_message' => [
            'description' => 'Add an internal note to a ticket',
            'code' => '$result = $teamleader->tickets()->addInternalMessage(
    "ticket-uuid",
    "<p>Internal note about this ticket...</p>"
);',
        ],
        'list_messages' => [
            'description' => 'Get all messages for a ticket',
            'code' => '$messages = $teamleader->tickets()->listMessages("ticket-uuid");',
        ],
        'get_message' => [
            'description' => 'Get a specific message',
            'code' => '$message = $teamleader->tickets()->getMessage("message-uuid");',
        ],
        'import_message' => [
            'description' => 'Import an existing message (e.g., from email)',
            'code' => '$result = $teamleader->tickets()->importMessage(
    "ticket-uuid",
    "<p>Message content...</p>",
    "contact",
    "contact-uuid",
    "2024-02-29T11:11:11+00:00"
);',
        ],
    ];

    /**
     * Get detailed ticket information
     *
     * @param  string  $id  Ticket UUID
     * @param  mixed  $includes  Optional includes
     */
    public function info($id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        $this->assertIncludes($includes, [], 'tickets.info');

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Get the base path for the tickets resource
     */
    protected function getBasePath(): string
    {
        return 'tickets';
    }

    /**
     * Create a new ticket
     *
     * Required fields: subject, customer, ticket_status_id
     *
     * Optional linkage (mutually exclusive):
     * - milestone_id (string): link to a legacy-projects milestone
     * - project_id (string): link to a new-projects project
     *
     * @param  array  $data  Ticket data
     */
    public function create(array $data): array
    {
        $this->validateTicketData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate ticket data before sending to API
     *
     * @param  string  $operation  Operation type (create or update)
     *
     * @throws InvalidArgumentException
     */
    protected function validateTicketData(array $data, string $operation = 'create'): void
    {
        $endpoint = 'tickets.'.$operation;

        if ($operation === 'create') {
            if (empty($data['subject'])) {
                throw new InvalidArgumentException('Ticket subject is required');
            }

            if (empty($data['customer'])) {
                throw new InvalidArgumentException('Customer is required');
            }

            if (empty($data['ticket_status_id'])) {
                throw new InvalidArgumentException('Ticket status ID is required');
            }

            $this->rejectUnknownFields($data, self::CREATE_FIELDS, $endpoint);
        } else {
            $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        }

        if (isset($data['customer'])) {
            if (! is_array($data['customer']) || ! isset($data['customer']['type']) || empty($data['customer']['id'])) {
                throw new InvalidArgumentException('Customer must have type and id');
            }

            $this->assertEnum($data['customer']['type'], self::CUSTOMER_TYPES, 'customer.type', $endpoint);
        }

        // null unassigns (update)
        if (isset($data['assignee'])) {
            if (! is_array($data['assignee']) || ! isset($data['assignee']['type']) || empty($data['assignee']['id'])) {
                throw new InvalidArgumentException('Assignee must have type and id');
            }

            $this->assertEnum($data['assignee']['type'], ['user'], 'assignee.type', $endpoint);
        }

        // null removes the third-party participant (update)
        if (isset($data['participant'])) {
            if (! is_array($data['participant']) || ! array_key_exists('customer', $data['participant'])) {
                throw new InvalidArgumentException('Participant must have customer');
            }

            $customer = $data['participant']['customer'];

            if ($customer !== null) {
                if (! is_array($customer) || ! isset($customer['type']) || empty($customer['id'])) {
                    throw new InvalidArgumentException('Participant customer must have type and id');
                }

                $this->assertEnum($customer['type'], ['company'], 'participant.customer.type', $endpoint);
            }
        }

        $this->assertEnum($data['initial_reply'] ?? null, self::INITIAL_REPLY_OPTIONS, 'initial_reply', $endpoint);

        if (isset($data['custom_fields'])) {
            if (! is_array($data['custom_fields'])) {
                throw new InvalidArgumentException('custom_fields must be a list of [id, value] entries');
            }

            foreach ($data['custom_fields'] as $field) {
                if (! is_array($field) || ! isset($field['id'])) {
                    throw new InvalidArgumentException('Each custom field must have an id');
                }
            }
        }

        // A ticket links to a legacy milestone OR a new project, but not both.
        if (! empty($data['milestone_id']) && ! empty($data['project_id'])) {
            throw new InvalidArgumentException(
                'A ticket can be linked to either a milestone_id or a project_id, but not both.'
            );
        }
    }

    /**
     * Update an existing ticket
     *
     * Optional linkage (mutually exclusive; pass null to unlink):
     * - milestone_id (string|null): link to a legacy-projects milestone
     * - project_id (string|null): link to a new-projects project
     *
     * @param  string  $id  Ticket UUID
     * @param  array  $data  Data to update
     */
    public function update($id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        $data['id'] = $id;
        $this->validateTicketData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Add a customer-facing reply to a ticket
     *
     * @param  string  $ticketId  Ticket UUID
     * @param  string  $body  HTML formatted message body
     * @param  string|null  $ticketStatusId  Optional status UUID to update
     * @param  array  $attachments  Optional array of file UUIDs
     */
    public function addReply(
        string $ticketId,
        string $body,
        ?string $ticketStatusId = null,
        array $attachments = []
    ): array {
        if (empty($ticketId)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        if (empty($body)) {
            throw new InvalidArgumentException('Message body is required');
        }

        $data = [
            'id' => $ticketId,
            'body' => $body,
        ];

        if ($ticketStatusId !== null) {
            $data['ticket_status_id'] = $ticketStatusId;
        }

        if (! empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        return $this->api->request('POST', $this->getBasePath().'.addReply', $data);
    }

    /**
     * Add an internal message (note) to a ticket
     *
     * @param  string  $ticketId  Ticket UUID
     * @param  string  $body  HTML formatted message body
     * @param  string|null  $ticketStatusId  Optional status UUID to update
     * @param  array  $attachments  Optional array of file UUIDs
     */
    public function addInternalMessage(
        string $ticketId,
        string $body,
        ?string $ticketStatusId = null,
        array $attachments = []
    ): array {
        if (empty($ticketId)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        if (empty($body)) {
            throw new InvalidArgumentException('Message body is required');
        }

        $data = [
            'id' => $ticketId,
            'body' => $body,
        ];

        if ($ticketStatusId !== null) {
            $data['ticket_status_id'] = $ticketStatusId;
        }

        if (! empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        return $this->api->request('POST', $this->getBasePath().'.addInternalMessage', $data);
    }

    /**
     * Import an existing message to a ticket (e.g., from email)
     *
     * @param  string  $ticketId  Ticket UUID
     * @param  string  $body  HTML formatted message body
     * @param  string  $sentByType  Type of sender (company, contact, user)
     * @param  string  $sentById  UUID of sender
     * @param  string  $sentAt  ISO 8601 datetime when message was sent
     * @param  array  $attachments  Optional array of file UUIDs
     */
    public function importMessage(
        string $ticketId,
        string $body,
        string $sentByType,
        string $sentById,
        string $sentAt,
        array $attachments = []
    ): array {
        if (empty($ticketId)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        if (empty($body)) {
            throw new InvalidArgumentException('Message body is required');
        }

        if (! in_array($sentByType, $this->sentByTypes)) {
            throw new InvalidArgumentException(
                'Invalid sent_by type. Must be one of: '.implode(', ', $this->sentByTypes)
            );
        }

        if (empty($sentById)) {
            throw new InvalidArgumentException('Sender ID is required');
        }

        if (empty($sentAt)) {
            throw new InvalidArgumentException('Sent at datetime is required');
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:\d{2})$/', $sentAt) || strtotime($sentAt) === false) {
            throw new InvalidArgumentException('sent_at must be an ISO 8601 datetime with a timezone, e.g. 2024-02-29T11:11:11+00:00');
        }

        $data = [
            'id' => $ticketId,
            'body' => $body,
            'sent_by' => [
                'type' => $sentByType,
                'id' => $sentById,
            ],
            'sent_at' => $sentAt,
        ];

        if (! empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        return $this->api->request('POST', $this->getBasePath().'.importMessage', $data);
    }

    /**
     * Get a specific message from a ticket
     *
     * @param  string  $messageId  Message UUID
     */
    public function getMessage(string $messageId): array
    {
        if (empty($messageId)) {
            throw new InvalidArgumentException('Message ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.getMessage', [
            'message_id' => $messageId,
        ]);
    }

    /**
     * List all messages for a ticket
     *
     * @param  string  $ticketId  Ticket UUID
     * @param  array  $filters  Optional message filters (type, created_before, created_after)
     * @param  array  $options  Pagination options
     */
    public function listMessages(string $ticketId, array $filters = [], array $options = []): array
    {
        if (empty($ticketId)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        $unknownOptions = array_diff(array_keys($options), ['page_size', 'page_number']);

        if ($unknownOptions !== []) {
            throw new InvalidArgumentException(
                'tickets.listMessages does not support: '.implode(', ', $unknownOptions).'. Supported: page_size, page_number.'
            );
        }

        // Unknown keys were dropped without a word until v2.2.13.
        $unknown = array_diff(array_keys($filters), self::MESSAGE_FILTERS);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter key for tickets.listMessages: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', self::MESSAGE_FILTERS).'.'
            );
        }

        $this->assertEnum($filters['type'] ?? null, self::MESSAGE_TYPES, 'filter.type', 'tickets.listMessages');

        $params = ['id' => $ticketId];
        $filter = array_filter($filters, fn ($value) => $value !== null);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        // listMessages documents a total count in `meta` with includes=pagination
        $params['includes'] = 'pagination';

        return $this->api->request('POST', $this->getBasePath().'.listMessages', $params);
    }

    /**
     * Get tickets for a specific customer (contact or company)
     *
     * @param  string  $customerType  Customer type (contact or company)
     * @param  string  $customerId  Customer UUID
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination options
     */
    public function forCustomer(
        string $customerType,
        string $customerId,
        array $additionalFilters = [],
        array $options = []
    ): array {
        $this->assertEnum($customerType, self::CUSTOMER_TYPES, 'filter.relates_to.type', 'tickets.list');

        $filters = array_merge(
            [
                'relates_to' => [
                    'type' => $customerType,
                    'id' => $customerId,
                ],
            ],
            $additionalFilters
        );

        return $this->list($filters, $options);
    }

    /**
     * List tickets with filtering and pagination
     *
     * @param  array  $filters  Filter parameters
     * @param  array  $options  Pagination options
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'tickets.list does not support: '.implode(', ', $unknown).'. Supported: page_size, page_number.'
            );
        }

        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build the filter object for tickets.list
     *
     * Until v2.2.13 every key was passed through unchecked. The dotted keys
     * this resource advertised (`relates_to.type`, `exclude.status_ids`) were
     * sent flat, which the API ignored — so they returned every ticket.
     *
     * @throws InvalidArgumentException On an unknown key or value
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'tickets.list', ['relates_to', 'exclude']);

        foreach (['relates_to.type', 'relates_to.id', 'exclude.status_ids'] as $dotted) {
            if (array_key_exists($dotted, $filters)) {
                [$root, $child] = explode('.', $dotted);
                $filters[$root][$child] = $filters[$dotted];
                unset($filters[$dotted]);
            }
        }

        if (isset($filters['relates_to'])) {
            $relatesTo = $filters['relates_to'];

            if (! is_array($relatesTo) || empty($relatesTo['id']) || ! isset($relatesTo['type'])) {
                throw new InvalidArgumentException('The relates_to filter needs both a type (contact or company) and an id.');
            }

            $this->assertEnum($relatesTo['type'], self::CUSTOMER_TYPES, 'filter.relates_to.type', 'tickets.list');
        }

        if (isset($filters['exclude']) && (! is_array($filters['exclude']) || array_keys($filters['exclude']) !== ['status_ids'])) {
            throw new InvalidArgumentException("The exclude filter takes status_ids only: ['exclude' => ['status_ids' => [...]]].");
        }

        foreach (['ids', 'project_ids', 'assignee_ids'] as $key) {
            if (array_key_exists($key, $filters) && ! is_array($filters[$key])) {
                $filters[$key] = [$filters[$key]];
            }
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Get tickets assigned to any of the given users
     *
     * Pass null as an entry to include unassigned tickets as well.
     *
     * @param  array<string|null>  $userIds
     */
    public function assignedTo(array $userIds, array $additionalFilters = [], array $options = []): array
    {
        if ($userIds === []) {
            throw new InvalidArgumentException('At least one user ID (or null for unassigned) is required');
        }

        return $this->list(array_merge(['assignee_ids' => array_values($userIds)], $additionalFilters), $options);
    }

    /**
     * Get tickets nobody is assigned to
     */
    public function unassigned(array $additionalFilters = [], array $options = []): array
    {
        return $this->assignedTo([null], $additionalFilters, $options);
    }

    /**
     * Get tickets for specific projects
     *
     * @param  array  $projectIds  Array of project UUIDs
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination options
     */
    public function forProjects(
        array $projectIds,
        array $additionalFilters = [],
        array $options = []
    ): array {
        if (empty($projectIds)) {
            throw new InvalidArgumentException('At least one project ID is required');
        }

        $filters = array_merge(
            ['project_ids' => $projectIds],
            $additionalFilters
        );

        return $this->list($filters, $options);
    }

    /**
     * Get tickets by specific IDs
     *
     * @param  array  $ids  Array of ticket UUIDs
     * @param  array  $options  Pagination options
     */
    public function byIds(array $ids, array $options = []): array
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('At least one ticket ID is required');
        }

        return $this->list(['ids' => $ids], $options);
    }

    /**
     * Exclude tickets with specific statuses
     *
     * @param  array  $statusIds  Array of status UUIDs to exclude
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination options
     */
    public function excludeStatuses(
        array $statusIds,
        array $additionalFilters = [],
        array $options = []
    ): array {
        if (empty($statusIds)) {
            throw new InvalidArgumentException('At least one status ID is required');
        }

        $filters = array_merge(
            [
                'exclude' => [
                    'status_ids' => $statusIds,
                ],
            ],
            $additionalFilters
        );

        return $this->list($filters, $options);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created ticket ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created ticket',
                    'data.type' => 'Resource type (always "ticket")',
                ],
            ],
            'update' => [
                'description' => 'Empty response with 204 status on success',
            ],
            'info' => [
                'description' => 'Complete ticket information',
                'fields' => [
                    'id' => 'Ticket UUID',
                    'reference' => 'Ticket reference number',
                    'subject' => 'Ticket subject',
                    'status' => 'Status object with id and type',
                    'assignee' => 'Assigned user (nullable)',
                    'created_at' => 'Creation timestamp',
                    'closed_at' => 'Closing timestamp (nullable)',
                    'customer' => 'Customer reference (contact or company)',
                    'participant' => 'Third-party participant (nullable)',
                    'last_message_at' => 'Last message timestamp (nullable)',
                    'description' => 'Ticket description (Markdown)',
                    'project' => 'Associated project (nullable)',
                    'milestone' => 'Associated milestone (nullable)',
                    'custom_fields' => 'Array of custom field values',
                ],
            ],
            'list' => [
                'description' => 'Array of tickets with pagination',
                'fields' => [
                    'data' => 'Array of ticket objects (similar to info)',
                ],
            ],
            'addReply' => [
                'description' => 'Response contains the created message ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created message',
                    'data.type' => 'Resource type (always "message")',
                ],
            ],
            'addInternalMessage' => [
                'description' => 'Response contains the created internal message ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created message',
                    'data.type' => 'Resource type (always "message")',
                ],
            ],
            'importMessage' => [
                'description' => 'Response contains the imported message ID and type',
                'fields' => [
                    'data.id' => 'UUID of the imported message',
                    'data.type' => 'Resource type (always "message")',
                ],
            ],
            'getMessage' => [
                'description' => 'Complete message information',
                'fields' => [
                    'message_id' => 'Message UUID',
                    'body' => 'Message body (HTML)',
                    'raw_body' => 'Raw message body (HTML)',
                    'created_at' => 'Creation timestamp',
                    'sent_by' => 'Sender information (type and id)',
                    'ticket' => 'Associated ticket reference',
                    'attachments' => 'Array of attached files',
                    'type' => 'Message type (customer, internal, thirdParty)',
                ],
            ],
            'listMessages' => [
                'description' => 'Array of messages with pagination',
                'fields' => [
                    'data' => 'Array of message objects',
                    'meta' => 'Pagination metadata (when includes=pagination)',
                ],
            ],
        ];
    }
}
