<?php

namespace McoreServices\TeamleaderSDK\Resources\Files;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\DownloadsDocuments;

class Files extends Resource
{
    use DownloadsDocuments;

    /** `sort[].order` on files.list — the API sorts newest first only */
    public const SORT_ORDERS = ['desc'];

    /**
     * The file types Teamleader knows, by extension: the `mime_type` values
     * files.info and files.list document. uploadFile() refuses other
     * extensions before an upload link is requested.
     */
    public const MIME_TYPES = [
        'doc' => 'application/msword',
        'pdf' => 'application/pdf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xml' => 'application/xml',
        'zip' => 'application/zip',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'html' => 'text/html',
        'htm' => 'text/html',
        'js' => 'text/javascript',
        'txt' => 'text/plain',
        '3gp' => 'video/3gpp',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
    ];

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

    // Filters files.list accepts. subject or ids is required (spec 1.223.0)
    protected array $commonFilters = [
        'subject' => 'Object containing subject type and id — see $listSubjectTypes for accepted types. Required unless ids is given',
        'ids' => 'Array of file UUIDs. Required unless subject is given; files the user has no access to are left out',
        'term' => 'Search the file name. Accents and special characters are transliterated, so cafe also matches Café.pdf',
    ];

    // Available sort fields — updated_at is the only field the API accepts
    protected array $availableSortFields = [
        'updated_at' => 'Sort by file update date',
    ];

    /**
     * Subject types accepted by files.list.
     *
     * Verified against @teamleader/focus-api-specification v1.197.0.
     *
     * This list deliberately differs from $uploadSubjectTypes: `meeting`,
     * `product` and `project` are valid for listing only, and `temporary` is
     * valid for uploading only — a temporary file has no subject to filter on.
     *
     * Both `project` and `nextgenProject` are accepted here. `project` is the
     * legacy project system and `nextgenProject` is the current one; see the
     * Projects and LegacyProjects resources, or call
     * Teamleader::accounts()->getProjectsVersion() to find out which system an
     * account is on.
     *
     * @see https://developer.focus.teamleader.eu/docs/api/files-list
     */
    protected array $listSubjectTypes = [
        'company',
        'contact',
        'creditNote',
        'deal',
        'invoice',
        'meeting',
        'nextgenProject',
        'product',
        'project',
        'ticket',
    ];

    /**
     * Subject types accepted by files.upload.
     *
     * `temporary` requires no subject id; every other type does.
     *
     * @see https://developer.focus.teamleader.eu/docs/api/files-upload
     */
    protected array $uploadSubjectTypes = [
        'company',
        'contact',
        'creditNote',
        'deal',
        'invoice',
        'nextgenProject',
        'temporary',
        'ticket',
    ];

