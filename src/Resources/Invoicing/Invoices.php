<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

/**
 * Invoices in Teamleader Focus.
 *
 * **Untitled sections.** In `grouped_lines`, the `section` key is optional. A
 * group with no title must omit the key entirely — Teamleader rejects both
 * `['section' => ['title' => null]]` and `['section' => ['title' => '']]` with
 * HTTP 400. This matters on read-modify-write: `info()` returns
 * `section.title` as `null` for untitled sections, so echoing that value straight
 * back is rejected. Drop the key instead:
 *
 *     foreach ($info['data']['grouped_lines'] as $group) {
 *         $out = ['line_items' => $lineItems];
 *
 *         if (! empty($group['section']['title'])) {
 *             $out['section'] = ['title' => $group['section']['title']];
 *         }
 *
 *         $groupedLines[] = $out;
 *     }
 *
 * Untitled sections are common: Teamleader's UI creates them by default when
 * the user does not name a section.
 */
class Invoices extends Resource
{
    protected string $description = 'Manage invoices in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'late_fees',
    ];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of invoice UUIDs',
        'term' => 'Search on invoice number, purchase order number, payment reference and invoicee',
        'invoice_number' => 'Full invoice number (fiscal year / number)',
        'department_id' => 'Filter on department (company entity)',
        'deal_id' => 'Filter on deal UUID',
        'project_id' => 'Filter on project UUID',
        'subscription_id' => 'Filter on subscription UUID',
        'status' => 'Array of statuses (draft, outstanding, matched)',
        'updated_since' => 'ISO 8601 datetime',
        'purchase_order_number' => 'Purchase order number',
        'payment_reference' => 'Payment reference',
        'invoice_date_after' => 'Date (inclusive, YYYY-MM-DD)',
        'invoice_date_before' => 'Date (inclusive, YYYY-MM-DD)',
        'customer' => 'Customer object with type and id',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'invoice_number',
        'invoice_date',
    ];

    // Valid invoice statuses
    protected array $validStatuses = [
        'draft',
        'outstanding',
        'matched',
    ];

    // Valid payment term types
    protected array $validPaymentTermTypes = [
        'cash',
        'end_of_month',
        'after_invoice_date',
    ];

    // Valid currency codes
    protected array $validCurrencyCodes = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK',
        'EUR', 'GBP', 'INR', 'ISK', 'JPY', 'MAD', 'MXN', 'NOK',
        'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    // Valid download formats
    protected array $validDownloadFormats = [
        'pdf',
        'ubl/e-fff',
        'ubl/peppol_bis_3',
        'ubl/xrechnung',
    ];

    // Valid expected_payment_method.method values (ExpectedPaymentMethod schema).
    // reference is required only when method is sepa_direct_debit.
    protected array $validExpectedPaymentMethods = [
        'direct_debit',
        'credit_card',
        'cash',
        'cheque',
        'bankers_draft',
        'bank_transfer',
        'payment_card',
        'sepa_direct_debit',
    ];

    // Valid Peppol submission statuses (returned in info/list responses)
    protected array $validPeppolStatuses = [
        'sending',
        'sending_failed',
        'sent',
        'application_acknowledged',
        'application_accepted',
        'application_rejected',
        'receiver_acknowledged',
        'receiver_accepted',
        'receiver_rejected',
        'receiver_is_processing',
        'receiver_awaits_feedback',
        'receiver_conditionally_accepted',
        'receiver_paid',
    ];

    // Usage examples specific to invoices
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all invoices',
            'code' => '$invoices = $teamleader->invoices()->list();',
        ],
        'create_draft' => [
            'description' => 'Draft a new invoice',
            'code' => '$invoice = $teamleader->invoices()->create([...]);',
        ],
        'create_draft_with_delivery_date' => [
            'description' => 'Draft a new invoice with a delivery date',
            'code' => '$invoice = $teamleader->invoices()->create([..., \'delivery_date\' => \'2025-12-08\']);',
        ],
        'filter_by_status' => [
            'description' => 'Get outstanding invoices',
            'code' => '$invoices = $teamleader->invoices()->outstanding();',
        ],
        'filter_by_customer' => [
            'description' => 'Get invoices for a specific customer',
            'code' => '$invoices = $teamleader->invoices()->forCustomer(\'company\', \'customer-uuid\');',
        ],
        'get_info' => [
            'description' => 'Get detailed invoice information',
            'code' => '$invoice = $teamleader->invoices()->info(\'invoice-uuid\');',
        ],
        'get_info_with_late_fees' => [
            'description' => 'Get invoice with late fee calculations',
            'code' => '$invoice = $teamleader->invoices()->info(\'invoice-uuid\', \'late_fees\');',
        ],
        'book_invoice' => [
            'description' => 'Book a draft invoice',
            'code' => '$result = $teamleader->invoices()->book(\'invoice-uuid\', \'2024-01-15\');',
        ],
        'copy_invoice' => [
            'description' => 'Create a new draft based on existing invoice',
            'code' => '$newInvoice = $teamleader->invoices()->copy(\'invoice-uuid\');',
        ],
        'update_draft' => [
            'description' => 'Update a draft invoice',
            'code' => '$result = $teamleader->invoices()->update(\'invoice-uuid\', [...]);',
        ],
        'update_draft_delivery_date' => [
            'description' => 'Update the delivery date on a draft invoice',
            'code' => '$result = $teamleader->invoices()->update(\'invoice-uuid\', [\'delivery_date\' => \'2025-12-08\']);',
        ],
        'update_booked' => [
            'description' => 'Update a booked invoice (if allowed in settings)',
            'code' => '$result = $teamleader->invoices()->updateBooked(\'invoice-uuid\', [...]);',
        ],
        'credit_completely' => [
            'description' => 'Credit an invoice completely',
            'code' => '$creditNote = $teamleader->invoices()->credit(\'invoice-uuid\', \'2024-02-04\');',
        ],
        'credit_partially' => [
            'description' => 'Credit an invoice partially',
            'code' => '$creditNote = $teamleader->invoices()->creditPartially(\'invoice-uuid\', \'2024-02-04\', [...]);',
        ],
        'download_pdf' => [
            'description' => 'Download invoice as PDF',
            'code' => '$download = $teamleader->invoices()->download(\'invoice-uuid\', \'pdf\');',
        ],
        'send_email' => [
            'description' => 'Send invoice via email',
            'code' => '$result = $teamleader->invoices()->send(\'invoice-uuid\', [...]);',
        ],
        'send_peppol' => [
            'description' => 'Send invoice via Peppol',
            'code' => '$result = $teamleader->invoices()->sendViaPeppol(\'invoice-uuid\');',
        ],
        'register_payment' => [
            'description' => 'Register a payment for an invoice',
            'code' => '$result = $teamleader->invoices()->registerPayment(\'invoice-uuid\', [...]);',
        ],
        'remove_payments' => [
            'description' => 'Remove all payments from an invoice',
            'code' => '$result = $teamleader->invoices()->removePayments(\'invoice-uuid\');',
        ],
        'delete_invoice' => [
            'description' => 'Delete a draft or last booked invoice',
            'code' => '$result = $teamleader->invoices()->delete(\'invoice-uuid\');',
        ],
    ];

    /**
     * Get detailed information about a specific invoice
     *
     * Response includes all list fields plus: discounts, grouped_lines (with full line
     * item detail), payment_term, payments, payment_reference, note, currency,
     * currency_exchange_rate, expected_payment_method, on_hold_since, custom_fields,
     * document_template, delivery_date (nullable), peppol_status (nullable)
     *
     * @param  string  $id  Invoice UUID
     * @param  mixed  $includes  Optional includes (e.g., 'late_fees')
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        if (! empty($includes)) {
            $params['includes'] = is_array($includes)
                ? implode(',', $includes)
                : $includes;
        }

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Get the base path for the invoices resource
     */
    protected function getBasePath(): string
    {
        return 'invoices';
    }

    /**
     * Draft a new invoice
     *
     * Required fields:
     * - invoicee (object): customer {type, id} and optional for_attention_of
     * - department_id (string): Department UUID
     * - payment_term (object): type and optional days
     * - grouped_lines (array): sections with line_items
     *
     * Optional fields:
     * - currency (object): code and optional exchange_rate
     * - project_id (string): Project UUID
     * - quotation_id (string): links the invoice to a source quotation and its deal, and marks the deal as won
     * - purchase_order_number (string)
     * - invoice_date (string): YYYY-MM-DD
     * - discounts (array)
     * - note (string)
     * - expected_payment_method (object): method and optional reference
     * - custom_fields (array)
     * - document_template_id (string)
     * - delivery_date (string|null): YYYY-MM-DD — the delivery/service date shown on the invoice
     *
     * @param  array  $data  Invoice data
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.draft', $data);
    }

    /**
     * Validate invoice creation data
     *
     * @throws InvalidArgumentException
     */
    private function validateCreateData(array $data): void
    {
        // Required fields
        if (! isset($data['invoicee'])) {
            throw new InvalidArgumentException('invoicee is required');
        }

        if (! isset($data['department_id'])) {
            throw new InvalidArgumentException('department_id is required');
        }

        if (! isset($data['payment_term'])) {
            throw new InvalidArgumentException('payment_term is required');
        }

        if (! isset($data['grouped_lines'])) {
            throw new InvalidArgumentException('grouped_lines is required');
        }

        // Validate invoicee
        if (! isset($data['invoicee']['customer']) ||
            ! isset($data['invoicee']['customer']['type']) ||
            ! isset($data['invoicee']['customer']['id'])) {
            throw new InvalidArgumentException('invoicee must have customer with type and id');
        }

        $this->validateCustomerType($data['invoicee']['customer']['type']);

        // Validate payment term
        if (! isset($data['payment_term']['type'])) {
            throw new InvalidArgumentException('payment_term must have type');
        }

        if (! in_array($data['payment_term']['type'], $this->validPaymentTermTypes)) {
            throw new InvalidArgumentException(
                'Invalid payment term type. Must be one of: '.
                implode(', ', $this->validPaymentTermTypes)
            );
        }

        // Validate currency if provided
        if (isset($data['currency']['code']) &&
            ! in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: '.
                implode(', ', $this->validCurrencyCodes)
            );
        }

        // Validate grouped lines
        $this->validateGroupedLines($data['grouped_lines']);

        // Validate expected_payment_method if provided
        if (isset($data['expected_payment_method']) && is_array($data['expected_payment_method'])) {
            $this->validateExpectedPaymentMethod($data['expected_payment_method']);
        }
    }

    /**
     * Validate customer type
     *
     * @throws InvalidArgumentException
     */
    private function validateCustomerType(string $type): void
    {
        $validTypes = ['contact', 'company'];
        if (! in_array($type, $validTypes)) {
            throw new InvalidArgumentException(
                "Invalid customer type '{$type}'. Must be one of: ".implode(', ', $validTypes)
            );
        }
    }

    /**
     * Validate grouped lines structure
     *
     * `section` is optional. The API's InvoicesGroupedLinesRequest structure
     * lists only `line_items` as required, and `invoices.info` returns
     * `section.title` as nullable — so a read-modify-write round trip on an
     * invoice with untitled sections produces groups with no title.
     *
     * When a group has no title the `section` key must be omitted entirely.
     * Teamleader rejects both `{"title": null}` and `{"title": ""}` with
     * HTTP 400 "grouped_lines must be valid", so there is exactly one accepted
     * way to express an untitled group. Requiring `section.title` — as this
     * method did before v2.1.2 — made invoices.draft, invoices.update and
     * invoices.updateBooked unreachable for any invoice with untitled sections,
     * which is what Teamleader's own UI creates when the user does not name one.
     *
     * @throws InvalidArgumentException
     */
    private function validateGroupedLines(array $groupedLines): void
    {
        foreach ($groupedLines as $group) {
            if (! is_array($group)) {
                throw new InvalidArgumentException('Each grouped line must be an array');
            }

            if (array_key_exists('section', $group)) {
                if (! is_array($group['section']) || ! array_key_exists('title', $group['section'])) {
                    throw new InvalidArgumentException('When present, section must contain a title');
                }

                if (! is_string($group['section']['title']) || $group['section']['title'] === '') {
                    throw new InvalidArgumentException(
                        'section.title must be a non-empty string. Omit the section key entirely '
                        .'for untitled groups — the API rejects both null and "".'
                    );
                }
            }

            if (! isset($group['line_items']) || ! is_array($group['line_items'])) {
                throw new InvalidArgumentException('Each grouped line must have line_items array');
            }

            // Validate each line item
            foreach ($group['line_items'] as $item) {
                $this->validateLineItem($item);
            }
        }
    }

    /**
     * Validate a single line item
     *
     * @throws InvalidArgumentException
     */
    private function validateLineItem(array $item): void
    {
        // Required fields
        if (! isset($item['quantity']) || ! is_numeric($item['quantity'])) {
            throw new InvalidArgumentException('Line item quantity is required and must be numeric');
        }

        if (! isset($item['description']) || empty($item['description'])) {
            throw new InvalidArgumentException('Line item description is required');
        }

        if (! isset($item['unit_price']) || ! is_array($item['unit_price'])) {
            throw new InvalidArgumentException('Line item unit_price is required and must be an object');
        }

        if (! isset($item['unit_price']['amount']) || ! is_numeric($item['unit_price']['amount'])) {
            throw new InvalidArgumentException('Line item unit_price.amount is required and must be numeric');
        }

        if (! isset($item['unit_price']['tax']) || $item['unit_price']['tax'] !== 'excluding') {
            throw new InvalidArgumentException('Line item unit_price.tax is required and must be "excluding"');
        }

        if (! isset($item['tax_rate_id']) || empty($item['tax_rate_id'])) {
            throw new InvalidArgumentException('Line item tax_rate_id is required');
        }

        // Validate discount if provided
        if (isset($item['discount'])) {
            if (! isset($item['discount']['value']) || ! is_numeric($item['discount']['value'])) {
                throw new InvalidArgumentException('Discount value must be numeric');
            }

            if (! isset($item['discount']['type']) || $item['discount']['type'] !== 'percentage') {
                throw new InvalidArgumentException('Discount type must be "percentage"');
            }

            if ($item['discount']['value'] < 0 || $item['discount']['value'] > 100) {
                throw new InvalidArgumentException('Discount value must be between 0 and 100');
            }
        }
    }

    /**
     * Validate an expected_payment_method object.
     *
     * - `method` must be one of the supported values
     * - when `method` is `sepa_direct_debit`, `reference` is required
     *
     * @throws InvalidArgumentException
     */
    protected function validateExpectedPaymentMethod(array $expectedPaymentMethod): void
    {
        if (! isset($expectedPaymentMethod['method'])) {
            throw new InvalidArgumentException('expected_payment_method must include a "method" value');
        }

        if (! in_array($expectedPaymentMethod['method'], $this->validExpectedPaymentMethods)) {
            throw new InvalidArgumentException(
                'Invalid expected_payment_method method. Must be one of: '.
                implode(', ', $this->validExpectedPaymentMethods)
            );
        }

        if ($expectedPaymentMethod['method'] === 'sepa_direct_debit' && empty($expectedPaymentMethod['reference'])) {
            throw new InvalidArgumentException(
                'expected_payment_method.reference is required when method is "sepa_direct_debit"'
            );
        }
    }

    /**
     * Update a draft invoice
     * Note: Booked invoices cannot be updated with this method
     *
     * Updatable fields:
     * - invoicee (object): customer {type, id} and optional for_attention_of
     * - payment_term (object): nullable, type and optional days
     * - currency (object): code and optional exchange_rate
     * - project_id (string)
     * - purchase_order_number (string)
     * - grouped_lines (array): sections with line_items
     * - invoice_date (string): YYYY-MM-DD
     * - note (string|null)
     * - discounts (array)
     * - expected_payment_method (object|null)
     * - custom_fields (array)
     * - document_template_id (string)
     * - delivery_date (string|null): YYYY-MM-DD — the delivery/service date shown on the invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  array  $data  Invoice data to update
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateUpdateData($data);

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Validate invoice update data
     *
     * @throws InvalidArgumentException
     */
    private function validateUpdateData(array $data): void
    {
        // ID is required
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Invoice ID is required for updates');
        }

        // Validate payment term type if provided
        if (isset($data['payment_term']['type']) &&
            ! in_array($data['payment_term']['type'], $this->validPaymentTermTypes)) {
            throw new InvalidArgumentException(
                'Invalid payment term type. Must be one of: '.
                implode(', ', $this->validPaymentTermTypes)
            );
        }

        // Validate currency code if provided
        if (isset($data['currency']['code']) &&
            ! in_array($data['currency']['code'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: '.
                implode(', ', $this->validCurrencyCodes)
            );
        }

        // Validate grouped lines structure if provided
        if (isset($data['grouped_lines'])) {
            $this->validateGroupedLines($data['grouped_lines']);
        }

        // Validate customer structure if provided
        if (isset($data['invoicee']['customer'])) {
            $customer = $data['invoicee']['customer'];
            if (empty($customer['type']) || empty($customer['id'])) {
                throw new InvalidArgumentException('Customer must have type and id');
            }
            $this->validateCustomerType($customer['type']);
        }

        // Validate expected_payment_method if provided
        if (isset($data['expected_payment_method']) && is_array($data['expected_payment_method'])) {
            $this->validateExpectedPaymentMethod($data['expected_payment_method']);
        }
    }

    /**
     * Update a booked invoice
     * Note: Only available when editing booked invoices is allowed through the settings
     *
     * @param  string  $id  Invoice UUID
     * @param  array  $data  Invoice data to update
     */
    public function updateBooked(string $id, array $data): array
    {
        $data['id'] = $id;
        $this->validateUpdateData($data);

        return $this->api->request('POST', $this->getBasePath().'.updateBooked', $data);
    }

    /**
     * Delete an existing invoice
     * Only possible for draft invoices or the last booked invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  mixed  ...$additionalParams  Additional parameters (not used for invoices)
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
        ]);
    }

    /**
     * Book a draft invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  string  $on  Booking date (YYYY-MM-DD format)
     */
    public function book(string $id, string $on): array
    {
        return $this->api->request('POST', $this->getBasePath().'.book', [
            'id' => $id,
            'on' => $on,
        ]);
    }

    /**
     * Copy an invoice to create a new draft
     *
     * @param  string  $id  Invoice UUID to copy
     */
    public function copy(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.copy', [
            'id' => $id,
        ]);
    }

    /**
     * Credit an invoice completely
     *
     * @param  string  $id  Invoice UUID
     * @param  string  $creditNoteDate  Credit note date (YYYY-MM-DD format)
     */
    public function credit(string $id, string $creditNoteDate): array
    {
        return $this->api->request('POST', $this->getBasePath().'.credit', [
            'id' => $id,
            'credit_note_date' => $creditNoteDate,
        ]);
    }

    /**
     * Credit an invoice partially
     *
     * @param  string  $id  Invoice UUID
     * @param  string  $creditNoteDate  Credit note date (YYYY-MM-DD format)
     * @param  array  $groupedLines  Grouped lines to credit
     * @param  array|null  $discounts  Optional discounts
     */
    public function creditPartially(string $id, string $creditNoteDate, array $groupedLines, ?array $discounts = null): array
    {
        $data = [
            'id' => $id,
            'credit_note_date' => $creditNoteDate,
            'grouped_lines' => $groupedLines,
        ];

        if (! empty($discounts)) {
            $data['discounts'] = $discounts;
        }

        $this->validateGroupedLines($groupedLines);

        return $this->api->request('POST', $this->getBasePath().'.creditPartially', $data);
    }

    /**
     * Download an invoice in a specific format
     *
     * @param  string  $id  Invoice UUID
     * @param  string  $format  Format (pdf, ubl/e-fff, ubl/peppol_bis_3, ubl/xrechnung)
     * @return array Returns temporary download URL and expiration
     */
    public function download(string $id, string $format = 'pdf'): array
    {
        if (! in_array($format, $this->validDownloadFormats)) {
            throw new InvalidArgumentException(
                "Invalid format '{$format}'. Must be one of: ".implode(', ', $this->validDownloadFormats)
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.download', [
            'id' => $id,
            'format' => $format,
        ]);
    }

    /**
     * Send an invoice via email
     *
     * @param  string  $id  Invoice UUID
     * @param  array  $content  Email content (subject, body, optional mail_template_id)
     * @param  array  $recipients  Recipients (to, cc, bcc arrays)
     * @param  array|null  $attachments  Optional file IDs to attach
     */
    public function send(string $id, array $content, array $recipients = [], ?array $attachments = null): array
    {
        $data = [
            'id' => $id,
            'content' => $content,
        ];

        if (! empty($recipients)) {
            $data['recipients'] = $recipients;
        }

        if (! empty($attachments)) {
            $data['attachments'] = $attachments;
        }

        $this->validateSendData($data);

        return $this->api->request('POST', $this->getBasePath().'.send', $data);
    }

    /**
     * Validate send email data
     *
     * @throws InvalidArgumentException
     */
    private function validateSendData(array $data): void
    {
        if (! isset($data['content']['subject']) || empty($data['content']['subject'])) {
            throw new InvalidArgumentException('Email subject is required');
        }

        if (! isset($data['content']['body']) || empty($data['content']['body'])) {
            throw new InvalidArgumentException('Email body is required');
        }

        // Validate recipient structure
        if (isset($data['recipients'])) {
            foreach (['to', 'cc', 'bcc'] as $field) {
                if (isset($data['recipients'][$field])) {
                    foreach ($data['recipients'][$field] as $recipient) {
                        if (! isset($recipient['email']) || empty($recipient['email'])) {
                            throw new InvalidArgumentException("Email is required for all {$field} recipients");
                        }

                        if (isset($recipient['customer'])) {
                            if (! isset($recipient['customer']['type']) || ! isset($recipient['customer']['id'])) {
                                throw new InvalidArgumentException('Customer must have type and id');
                            }
                            $this->validateCustomerType($recipient['customer']['type']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Send an invoice via the Peppol network
     *
     * @param  string  $id  Invoice UUID
     */
    public function sendViaPeppol(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.sendViaPeppol', [
            'id' => $id,
        ]);
    }

    /**
     * Register a payment for an invoice
     *
     * @param  string  $id  Invoice UUID
     * @param  array  $payment  Payment data (amount, currency)
     * @param  string  $paidAt  Payment date (ISO 8601 format)
     * @param  string|null  $paymentMethodId  Optional payment method UUID
     */
    public function registerPayment(string $id, array $payment, string $paidAt, ?string $paymentMethodId = null): array
    {
        $data = [
            'id' => $id,
            'payment' => $payment,
            'paid_at' => $paidAt,
        ];

        if (! empty($paymentMethodId)) {
            $data['payment_method_id'] = $paymentMethodId;
        }

        $this->validatePaymentData($payment);

        return $this->api->request('POST', $this->getBasePath().'.registerPayment', $data);
    }

    /**
     * Validate payment data
     *
     * @throws InvalidArgumentException
     */
    private function validatePaymentData(array $payment): void
    {
        if (! isset($payment['amount']) || ! is_numeric($payment['amount'])) {
            throw new InvalidArgumentException('Payment amount is required and must be numeric');
        }

        if (! isset($payment['currency']) || empty($payment['currency'])) {
            throw new InvalidArgumentException('Payment currency is required');
        }

        if (! in_array($payment['currency'], $this->validCurrencyCodes)) {
            throw new InvalidArgumentException(
                'Invalid currency code. Must be one of: '.
                implode(', ', $this->validCurrencyCodes)
            );
        }
    }

    /**
     * Remove all payments from an invoice (marks as unpaid)
     *
     * @param  string  $id  Invoice UUID
     */
    public function removePayments(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.removePayments', [
            'id' => $id,
        ]);
    }

    /**
     * List draft invoices.
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     *
     * @deprecated Use listDrafts() — draft() is easily confused with create(),
     *             which is what actually creates a draft (POST invoices.draft).
     */
    public function draft(array $additionalFilters = [], array $options = []): array
    {
        return $this->listDrafts($additionalFilters, $options);
    }

    /**
     * List draft invoices.
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function listDrafts(array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['draft']], $additionalFilters),
            $options
        );
    }

    /**
     * List invoices with filtering and sorting
     *
     * Response includes: id, department, invoice_number, invoice_date, status, due_on,
     * paid, paid_at, sent, purchase_order_number, payment_reference, invoicee, total,
     * currency_exchange_rate, created_at, updated_at, web_url, file, deal, project,
     * subscription (nullable object: {id, type}), delivery_date (nullable),
     * peppol_status (nullable)
     *
     * @param  array  $filters  Filter parameters
     * @param  array  $options  Pagination and sorting options
     */
    public function list(array $filters = [], array $options = []): array
    {
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

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        }

        // Apply includes
        if (! empty($options['includes'])) {
            $params['includes'] = is_array($options['includes'])
                ? implode(',', $options['includes'])
                : $options['includes'];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($key === 'status') {
                // status must be an array of valid statuses; coerce a lone string.
                $statuses = is_array($value) ? $value : [$value];

                foreach ($statuses as $status) {
                    if (! in_array($status, $this->validStatuses)) {
                        throw new InvalidArgumentException(
                            "Invalid status '{$status}'. Must be one of: ".
                            implode(', ', $this->validStatuses)
                        );
                    }
                }

                $apiFilters[$key] = array_values($statuses);
            } else {
                $apiFilters[$key] = $value;
            }
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        $apiSort = [];

        foreach ($sort as $sortItem) {
            if (! isset($sortItem['field'])) {
                continue;
            }

            // Validate sort field
            if (! in_array($sortItem['field'], $this->availableSortFields)) {
                throw new InvalidArgumentException(
                    "Invalid sort field '{$sortItem['field']}'. Available fields: ".
                    implode(', ', $this->availableSortFields)
                );
            }

            $apiSort[] = [
                'field' => $sortItem['field'],
                'order' => $sortItem['order'] ?? 'desc',
            ];
        }

        return $apiSort;
    }

    /**
     * Get outstanding invoices
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function outstanding(array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['outstanding']], $additionalFilters),
            $options
        );
    }

    /**
     * Get matched invoices
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function matched(array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['matched']], $additionalFilters),
            $options
        );
    }

    /**
     * Get invoices for a specific customer
     *
     * @param  string  $customerType  Customer type (contact or company)
     * @param  string  $customerId  Customer UUID
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function forCustomer(string $customerType, string $customerId, array $additionalFilters = [], array $options = []): array
    {
        $this->validateCustomerType($customerType);

        return $this->list(
            array_merge([
                'customer' => [
                    'type' => $customerType,
                    'id' => $customerId,
                ],
            ], $additionalFilters),
            $options
        );
    }

    /**
     * Get invoices for a specific project
     *
     * @param  string  $projectId  Project UUID
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function forProject(string $projectId, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['project_id' => $projectId], $additionalFilters),
            $options
        );
    }

    /**
     * Get invoices for a specific deal
     *
     * @param  string  $dealId  Deal UUID
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function forDeal(string $dealId, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['deal_id' => $dealId], $additionalFilters),
            $options
        );
    }

    /**
     * Get invoices for a specific department
     *
     * @param  string  $departmentId  Department UUID
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function forDepartment(string $departmentId, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['department_id' => $departmentId], $additionalFilters),
            $options
        );
    }

    /**
     * Search invoices by term
     *
     * @param  string  $term  Search term
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function search(string $term, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $term], $additionalFilters),
            $options
        );
    }

    /**
     * Get invoices updated since a specific date
     *
     * @param  string  $datetime  ISO 8601 datetime
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function updatedSince(string $datetime, array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['updated_since' => $datetime], $additionalFilters),
            $options
        );
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of invoices with pagination',
                'fields' => [
                    'data' => 'Array of invoice objects',
                    'data[].id' => 'Invoice UUID',
                    'data[].department' => 'Department reference',
                    'data[].invoice_number' => 'Invoice number (nullable until booked)',
                    'data[].invoice_date' => 'Invoice date (nullable)',
                    'data[].status' => 'Status (draft, outstanding, matched)',
                    'data[].due_on' => 'Due date (nullable)',
                    'data[].paid' => 'Whether the invoice is fully paid (boolean)',
                    'data[].paid_at' => 'Payment date (nullable)',
                    'data[].sent' => 'Whether the invoice has been sent (boolean)',
                    'data[].purchase_order_number' => 'PO number (nullable)',
                    'data[].payment_reference' => 'Structured payment reference (nullable)',
                    'data[].invoicee' => 'Invoicee with name, vat_number, customer, for_attention_of',
                    'data[].total' => 'Total amounts (tax_exclusive, tax_inclusive, payable, due)',
                    'data[].currency_exchange_rate' => 'Exchange rate (nullable)',
                    'data[].deal' => 'Related deal reference (nullable)',
                    'data[].project' => 'Related project reference (nullable)',
                    'data[].subscription' => 'Subscription that generated this invoice (nullable: {type, id})',
                    'data[].file' => 'Attached file reference (nullable)',
                    'data[].delivery_date' => 'Delivery/service date YYYY-MM-DD (nullable)',
                    'data[].peppol_status' => 'Peppol submission status (nullable)',
                    'data[].created_at' => 'Creation timestamp ISO 8601',
                    'data[].updated_at' => 'Last update timestamp ISO 8601',
                    'data[].web_url' => 'Web URL to the invoice in Teamleader Focus',
                ],
            ],
            'info' => [
                'description' => 'Complete invoice information',
                'fields' => [
                    'data.id' => 'Invoice UUID',
                    'data.department' => 'Department reference',
                    'data.invoice_number' => 'Invoice number (nullable until booked)',
                    'data.invoice_date' => 'Invoice date (nullable)',
                    'data.status' => 'Status (draft, outstanding, matched)',
                    'data.due_on' => 'Due date (nullable)',
                    'data.paid' => 'Whether the invoice is fully paid (boolean)',
                    'data.paid_at' => 'Payment date (nullable)',
                    'data.sent' => 'Whether the invoice has been sent (boolean)',
                    'data.purchase_order_number' => 'PO number (nullable)',
                    'data.invoicee' => 'Invoicee with name, vat_number, customer, for_attention_of, email, national_identification_number',
                    'data.discounts' => 'Invoice-level discounts',
                    'data.grouped_lines' => 'Array of grouped line item sections',
                    'data.payment_term' => 'Payment term (type, days)',
                    'data.payments' => 'Array of registered payments',
                    'data.payment_reference' => 'Structured payment reference (nullable)',
                    'data.note' => 'Internal note (nullable)',
                    'data.currency' => 'Currency code',
                    'data.currency_exchange_rate' => 'Exchange rate (nullable)',
                    'data.expected_payment_method' => 'Expected payment method (nullable)',
                    'data.total' => 'Complete totals including taxes, withholding, payable, due',
                    'data.file' => 'Attached file reference (nullable)',
                    'data.deal' => 'Related deal reference (nullable)',
                    'data.project' => 'Related project reference (nullable)',
                    'data.on_hold_since' => 'On-hold datetime (nullable)',
                    'data.custom_fields' => 'Custom field values',
                    'data.document_template' => 'Document template reference (nullable)',
                    'data.delivery_date' => 'Delivery/service date YYYY-MM-DD (nullable)',
                    'data.peppol_status' => 'Peppol submission status (nullable)',
                    'data.created_at' => 'Creation timestamp ISO 8601',
                    'data.updated_at' => 'Last update timestamp ISO 8601',
                ],
            ],
            'create' => [
                'description' => 'Response contains the created invoice reference',
                'fields' => [
                    'data.type' => 'Resource type (always "invoice")',
                    'data.id' => 'UUID of the created invoice',
                ],
            ],
            'book' => [
                'description' => 'Empty response on success (204 No Content)',
                'fields' => [],
            ],
            'copy' => [
                'description' => 'Response contains the new draft invoice reference',
                'fields' => [
                    'data.type' => 'Resource type (always "invoice")',
                    'data.id' => 'UUID of the new draft invoice',
                ],
            ],
            'credit' => [
                'description' => 'Response contains the created credit note reference',
                'fields' => [
                    'data.type' => 'Resource type (always "creditNote")',
                    'data.id' => 'UUID of the created credit note',
                ],
            ],
            'download' => [
                'description' => 'Temporary download link for the invoice file',
                'fields' => [
                    'location' => 'Temporary download URL',
                    'expires' => 'Expiration timestamp',
                ],
            ],
            'registerPayment' => [
                'description' => 'Empty response on success (204 No Content)',
                'fields' => [],
            ],
        ];
    }

    /**
     * Get the possible Peppol submission statuses.
     *
     * These are the values that may appear in the (nullable) `peppol_status`
     * field of info()/list() responses. Peppol status is read-only — it is not
     * a request parameter or a list filter.
     *
     * @return array<int, string>
     */
    public function getValidPeppolStatuses(): array
    {
        return $this->validPeppolStatuses;
    }
}
