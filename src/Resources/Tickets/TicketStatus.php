<?php

namespace McoreServices\TeamleaderSDK\Resources\Tickets;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class TicketStatus extends Resource
{
    protected string $description = 'Manage ticket statuses in Teamleader Focus';

    // Resource capabilities - Ticket statuses are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false; // No pagination mentioned in API docs

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of ticket status UUIDs',
    ];

    // Valid status types
    protected array $statusTypes = [
        'new',
        'open',
        'waiting_for_client',
        'escalated_thirdparty',
        'closed',
        'custom',
    ];

    // Usage examples specific to ticket statuses
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all ticket statuses',
            'code' => '$statuses = $teamleader->ticketStatus()->list();',
        ],
        'filter_by_ids' => [
            'description' => 'Get specific ticket statuses by IDs',
            'code' => '$statuses = $teamleader->ticketStatus()->byIds([\'uuid1\', \'uuid2\']);',
        ],
        'find_by_type' => [
            'description' => 'Find statuses by type',
            'code' => '$openStatuses = $teamleader->ticketStatus()->findByType("open");',
        ],
        'get_custom_statuses' => [
            'description' => 'Get only custom statuses',
            'code' => '$customStatuses = $teamleader->ticketStatus()->customStatuses();',
        ],
        'as_options' => [
            'description' => 'Get statuses as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->ticketStatus()->asOptions();',
        ],
    ];

    /**
     * Get the base path for the ticket status resource
     */
    protected function getBasePath(): string
    {
        return 'ticketStatus';
    }

    /**
     * List ticket statuses with optional filtering
     *
     * @param  array  $filters  Filter parameters
     * @param  array  $options  Not used for ticket statuses
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get ticket statuses by specific IDs
     *
     * @param  array  $ids  Array of ticket status UUIDs
     */
    public function byIds(array $ids): array
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('At least one ticket status ID is required');
        }

        return $this->list(['ids' => $ids]);
    }

    /**
     * Find ticket statuses by type
     *
     * @param  string  $type  Status type (new, open, waiting_for_client, escalated_thirdparty, closed, custom)
     * @return array|null Matching statuses or null if not found
     */
    public function findByType(string $type): ?array
    {
        if (! in_array($type, $this->statusTypes)) {
            throw new InvalidArgumentException(
                'Invalid status type. Must be one of: '.implode(', ', $this->statusTypes)
            );
        }

        $result = $this->list();

        if (empty($result['data'])) {
            return null;
        }

        $matches = array_filter($result['data'], function ($status) use ($type) {
            return isset($status['status']) && $status['status'] === $type;
        });

        return ! empty($matches) ? array_values($matches) : null;
    }

    /**
     * Get all custom ticket statuses
     *
     * @return array|null Custom statuses or null if none found
     */
    public function customStatuses(): ?array
    {
        return $this->findByType('custom');
    }

    /**
     * Get statuses formatted as key-value pairs for dropdowns
     *
     * @return array Associative array with status IDs as keys and labels as values
     */
    public function asOptions(): array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return [];
        }

        $options = [];
        foreach ($result['data'] as $status) {
            // For custom statuses, use the label; for standard statuses, use the status type
            $label = $status['label'] ?? $this->formatStatusLabel($status['status']);
            $options[$status['id']] = $label;
        }

        return $options;
    }

    /**
     * Get all status IDs
     *
     * @return array Array of status UUIDs
     */
    public function allIds(): array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return [];
        }

        return array_column($result['data'], 'id');
    }

    /**
     * Find a status by its label (for custom statuses)
     *
     * @param  string  $label  Status label to search for
     * @return array|null Status data or null if not found
     */
    public function findByLabel(string $label): ?array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return null;
        }

        foreach ($result['data'] as $status) {
            // Check custom statuses by label
            if (isset($status['label']) && strcasecmp($status['label'], $label) === 0) {
                return $status;
            }

            // Check standard statuses by formatted label
            if (! isset($status['label'])) {
                $formattedLabel = $this->formatStatusLabel($status['status']);
                if (strcasecmp($formattedLabel, $label) === 0) {
                    return $status;
                }
            }
        }

        return null;
    }

    /**
     * Check if a status type is valid
     */
    public function isValidStatusType(string $type): bool
    {
        return in_array($type, $this->statusTypes);
    }

    /**
     * Get all valid status types
     */
    public function getValidStatusTypes(): array
    {
        return $this->statusTypes;
    }

    /**
     * Format a status type into a human-readable label
     *
     * @param  string  $status  Status type
     * @return string Formatted label
     */
    protected function formatStatusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    /**
     * Build filters for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $formatted = [];

        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $formatted['ids'] = $filters['ids'];
        }

        return $formatted;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of ticket status objects',
                'fields' => [
                    'data' => 'Array of status objects',
                    'data[].id' => 'Status UUID',
                    'data[].status' => 'Status type (new, open, waiting_for_client, escalated_thirdparty, closed, custom)',
                    'data[].label' => 'Custom label (only available for custom status type)',
                ],
            ],
        ];
    }
}