    // Usage examples specific to files
    protected array $usageExamples = [
        'list_for_subject' => [
            'description' => 'Get all files for a company',
            'code' => '$files = $teamleader->files()->list([\'subject\' => [\'type\' => \'company\', \'id\' => \'company-uuid\']]);',
        ],
        'search_for_subject' => [
            'description' => 'Search the files of a deal by file name',
            'code' => '$files = $teamleader->files()->forDeal(\'deal-uuid\', [\'filters\' => [\'term\' => \'offerte\']]);',
        ],
        'list_by_ids' => [
            'description' => 'Get files by id, without naming their subject',
            'code' => '$files = $teamleader->files()->byIds([\'file-uuid-1\', \'file-uuid-2\']);',
        ],
        'list_for_product' => [
            'description' => 'Get all files attached to a product (technical sheets, EPB documentation)',
            'code' => '$files = $teamleader->files()->forProduct(\'product-uuid\');',
        ],
        'upload_file' => [
            'description' => 'Upload a file to a company',
            'code' => '$upload = $teamleader->files()->upload(\'document.pdf\', \'company\', \'company-uuid\', \'Documents\');',
        ],
        'upload_temporary' => [
            'description' => 'Upload a temporary file (no subject id required)',
            'code' => '$upload = $teamleader->files()->upload(\'attachment.pdf\', \'temporary\');',
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
     * Validate a subject type against the types the given operation accepts.
     *
     * files.list and files.upload accept different sets — see $listSubjectTypes
     * and $uploadSubjectTypes.
     *
     * @param  string  $type  The subject type to validate
     * @param  string  $operation  Either 'list' or 'upload'
     *
     * @throws InvalidArgumentException When the type is not valid for the operation
     */
    protected function validateSubjectType(string $type, string $operation = 'list'): void
    {
        $validTypes = $operation === 'upload'
            ? $this->uploadSubjectTypes
            : $this->listSubjectTypes;

        if (! in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException(
                "Invalid subject type: {$type}. files.{$operation} accepts: "
                .implode(', ', $validTypes).'.'
            );
        }
    }

    /**
     * Build query parameters for Files API requests.
     *
     * files.list needs a 'filter' object with `subject` (type and id) or
     * `ids`, or both. `term` narrows either one down by file name; on its own
     * it is not enough.
     *
     * @param  array  $baseParams  Base parameters
     * @param  array  $filters  subject, ids and/or term — subject or ids is required
     * @param  string|null  $sort  Sorting field (only 'updated_at' is accepted)
     * @param  string  $sortOrder  Sort order
     * @param  int  $pageSize  Page size
     * @param  int  $pageNumber  Page number
     * @param  mixed  $includes  Ignored — files do not support sideloading
     * @return array Complete parameters array
     *
     * @throws InvalidArgumentException When neither subject nor ids is given, or a filter is invalid
     */
    protected function buildQueryParams(
        array $baseParams = [],
        array $filters = [],
        $sort = null,
        string $sortOrder = 'asc',
        int $pageSize = 20,
        int $pageNumber = 1,
        $includes = null
    ): array {
        $params = $baseParams;

        // Anything else was dropped without a word until v2.2.17.
        $unknown = array_diff(array_keys($filters), array_keys($this->commonFilters));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key').' for files.list: '
                .implode(', ', $unknown).'. Supported: '.implode(', ', array_keys($this->commonFilters)).'.'
            );
        }

        // subject or ids is required by the API — fail here rather than
        // sending a request that can only come back as a 400.
        if (! isset($filters['subject']) && ! isset($filters['ids'])) {
            throw new InvalidArgumentException(
                'The subject filter is required for files.list, unless ids is given. Pass '
                ."['subject' => ['type' => ..., 'id' => ...]] or ['ids' => [...]], or use one of the "
                .'forCompany(), forDeal(), forProduct(), byIds() helpers.'
            );
        }

        $filter = [];

        if (isset($filters['subject'])) {
            $subject = $filters['subject'];

            if (! is_array($subject) || ! isset($subject['type']) || ! isset($subject['id'])) {
                throw new InvalidArgumentException(
                    'subject filter must contain both type and id'
                );
            }

            $this->validateSubjectType($subject['type'], 'list');

            $filter['subject'] = [
                'type' => $subject['type'],
                'id' => $subject['id'],
            ];
        }

        if (isset($filters['ids'])) {
            $ids = is_array($filters['ids']) ? array_values($filters['ids']) : [$filters['ids']];

            if ($ids === []) {
                throw new InvalidArgumentException(
                    'The ids filter for files.list needs at least one file id.'
                );
            }

            foreach ($ids as $id) {
                if (! is_string($id) || $id === '') {
                    throw new InvalidArgumentException(
                        'The ids filter for files.list takes file ids as non-empty strings.'
                    );
                }
            }

            $filter['ids'] = $ids;
        }

        if (isset($filters['term']) && $filters['term'] !== '') {
            // The CLI reads term=2026 as an integer; the API wants a string.
            if (! is_string($filters['term']) && ! is_int($filters['term']) && ! is_float($filters['term'])) {
                throw new InvalidArgumentException(
                    'The term filter for files.list must be a string.'
                );
            }

            $filter['term'] = (string) $filters['term'];
        }

        $params['filter'] = $filter;

        // Build sort object.
        //
        // The API expects an array of objects — [['field' => ..., 'order' => ...]].
        // A string array such as ['-updated_at'] is silently ignored, so results
        // come back in the API's own order with no error.
        if ($sort !== null) {
            // normaliseSort() accepts a field name, ['field' => ..., 'order' => ...]
            // or a list of those; an array sort was a TypeError until v2.2.17.
            $params['sort'] = $this->normaliseSort($sort, $sortOrder);

            foreach ($params['sort'] as $entry) {
                if (! in_array($entry['order'], self::SORT_ORDERS, true)) {
                    throw new InvalidArgumentException(
                        "Invalid sort order for files.list: {$entry['order']}. The API sorts updated_at in descending order only."
                    );
                }
            }
        }

        if ($this->supportsPagination) {
            $params['page'] = [
                'size' => $pageSize,
                'number' => $pageNumber,
            ];
        }

        return $params;
    }

