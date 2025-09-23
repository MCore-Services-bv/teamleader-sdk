<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Phases extends Resource
{
    protected string $description = 'Manage deal phases in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsPagination = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSorting = false;
    protected bool $supportsSideloading = false;
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;

    // Available includes for sideloading (none for deal phases)
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of deal phase UUIDs to filter by',
        'deal_pipeline_id' => 'Filter phases by specific pipeline UUID',
    ];

    // Usage examples specific to deal phases
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all phases across all pipelines',
            'code' => '$phases = $teamleader->dealPhases()->list();'
        ],
        'list_for_pipeline' => [
            'description' => 'Get phases for specific pipeline',
            'code' => '$phases = $teamleader->dealPhases()->list([\'deal_pipeline_id\' => \'pipeline-uuid\']);'
        ],
        'create_phase' => [
            'description' => 'Create a new phase',
            'code' => '$phase = $teamleader->dealPhases()->create([\'name\' => \'New Phase\', \'deal_pipeline_id\' => \'uuid\', \'requires_attention_after\' => [\'amount\' => 7, \'unit\' => \'days\']]);'
        ],
        'duplicate_phase' => [
            'description' => 'Duplicate an existing phase',
            'code' => '$newPhase = $teamleader->dealPhases()->duplicate(\'source-phase-uuid\');'
        ],
        'move_phase' => [
            'description' => 'Move phase to new position',
            'code' => '$teamleader->dealPhases()->move(\'phase-uuid\', \'after-phase-uuid\');'
        ]
    ];

    /**
     * Get the base path for the deal phases resource
     */
    protected function getBasePath(): string
    {
        return 'dealPhases';
    }

    /**
     * List deal phases with enhanced filtering and pagination
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (pagination)
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
     * Create a new deal phase
     *
     * @param array $data Phase data
     * @return array
     */
    public function create(array $data): array
    {
        if (!$this->supportsCreation) {
            throw new InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support creation"
            );
        }

        // Validate required fields
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Phase name is required');
        }

        if (empty($data['deal_pipeline_id'])) {
            throw new InvalidArgumentException('Deal pipeline ID is required');
        }

        if (empty($data['requires_attention_after'])) {
            throw new InvalidArgumentException('Requires attention after configuration is required');
        }

        // Validate requires_attention_after structure
        $attentionAfter = $data['requires_attention_after'];
        if (!isset($attentionAfter['amount']) || !isset($attentionAfter['unit'])) {
            throw new InvalidArgumentException('requires_attention_after must include amount and unit');
        }

        if (!in_array($attentionAfter['unit'], ['days', 'weeks'])) {
            throw new InvalidArgumentException('requires_attention_after unit must be "days" or "weeks"');
        }

        // Validate follow_up_actions if provided
        if (isset($data['follow_up_actions']) && is_array($data['follow_up_actions'])) {
            $validActions = ['create_event', 'create_call', 'create_task'];
            foreach ($data['follow_up_actions'] as $action) {
                if (!in_array($action, $validActions)) {
                    throw new InvalidArgumentException("Invalid follow_up_action: {$action}");
                }
            }
        }

        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Update a deal phase
     *
     * @param string $id Phase UUID
     * @param array $data Update data
     * @return array
     */
    public function update($id, array $data): array
    {
        if (!$this->supportsUpdate) {
            throw new InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support updates"
            );
        }

        $data['id'] = $id;

        // Validate requires_attention_after if provided
        if (isset($data['requires_attention_after'])) {
            $attentionAfter = $data['requires_attention_after'];
            if (!isset($attentionAfter['amount']) || !isset($attentionAfter['unit'])) {
                throw new InvalidArgumentException('requires_attention_after must include amount and unit');
            }

            if (!in_array($attentionAfter['unit'], ['days', 'weeks'])) {
                throw new InvalidArgumentException('requires_attention_after unit must be "days" or "weeks"');
            }
        }

        // Validate follow_up_actions if provided
        if (isset($data['follow_up_actions']) && is_array($data['follow_up_actions'])) {
            $validActions = ['create_event', 'create_call', 'create_task'];
            foreach ($data['follow_up_actions'] as $action) {
                if (!in_array($action, $validActions)) {
                    throw new InvalidArgumentException("Invalid follow_up_action: {$action}");
                }
            }
        }

        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Delete a deal phase with migration to another phase
     *
     * @param string $id Phase UUID to delete
     * @param mixed ...$additionalParams Additional parameters (expects newPhaseId as first param)
     * @return array
     */
    public function delete($id, ...$additionalParams): array
    {
        if (empty($additionalParams) || empty($additionalParams[0])) {
            throw new InvalidArgumentException(
                'Deal phase deletion requires a target phase for deal migration. Usage: delete($phaseId, $newPhaseId)'
            );
        }

        return parent::delete($id, $additionalParams[0]);
    }

    /**
     * Prepare additional data for delete operation
     */
    protected function prepareDeleteData($id, ...$additionalParams): array
    {
        if (empty($additionalParams) || empty($additionalParams[0])) {
            throw new InvalidArgumentException(
                'Deal phase deletion requires a target phase for deal migration'
            );
        }

        return ['new_phase_id' => $additionalParams[0]];
    }

    /**
     * Duplicate an existing deal phase
     *
     * @param string $id Source phase UUID
     * @return array
     */
    public function duplicate(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.duplicate', [
            'id' => $id
        ]);
    }

    /**
     * Move a phase to a new position in the pipeline
     *
     * @param string $id Phase UUID to move
     * @param string $afterPhaseId Phase UUID to place this phase after
     * @return array
     */
    public function move(string $id, string $afterPhaseId): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.move', [
            'id' => $id,
            'after_phase_id' => $afterPhaseId
        ]);
    }

    /**
     * Get phases for a specific pipeline
     *
     * @param string $pipelineId Pipeline UUID
     * @return array
     */
    public function forPipeline(string $pipelineId): array
    {
        return $this->list(['deal_pipeline_id' => $pipelineId]);
    }

    /**
     * Get phases by specific IDs
     *
     * @param array $ids Array of phase UUIDs
     * @return array
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters
     * @return array
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // Handle pipeline ID filter
        if (isset($filters['deal_pipeline_id'])) {
            $apiFilters['deal_pipeline_id'] = $filters['deal_pipeline_id'];
        }

        return $apiFilters;
    }

    /**
     * Get available follow-up actions
     *
     * @return array
     */
    public function getAvailableFollowUpActions(): array
    {
        return ['create_event', 'create_call', 'create_task'];
    }

    /**
     * Get available attention after units
     *
     * @return array
     */
    public function getAvailableAttentionAfterUnits(): array
    {
        return ['days', 'weeks'];
    }

    /**
     * Validate phase data
     *
     * @param array $data
     * @param string $operation
     * @return array
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Remove empty values but keep required fields
        $data = array_filter($data, function ($value, $key) {
            if (in_array($key, ['name', 'deal_pipeline_id', 'requires_attention_after', 'id'])) {
                return true; // Keep required fields even if empty for validation
            }
            return $value !== '' && $value !== null && $value !== [];
        }, ARRAY_FILTER_USE_BOTH);

        return $data;
    }

    /**
     * Override getSuggestedIncludes as phases don't have common includes
     *
     * @return array
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Deal phases don't have sideloadable relationships
    }
}
