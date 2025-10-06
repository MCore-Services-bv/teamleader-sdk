<?php

namespace McoreServices\TeamleaderSDK\Resources\Templates;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class MailTemplates extends Resource
{
    protected string $description = 'Manage mail templates in Teamleader Focus';

    // Resource capabilities - Mail templates are read-only
    protected bool $supportsCreation = false;
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'department_id' => 'Filter by department UUID (optional)',
        'type' => 'Template type (REQUIRED): invoice, quotation, work_order, credit_note',
    ];

    // Valid template types
    protected array $validTypes = [
        'invoice',
        'quotation',
        'work_order',
        'credit_note',
    ];

    // Usage examples specific to mail templates
    protected array $usageExamples = [
        'list_by_type' => [
            'description' => 'Get all mail templates for invoices',
            'code' => '$templates = $teamleader->mailTemplates()->forType(\'invoice\');'
        ],
        'list_by_type_and_department' => [
            'description' => 'Get mail templates for quotations in a specific department',
            'code' => '$templates = $teamleader->mailTemplates()->forType(\'quotation\', \'department-uuid\');'
        ],
        'find_by_name' => [
            'description' => 'Find a mail template by name',
            'code' => '$template = $teamleader->mailTemplates()->findByName(\'Send link in english\', \'invoice\');'
        ],
        'as_options' => [
            'description' => 'Get mail templates as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->mailTemplates()->asOptions(\'invoice\');'
        ],
    ];

    /**
     * Get the base path for the mail templates resource
     */
    protected function getBasePath(): string
    {
        return 'mailTemplates';
    }

    /**
     * List mail templates with filtering
     *
     * Note: The 'type' parameter is REQUIRED by the API
     *
     * @param array $filters Filters to apply (type is required)
     * @param array $options Additional options (not used for this endpoint)
     * @return array
     * @throws InvalidArgumentException When required type parameter is missing or invalid
     */
    public function list(array $filters = [], array $options = []): array
    {
        // Validate required type parameter
        if (empty($filters['type'])) {
            throw new InvalidArgumentException(
                'type is required for mail templates. Must be one of: ' .
                implode(', ', $this->validTypes)
            );
        }

        // Validate type value
        $this->validateType($filters['type']);

        $params = [];

        // Build filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get mail templates for a specific type
     *
     * @param string $type Template type (invoice, quotation, work_order, credit_note)
     * @param string|null $departmentId Optional department UUID
     * @return array
     * @throws InvalidArgumentException
     */
    public function forType(string $type, ?string $departmentId = null): array
    {
        $this->validateType($type);

        $filters = ['type' => $type];

        if ($departmentId !== null) {
            $filters['department_id'] = $departmentId;
        }

        return $this->list($filters);
    }

    /**
     * Get mail templates for invoices
     *
     * @param string|null $departmentId Optional department UUID
     * @return array
     */
    public function forInvoices(?string $departmentId = null): array
    {
        return $this->forType('invoice', $departmentId);
    }

    /**
     * Get mail templates for quotations
     *
     * @param string|null $departmentId Optional department UUID
     * @return array
     */
    public function forQuotations(?string $departmentId = null): array
    {
        return $this->forType('quotation', $departmentId);
    }

    /**
     * Get mail templates for work orders
     *
     * @param string|null $departmentId Optional department UUID
     * @return array
     */
    public function forWorkOrders(?string $departmentId = null): array
    {
        return $this->forType('work_order', $departmentId);
    }

    /**
     * Get mail templates for credit notes
     *
     * @param string|null $departmentId Optional department UUID
     * @return array
     */
    public function forCreditNotes(?string $departmentId = null): array
    {
        return $this->forType('credit_note', $departmentId);
    }

    /**
     * Find a mail template by name
     *
     * @param string $name Template name to search for
     * @param string $type Template type (required)
     * @param string|null $departmentId Optional department UUID
     * @return array|null Template data or null if not found
     * @throws InvalidArgumentException
     */
    public function findByName(string $name, string $type, ?string $departmentId = null): ?array
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Template name is required');
        }

        $result = $this->forType($type, $departmentId);

        foreach ($result['data'] as $template) {
            if (strcasecmp($template['name'], $name) === 0) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Find a mail template by language
     *
     * @param string $language Language code (e.g., 'en', 'nl', 'fr')
     * @param string $type Template type (required)
     * @param string|null $departmentId Optional department UUID
     * @return array|null Template data or null if not found
     * @throws InvalidArgumentException
     */
    public function findByLanguage(string $language, string $type, ?string $departmentId = null): ?array
    {
        if (empty($language)) {
            throw new InvalidArgumentException('Language code is required');
        }

        $result = $this->forType($type, $departmentId);

        foreach ($result['data'] as $template) {
            if (isset($template['language']) && strcasecmp($template['language'], $language) === 0) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Get all mail templates as key-value pairs for dropdown options
     *
     * @param string $type Template type (required)
     * @param string|null $departmentId Optional department UUID
     * @param string $labelField Field to use as label (default: 'name')
     * @return array Associative array with template IDs as keys and labels as values
     * @throws InvalidArgumentException
     */
    public function asOptions(string $type, ?string $departmentId = null, string $labelField = 'name'): array
    {
        $result = $this->forType($type, $departmentId);
        $options = [];

        foreach ($result['data'] as $template) {
            $options[$template['id']] = $template[$labelField] ?? $template['name'];
        }

        return $options;
    }

    /**
     * Get template names grouped by language
     *
     * @param string $type Template type (required)
     * @param string|null $departmentId Optional department UUID
     * @return array Templates grouped by language code
     * @throws InvalidArgumentException
     */
    public function groupedByLanguage(string $type, ?string $departmentId = null): array
    {
        $result = $this->forType($type, $departmentId);
        $grouped = [];

        foreach ($result['data'] as $template) {
            $language = $template['language'] ?? 'unknown';
            if (!isset($grouped[$language])) {
                $grouped[$language] = [];
            }
            $grouped[$language][] = $template;
        }

        return $grouped;
    }

    /**
     * Validate template type
     *
     * @param string $type
     * @throws InvalidArgumentException
     */
    protected function validateType(string $type): void
    {
        if (!in_array($type, $this->validTypes)) {
            throw new InvalidArgumentException(
                "Invalid template type: {$type}. Must be one of: " .
                implode(', ', $this->validTypes)
            );
        }
    }

    /**
     * Build filters for the API request
     *
     * @param array $filters
     * @return array
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle department_id (optional)
        if (isset($filters['department_id'])) {
            $apiFilters['department_id'] = $filters['department_id'];
        }

        // Handle type (required)
        if (isset($filters['type'])) {
            $apiFilters['type'] = $filters['type'];
        }

        return $apiFilters;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of mail templates',
                'fields' => [
                    'data' => 'Array of mail template objects',
                    'data[].id' => 'Template UUID',
                    'data[].department' => 'Department reference object (nullable)',
                    'data[].department.id' => 'Department UUID',
                    'data[].type' => 'Template type (invoice, quotation, work_order, credit_note)',
                    'data[].name' => 'Template name (e.g., "Send link in english")',
                    'data[].content' => 'Template content object',
                    'data[].content.subject' => 'Email subject',
                    'data[].content.body' => 'Email body with placeholders',
                    'data[].language' => 'Language code (e.g., "en")',
                ],
                'notes' => [
                    'The type parameter is required for listing mail templates',
                    'Templates contain placeholders like #LINK for dynamic content',
                    'Department can be null for system-wide templates',
                ]
            ]
        ];
    }
}