    /**
     * List files for a subject, or by id.
     *
     * subject or ids is required; term searches the file name within either
     * — see buildQueryParams().
     *
     * @param  array  $filters  'subject' => ['type' => ..., 'id' => ...] and/or 'ids' => [...]; optionally 'term'
     * @param  array  $options  sort, sort_order, page_size, page_number
     *
     * @throws InvalidArgumentException When neither subject nor ids is given, or a filter is invalid
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'sort', 'sort_order', 'filters']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'files.list does not support: '.implode(', ', $unknown).'. Supported: page_size, page_number, sort, sort_order.'
            );
        }
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
     * Note that files.upload accepts a narrower set of subject types than
     * files.list — see $uploadSubjectTypes.
     *
     * This only returns a temporary link; the contents still have to be POSTed
     * to it. To upload a local file in one call, use uploadFile().
     *
     * @param  string  $name  File name with extension
     * @param  string  $subjectType  Subject type — see $uploadSubjectTypes
     * @param  string|null  $subjectId  Subject UUID — not required when subjectType is 'temporary'
     * @param  string|null  $folder  Optional folder name (defaults to General in account language)
     * @return array Upload location and expires_at
     *
     * @throws InvalidArgumentException When the name, subject type or subject id is missing or invalid
     */
    public function upload(string $name, string $subjectType, ?string $subjectId = null, ?string $folder = null): array
    {
        if (empty($name)) {
            throw new InvalidArgumentException('File name is required');
        }

        if (empty($subjectType)) {
            throw new InvalidArgumentException('Subject type is required');
        }

        $this->validateSubjectType($subjectType, 'upload');

        // subject.id is required for all types except temporary
        if ($subjectType !== 'temporary' && empty($subjectId)) {
            throw new InvalidArgumentException('Subject ID is required for subject type: '.$subjectType);
        }

        $params = [
            'name' => $name,
            'subject' => [
                'type' => $subjectType,
            ],
        ];

        if (! empty($subjectId)) {
            $params['subject']['id'] = $subjectId;
        }

        if ($folder !== null) {
            $params['folder'] = $folder;
        }

        return $this->api->request('POST', $this->getBasePath().'.upload', $params);
    }

    /**
     * Upload a local file in one call: request the link, send the contents.
     *
     * upload() only returns a temporary link, which expires, so a bulk upload
     * needs both steps per row. This does both, and works with bulk():
     *
     *     Teamleader::bulk()->call('files', 'uploadFile', [
     *         'lead-12' => [storage_path('docs/offerte.pdf'), 'deal', $dealId, 'Offertes'],
     *     ])->run();
     *
     * The file is sent as raw bytes (`application/octet-stream`). Its name in
     * Teamleader is $name, or the file's own name, and its extension must be
     * one of MIME_TYPES — anything else is refused before the link is
     * requested. A failed upload throws, whatever throw_exceptions says, so a
     * bulk run records it as a failed row.
     *
     * Queued bulk: the path is read by the worker, so it must exist there.
     *
     * @param  string  $path  Path to the local file
     * @param  string  $subjectType  Subject type — see $uploadSubjectTypes
     * @param  string|null  $subjectId  Subject UUID — not required when subjectType is 'temporary'
     * @param  string|null  $folder  Folder in Teamleader (defaults to General in the account's language)
     * @param  string|null  $name  Name in Teamleader, with extension; defaults to the file's own name
     * @param  bool  $checkType  False to skip the MIME_TYPES check
     * @return array The upload response; `data.id` is the new file where Teamleader returns it
     *
     * @throws InvalidArgumentException When the file cannot be read, or its type is not accepted
     * @throws TeamleaderException When Teamleader returns no link or refuses the contents
     */
    public function uploadFile(
        string $path,
        string $subjectType,
        ?string $subjectId = null,
        ?string $folder = null,
        ?string $name = null,
        bool $checkType = true,
    ): array {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File not found or not readable: {$path}");
        }

