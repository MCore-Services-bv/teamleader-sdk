<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class EmailTracking extends Resource
{
    use ValidatesWritePayload;

    /** Body fields emailTracking.create accepts */
    public const CREATE_FIELDS = ['subject', 'title', 'content', 'attachments'];

    /** `subject.type` on create and `filter.subject.type` on list */
    public const SUBJECT_TYPES = [
        'contact', 'company', 'deal', 'invoice', 'creditNote', 'subscription', 'product', 'quotation', 'nextgenProject',
    ];

    protected string $description = 'Manage email tracking in Teamleader Focus - track emails sent to various entities';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = false;    // Not supported by API

    protected bool $supportsDeletion = false;  // Not supported by API

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = false;   // Not mentioned in API docs

    protected bool $supportsSideloading = false;

    // Available subject types for email tracking
    protected array $availableSubjectTypes = self::SUBJECT_TYPES;

    // Common filters based on API documentation
    protected array $commonFilters = [
        'subject.id' => 'UUID of the subject entity',
        'subject.type' => 'Type of subject entity (contact, company, deal, etc.)',
    ];

    // Usage examples specific to email tracking
    protected array $usageExamples = [
        'list_for_contact' => [
            'description' => 'Get all email tracking for a specific contact',
            'code' => '$emails = $teamleader->emailTracking()->forSubject("contact", "contact-uuid");',
        ],
        'list_for_company' => [
            'description' => 'Get all email tracking for a specific company',
            'code' => '$emails = $teamleader->emailTracking()->forSubject("company", "company-uuid");',
        ],
        'create_for_contact' => [
            'description' => 'Create email tracking for a contact',
            'code' => '$email = $teamleader->emailTracking()->createForContact("contact-uuid", "Subject", "Email content");',
        ],
        'create_with_attachments' => [
            'description' => 'Create email tracking with attachments',
            'code' => '$email = $teamleader->emailTracking()->create(["subject" => ["type" => "contact", "id" => "uuid"], "title" => "Subject", "content" => "Content", "attachments" => ["file-uuid"]]);',
        ],
    ];

    /**
     * Get email tracking for a specific subject
     *
     * @param  string  $subjectType  Type of subject (contact, company, deal, etc.)
     * @param  string  $subjectId  UUID of the subject
     * @param  array  $options  Additional options (pagination)
     */
    public function forSubject(string $subjectType, string $subjectId, array $options = []): array
    {
        return $this->list([
            'subject' => [
                'type' => $subjectType,
                'id' => $subjectId,
            ],
        ], $options);
    }

    /**
     * List email tracking entries with filtering
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'emailTracking.list does not support: '.implode(', ', $unknown).'. Supported: page_size, page_number.'
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
     * Build the filter object for emailTracking.list
     *
     * The subject is required. Until v2.2.14 a list without one was sent with
     * an empty filter (which the API refuses), the advertised dotted keys
     * `subject.type` / `subject.id` were ignored, and unknown keys were
     * dropped without a word.
     *
     * Accepted forms: ['subject' => ['type' => ..., 'id' => ...]],
     * ['subject.type' => ..., 'subject.id' => ...] and the older
     * ['subject_type' => ..., 'subject_id' => ...].
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'emailTracking.list', ['subject', 'subject_type', 'subject_id']);

        $subject = $filters['subject'] ?? [
            'type' => $filters['subject.type'] ?? $filters['subject_type'] ?? null,
            'id' => $filters['subject.id'] ?? $filters['subject_id'] ?? null,
        ];

        if (! is_array($subject) || empty($subject['type']) || empty($subject['id'])) {
            throw new InvalidArgumentException(
                'emailTracking.list requires a subject: [\'subject\' => [\'type\' => ..., \'id\' => uuid]].'
            );
        }

        $this->assertEnum($subject['type'], self::SUBJECT_TYPES, 'filter.subject.type', 'emailTracking.list');

        return ['subject' => ['type' => $subject['type'], 'id' => $subject['id']]];
    }

    /**
     * Get the base path for the email tracking resource
     */
    protected function getBasePath(): string
    {
        return 'emailTracking';
    }

    /**
     * Create email tracking for a contact
     *
     * @param  string  $contactId  Contact UUID
     * @param  string  $title  Email subject
     * @param  string  $content  Email content
     * @param  array  $attachments  Array of attachment file UUIDs
     */
    public function createForContact(string $contactId, string $title, string $content, array $attachments = []): array
    {
        return $this->create([
            'subject' => [
                'type' => 'contact',
                'id' => $contactId,
            ],
            'title' => $title,
            'content' => $content,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Create new email tracking entry
     *
     * Teamleader files every item as a **received** e-mail, also when it was
     * one you sent; the API has no direction field. Items cannot be updated or
     * deleted through the API afterwards, so try one record before a bulk run.
     * The item is dated at the moment of the request — keep the original date
     * in the content when it matters.
     *
     * @param  array  $data  Email tracking data
     */
    public function create(array $data): array
    {
        // Validate required fields
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate data for email tracking creation
     *
     * @throws InvalidArgumentException
     */
    private function validateCreateData(array $data): void
    {
        $this->rejectUnknownFields($data, self::CREATE_FIELDS, 'emailTracking.create');
        // Check required fields
        if (empty($data['subject'])) {
            throw new InvalidArgumentException('Subject is required for email tracking');
        }

        if (empty($data['subject']['type']) || empty($data['subject']['id'])) {
            throw new InvalidArgumentException('Subject must have both type and id');
        }

        if (empty($data['content'])) {
            throw new InvalidArgumentException('Content is required for email tracking');
        }

        // Validate subject type
        if (! in_array($data['subject']['type'], $this->availableSubjectTypes)) {
            throw new InvalidArgumentException(
                'Invalid subject type. Must be one of: '.implode(', ', $this->availableSubjectTypes)
            );
        }

        // Validate UUID format for subject ID
        if (! $this->isValidUuid($data['subject']['id'])) {
            throw new InvalidArgumentException('Subject ID must be a valid UUID');
        }

        // Validate attachments if provided
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                if (! is_string($attachment) || ! $this->isValidUuid($attachment)) {
                    throw new InvalidArgumentException('All attachment IDs must be valid UUIDs');
                }
            }
        }
    }

    /**
     * Check if a string is a valid UUID
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Create email tracking for a company
     *
     * @param  string  $companyId  Company UUID
     * @param  string  $title  Email subject
     * @param  string  $content  Email content
     * @param  array  $attachments  Array of attachment file UUIDs
     */
    public function createForCompany(string $companyId, string $title, string $content, array $attachments = []): array
    {
        return $this->create([
            'subject' => [
                'type' => 'company',
                'id' => $companyId,
            ],
            'title' => $title,
            'content' => $content,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Create email tracking for a deal
     *
     * @param  string  $dealId  Deal UUID
     * @param  string  $title  Email subject
     * @param  string  $content  Email content
     * @param  array  $attachments  Array of attachment file UUIDs
     */
    public function createForDeal(string $dealId, string $title, string $content, array $attachments = []): array
    {
        return $this->create([
            'subject' => [
                'type' => 'deal',
                'id' => $dealId,
            ],
            'title' => $title,
            'content' => $content,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Get available subject types
     */
    public function getAvailableSubjectTypes(): array
    {
        return $this->availableSubjectTypes;
    }

    /**
     * Override getSuggestedIncludes as email tracking doesn't support includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Email tracking doesn't support sideloading
    }

    /**
     * Get rate limit cost information
     */
    protected function getRateLimitCost(): array
    {
        return [
            'list' => 1,
            'create' => 1,
            'forSubject' => 1,
            'createForContact' => 1,
            'createForCompany' => 1,
            'createForDeal' => 1,
        ];
    }
}
