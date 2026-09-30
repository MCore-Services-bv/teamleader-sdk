<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use BadMethodCallException;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Notes extends Resource
{
    use ValidatesWritePayload;

    /** Body fields notes.create accepts */
    public const CREATE_FIELDS = ['subject', 'content', 'notify'];

    /** Body fields notes.update accepts, besides `id` — only the content can change */
    public const UPDATE_FIELDS = ['content'];

    /** `subject.type` on notes.create — no legacy `project` */
    public const CREATE_SUBJECT_TYPES = [
        'company', 'contact', 'creditNote', 'deal', 'invoice', 'meeting', 'nextgenProject', 'product', 'quotation', 'subscription',
    ];

    /** `filter.subject.type` on notes.list — includes legacy `project` */
    public const LIST_SUBJECT_TYPES = [
        'company', 'contact', 'creditNote', 'deal', 'invoice', 'meeting', 'nextgenProject', 'product', 'project', 'quotation', 'subscription',
    ];

    protected string $description = 'Manage notes in Teamleader Focus - attach notes to various entities like companies, contacts, deals, etc.';

    // Resource capabilities - Notes support all major operations
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = false; // No sort mentioned in API docs

    protected bool $supportsSideloading = false; // No includes mentioned

    // Available subject types for notes (from API docs)
    /**
     * Every subject type notes can be listed for. Notes can be read on a
     * legacy `project` but not created on one — see CREATE_SUBJECT_TYPES.
     */
    protected array $availableSubjectTypes = self::LIST_SUBJECT_TYPES;

    // Common filters based on API documentation
    protected array $commonFilters = [
        'subject.id' => 'UUID of the subject entity the note belongs to',
        'subject.type' => 'Type of subject entity (company, contact, deal, etc.)',
    ];

    // Usage examples specific to notes
    protected array $usageExamples = [
        'list_for_contact' => [
            'description' => 'Get all notes for a specific contact',
            'code' => '$notes = $teamleader->notes()->list([\'subject\' => [\'type\' => \'contact\', \'id\' => \'contact-uuid\']]);',
        ],
        'list_for_company' => [
            'description' => 'Get all notes for a specific company',
            'code' => '$notes = $teamleader->notes()->list([\'subject\' => [\'type\' => \'company\', \'id\' => \'company-uuid\']]);',
        ],
        'create_contact_note' => [
            'description' => 'Create a note for a contact',
            'code' => '$note = $teamleader->notes()->create([\'subject\' => [\'type\' => \'contact\', \'id\' => \'contact-uuid\'], \'content\' => \'Meeting notes from today\']);',
        ],
        'create_with_notification' => [
            'description' => 'Create a note and notify users',
            'code' => '$note = $teamleader->notes()->create([\'subject\' => [\'type\' => \'deal\', \'id\' => \'deal-uuid\'], \'content\' => \'Important update\', \'notify\' => [[\'type\' => \'user\', \'id\' => \'user-uuid\']]]);',
        ],
        'update_note' => [
            'description' => 'Update an existing note',
            'code' => '$note = $teamleader->notes()->update(\'note-uuid\', [\'content\' => \'Updated note content\']);',
        ],
    ];

    /**
     * Update an existing note
     *
     * @param  mixed  $id  Note ID
     * @param  array  $data  Data to update
     * @return array
     */
    public function update($id, array $data)
    {
        $data['id'] = $id;

        // Validate update data
        $this->validateUpdateData($data);

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Validate data for note update
     *
     * @throws InvalidArgumentException
     */
    private function validateUpdateData(array $data): void
    {
        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], 'notes.update');
        // Required: id
        if (! isset($data['id']) || empty($data['id'])) {
            throw new InvalidArgumentException('Note ID is required for updates');
        }

        // Content is optional for updates, but if provided must not be empty
        if (isset($data['content']) && empty(trim($data['content']))) {
            throw new InvalidArgumentException('Content cannot be empty if provided');
        }
    }

    /**
     * Get the base path for the notes resource
     */
    protected function getBasePath(): string
    {
        return 'notes';
    }

    /**
     * Get notes for a company
     *
     * @param  string  $companyId  Company UUID
     * @param  array  $options  Additional options
     * @return array
     */
    public function forCompany(string $companyId, array $options = [])
    {
        return $this->forSubject('company', $companyId, $options);
    }

    /**
     * Get notes for a specific subject
     *
     * @param  string  $subjectType  Type of subject (company, contact, deal, etc.)
     * @param  string  $subjectId  UUID of the subject
     * @param  array  $options  Additional options (pagination)
     * @return array
     */
    public function forSubject(string $subjectType, string $subjectId, array $options = [])
    {
        $this->validateSubjectType($subjectType);

        return $this->list([
            'subject' => [
                'type' => $subjectType,
                'id' => $subjectId,
            ],
        ], $options);
    }

    /**
     * Validate subject type
     *
     * @throws InvalidArgumentException
     */
    private function validateSubjectType(string $type, array $allowed = self::LIST_SUBJECT_TYPES): void
    {
        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException(
                "Invalid subject type '{$type}'. Available types: ".
                implode(', ', $allowed)
            );
        }
    }

    /**
     * List notes with filtering support
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination)
     * @return array
     */
    public function list(array $filters = [], array $options = [])
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'notes.list does not support: '.implode(', ', $unknown).'. Supported: page_size, page_number.'
            );
        }

        $params = ['filter' => $this->buildFilters($filters)];

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build the filter object for notes.list
     *
     * The subject is required. Until v2.2.14 a subject missing its type or id
     * was dropped without a word, the advertised dotted keys `subject.type` /
     * `subject.id` were ignored, and unknown keys were dropped.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'notes.list', ['subject']);

        $subject = $filters['subject'] ?? [
            'type' => $filters['subject.type'] ?? null,
            'id' => $filters['subject.id'] ?? null,
        ];

        if (! is_array($subject) || empty($subject['type']) || empty($subject['id'])) {
            throw new InvalidArgumentException(
                'notes.list requires a subject: [\'subject\' => [\'type\' => ..., \'id\' => uuid]].'
            );
        }

        $this->validateSubjectType($subject['type']);

        return ['subject' => ['type' => $subject['type'], 'id' => $subject['id']]];
    }

    /**
     * Create a note for a company
     *
     * @param  string  $companyId  Company UUID
     * @param  string  $content  Note content
     * @param  array  $notify  Users to notify
     * @return array
     */
    public function createForCompany(string $companyId, string $content, array $notify = [])
    {
        return $this->createForSubject('company', $companyId, $content, $notify);
    }

    /**
     * Create a note for a specific subject
     *
     * @param  string  $subjectType  Type of subject
     * @param  string  $subjectId  UUID of the subject
     * @param  string  $content  Note content
     * @param  array  $notify  Optional array of users to notify
     * @return array
     */
    public function createForSubject(string $subjectType, string $subjectId, string $content, array $notify = [])
    {
        $this->validateSubjectType($subjectType, self::CREATE_SUBJECT_TYPES);

        $data = [
            'subject' => [
                'type' => $subjectType,
                'id' => $subjectId,
            ],
            'content' => $content,
        ];

        if (! empty($notify)) {
            $data['notify'] = $notify;
        }

        return $this->create($data);
    }

    /**
     * Create a new note
     *
     * @param  array  $data  Note data
     * @return array
     */
    public function create(array $data)
    {
        // Validate required fields
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate data for note creation
     *
     * @throws InvalidArgumentException
     */
    private function validateCreateData(array $data): void
    {
        // Required: subject
        if (! isset($data['subject']) || ! is_array($data['subject'])) {
            throw new InvalidArgumentException('Subject is required and must be an array with type and id');
        }

        if (! isset($data['subject']['type']) || ! isset($data['subject']['id'])) {
            throw new InvalidArgumentException('Subject must contain both type and id');
        }

        $this->rejectUnknownFields($data, self::CREATE_FIELDS, 'notes.create');

        // notes.create does not accept a legacy `project` subject (list does)
        $this->validateSubjectType($data['subject']['type'], self::CREATE_SUBJECT_TYPES);

        // Required: content
        if (! isset($data['content']) || empty(trim($data['content']))) {
            throw new InvalidArgumentException('Content is required and cannot be empty');
        }

        // Optional: validate notify array structure if provided
        if (isset($data['notify'])) {
            if (! is_array($data['notify'])) {
                throw new InvalidArgumentException('Notify must be an array');
            }

            foreach ($data['notify'] as $notification) {
                if (! is_array($notification) ||
                    ! isset($notification['type']) ||
                    ! isset($notification['id'])) {
                    throw new InvalidArgumentException(
                        'Each notification must be an array with type and id'
                    );
                }

                // Currently API only supports user notifications
                if ($notification['type'] !== 'user') {
                    throw new InvalidArgumentException(
                        'Only user notifications are supported'
                    );
                }
            }
        }
    }

    /**
     * Get notes for a contact
     *
     * @param  string  $contactId  Contact UUID
     * @param  array  $options  Additional options
     * @return array
     */
    public function forContact(string $contactId, array $options = [])
    {
        return $this->forSubject('contact', $contactId, $options);
    }

    /**
     * Create a note for a contact
     *
     * @param  string  $contactId  Contact UUID
     * @param  string  $content  Note content
     * @param  array  $notify  Users to notify
     * @return array
     */
    public function createForContact(string $contactId, string $content, array $notify = [])
    {
        return $this->createForSubject('contact', $contactId, $content, $notify);
    }

    /**
     * Get notes for a deal
     *
     * @param  string  $dealId  Deal UUID
     * @param  array  $options  Additional options
     * @return array
     */
    public function forDeal(string $dealId, array $options = [])
    {
        return $this->forSubject('deal', $dealId, $options);
    }

    /**
     * Create a note for a deal
     *
     * @param  string  $dealId  Deal UUID
     * @param  string  $content  Note content
     * @param  array  $notify  Users to notify
     * @return array
     */
    public function createForDeal(string $dealId, string $content, array $notify = [])
    {
        return $this->createForSubject('deal', $dealId, $content, $notify);
    }

    /**
     * Get available subject types
     */
    public function getAvailableSubjectTypes(): array
    {
        return $this->availableSubjectTypes;
    }

    /**
     * Override parent method since Notes use different structure
     */
    public function info($id, $includes = null)
    {
        throw new BadMethodCallException(
            'Notes do not support individual info retrieval. Use list() with subject filters instead.'
        );
    }

    /**
     * Delete a note.
     *
     * @param  string  $id  Note UUID
     * @param  mixed  ...$additionalParams  Unused
     *
     * @throws InvalidArgumentException When the ID is not a valid UUID
     */
    public function delete($id, ...$additionalParams): array
    {
        $this->validateId($id);

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
        ]);
    }

    /**
     * Get suggested includes - Notes don't support includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Notes don't support sideloading
    }
}