        $name ??= basename($path);

        if ($checkType) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (! isset(self::MIME_TYPES[$extension])) {
                throw new InvalidArgumentException(
                    "Teamleader does not accept '".($extension === '' ? $name : '.'.$extension)."' files. Accepted: "
                    .implode(', ', array_keys(self::MIME_TYPES)).'. Pass $checkType = false to send it anyway.'
                );
            }
        }

        // Sent the way uploads were proven to work in production: the
        // bytes as a string body, application/octet-stream, no token. Read
        // before the link is requested, so an unreadable file costs nothing.
        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            throw new InvalidArgumentException("File could not be read, or is empty: {$path}");
        }

        $link = $this->upload($name, $subjectType, $subjectId, $folder);
        $location = $link['data']['location'] ?? null;

        if (! is_string($location) || $location === '') {
            throw new TeamleaderException(
                'files.upload returned no upload link'.(isset($link['message']) ? ': '.$link['message'] : '.')
            );
        }

        return $this->api->sendFileContents($location, $contents) + ['data' => []];
    }

    /**
     * Request download link for a file
     *
     * Returns a temporary link, which expires. For the document itself use
     * downloadContents(), or downloadTo() to store it on a Laravel disk.
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
     * @param  string  $subjectType  Subject type — see $listSubjectTypes
     * @param  string  $subjectId  Subject UUID
     * @param  array  $options  Additional options (sort, sort_order, page_size, page_number, filters)
     *                          — filters takes term to search the file name:
     *                          ['filters' => ['term' => 'offerte']]
     *
     * @throws InvalidArgumentException When the subject type is not valid for files.list
     */
    public function forSubject(string $subjectType, string $subjectId, array $options = []): array
    {
        $this->validateSubjectType($subjectType, 'list');

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
     * Get files by id
     *
     * Uses the `ids` filter, added to files.list in specification 1.223.0:
     * the one way to list files without naming their subject. Files the user
     * has no access to are left out of the result rather than reported.
     *
     * @param  array<int, string>  $ids  File UUIDs
     * @param  array  $options  sort, sort_order, page_size, page_number, filters (e.g. term)
     *
     * @throws InvalidArgumentException When $ids is empty or holds anything but non-empty strings
     */
    public function byIds(array $ids, array $options = []): array
    {
        return $this->list(
            array_merge(['ids' => $ids], $options['filters'] ?? []),
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
     * Get files for a credit note
     */
    public function forCreditNote(string $creditNoteId, array $options = []): array
    {
        return $this->forSubject('creditNote', $creditNoteId, $options);
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
     * Get files for a meeting
     *
     * Listing only — files cannot be uploaded to a meeting through the API.
     */
    public function forMeeting(string $meetingId, array $options = []): array
    {
        return $this->forSubject('meeting', $meetingId, $options);
    }

    /**
     * Get files for a product
     *
     * Products carry technical attachments — specification sheets, EPB
     * documentation, installation instructions — which an integration mirroring
     * the product catalogue needs to enumerate alongside the product record.
     *
     * Listing only — files cannot be uploaded to a product through the API.
     */
    public function forProduct(string $productId, array $options = []): array
    {
        return $this->forSubject('product', $productId, $options);
    }

    /**
     * Get files for a project on the current (nextgen) project system
     *
     * This is the counterpart to Teamleader::projects(). For accounts still on
     * the old project system use forLegacyProject() instead.
     */
    public function forProject(string $projectId, array $options = []): array
    {
        return $this->forSubject('nextgenProject', $projectId, $options);
    }

    /**
     * Get files for a project on the legacy project system
     *
     * This is the counterpart to Teamleader::legacyProjects(). For accounts on
     * the current project system use forProject() instead.
     *
     * Listing only — files cannot be uploaded to a legacy project through the API.
     */
    public function forLegacyProject(string $projectId, array $options = []): array
    {
        return $this->forSubject('project', $projectId, $options);
    }

    /**
     * Get files for a ticket
     */
    public function forTicket(string $ticketId, array $options = []): array
    {
        return $this->forSubject('ticket', $ticketId, $options);
    }
}
