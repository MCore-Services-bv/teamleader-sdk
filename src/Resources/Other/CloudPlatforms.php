<?php

namespace McoreServices\TeamleaderSDK\Resources\Other;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class CloudPlatforms extends Resource
{
    protected string $description = 'Fetch cloud platform URLs for invoices, quotations, and tickets';

    // Resource capabilities - CloudPlatforms only supports URL fetching
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = false;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for cloud platforms)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters (none for cloud platforms)
    protected array $commonFilters = [];

    // Valid resource types that support cloud platform URLs
    protected array $supportedTypes = [
        'invoice',
        'quotation',
        'ticket',
    ];

    // Usage examples specific to cloud platforms
    protected array $usageExamples = [
        'get_invoice_url' => [
            'description' => 'Get cloud platform URL for an invoice',
            'code' => '$result = $teamleader->cloudPlatforms()->url("invoice", "invoice-uuid");
$url = $result["data"]["url"];',
        ],
        'get_quotation_url' => [
            'description' => 'Get cloud platform URL for a quotation',
            'code' => '$result = $teamleader->cloudPlatforms()->url("quotation", "quotation-uuid");
$url = $result["data"]["url"];',
        ],
        'get_ticket_url' => [
            'description' => 'Get cloud platform URL for a ticket',
            'code' => '$result = $teamleader->cloudPlatforms()->url("ticket", "ticket-uuid");
$url = $result["data"]["url"];',
        ],
        'redirect_to_invoice' => [
            'description' => 'Redirect user to invoice in cloud platform',
            'code' => '$result = $teamleader->cloudPlatforms()->url("invoice", $invoiceId);
return redirect($result["data"]["url"]);',
        ],
        'get_multiple_urls' => [
            'description' => 'Get cloud platform URLs for multiple resources',
            'code' => '$invoiceIds = ["uuid1", "uuid2", "uuid3"];
$urls = [];

foreach ($invoiceIds as $id) {
    $result = $teamleader->cloudPlatforms()->url("invoice", $id);
    $urls[$id] = $result["data"]["url"];
}',
        ],
    ];

    /**
     * Get the base path for the cloud platforms resource
     */
    protected function getBasePath(): string
    {
        return 'cloudPlatforms';
    }

    /**
     * Fetch cloud platform URL for a specific resource type and ID
     *
     * @param  string  $type  Resource type (invoice, quotation, or ticket)
     * @param  string  $id  Resource UUID
     * @return array Response containing the cloud platform URL
     *
     * @throws InvalidArgumentException
     */
    public function url(string $type, string $id): array
    {
        $this->validateUrlRequest($type, $id);

        $data = [
            'type' => $type,
            'id' => $id,
        ];

        return $this->api->request('POST', $this->getBasePath().'.url', $data);
    }

    /**
     * Get cloud platform URL for an invoice
     * Convenience method for invoice-specific URLs
     *
     * @param  string  $invoiceId  Invoice UUID
     * @return array Response containing the cloud platform URL
     */
    public function invoiceUrl(string $invoiceId): array
    {
        return $this->url('invoice', $invoiceId);
    }

    /**
     * Get cloud platform URL for a quotation
     * Convenience method for quotation-specific URLs
     *
     * @param  string  $quotationId  Quotation UUID
     * @return array Response containing the cloud platform URL
     */
    public function quotationUrl(string $quotationId): array
    {
        return $this->url('quotation', $quotationId);
    }

    /**
     * Get cloud platform URL for a ticket
     * Convenience method for ticket-specific URLs
     *
     * @param  string  $ticketId  Ticket UUID
     * @return array Response containing the cloud platform URL
     */
    public function ticketUrl(string $ticketId): array
    {
        return $this->url('ticket', $ticketId);
    }

    /**
     * Get cloud platform URLs for multiple resources of the same type
     * This is a convenience method that calls the API multiple times
     *
     * @param  string  $type  Resource type
     * @param  array  $ids  Array of resource UUIDs
     * @return array Associative array mapping IDs to URLs
     *
     * @throws InvalidArgumentException
     */
    public function batchUrls(string $type, array $ids): array
    {
        if (! in_array($type, $this->supportedTypes)) {
            throw new InvalidArgumentException(
                "Invalid type: {$type}. Must be one of: ".implode(', ', $this->supportedTypes)
            );
        }

        if (empty($ids)) {
            throw new InvalidArgumentException('At least one ID is required');
        }

        $urls = [];

        foreach ($ids as $id) {
            $result = $this->url($type, $id);
            $urls[$id] = $result['data']['url'];
        }

        return $urls;
    }

    /**
     * Extract just the URL string from the API response
     *
     * @param  string  $type  Resource type
     * @param  string  $id  Resource UUID
     * @return string The cloud platform URL
     */
    public function getUrl(string $type, string $id): string
    {
        $result = $this->url($type, $id);

        return $result['data']['url'];
    }

    /**
     * Extract just the URL string for an invoice
     *
     * @param  string  $invoiceId  Invoice UUID
     * @return string The cloud platform URL
     */
    public function getInvoiceUrl(string $invoiceId): string
    {
        return $this->getUrl('invoice', $invoiceId);
    }

    /**
     * Extract just the URL string for a quotation
     *
     * @param  string  $quotationId  Quotation UUID
     * @return string The cloud platform URL
     */
    public function getQuotationUrl(string $quotationId): string
    {
        return $this->getUrl('quotation', $quotationId);
    }

    /**
     * Extract just the URL string for a ticket
     *
     * @param  string  $ticketId  Ticket UUID
     * @return string The cloud platform URL
     */
    public function getTicketUrl(string $ticketId): string
    {
        return $this->getUrl('ticket', $ticketId);
    }

    /**
     * Validate URL request parameters
     *
     * @throws InvalidArgumentException
     */
    protected function validateUrlRequest(string $type, string $id): void
    {
        // Validate type
        if (empty($type)) {
            throw new InvalidArgumentException('Resource type is required');
        }

        if (! in_array($type, $this->supportedTypes)) {
            throw new InvalidArgumentException(
                "Invalid type: {$type}. Must be one of: ".implode(', ', $this->supportedTypes)
            );
        }

        // Validate ID
        if (empty($id)) {
            throw new InvalidArgumentException('Resource ID is required');
        }

        // Basic UUID format validation
        if (! $this->isValidUuid($id)) {
            throw new InvalidArgumentException(
                "Invalid ID format: {$id}. Must be a valid UUID."
            );
        }
    }

    /**
     * Check if a string is a valid UUID format
     */
    protected function isValidUuid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $uuid) === 1;
    }

    /**
     * Get all supported resource types
     */
    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    /**
     * Check if a resource type is supported
     */
    public function isTypeSupported(string $type): bool
    {
        return in_array($type, $this->supportedTypes);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'url' => [
                'description' => 'Cloud platform URL for the specified resource',
                'fields' => [
                    'data.url' => 'The cloud platform URL (JWT-encoded URL to teamleader.cloud)',
                ],
                'notes' => [
                    'The URL is a JWT-encoded link to the resource in the Teamleader cloud platform',
                    'URLs are time-limited and may expire',
                    'Users will need appropriate permissions to view the resource',
                ],
            ],
        ];
    }

    /**
     * Override list method as it's not supported for cloud platforms
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            'The cloudPlatforms resource does not support list operations. Use url() method instead.'
        );
    }

    /**
     * Override info method as it's not supported for cloud platforms
     */
    public function info($id, $includes = null): array
    {
        throw new InvalidArgumentException(
            'The cloudPlatforms resource does not support info operations. Use url() method instead.'
        );
    }
}
