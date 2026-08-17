<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Invoices;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Regression tests for the v2.1.2 Invoices grouped-lines fix.
 *
 * validateGroupedLines() required every group to carry a section.title. The API
 * declares `section` optional and returns section.title as nullable, and rejects
 * both a null and an empty-string title with HTTP 400 — so the SDK required the
 * one shape the API refuses and refused the one shape it accepts.
 *
 * The practical effect: a read-modify-write round trip on any invoice with an
 * untitled section was impossible through the SDK, and Teamleader's UI creates
 * untitled sections by default.
 */
final class InvoicesGroupedLinesTest extends ResourceTestCase
{
    private Invoices $invoices;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoices = $this->resource(Invoices::class);
    }

    /**
     * A minimal valid line item, so each test only varies the section.
     */
    private function lineItem(): array
    {
        return [
            'quantity' => 1,
            'description' => 'An awesome product',
            'unit_price' => ['amount' => 100, 'tax' => 'excluding'],
            'tax_rate_id' => 'tax-rate-uuid',
        ];
    }

    /**
     * A minimal valid draft payload with the given grouped lines.
     */
    private function draftPayload(array $groupedLines): array
    {
        return [
            'invoicee' => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
            'department_id' => 'department-uuid',
            'payment_term' => ['type' => 'cash'],
            'grouped_lines' => $groupedLines,
        ];
    }

    // ---------------------------------------------------------------------
    // Section omitted — the case that was impossible before
    // ---------------------------------------------------------------------

    public function test_a_group_without_a_section_key_is_accepted(): void
    {
        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['line_items' => [$this->lineItem()]],
            ],
        ]);

        $this->assertLastEndpoint('invoices.update');
        $this->assertLastBodyMissing('grouped_lines.0.section');
    }

    public function test_create_accepts_a_group_without_a_section_key(): void
    {
        $this->invoices->create($this->draftPayload([
            ['line_items' => [$this->lineItem()]],
        ]));

        $this->assertLastEndpoint('invoices.draft');
    }

    public function test_update_booked_accepts_a_group_without_a_section_key(): void
    {
        $this->invoices->updateBooked('invoice-uuid', [
            'grouped_lines' => [
                ['line_items' => [$this->lineItem()]],
            ],
        ]);

        $this->assertLastEndpoint('invoices.updateBooked');
    }

    public function test_credit_partially_accepts_a_group_without_a_section_key(): void
    {
        $this->invoices->creditPartially('invoice-uuid', '2026-02-04', [
            ['line_items' => [$this->lineItem()]],
        ]);

        $this->assertLastEndpoint('invoices.creditPartially');
    }

    public function test_a_read_modify_write_round_trip_survives_untitled_sections(): void
    {
        // Reproduces the reported failure: read an invoice whose section has a
        // null title, rebuild the payload dropping the empty section, write back.
        $this->api->queueResponse([
            'data' => [
                'id' => 'invoice-uuid',
                'grouped_lines' => [
                    ['section' => ['title' => null], 'line_items' => []],
                    ['section' => ['title' => 'Materials'], 'line_items' => []],
                ],
            ],
            'headers' => [],
        ]);

        $info = $this->invoices->info('invoice-uuid');

        $groupedLines = [];

        foreach ($info['data']['grouped_lines'] as $group) {
            $out = ['line_items' => [$this->lineItem()]];

            if (! empty($group['section']['title'])) {
                $out['section'] = ['title' => $group['section']['title']];
            }

            $groupedLines[] = $out;
        }

        $this->invoices->update('invoice-uuid', ['grouped_lines' => $groupedLines]);

        $this->assertLastBodyMissing('grouped_lines.0.section');
        $this->assertLastBodyHas('grouped_lines.1.section.title', 'Materials');
    }

    // ---------------------------------------------------------------------
    // Section present
    // ---------------------------------------------------------------------

    public function test_a_group_with_a_titled_section_is_accepted(): void
    {
        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['section' => ['title' => 'Labour'], 'line_items' => [$this->lineItem()]],
            ],
        ]);

        $this->assertLastBodyHas('grouped_lines.0.section.title', 'Labour');
    }

    public function test_a_null_section_title_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Omit the section key entirely');

        try {
            $this->invoices->update('invoice-uuid', [
                'grouped_lines' => [
                    ['section' => ['title' => null], 'line_items' => [$this->lineItem()]],
                ],
            ]);
        } finally {
            $this->assertNoRequestMade();
        }
    }

    public function test_an_empty_string_section_title_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Omit the section key entirely');

        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['section' => ['title' => ''], 'line_items' => [$this->lineItem()]],
            ],
        ]);
    }

    public function test_a_section_without_a_title_key_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('section must contain a title');

        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['section' => [], 'line_items' => [$this->lineItem()]],
            ],
        ]);
    }

    public function test_a_non_array_section_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('section must contain a title');

        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['section' => 'Labour', 'line_items' => [$this->lineItem()]],
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // Unchanged validation
    // ---------------------------------------------------------------------

    public function test_line_items_are_still_required(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('line_items');

        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['section' => ['title' => 'Labour']],
            ],
        ]);
    }

    public function test_line_items_are_still_validated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tax_rate_id');

        $item = $this->lineItem();
        unset($item['tax_rate_id']);

        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['line_items' => [$item]],
            ],
        ]);
    }

    public function test_a_non_array_group_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Each grouped line must be an array');

        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => ['not-a-group'],
        ]);
    }

    public function test_mixed_titled_and_untitled_groups_are_accepted(): void
    {
        $this->invoices->update('invoice-uuid', [
            'grouped_lines' => [
                ['line_items' => [$this->lineItem()]],
                ['section' => ['title' => 'Materials'], 'line_items' => [$this->lineItem()]],
                ['line_items' => [$this->lineItem()]],
            ],
        ]);

        $this->assertLastBodyMissing('grouped_lines.0.section');
        $this->assertLastBodyHas('grouped_lines.1.section.title', 'Materials');
        $this->assertLastBodyMissing('grouped_lines.2.section');
    }
}
