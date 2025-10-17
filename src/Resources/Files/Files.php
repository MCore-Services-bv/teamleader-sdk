<?php

namespace McoreServices\TeamleaderSDK\Resources\Files;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Files extends Resource
{
    protected string $description = 'Manage files in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;  // via upload

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for files)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'subject' => 'Object containing subject type and id (company, contact, deal, invoice, etc.)',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'updated_at' => 'Sort by file update date',
    ];

    // Usage examples specific to files
    protected array $usageExamples = [
        'list_for_subject' => [
            'description' => 'Get all files for a company',
            'code' => '$files = $teamleader->files()->list([\'subject\' => [\'type\' => \'company\', \'id\' => \'company-uuid\']]);',
        ],
        'upload_file' => [
            'description' => 'Upload a file to a company',
            'code' => '$upload = $teamleader->files()->upload(\'document.pdf\', \'company\', \'company-uuid\', \'Documents\');',
        ],
        'download_file' => [
            'description' => 'Get download link for a file',
            'code' => '$link = $teamleader->files()->download(\'file-uuid\');',
        ],
        'delete_file' => [
            'description' => 'Delete a file',
            'code' => '$teamleader->files()->delete(\'file-uuid\');',
        ],
    ];

    /**
     * Get the base path for the files resource
     */
    protected function getBasePath(): string
    {
        return 'files';
    }

    /**
     * List files with enhanced filtering and sorting
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = $this->buildQueryParams(
            [],
            $filters,
            $options['sort'] ?? null,
            $options['sort_order'] ?? 'desc',
            $options['page_size'] ?? 20,
            $options['page_number'] ?? 1,
            $options['include'] ?? null
        );

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get detailed information about a file
     */
    public function info(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('File ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Request upload link for a file
     *
     * @param  string  $name  File name with extension
     * @param  string  $subjectType  Subject type (company, contact, deal, invoice, creditNote, nextgenProject, ticket)
     * @param  string  $subjectId  Subject UUID
     * @param  string|null  $folder  Optional folder name
     * @return array Upload location and expires_at
     */
    public function upload(string $name, string $subjectType, string $subjectId, ?string $folder = null): array
    {
        if (empty($name)) {
            throw new InvalidArgumentException('File name is required');
        }

        if (empty($subjectType)) {
            throw new InvalidArgumentException('Subject type is required');
        }

        if (empty($subjectId)) {
            throw new InvalidArgumentException('Subject ID is required');
        }

        $validSubjectTypes = ['company', 'contact', 'deal', 'invoice', 'creditNote', 'nextgenProject', 'ticket'];
        if (! in_array($subjectType, $validSubjectTypes)) {
            throw new InvalidArgumentException('Invalid subject type. Must be one of: '.implode(', ', $validSubjectTypes));
        }

        $params = [
            'name' => $name,
            'subject' => [
                'type' => $subjectType,
                'id' => $subjectId,
            ],
        ];

        if ($folder !== null) {
            $params['folder'] = $folder;
        }

        return $this->api->request('POST', $this->getBasePath().'.upload', $params);
    }

    /**
     * Request download link for a file
     *
     * @param  string  $id  File UUID
     * @return array Download location and expires_at
     */
    public function download(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('File ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.download', [
            'id' => $id,
        ]);
    }

    /**
     * Delete a file
     *
     * @param  string  $id  File UUID
     */
    public function delete(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('File ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
        ]);
    }

    /**
     * Helper method to get files for a specific subject
     *
     * @param  string  $subjectType  Subject type (company, contact, deal, etc.)
     * @param  string  $subjectId  Subject UUID
     * @param  array  $options  Additional options
     */
    public function forSubject(string $subjectType, string $subjectId, array $options = []): array
    {
        return $this->list(
            array_merge([
                'subject' => [
                    'type' => $subjectType,
                    'id' => $subjectId,
                ],
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get files for a company
     */
    public function forCompany(string $companyId, array $options = []): array
    {
        return $this->forSubject('company', $companyId, $options);
    }

    /**
     * Get files for a contact
     */
    public function forContact(string $contactId, array $options = []): array
    {
        return $this->forSubject('contact', $contactId, $options);
    }

    /**
     * Get files for a deal
     */
    public function forDeal(string $dealId, array $options = []): array
    {
        return $this->forSubject('deal', $dealId, $options);
    }

    /**
     * Get files for an invoice
     */
    public function forInvoice(string $invoiceId, array $options = []): array
    {
        return $this->forSubject('invoice', $invoiceId, $options);
    }

    /**
     * Get files for a project
     */
    public function forProject(string $projectId, array $options = []): array
    {
        return $this->forSubject('nextgenProject', $projectId, $options);
    }

    /**
     * Get files for a ticket
     */
    public function forTicket(string $ticketId, array $options = []): array
    {
        return $this->forSubject('ticket', $ticketId, $options);
    }
}
