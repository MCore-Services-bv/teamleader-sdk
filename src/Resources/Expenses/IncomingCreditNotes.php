<?php

namespace McoreServices\TeamleaderSDK\Resources\Expenses;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class IncomingCreditNotes extends Resource
{
    protected string $description = 'Manage incoming credit notes from suppliers in Teamleader Focus';

    // Resource capabilities - Incoming credit notes support create, update, delete, and info
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters - Not supported for this resource
    protected array $commonFilters = [];

    // Valid currency codes
    protected array $validCurrencyCodes = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK',
        'EUR', 'GBP', 'INR', 'ISK', 'JPY', 'MAD', 'MXN', 'NOK',
        'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR'
    ];

    // Valid review statuses
    protected array $validReviewStatuses = [
        'pending',
        'approved',
        'refused',
    ];

    // Usage examples specific to incoming credit notes
    protected array $usageExamples = [
        'create_basic' => [
            'description' => 'Create a basic incoming credit note',
            'code' => '$creditNote = $teamleader->incomingCreditNotes()->add([\'title\' => \'Credit Note\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 500]]]);'
        ],
        'create_complete' => [
            'description' => 'Create a complete incoming credit note with all details',
            'code' => '$creditNote = $teamleader->incomingCreditNotes()->add([\'title\' => \'Return Credit\', \'supplier_id\' => \'uuid\', \'document_number\' => \'CN-001\', \'invoice_date\' => \'2024-01-15\', \'due_date\' => \'2024-02-15\', \'currency\' => [\'code\' => \'EUR\'], \'total\' => [\'tax_exclusive\' => [\'amount\' => 750]]]);'
        ],
        'get_info' => [
            'description' => 'Get credit note details',
            'code' => '$creditNote = $teamleader->incomingCreditNotes()->info(\'creditnote-uuid\');'
        ],
        'update_creditnote' => [
            'description' => 'Update an existing credit note',
            'code' => '$teamleader->incomingCreditNotes()->update(\'creditnote-uuid\', [\'title\' => \'Updated Title\', \'due_date\' => \'2024-03-15\']);'
        ],
        'approve_creditnote' => [
            'description' => 'Approve a credit note',
            'code' => '$teamleader->incomingCreditNotes()->approve(\'creditnote-uuid\');'
        ],
        'refuse_creditnote' => [
            'description' => 'Refuse a credit note',
            'code' => '$teamleader->incomingCreditNotes()->refuse(\'creditnote-uuid\');'
        ],
        'send_to_bookkeeping' => [
            'description' => 'Send credit note to bookkeeping',
            'code' => '$teamleader->incomingCreditNotes()->sendToBookkeeping(\'creditnote-uuid\');'
        ],
        'delete_creditnote' => [
            'description' => 'Delete a credit note',
            'code' => '$teamleader->incomingCreditNotes()->delete(\'creditnote-uuid\');'
        ]
    ];

    /**
     * Get the base path for the incoming credit notes resource
     */
    protected function getBasePath(): string
    {
        return 'incomingCreditNotes';
    }

    /**
     * Create a new incoming credit note
     *
     * @param array $data Credit note data
     * @return array Created credit note response
     * @throws InvalidArgumentException When required fields are missing
     */
    public function add(array $data): array
    {
        // Validate required fields
        if (empty($data['title'])) {
            throw new InvalidArgumentException('title is required for incoming credit notes');
        }

        if (empty($data['currency']['code'])) {
            throw new InvalidArgumentException('currency.code is required for incoming credit notes');
        }

        if (empty($data['total'])) {
            throw new InvalidArgumentException('total is required for incoming credit notes');
        }

        // Validate that either tax_exclusive or tax_inclusive is provided
        if (empty($data['total']['tax_exclusive']) && empty($data['total']['tax_inclusive'])) {
            throw new InvalidArgumentException('Either total.tax_exclusive or total.tax_inclusive is required');
        }

        // Validate currency code
        if (!in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: ' . implode(', ', $this->validCurrencyCodes)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.add', $data);
    }

    /**
     * Alias for add() method to maintain consistency with other resources
     *
     * @param array $data Credit note data
     * @return array
     */
    public function create(array $data): array
    {
        return $this->add($data);
    }

    /**
     * Update an existing incoming credit note
     *
     * @param string $id Credit note UUID
     * @param array $data Data to update
     * @return array Update response
     * @throws InvalidArgumentException When ID is empty
     */
    public function update(string $id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        // Validate currency code if provided
        if (isset($data['currency']['code']) && !in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: ' . implode(', ', $this->validCurrencyCodes)
            );
        }

        $params = array_merge(['id' => $id], $data);

        return $this->api->request('POST', $this->getBasePath() . '.update', $params);
    }

    /**
     * Get information about a specific incoming credit note
     *
     * @param string $id Credit note UUID
     * @param mixed $includes Not used for incoming credit notes
     * @return array Credit note information
     * @throws InvalidArgumentException When ID is empty
     */
    public function info(string $id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.info', ['id' => $id]);
    }

    /**
     * Delete an incoming credit note
     *
     * @param string $id Credit note UUID
     * @return array Delete response
     * @throws InvalidArgumentException When ID is empty
     */
    public function delete(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Approve an incoming credit note
     *
     * @param string $id Credit note UUID
     * @return array Approval response
     * @throws InvalidArgumentException When ID is empty
     */
    public function approve(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.approve', ['id' => $id]);
    }

    /**
     * Refuse an incoming credit note
     *
     * @param string $id Credit note UUID
     * @return array Refusal response
     * @throws InvalidArgumentException When ID is empty
     */
    public function refuse(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.refuse', ['id' => $id]);
    }

    /**
     * Mark an incoming credit note as pending review
     *
     * @param string $id Credit note UUID
     * @return array Response
     * @throws InvalidArgumentException When ID is empty
     */
    public function markAsPendingReview(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.markAsPendingReview', ['id' => $id]);
    }

    /**
     * Send an incoming credit note to bookkeeping
     *
     * @param string $id Credit note UUID
     * @return array Response
     * @throws InvalidArgumentException When ID is empty
     */
    public function sendToBookkeeping(string $id): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Credit note ID is required');
        }

        return $this->api->request('POST', $this->getBasePath() . '.sendToBookkeeping', ['id' => $id]);
    }

    /**
     * List method is not supported for incoming credit notes
     *
     * @param array $filters
     * @param array $options
     * @return array
     * @throws InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            'The list method is not supported for incoming credit notes. Use info() to get a specific credit note.'
        );
    }

    /**
     * Get valid currency codes for incoming credit notes
     *
     * @return array Array of valid currency codes
     */
    public function getValidCurrencyCodes(): array
    {
        return $this->validCurrencyCodes;
    }

    /**
     * Get valid review statuses for incoming credit notes
     *
     * @return array Array of valid review statuses
     */
    public function getValidReviewStatuses(): array
    {
        return $this->validReviewStatuses;
    }

    /**
     * Validate credit note data before creating or updating
     *
     * @param array $data Credit note data to validate
     * @param bool $isUpdate Whether this is for an update operation
     * @return array Validated data
     */
    protected function validateCreditNoteData(array $data, bool $isUpdate = false): array
    {
        // For updates, required fields are not mandatory
        if (!$isUpdate) {
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required');
            }

            if (empty($data['currency']['code'])) {
                throw new InvalidArgumentException('currency.code is required');
            }

            if (empty($data['total'])) {
                throw new InvalidArgumentException('total is required');
            }

            if (empty($data['total']['tax_exclusive']) && empty($data['total']['tax_inclusive'])) {
                throw new InvalidArgumentException('Either total.tax_exclusive or total.tax_inclusive is required');
            }
        }

        // Validate currency code if provided
        if (isset($data['currency']['code']) && !in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: ' . implode(', ', $this->validCurrencyCodes)
            );
        }

        return $data;
    }
}
