<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class BookkeepingSubmissions extends Resource
{
    protected string $description = 'Manage bookkeeping submissions for expense documents in Teamleader Focus';

    // Resource capabilities
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
        'subject' => 'REQUIRED: Filter by financial document (id and type)',
    ];

    // Usage examples specific to bookkeeping submissions
    protected array $usageExamples = [
        'list_for_document' => [
            'description' => 'Get all bookkeeping submissions for a specific document',
            'code' => '$submissions = $teamleader->bookkeepingSubmissions()->forDocument("document-uuid", "incoming_invoice");'
        ],
        'list_for_invoice' => [
            'description' => 'Get submissions for an incoming invoice',
            'code' => '$submissions = $teamleader->bookkeepingSubmissions()->forInvoice("invoice-uuid");'
        ],
        'list_for_credit_note' => [
            'description' => 'Get submissions for an incoming credit note',
            'code' => '$submissions = $teamleader->bookkeepingSubmissions()->forCreditNote("credit-note-uuid");'
        ],
        'list_for_receipt' => [
            'description' => 'Get submissions for a receipt',
            'code' => '$submissions = $teamleader->bookkeepingSubmissions()->forReceipt("receipt-uuid");'
        ]
    ];

    /**
     * Get the base path for the bookkeeping submissions resource
     */
    protected function getBasePath(): string
    {
        return 'bookkeepingSubmissions';
    }

    /**
     * List bookkeeping submissions for a specific financial document
     *
     * Note: The subject filter (document id and type) is REQUIRED by the API
     *
     * @param array $filters Filters to apply (must include subject with id and type)
     * @param array $options Additional options (not used for this endpoint)
     * @return array
     * @throws InvalidArgumentException When subject filter is missing or invalid
     */
    public function list(array $filters = [], array $options = []): array
    {
        // Validate that subject filter is provided
        if (empty($filters['subject'])) {
            throw new InvalidArgumentException(
                'The subject filter is required for bookkeeping submissions. ' .
                'It must include both "id" and "type" fields. ' .
                'Use forDocument(), forInvoice(), forCreditNote(), or forReceipt() methods instead.'
            );
        }

        if (empty($filters['subject']['id'])) {
            throw new InvalidArgumentException('The subject.id (document UUID) is required');
        }

        if (empty($filters['subject']['type'])) {
            throw new InvalidArgumentException('The subject.type is required');
        }

        // Validate subject type
        $validTypes = ['incoming_invoice', 'incoming_credit_note', 'receipt'];
        if (!in_array($filters['subject']['type'], $validTypes)) {
            throw new InvalidArgumentException(
                'Invalid subject.type. Must be one of: ' . implode(', ', $validTypes)
            );
        }

        $params = [];

        // Build filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get bookkeeping submissions for a specific financial document
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document: incoming_invoice, incoming_credit_note, or receipt
     * @return array
     * @throws InvalidArgumentException When document type is invalid
     */
    public function forDocument(string $documentId, string $documentType): array
    {
        $validTypes = ['incoming_invoice', 'incoming_credit_note', 'receipt'];

        if (!in_array($documentType, $validTypes)) {
            throw new InvalidArgumentException(
                "Invalid document type '{$documentType}'. Must be one of: " . implode(', ', $validTypes)
            );
        }

        return $this->list([
            'subject' => [
                'id' => $documentId,
                'type' => $documentType
            ]
        ]);
    }

    /**
     * Get bookkeeping submissions for an incoming invoice
     *
     * @param string $invoiceId UUID of the incoming invoice
     * @return array
     */
    public function forInvoice(string $invoiceId): array
    {
        return $this->forDocument($invoiceId, 'incoming_invoice');
    }

    /**
     * Get bookkeeping submissions for an incoming credit note
     *
     * @param string $creditNoteId UUID of the incoming credit note
     * @return array
     */
    public function forCreditNote(string $creditNoteId): array
    {
        return $this->forDocument($creditNoteId, 'incoming_credit_note');
    }

    /**
     * Get bookkeeping submissions for a receipt
     *
     * @param string $receiptId UUID of the receipt
     * @return array
     */
    public function forReceipt(string $receiptId): array
    {
        return $this->forDocument($receiptId, 'receipt');
    }

    /**
     * Filter submissions by status
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @param string $status Status to filter by: sending, confirmed, or failed
     * @return array
     * @throws InvalidArgumentException When status is invalid
     */
    public function byStatus(string $documentId, string $documentType, string $status): array
    {
        $validStatuses = ['sending', 'confirmed', 'failed'];

        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException(
                "Invalid status '{$status}'. Must be one of: " . implode(', ', $validStatuses)
            );
        }

        $result = $this->forDocument($documentId, $documentType);

        // Filter client-side since API doesn't support status filtering
        if (isset($result['data']) && is_array($result['data'])) {
            $result['data'] = array_filter($result['data'], function($submission) use ($status) {
                return isset($submission['status']) && $submission['status'] === $status;
            });
            $result['data'] = array_values($result['data']); // Re-index array
        }

        return $result;
    }

    /**
     * Get confirmed submissions for a document
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @return array
     */
    public function confirmed(string $documentId, string $documentType): array
    {
        return $this->byStatus($documentId, $documentType, 'confirmed');
    }

    /**
     * Get failed submissions for a document
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @return array
     */
    public function failed(string $documentId, string $documentType): array
    {
        return $this->byStatus($documentId, $documentType, 'failed');
    }

    /**
     * Get sending submissions for a document
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @return array
     */
    public function sending(string $documentId, string $documentType): array
    {
        return $this->byStatus($documentId, $documentType, 'sending');
    }

    /**
     * Get the latest submission for a document
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @return array|null Returns the latest submission or null if none exist
     */
    public function latest(string $documentId, string $documentType): ?array
    {
        $result = $this->forDocument($documentId, $documentType);

        if (empty($result['data']) || !is_array($result['data'])) {
            return null;
        }

        // Sort by created_at descending (newest first)
        $submissions = $result['data'];
        usort($submissions, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? '1970-01-01');
            $timeB = strtotime($b['created_at'] ?? '1970-01-01');
            return $timeB - $timeA;
        });

        return $submissions[0] ?? null;
    }

    /**
     * Check if a document has any confirmed submissions
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @return bool
     */
    public function hasConfirmed(string $documentId, string $documentType): bool
    {
        $result = $this->confirmed($documentId, $documentType);
        return !empty($result['data']);
    }

    /**
     * Check if a document has any failed submissions
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @return bool
     */
    public function hasFailed(string $documentId, string $documentType): bool
    {
        $result = $this->failed($documentId, $documentType);
        return !empty($result['data']);
    }

    /**
     * Get submission statistics for a document
     *
     * @param string $documentId UUID of the financial document
     * @param string $documentType Type of document
     * @return array Statistics including total, by status, and email addresses
     */
    public function statistics(string $documentId, string $documentType): array
    {
        $result = $this->forDocument($documentId, $documentType);
        $submissions = $result['data'] ?? [];

        $stats = [
            'total' => count($submissions),
            'by_status' => [
                'sending' => 0,
                'confirmed' => 0,
                'failed' => 0
            ],
            'email_addresses' => [],
            'latest_submission' => null,
            'first_submission' => null
        ];

        if (empty($submissions)) {
            return $stats;
        }

        // Collect statistics
        foreach ($submissions as $submission) {
            if (isset($submission['status'])) {
                $stats['by_status'][$submission['status']] =
                    ($stats['by_status'][$submission['status']] ?? 0) + 1;
            }

            if (isset($submission['email_address']) && !in_array($submission['email_address'], $stats['email_addresses'])) {
                $stats['email_addresses'][] = $submission['email_address'];
            }
        }

        // Sort by created_at to get latest and first
        usort($submissions, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? '1970-01-01');
            $timeB = strtotime($b['created_at'] ?? '1970-01-01');
            return $timeB - $timeA;
        });

        $stats['latest_submission'] = $submissions[0] ?? null;
        $stats['first_submission'] = end($submissions) ?: null;

        return $stats;
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

        // Handle subject filter (REQUIRED)
        if (isset($filters['subject']) && is_array($filters['subject'])) {
            $apiFilters['subject'] = [
                'id' => $filters['subject']['id'],
                'type' => $filters['subject']['type']
            ];
        }

        return $apiFilters;
    }

    /**
     * Override info method as individual submission info is not supported
     *
     * @param string $id
     * @param mixed $includes
     * @return array
     * @throws InvalidArgumentException
     */
    public function info($id, $includes = null): array
    {
        throw new InvalidArgumentException(
            'Bookkeeping submissions do not support individual info requests. ' .
            'Use forDocument(), forInvoice(), forCreditNote(), or forReceipt() to get submissions for a specific document.'
        );
    }
}
