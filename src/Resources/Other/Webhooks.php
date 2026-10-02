<?php

namespace McoreServices\TeamleaderSDK\Resources\Other;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Webhooks extends Resource
{
    /**
     * `types[]` on webhooks.register and webhooks.unregister. Checked against
     * the specification by OtherSpecContractTest, so a new event type in the
     * spec fails the suite until it is added here.
     */
    public const EVENT_TYPES = [
        'account.deactivated',
        'account.deleted',
        'call.added',
        'call.completed',
        'call.deleted',
        'call.updated',
        'company.added',
        'company.deleted',
        'company.updated',
        'contact.added',
        'contact.deleted',
        'contact.linkedToCompany',
        'contact.unlinkedFromCompany',
        'contact.updatedLinkToCompany',
        'contact.updated',
        'creditNote.booked',
        'creditNote.deleted',
        'creditNote.peppolSubmissionFailed',
        'creditNote.peppolSubmissionSucceeded',
        'creditNote.sent',
        'creditNote.updated',
        'deal.created',
        'deal.deleted',
        'deal.lost',
        'deal.moved',
        'deal.updated',
        'deal.won',
        'incomingCreditNote.added',
        'incomingCreditNote.approved',
        'incomingCreditNote.bookkeepingSubmissionFailed',
        'incomingCreditNote.bookkeepingSubmissionSucceeded',
        'incomingCreditNote.deleted',
        'incomingCreditNote.refused',
        'incomingCreditNote.updated',
        'incomingInvoice.added',
        'incomingInvoice.approved',
        'incomingInvoice.bookkeepingSubmissionFailed',
        'incomingInvoice.bookkeepingSubmissionSucceeded',
        'incomingInvoice.deleted',
        'incomingInvoice.refused',
        'incomingInvoice.updated',
        'invoice.booked',
        'invoice.deleted',
        'invoice.drafted',
        'invoice.paymentRegistered',
        'invoice.paymentRemoved',
        'invoice.peppolSubmissionFailed',
        'invoice.peppolSubmissionSucceeded',
        'invoice.sent',
        'invoice.updated',
        'meeting.completed',
        'meeting.created',
        'meeting.deleted',
        'meeting.updated',
        'milestone.created',
        'milestone.updated',
        'nextgenProject.closed',
        'nextgenProject.created',
        'nextgenProject.deleted',
        'nextgenProject.updated',
        'nextgenTask.completed',
        'nextgenTask.created',
        'nextgenTask.deleted',
        'nextgenTask.updated',
        'product.added',
        'product.deleted',
        'product.updated',
        'project.created',
        'project.deleted',
        'project.updated',
        'receipt.added',
        'receipt.approved',
        'receipt.bookkeepingSubmissionFailed',
        'receipt.bookkeepingSubmissionSucceeded',
        'receipt.deleted',
        'receipt.refused',
        'receipt.updated',
        'subscription.added',
        'subscription.deactivated',
        'subscription.deleted',
        'subscription.updated',
        'task.completed',
        'task.created',
        'task.deleted',
        'task.updated',
        'ticket.closed',
        'ticket.created',
        'ticket.deleted',
        'ticket.reopened',
        'ticket.updated',
        'ticketMessage.added',
        'timeTracking.added',
        'timeTracking.deleted',
        'timeTracking.updated',
        'user.deactivated',
    ];

    protected string $description = 'Manage webhooks for real-time event notifications in Teamleader Focus';

