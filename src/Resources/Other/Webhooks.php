<?php

namespace McoreServices\TeamleaderSDK\Resources\Other;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Webhooks extends Resource
{
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

    // Available webhook event types
    protected array $eventTypes = [
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
        'invoice.sent',
        'invoice.updated',
        'meeting.created',
        'meeting.completed',
        'meeting.deleted',
        'meeting.updated',
        'milestone.created',
        'milestone.updated',
        'nextgenProject.created',
        'nextgenProject.updated',
        'nextgenProject.closed',
        'nextgenProject.deleted',
        'nextgenTask.completed',
        'nextgenTask.created',
        'nextgenTask.deleted',
        'nextgenTask.updated',
        'product.added',
        'product.updated',
        'product.deleted',
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
     * @param  array  $filters  Not used for webhooks
     * @param  array  $options  Not used for webhooks
     */
    public function list(array $filters = [], array $options = []): array
    {
        return $this->api->request('POST', $this->getBasePath().'.list');
    }

    /**
     * Register a new webhook
     *
     * @param  string  $url  Your webhook URL (must be a valid HTTPS URL)
     * @param  array  $types  Array of event types that should trigger this webhook
     * @return array Response with 204 status on success
     *
     * @throws InvalidArgumentException
     */
    public function register(string $url, array $types): array
    {
        $this->validateWebhookData($url, $types);

        $data = [
            'url' => $url,
            'types' => $types,
        ];

        return $this->api->request('POST', $this->getBasePath().'.register', $data);
    }

    /**
     * Unregister a webhook
     * Removes the specified event types from the webhook URL
     *
     * @param  string  $url  Your webhook URL
     * @param  array  $types  Array of event types to unregister
     * @return array Response with 204 status on success
     *
     * @throws InvalidArgumentException
     */
    public function unregister(string $url, array $types): array
    {
        $this->validateWebhookData($url, $types);

        $data = [
            'url' => $url,
            'types' => $types,
        ];

        return $this->api->request('POST', $this->getBasePath().'.unregister', $data);
    }

    /**
     * Validate webhook registration/unregistration data
     *
     * @throws InvalidArgumentException
     */
    protected function validateWebhookData(string $url, array $types): void
    {
        // Validate URL
        if (empty($url)) {
            throw new InvalidArgumentException('Webhook URL is required');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid webhook URL format');
        }

        // Validate URL is HTTPS
        if (! str_starts_with($url, 'https://')) {
            throw new InvalidArgumentException('Webhook URL must use HTTPS protocol');
        }

        // Validate types
        if (empty($types)) {
            throw new InvalidArgumentException('At least one event type is required');
        }

        if (! is_array($types)) {
            throw new InvalidArgumentException('Event types must be an array');
        }

        // Validate each event type
        foreach ($types as $type) {
            if (! in_array($type, $this->eventTypes)) {
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
     * Get event types filtered by category
     *
     * @param  string  $category  Category name (e.g., 'invoice', 'contact', 'deal')
     * @return array Array of event types for the specified category
     */
    public function getEventTypesByCategory(string $category): array
    {
        $filtered = array_filter($this->eventTypes, function ($type) use ($category) {
            return str_starts_with($type, $category.'.');
        });

        return array_values($filtered);
    }

    /**
     * Get all invoice-related event types
     */
    public function getInvoiceEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('invoice'),
            $this->getEventTypesByCategory('incomingInvoice')
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
     * Get all project-related event types
     */
    public function getProjectEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('project'),
            $this->getEventTypesByCategory('nextgenProject')
        );
    }

    /**
     * Get all task-related event types
     */
    public function getTaskEventTypes(): array
    {
        return array_merge(
            $this->getEventTypesByCategory('task'),
            $this->getEventTypesByCategory('nextgenTask')
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
