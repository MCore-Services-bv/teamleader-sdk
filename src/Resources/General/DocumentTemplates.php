<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class DocumentTemplates extends Resource
{
    use ValidatesWritePayload;

    /** `filter.document_type` on documentTemplates.list */
    public const DOCUMENT_TYPES = [
        'delivery_note', 'invoice', 'order', 'order_confirmation', 'quotation', 'timetracking_report', 'workorder',
    ];

    /** `filter.status[]` on documentTemplates.list */
    public const STATUSES = ['active', 'archived'];

    protected string $description = 'Manage document templates in Teamleader Focus';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = false;  // Only list endpoint available

    protected bool $supportsUpdate = false;    // Only list endpoint available

    protected bool $supportsDeletion = false;  // Only list endpoint available

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    // documentTemplates.list takes no sort and no includes; both flags
    // defaulted to true until v2.2.14.
    protected bool $supportsSorting = false;

    protected bool $supportsSideloading = false; // No pagination mentioned in API docs

    // Available includes for sideloading
    protected array $availableIncludes = [
        // No specific includes mentioned in API docs for document templates
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'department_id' => 'Department UUID (required)',
        'document_type' => 'Type of document template (required)',
        'status' => 'Filter by template status (active, archived)',
    ];

    // Usage examples specific to document templates
    protected array $usageExamples = [
        'list_by_department_and_type' => [
            'description' => 'Get document templates for a specific department and type',
            'code' => '$templates = $teamleader->documentTemplates()->list([
                \'department_id\' => \'a344c251-2494-0013-b433-ccee8e8435e5\',
                \'document_type\' => \'invoice\'
            ]);',
        ],
        'list_active_templates' => [
            'description' => 'Get only active document templates',
            'code' => '$templates = $teamleader->documentTemplates()->list([
                \'department_id\' => \'a344c251-2494-0013-b433-ccee8e8435e5\',
                \'document_type\' => \'invoice\',
                \'status\' => [\'active\']
            ]);',
        ],
        'by_document_type' => [
            'description' => 'Get templates by document type for a department',
            'code' => '$invoiceTemplates = $teamleader->documentTemplates()->byType(\'department-id\', \'invoice\');',
        ],
        'active_for_department' => [
            'description' => 'Get active templates for a department',
            'code' => '$activeTemplates = $teamleader->documentTemplates()->activeForDepartment(\'department-id\', \'quotation\');',
        ],
    ];

    /**
     * Get active document templates for a specific department and type
     *
     * @param  string  $departmentId  Department UUID
     * @param  string  $documentType  Document type
     */
    public function activeForDepartment(string $departmentId, string $documentType): array
    {
        return $this->byType($departmentId, $documentType, [
            'status' => ['active'],
        ]);
    }

    /**
     * Get document templates by document type for a specific department
     *
     * @param  string  $departmentId  Department UUID
     * @param  string  $documentType  Document type
     * @param  array  $additionalFilters  Additional filters (like status)
     */
    public function byType(string $departmentId, string $documentType, array $additionalFilters = []): array
    {
        $filters = array_merge([
            'department_id' => $departmentId,
            'document_type' => $documentType,
        ], $additionalFilters);

        return $this->list($filters);
    }

    /**
     * List document templates with filtering
     *
     * Note: Both department_id and document_type are REQUIRED parameters
     *
     * @param  array  $filters  Filters to apply (department_id and document_type are required)
     * @param  array  $options  Additional options (not used for this endpoint)
     *
     * @throws InvalidArgumentException When required parameters are missing
     */
    public function list(array $filters = [], array $options = []): array
    {
        if ($options !== []) {
            throw new InvalidArgumentException(
                'documentTemplates.list takes no paging, sorting or includes; got: '.implode(', ', array_keys($options)).'.'
            );
        }

        if (empty($filters['department_id'])) {
            throw new InvalidArgumentException('department_id is required for document templates');
        }

        if (empty($filters['document_type'])) {
            throw new InvalidArgumentException('document_type is required for document templates');
        }

        return $this->api->request('POST', $this->getBasePath().'.list', ['filter' => $this->buildFilters($filters)]);
    }

    /**
     * Build the filter object for documentTemplates.list
     *
     * Until v2.2.14 unknown keys were dropped and neither the document type
     * nor the status was checked.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'documentTemplates.list');
        $this->assertEnum($filters['document_type'] ?? null, self::DOCUMENT_TYPES, 'filter.document_type', 'documentTemplates.list');

        if (isset($filters['status'])) {
            $filters['status'] = is_array($filters['status']) ? array_values($filters['status']) : [$filters['status']];

            foreach ($filters['status'] as $index => $status) {
                $this->assertEnum($status, self::STATUSES, "filter.status[{$index}]", 'documentTemplates.list');
            }
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Get the base path for the document templates resource
     */
    protected function getBasePath(): string
    {
        return 'documentTemplates';
    }

    /**
     * Get archived document templates for a specific department and type
     *
     * @param  string  $departmentId  Department UUID
     * @param  string  $documentType  Document type
     */
    public function archivedForDepartment(string $departmentId, string $documentType): array
    {
        return $this->byType($departmentId, $documentType, [
            'status' => ['archived'],
        ]);
    }

    /**
     * Get all document templates for a department (across all types)
     * Note: This will make multiple API calls since document_type is required
     *
     * @param  string  $departmentId  Department UUID
     * @param  array  $documentTypes  Array of document types to fetch
     * @return array Combined results from all document types
     */
    public function allForDepartment(string $departmentId, ?array $documentTypes = null): array
    {
        if ($documentTypes === null) {
            $documentTypes = $this->getAvailableDocumentTypes();
        }

        $allTemplates = [];

        foreach ($documentTypes as $documentType) {
            try {
                $templates = $this->byType($departmentId, $documentType);

                if (isset($templates['data']) && is_array($templates['data'])) {
                    $allTemplates = array_merge($allTemplates, $templates['data']);
                }
            } catch (Exception $e) {
                // Continue with other types if one fails
                continue;
            }
        }

        return [
            'data' => $allTemplates,
            'meta' => [
                'department_id' => $departmentId,
                'document_types_checked' => $documentTypes,
                'total_templates' => count($allTemplates),
            ],
        ];
    }

    /**
     * Get available document types based on API documentation
     */
    public function getAvailableDocumentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }

    /**
     * Get available status values for filtering
     */
    public function getAvailableStatuses(): array
    {
        return self::STATUSES;
    }

    /**
     * Get document type display names for UI
     */
    public function getDocumentTypeDisplayNames(): array
    {
        return [
            'delivery_note' => 'Delivery Note',
            'invoice' => 'Invoice',
            'order' => 'Order',
            'order_confirmation' => 'Order Confirmation',
            'quotation' => 'Quotation',
            'timetracking_report' => 'Time Tracking Report',
            'workorder' => 'Work Order',
        ];
    }

    /**
     * Override info method since it's not supported for document templates
     */
    public function info($id, $includes = null)
    {
        throw new BadMethodCallException('Document templates do not support the info() method. Use list() with filters instead.');
    }

    /**
     * Override create method since it's not supported
     */
    public function create(array $data)
    {
        throw new BadMethodCallException('Document templates do not support creation via API');
    }

    /**
     * Override update method since it's not supported
     */
    public function update($id, array $data)
    {
        throw new BadMethodCallException('Document templates do not support updates via API');
    }

    /**
     * Override delete method since it's not supported
     */
    public function delete($id, ...$additionalParams): array
    {
        throw new BadMethodCallException('Document templates do not support deletion via API');
    }

    /**
     * Override the default validation since document templates require specific fields
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Document templates are read-only in this API
        return $data;
    }

    /**
     * Override getSuggestedIncludes as document templates don't have common includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Document templates don't have sideloadable relationships in the API
    }
}