    // Resource capabilities - Webhooks support list, register, and unregister operations
    protected bool $supportsCreation = false;  // Uses custom register() method instead

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;  // Uses custom unregister() method instead

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = false;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for webhooks)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters (none for webhooks)
    protected array $commonFilters = [];

    /**
     * Webhook event types accepted by webhooks.register and webhooks.unregister.
     *
     * Verified complete against @teamleader/focus-api-specification v1.197.0 on
     * 2026-08-17: 95 types, exact match in both directions.
     *
     * Note the two project families — `project.*` for the legacy project system
     * and `nextgenProject.*` for the current one. See the Projects and
     * LegacyProjects resources for which SDK method corresponds to which.
     */
    /**
     * Kept for backwards compatibility — see EVENT_TYPES.
     */
    protected array $eventTypes = self::EVENT_TYPES;

    // Usage examples specific to webhooks
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all registered webhooks',
            'code' => '$webhooks = $teamleader->webhooks()->list();',
        ],
        'register_single_event' => [
            'description' => 'Register a webhook for a single event type',
            'code' => '$result = $teamleader->webhooks()->register(
                "https://example.com/webhook",
                ["invoice.booked"]
            );',
        ],
        'register_multiple_events' => [
            'description' => 'Register a webhook for multiple event types',
            'code' => '$result = $teamleader->webhooks()->register(
                "https://example.com/webhook",
                [
                    "invoice.booked",
                    "invoice.sent",
                    "invoice.paymentRegistered"
                ]
            );',
        ],
        'register_all_invoice_events' => [
            'description' => 'Register a webhook for all invoice-related events',
            'code' => '$types = $teamleader->webhooks()->getInvoiceEventTypes();
            $result = $teamleader->webhooks()->register("https://example.com/webhook", $types);',
        ],
        'unregister_specific_events' => [
            'description' => 'Unregister specific event types from a webhook',
            'code' => '$result = $teamleader->webhooks()->unregister(
                "https://example.com/webhook",
                ["invoice.booked"]
            );',
        ],
        'unregister_all_events' => [
            'description' => 'Unregister all events for a webhook URL',
            'code' => '$webhooks = $teamleader->webhooks()->list();
            $url = "https://example.com/webhook";
            $types = [];
            foreach ($webhooks["data"] as $webhook) {
                if ($webhook["url"] === $url) {
                    $types = $webhook["types"];
                    break;
                }
            }
            $result = $teamleader->webhooks()->unregister($url, $types);',
        ],
        'get_event_types_by_category' => [
            'description' => 'Get event types for a specific category',
            'code' => '$contactEvents = $teamleader->webhooks()->getEventTypesByCategory("contact");
            $dealEvents = $teamleader->webhooks()->getEventTypesByCategory("deal");',
        ],
    ];

    /**
     * Get the base path for the webhooks resource
     */
    protected function getBasePath(): string
    {
        return 'webhooks';
    }

    /**
     * List all registered webhooks
     * Webhooks are returned ordered by URL
     *
     * `webhooks.list` takes no request body — no filter, page or sort parameter
     * exists for it. Arguments are rejected rather than discarded.
     *
     * @param  array  $filters  Must be empty — this endpoint accepts no filters
     * @param  array  $options  Must be empty — this endpoint accepts no sorting or pagination
     *
     * @throws InvalidArgumentException When any argument is passed
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->rejectUnsupportedListArguments($filters, $options);

        return $this->api->request('POST', $this->getBasePath().'.list');
    }

    /**
     * Register a new webhook
     *
     * Webhooks are not a complete record of changes: `subscription.updated`,
     * for one, fires for the subscription's own fields but not for its lines.
     * Pair them with a periodic re-sync.
     *
     * @param  string  $url  Your webhook URL (must be a valid HTTPS URL)
     * @param  array  $types  Array of event types that should trigger this webhook
     *
     * @throws InvalidArgumentException
     */
    public function register(string $url, array $types): array
    {
        $this->validateWebhookData($url, $types);

        return $this->api->request('POST', $this->getBasePath().'.register', [
            'url' => $url,
            'types' => $types,
        ]);
    }

    /**
     * Unregister a webhook
     * Removes the specified event types from the webhook URL
     *
     * @param  string  $url  Your webhook URL
     * @param  array  $types  Array of event types to unregister
     *
     * @throws InvalidArgumentException
     */
    public function unregister(string $url, array $types): array
    {
        $this->validateWebhookData($url, $types);

        return $this->api->request('POST', $this->getBasePath().'.unregister', [
            'url' => $url,
            'types' => $types,
        ]);
    }

    /**
     * Validate webhook registration/unregistration data
     *
     * @throws InvalidArgumentException
     */
    protected function validateWebhookData(string $url, array $types): void
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Webhook URL is required');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid webhook URL format');
        }

        if (! str_starts_with($url, 'https://')) {
            throw new InvalidArgumentException('Webhook URL must use HTTPS protocol');
        }

        if (empty($types)) {
            throw new InvalidArgumentException('At least one event type is required');
        }

        if (! is_array($types)) {
            throw new InvalidArgumentException('Event types must be an array');
        }

        foreach ($types as $type) {
            if (! in_array($type, self::EVENT_TYPES, true)) {
                throw new InvalidArgumentException(
                    "Invalid event type: {$type}. Use getAvailableEventTypes() to see all valid types."
                );
            }
        }
    }

    /**
     * Get all available webhook event types
     */
    public function getAvailableEventTypes(): array
    {
        return $this->eventTypes;
    }

    /**
     * Get event types filtered by category prefix
     *
     * @param  string  $category  Category prefix (e.g. 'invoice', 'contact', 'deal')
     */
    public function getEventTypesByCategory(string $category): array
    {
        $filtered = array_filter($this->eventTypes, function ($type) use ($category) {
            return str_starts_with($type, $category.'.');
        });

        return array_values($filtered);
    }

    /**
     * Get all invoice-related event types (invoice + incomingInvoice)
     */
    public function getInvoiceEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('invoice'),
            $this->getEventTypesByCategory('incomingInvoice')
        );
    }

    /**
     * Get all credit note-related event types (creditNote + incomingCreditNote)
     */
    public function getCreditNoteEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('creditNote'),
            $this->getEventTypesByCategory('incomingCreditNote')
        );
    }

    /**
     * Get all deal-related event types
     */
    public function getDealEventTypes(): array
    {
        return $this->getEventTypesByCategory('deal');
    }

    /**
     * Get all contact-related event types
     */
    public function getContactEventTypes(): array
    {
        return $this->getEventTypesByCategory('contact');
    }

    /**
     * Get all company-related event types
     */
    public function getCompanyEventTypes(): array
    {
        return $this->getEventTypesByCategory('company');
    }

    /**
     * Get all project-related event types (project + nextgenProject)
     */
    public function getProjectEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('project'),
            $this->getEventTypesByCategory('nextgenProject')
        );
    }

    /**
     * Get all task-related event types (task + nextgenTask)
     */
    public function getTaskEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('task'),
            $this->getEventTypesByCategory('nextgenTask')
        );
    }

    /**
     * Get all ticket-related event types
     */
    public function getTicketEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('ticket'),
            $this->getEventTypesByCategory('ticketMessage')
        );
    }

    /**
     * Get all time tracking-related event types
     */
    public function getTimeTrackingEventTypes(): array
    {
        return $this->getEventTypesByCategory('timeTracking');
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of registered webhooks ordered by URL',
                'fields' => [
                    'data' => 'Array of webhook objects',
                    'data[].url' => 'Your webhook URL',
                    'data[].types' => 'Array of event types that fire the webhook',
                ],
            ],
            'register' => [
                'description' => 'Empty response with 204 status code on success',
                'fields' => [],
            ],
            'unregister' => [
                'description' => 'Empty response with 204 status code on success',
                'fields' => [],
            ],
        ];
    }
}
