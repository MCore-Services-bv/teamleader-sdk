<?php

namespace McoreServices\TeamleaderSDK\Resources\Tickets;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Tickets extends Resource
{
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
    protected array $commonFilters = [
        'ids' => 'Array of ticket UUIDs',
        'relates_to.type' => 'Related entity type (contact, company)',
        'relates_to.id' => 'Related entity UUID',
        'project_ids' => 'Array of project UUIDs',
        'exclude.status_ids' => 'Array of status UUIDs to exclude',
    ];

    // Valid customer types
    protected array $customerTypes = [
        'contact',
        'company',
    ];

    // Valid initial reply options
    protected array $initialReplyOptions = [
        'automatic',
        'disabled',
    ];

    // Valid message types
    protected array $messageTypes = [
        'customer',
        'internal',
        'thirdParty',
    ];

    // Valid sent_by types for importing messages
    protected array $sentByTypes = [
        'company',
        'contact',
        'user',
    ];

    // Usage examples specific to tickets
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all tickets',
            'code' => '$tickets = $teamleader->tickets()->list();'
        ],
        'filter_by_customer' => [
            'description' => 'Get tickets for a specific customer',
            'code' => '$tickets = $teamleader->tickets()->forCustomer("company", "company-uuid");'
        ],
        'filter_by_project' => [
            'description' => 'Get tickets for specific projects',
            'code' => '$tickets = $teamleader->tickets()->forProjects(["project-uuid-1", "project-uuid-2"]);'
        ],
        'get_ticket_info' => [
            'description' => 'Get detailed ticket information',
            'code' => '$ticket = $teamleader->tickets()->info("ticket-uuid");'
        ],
        'create_ticket' => [
            'description' => 'Create a new ticket',
            'code' => '$ticket = $teamleader->tickets()->create([
    "subject" => "Customer issue",
    "customer" => ["type" => "company", "id" => "company-uuid"],
    "ticket_status_id" => "status-uuid",
    "assignee" => ["type" => "user", "id" => "user-uuid"]
]);'
        ],
        'update_ticket' => [
            'description' => 'Update an existing ticket',
            'code' => '$result = $teamleader->tickets()->update("ticket-uuid", [
    "subject" => "Updated subject",
    "ticket_status_id" => "new-status-uuid"
]);'
        ],
        'add_reply' => [
            'description' => 'Add a customer-facing reply to a ticket',
            'code' => '$result = $teamleader->tickets()->addReply(
    "ticket-uuid",
    "<p>Thank you for your inquiry...</p>",
    "status-uuid"
);'
        ],
        'add_internal_message' => [
            'description' => 'Add an internal note to a ticket',
            'code' => '$result = $teamleader->tickets()->addInternalMessage(
    "ticket-uuid",
    "<p>Internal note about this ticket...</p>"
);'
        ],
        'list_messages' => [
            'description' => 'Get all messages for a ticket',
            'code' => '$messages = $teamleader->tickets()->listMessages("ticket-uuid");'
        ],
        'get_message' => [
            'description' => 'Get a specific message',
            'code' => '$message = $teamleader->tickets()->getMessage("message-uuid");'
        ],
        'import_message' => [
            'description' => 'Import an existing message (e.g., from email)',
            'code' => '$result = $teamleader->tickets()->importMessage(
    "ticket-uuid",
    "<p>Message content...</p>",
    "contact",
    "contact-uuid",
    "2024-02-29T11:11:11+00:00"
);'
        ],
    ];

    /**
     * Get the base path for the tickets resource
     */
    protected function getBasePath(): string
    {
        return 'tickets';
    }

    /**
     * List tickets with filtering and pagination
     *
     * @param array $filters Filter parameters
     * @param array $options Pagination options
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1
            ];
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get detailed ticket information
     *
     * @param string $id Ticket UUID
     * @param mixed $includes Optional includes
     * @return array
     */
    public function info($id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.info', [
            'id' => $id
        ]);
    }

    /**
     * Create a new ticket
     *
     * Required fields: subject, customer, ticket_status_id
     *
     * @param array $data Ticket data
     * @return array
     */
    public function create(array $data): array
    {
        $this->validateTicketData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Update an existing ticket
     *
     * @param string $id Ticket UUID
     * @param array $data Data to update
     * @return array
     */
    public function update($id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        $data['id'] = $id;
        $this->validateTicketData($data, 'update');

        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Add a customer-facing reply to a ticket
     *
     * @param string $ticketId Ticket UUID
     * @param string $body HTML formatted message body
     * @param string|null $ticketStatusId Optional status UUID to update
     * @param array $attachments Optional array of file UUIDs
     * @return array
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

        if (!empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        return $this->api->request('POST', $this->getBasePath() . '.addReply', $data);
    }

    /**
     * Add an internal message (note) to a ticket
     *
     * @param string $ticketId Ticket UUID
     * @param string $body HTML formatted message body
     * @param string|null $ticketStatusId Optional status UUID to update
     * @param array $attachments Optional array of file UUIDs
     * @return array
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

        if (!empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        return $this->api->request('POST', $this->getBasePath() . '.addInternalMessage', $data);
    }

    /**
     * Import an existing message to a ticket (e.g., from email)
     *
     * @param string $ticketId Ticket UUID
     * @param string $body HTML formatted message body
     * @param string $sentByType Type of sender (company, contact, user)
     * @param string $sentById UUID of sender
     * @param string $sentAt ISO 8601 datetime when message was sent
     * @param array $attachments Optional array of file UUIDs
     * @return array
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

        if (!in_array($sentByType, $this->sentByTypes)) {
            throw new InvalidArgumentException(
                'Invalid sent_by type. Must be one of: ' . implode(', ', $this->sentByTypes)
            );
        }

        if (empty($sentById)) {
            throw new InvalidArgumentException('Sender ID is required');
        }

        if (empty($sentAt)) {
            throw new InvalidArgumentException('Sent at datetime is required');
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

        if (!empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        return $this->api->request('POST', $this->getBasePath() . '.importMessage', $data);
    }

    /**
     * Get a specific message from a ticket
     *
     * @param string $messageId Message UUID
     * @return array
     */
    public function getMessage(string $messageId): array
    {
        if (empty($messageId)) {
            throw new InvalidArgumentException('Message ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.getMessage', [
            'message_id' => $messageId
        ]);
    }

    /**
     * List all messages for a ticket
     *
     * @param string $ticketId Ticket UUID
     * @param array $filters Optional message filters (type, created_before, created_after)
     * @param array $options Pagination options
     * @return array
     */
    public function listMessages(string $ticketId, array $filters = [], array $options = []): array
    {
        if (empty($ticketId)) {
            throw new InvalidArgumentException('Ticket ID is required');
        }

        $params = ['id' => $ticketId];

        // Apply filters
        if (!empty($filters)) {
            $params['filter'] = [];

            if (isset($filters['type'])) {
                if (!in_array($filters['type'], $this->messageTypes)) {
                    throw new InvalidArgumentException(
                        'Invalid message type. Must be one of: ' . implode(', ', $this->messageTypes)
                    );
                }
                $params['filter']['type'] = $filters['type'];
            }

            if (isset($filters['created_before'])) {
                $params['filter']['created_before'] = $filters['created_before'];
            }

            if (isset($filters['created_after'])) {
                $params['filter']['created_after'] = $filters['created_after'];
            }
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1
            ];
        }

        return $this->api->request('POST', $this->getBasePath() . '.listMessages', $params);
    }

    /**
     * Get tickets for a specific customer (contact or company)
     *
     * @param string $customerType Customer type (contact or company)
     * @param string $customerId Customer UUID
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
     */
    public function forCustomer(
        string $customerType,
        string $customerId,
        array $additionalFilters = [],
        array $options = []
    ): array {
        if (!in_array($customerType, $this->customerTypes)) {
            throw new InvalidArgumentException(
                'Invalid customer type. Must be one of: ' . implode(', ', $this->customerTypes)
            );
        }

        $filters = array_merge(
            [
                'relates_to' => [
                    'type' => $customerType,
                    'id' => $customerId,
                ]
            ],
            $additionalFilters
        );

        return $this->list($filters, $options);
    }

    /**
     * Get tickets for specific projects
     *
     * @param array $projectIds Array of project UUIDs
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
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
     * @param array $ids Array of ticket UUIDs
     * @param array $options Pagination options
     * @return array
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
     * @param array $statusIds Array of status UUIDs to exclude
     * @param array $additionalFilters Additional filters to apply
     * @param array $options Pagination options
     * @return array
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
                ]
            ],
            $additionalFilters
        );

        return $this->list($filters, $options);
    }

    /**
     * Build filters for the API request
     *
     * @param array $filters
     * @return array
     */
    protected function buildFilters(array $filters): array
    {
        $formatted = [];

        foreach ($filters as $key => $value) {
            // Handle nested filters like relates_to and exclude
            if (in_array($key, ['relates_to', 'exclude'])) {
                $formatted[$key] = $value;
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Validate ticket data before sending to API
     *
     * @param array $data
     * @param string $operation Operation type (create or update)
     * @throws InvalidArgumentException
     */
    protected function validateTicketData(array $data, string $operation = 'create'): void
    {
        // Required fields for creation
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
        }

        // Validate customer structure
        if (isset($data['customer'])) {
            if (!isset($data['customer']['type']) || !isset($data['customer']['id'])) {
                throw new InvalidArgumentException('Customer must have type and id');
            }

            if (!in_array($data['customer']['type'], $this->customerTypes)) {
                throw new InvalidArgumentException(
                    'Invalid customer type. Must be one of: ' . implode(', ', $this->customerTypes)
                );
            }
        }

        // Validate assignee structure if present
        if (isset($data['assignee'])) {
            if (!isset($data['assignee']['type']) || !isset($data['assignee']['id'])) {
                throw new InvalidArgumentException('Assignee must have type and id');
            }

            if ($data['assignee']['type'] !== 'user') {
                throw new InvalidArgumentException('Assignee type must be "user"');
            }
        }

        // Validate participant structure if present
        if (isset($data['participant'])) {
            if (!isset($data['participant']['customer'])) {
                throw new InvalidArgumentException('Participant must have customer');
            }

            $customer = $data['participant']['customer'];
            if (!isset($customer['type']) || !isset($customer['id'])) {
                throw new InvalidArgumentException('Participant customer must have type and id');
            }

            if ($customer['type'] !== 'company') {
                throw new InvalidArgumentException('Participant customer type must be "company"');
            }
        }

        // Validate initial_reply if present
        if (isset($data['initial_reply'])) {
            if (!in_array($data['initial_reply'], $this->initialReplyOptions)) {
                throw new InvalidArgumentException(
                    'Invalid initial_reply value. Must be one of: ' . implode(', ', $this->initialReplyOptions)
                );
            }
        }

        // Validate custom fields structure if present
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            foreach ($data['custom_fields'] as $field) {
                if (!isset($field['id'])) {
                    throw new InvalidArgumentException('Each custom field must have an id');
                }
            }
        }
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
                    'data.type' => 'Resource type (always "ticket")'
                ]
            ],
            'update' => [
                'description' => 'Empty response with 204 status on success'
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
                    'custom_fields' => 'Array of custom field values'
                ]
            ],
            'list' => [
                'description' => 'Array of tickets with pagination',
                'fields' => [
                    'data' => 'Array of ticket objects (similar to info)',
                ]
            ],
            'addReply' => [
                'description' => 'Response contains the created message ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created message',
                    'data.type' => 'Resource type (always "message")'
                ]
            ],
            'addInternalMessage' => [
                'description' => 'Response contains the created internal message ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created message',
                    'data.type' => 'Resource type (always "message")'
                ]
            ],
            'importMessage' => [
                'description' => 'Response contains the imported message ID and type',
                'fields' => [
                    'data.id' => 'UUID of the imported message',
                    'data.type' => 'Resource type (always "message")'
                ]
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
                    'type' => 'Message type (customer, internal, thirdParty)'
                ]
            ],
            'listMessages' => [
                'description' => 'Array of messages with pagination',
                'fields' => [
                    'data' => 'Array of message objects',
                    'meta' => 'Pagination metadata (when includes=pagination)'
                ]
            ]
        ];
    }
}
