<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Phases extends Resource
{
    use ValidatesWritePayload;

    /** Body fields dealPhases.create accepts. From specification v1.221.0. */
    public const CREATE_FIELDS = ['name', 'deal_pipeline_id', 'estimated_probability', 'follow_up_actions', 'requires_attention_after'];

    /** Body fields dealPhases.update accepts — `deal_pipeline_id` cannot change */
    public const UPDATE_FIELDS = ['id', 'name', 'estimated_probability', 'follow_up_actions', 'requires_attention_after'];

    /** `follow_up_actions[]` on dealPhases.create / dealPhases.update */
    public const FOLLOW_UP_ACTIONS = ['create_event', 'create_call', 'create_task'];

    /** `requires_attention_after.unit` on dealPhases.create / dealPhases.update */
    public const ATTENTION_UNITS = ['days', 'weeks'];

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
            'code' => '$phases = $teamleader->dealPhases()->list();',
        ],
        'list_for_pipeline' => [
            'description' => 'Get phases for specific pipeline',
            'code' => '$phases = $teamleader->dealPhases()->list([\'deal_pipeline_id\' => \'pipeline-uuid\']);',
        ],
        'delete_phase' => [
            'description' => 'Delete a phase, moving its deals to another phase',
            'code' => '$teamleader->dealPhases()->delete(\'phase-uuid\', \'new-phase-uuid\');',
        ],
        'create_phase' => [
            'description' => 'Create a new phase',
            'code' => '$phase = $teamleader->dealPhases()->create([\'name\' => \'New Phase\', \'deal_pipeline_id\' => \'uuid\', \'requires_attention_after\' => [\'amount\' => 7, \'unit\' => \'days\']]);',
        ],
        'duplicate_phase' => [
            'description' => 'Duplicate an existing phase',
            'code' => '$newPhase = $teamleader->dealPhases()->duplicate(\'source-phase-uuid\');',
        ],
        'move_phase' => [
            'description' => 'Move phase to new position',
            'code' => '$teamleader->dealPhases()->move(\'phase-uuid\', \'after-phase-uuid\');',
        ],
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
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->rejectUnsupportedListArguments($filters, $options);

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
     * Create a new deal phase
     *
     * dealPhases.create requires name, deal_pipeline_id and
     * requires_attention_after.
     *
     * @param  array  $data  Phase data
     *
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        $this->rejectUnknownFields($data, self::CREATE_FIELDS, 'dealPhases.create');

        if (empty($data['name'])) {
            throw new InvalidArgumentException('Phase name is required');
        }

        if (empty($data['deal_pipeline_id'])) {
            throw new InvalidArgumentException('Deal pipeline ID is required');
        }

        $this->validatePhaseSettings($data, 'dealPhases.create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update a deal phase
     *
     * dealPhases.update requires requires_attention_after on every call, not
     * only when it changes. Before v2.2.5 the SDK treated it as optional, so an
     * update without it passed client-side and was rejected by the API.
     *
     * @param  string  $id  Phase UUID
     * @param  array  $data  Update data
     *
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;

        $this->rejectUnknownFields($data, self::UPDATE_FIELDS, 'dealPhases.update');
        $this->validatePhaseSettings($data, 'dealPhases.update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Check requires_attention_after (required on create and update) and
     * follow_up_actions against the specification.
     *
     * @throws InvalidArgumentException
     */
    protected function validatePhaseSettings(array $data, string $endpoint): void
    {
        if (empty($data['requires_attention_after'])) {
            throw new InvalidArgumentException(
                "{$endpoint} requires requires_attention_after, e.g. ['amount' => 7, 'unit' => 'days']."
            );
        }

        $attentionAfter = $data['requires_attention_after'];

        if (! is_array($attentionAfter) || ! isset($attentionAfter['amount'], $attentionAfter['unit'])) {
            throw new InvalidArgumentException('requires_attention_after must include amount and unit');
        }

        $this->assertEnum($attentionAfter['unit'], self::ATTENTION_UNITS, 'requires_attention_after.unit', $endpoint);

        if (isset($data['follow_up_actions']) && is_array($data['follow_up_actions'])) {
            foreach ($data['follow_up_actions'] as $action) {
                $this->assertEnum($action, self::FOLLOW_UP_ACTIONS, 'follow_up_actions[]', $endpoint);
            }
        }
    }

    /**
     * Delete a deal phase, moving its deals to another phase
     *
     * `new_phase_id` is optional in the specification. Before v2.2.5 this
     * method required it and then called parent::delete(), which does not
     * exist — so every call was a fatal "Call to undefined method", and
     * dealPhases.delete could not be reached at all. The same defect was fixed
     * on Pipelines in v2.2.2.
     *
     * @param  string  $id  Phase UUID to delete
     * @param  mixed  ...$additionalParams  Optional UUID of the phase that takes over the deals
     */
    public function delete($id, ...$additionalParams): array
    {
        $newPhaseId = $additionalParams[0] ?? null;

        if ($newPhaseId !== null && (! is_string($newPhaseId) || $newPhaseId === '')) {
            throw new InvalidArgumentException('The new phase for the deals must be a phase UUID string.');
        }

        $params = ['id' => $id];

        if ($newPhaseId !== null) {
            $params['new_phase_id'] = $newPhaseId;
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', $params);
    }

    /**
     * Duplicate an existing deal phase
     *
     * @param  string  $id  Source phase UUID
     */
    public function duplicate(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.duplicate', [
            'id' => $id,
        ]);
    }

    /**
     * Move a phase to a new position in the pipeline
     *
     * @param  string  $id  Phase UUID to move
     * @param  string  $afterPhaseId  Phase UUID to place this phase after
     */
    public function move(string $id, string $afterPhaseId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.move', [
            'id' => $id,
            'after_phase_id' => $afterPhaseId,
        ]);
    }

    /**
     * Get phases for a specific pipeline
     *
     * @param  string  $pipelineId  Pipeline UUID
     */
    public function forPipeline(string $pipelineId): array
    {
        return $this->list(['deal_pipeline_id' => $pipelineId]);
    }

    /**
     * Get phases by specific IDs
     *
     * @param  array  $ids  Array of phase UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Build filters array for the API request
     *
     * dealPhases.list accepts `ids` and `deal_pipeline_id`. Before v2.2.5 any
     * other key was dropped here without a word, and a string `ids` was
     * dropped too.
     *
     * @throws InvalidArgumentException When an unsupported filter key is passed
     */
    protected function buildFilters(array $filters): array
    {
        $unknown = array_diff(array_keys($filters), array_keys($this->commonFilters));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key').' for dealPhases.list: '
                .implode(', ', $unknown).'. Supported: '.implode(', ', array_keys($this->commonFilters)).'.'
            );
        }

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? array_values($filters['ids']) : [$filters['ids']];
        }

        if (isset($filters['deal_pipeline_id'])) {
            $apiFilters['deal_pipeline_id'] = $filters['deal_pipeline_id'];
        }

        return $apiFilters;
    }

    /**
     * Get available follow-up actions
     */
    public function getAvailableFollowUpActions(): array
    {
        return self::FOLLOW_UP_ACTIONS;
    }

    /**
     * Get available attention after units
     */
    public function getAvailableAttentionAfterUnits(): array
    {
        return self::ATTENTION_UNITS;
    }

    /**
     * Override getSuggestedIncludes as phases don't have common includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Deal phases don't have sideloadable relationships
    }
}
