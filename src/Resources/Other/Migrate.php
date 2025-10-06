<?php

namespace McoreServices\TeamleaderSDK\Resources\Other;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Migrate extends Resource
{
    protected string $description = 'Utility endpoints for migrating from the deprecated Teamleader API to the new UUID-based API';

    // Resource capabilities - Migration endpoints don't follow CRUD patterns
    protected bool $supportsCreation = false;
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for migration endpoints)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters (none for migration endpoints)
    protected array $commonFilters = [];

    // Valid activity types for migration
    protected array $activityTypes = [
        'meeting',
        'call',
        'task',
    ];

    // Valid resource types for ID migration
    protected array $resourceTypes = [
        'account',
        'user',
        'department',
        'product',
        'contact',
        'company',
        'deal',
        'dealPhase',
        'project',
        'milestone',
        'task',
        'meeting',
        'call',
        'ticket',
        'invoice',
        'creditNote',
        'subscription',
        'quotation',
        'timeTracking',
        'customField',
    ];

    // Valid response types for ID migration (some differ from request types)
    protected array $responseTypes = [
        'account',
        'user',
        'department',
        'product',
        'contact',
        'company',
        'deal',
        'dealPhase',
        'project',
        'milestone',
        'todo',      // task becomes todo in response
        'event',     // meeting/call become event in response
        'ticket',
        'invoice',
        'creditNote',
        'subscription',
        'quotation',
        'timeTracking',
        'customField',
    ];

    // Usage examples specific to migration
    protected array $usageExamples = [
        'migrate_activity_type' => [
            'description' => 'Translate meeting, call, or task into activity type UUID',
            'code' => '$result = $teamleader->migrate()->activityType("meeting");
// Returns: ["data" => ["id" => "uuid", "type" => "meeting"]]'
        ],
        'migrate_tax_rate' => [
            'description' => 'Translate old tax rate to new UUID tax rate',
            'code' => '$result = $teamleader->migrate()->taxRate(
    "department-uuid",
    "21"
);'
        ],
        'migrate_contact_id' => [
            'description' => 'Translate old contact ID to new UUID',
            'code' => '$result = $teamleader->migrate()->id("contact", 123);
// Returns: ["data" => ["type" => "contact", "id" => "new-uuid"]]'
        ],
        'migrate_invoice_id' => [
            'description' => 'Translate old invoice ID to new UUID',
            'code' => '$result = $teamleader->migrate()->id("invoice", 456);'
        ],
        'batch_migrate_ids' => [
            'description' => 'Migrate multiple IDs in a loop',
            'code' => '$oldIds = [1, 2, 3, 4, 5];
$newIds = [];

foreach ($oldIds as $oldId) {
    $result = $teamleader->migrate()->id("contact", $oldId);
    $newIds[$oldId] = $result["data"]["id"];
}'
        ],
    ];

    /**
     * Get the base path for the migrate resource
     */
    protected function getBasePath(): string
    {
        return 'migrate';
    }

    /**
     * Translate activity type (meeting, call, task) into activity type UUID
     *
     * @param string $type Activity type (meeting, call, or task)
     * @return array Response with activity type UUID
     * @throws InvalidArgumentException
     */
    public function activityType(string $type): array
    {
        if (!in_array($type, $this->activityTypes)) {
            throw new InvalidArgumentException(
                "Invalid activity type: {$type}. Must be one of: " . implode(', ', $this->activityTypes)
            );
        }

        $data = ['type' => $type];

        return $this->api->request('POST', $this->getBasePath() . '.activityType', $data);
    }

    /**
     * Translate old tax rate to new UUID tax rate
     *
     * @param string $departmentId Department UUID
     * @param string $taxRate Tax rate as string (e.g., "21", "6", "0")
     * @return array Response with tax rate UUID
     * @throws InvalidArgumentException
     */
    public function taxRate(string $departmentId, string $taxRate): array
    {
        if (empty($departmentId)) {
            throw new InvalidArgumentException('Department ID is required');
        }

        if (empty($taxRate)) {
            throw new InvalidArgumentException('Tax rate is required');
        }

        // Validate tax rate is numeric
        if (!is_numeric($taxRate)) {
            throw new InvalidArgumentException('Tax rate must be a numeric value (as string)');
        }

        $data = [
            'department_id' => $departmentId,
            'tax_rate' => $taxRate,
        ];

        return $this->api->request('POST', $this->getBasePath() . '.taxRate', $data);
    }

    /**
     * Translate old numeric ID to new UUID
     *
     * @param string $type Resource type (contact, company, invoice, etc.)
     * @param int $id Old numeric ID
     * @return array Response with new UUID
     * @throws InvalidArgumentException
     */
    public function id(string $type, int $id): array
    {
        if (!in_array($type, $this->resourceTypes)) {
            throw new InvalidArgumentException(
                "Invalid resource type: {$type}. Must be one of: " . implode(', ', $this->resourceTypes)
            );
        }

        if ($id < 1) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }

        $data = [
            'type' => $type,
            'id' => $id,
        ];

        return $this->api->request('POST', $this->getBasePath() . '.id', $data);
    }

    /**
     * Batch migrate multiple IDs of the same type
     * This is a convenience method that calls the API multiple times
     *
     * @param string $type Resource type
     * @param array $ids Array of old numeric IDs
     * @return array Associative array mapping old IDs to new UUIDs
     * @throws InvalidArgumentException
     */
    public function batchIds(string $type, array $ids): array
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('At least one ID is required');
        }

        $mapping = [];

        foreach ($ids as $oldId) {
            if (!is_int($oldId) && !ctype_digit((string)$oldId)) {
                throw new InvalidArgumentException("All IDs must be integers, got: {$oldId}");
            }

            $result = $this->id($type, (int)$oldId);
            $mapping[$oldId] = $result['data']['id'];
        }

        return $mapping;
    }

    /**
     * Get all valid activity types
     *
     * @return array
     */
    public function getActivityTypes(): array
    {
        return $this->activityTypes;
    }

    /**
     * Get all valid resource types for ID migration
     *
     * @return array
     */
    public function getResourceTypes(): array
    {
        return $this->resourceTypes;
    }

    /**
     * Check if an activity type is valid
     *
     * @param string $type
     * @return bool
     */
    public function isValidActivityType(string $type): bool
    {
        return in_array($type, $this->activityTypes);
    }

    /**
     * Check if a resource type is valid for migration
     *
     * @param string $type
     * @return bool
     */
    public function isValidResourceType(string $type): bool
    {
        return in_array($type, $this->resourceTypes);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'activityType' => [
                'description' => 'Activity type UUID for meeting, call, or task',
                'fields' => [
                    'data.id' => 'Activity type UUID',
                    'data.type' => 'Activity type string (meeting, call, or task)',
                ],
            ],
            'taxRate' => [
                'description' => 'Tax rate UUID for the given department and rate',
                'fields' => [
                    'data.id' => 'Tax rate UUID',
                    'data.type' => 'Resource type (always "taxRate")',
                ],
            ],
            'id' => [
                'description' => 'New UUID for the migrated resource',
                'fields' => [
                    'data.type' => 'Resource type (may differ from request type, e.g., task becomes todo)',
                    'data.id' => 'New UUID for the resource',
                ],
                'notes' => [
                    'The response type may differ from the request type',
                    'task becomes todo in the response',
                    'meeting and call become event in the response',
                ],
            ],
        ];
    }

    /**
     * Override list method as it's not supported for migration endpoints
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            'The migrate resource does not support list operations. Use activityType(), taxRate(), or id() methods.'
        );
    }

    /**
     * Override info method as it's not supported for migration endpoints
     */
    public function info($id, $includes = null): array
    {
        throw new InvalidArgumentException(
            'The migrate resource does not support info operations. Use activityType(), taxRate(), or id() methods.'
        );
    }
}
