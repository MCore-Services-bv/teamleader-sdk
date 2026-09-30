<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Tickets;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Tickets\Tickets;
use McoreServices\TeamleaderSDK\Resources\Tickets\TicketStatus;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * Payload tests for the Tickets category, against specification 1.221.0.
 */
final class TicketsPayloadTest extends ResourceTestCase
{
    private function expectRejected(callable $call, ?string $message = null): void
    {
        $this->expectException(InvalidArgumentException::class);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }

        try {
            $call();
        } finally {
            $this->assertNoRequestMade();
        }
    }

    // -- list ------------------------------------------------------------------

    /**
     * The dotted keys were advertised but sent flat until v2.2.13, and ignored.
     */
    public function test_dotted_filters_are_nested(): void
    {
        $this->resource(Tickets::class)->list([
            'relates_to.type' => 'company',
            'relates_to.id' => 'company-uuid',
            'exclude.status_ids' => ['status-uuid'],
        ]);

        $this->assertLastEndpoint('tickets.list');
        $this->assertLastBody(['filter' => [
            'relates_to' => ['type' => 'company', 'id' => 'company-uuid'],
            'exclude' => ['status_ids' => ['status-uuid']],
        ]]);
    }

    public function test_unassigned_sends_a_null_assignee(): void
    {
        $this->resource(Tickets::class)->unassigned();

        $this->assertLastBody(['filter' => ['assignee_ids' => [null]]]);
    }

    public function test_assigned_to_can_include_unassigned(): void
    {
        $this->resource(Tickets::class)->assignedTo(['user-uuid', null]);

        $this->assertLastBodyHas('filter.assignee_ids', ['user-uuid', null]);
    }

    public function test_unknown_filter_throws(): void
    {
        $this->expectRejected(fn () => $this->resource(Tickets::class)->list(['status_id' => 's']), 'status_id');
    }

    public function test_relates_to_type_is_checked(): void
    {
        $this->expectRejected(
            fn () => $this->resource(Tickets::class)->list(['relates_to' => ['type' => 'deal', 'id' => 'd']]),
            'filter.relates_to.type'
        );
    }

    public function test_exclude_takes_status_ids_only(): void
    {
        $this->expectRejected(fn () => $this->resource(Tickets::class)->list(['exclude' => ['ids' => ['x']]]), 'status_ids');
    }

    // -- write -----------------------------------------------------------------

    public function test_create(): void
    {
        $this->resource(Tickets::class)->create([
            'subject' => 'Printer offline',
            'customer' => ['type' => 'company', 'id' => 'company-uuid'],
            'ticket_status_id' => 'status-uuid',
            'initial_reply' => 'disabled',
        ]);

        $this->assertLastEndpoint('tickets.create');
        $this->assertLastBodyHas('initial_reply', 'disabled');
    }

    public function test_update_rejects_create_only_fields(): void
    {
        $this->expectRejected(
            fn () => $this->resource(Tickets::class)->update('ticket-uuid', ['initial_reply' => 'disabled']),
            'tickets.update does not accept: initial_reply'
        );
    }

    public function test_update_can_unassign_and_unlink(): void
    {
        $this->resource(Tickets::class)->update('ticket-uuid', [
            'assignee' => null,
            'participant' => ['customer' => null],
            'project_id' => null,
        ]);

        $this->assertLastBody([
            'assignee' => null,
            'participant' => ['customer' => null],
            'project_id' => null,
            'id' => 'ticket-uuid',
        ]);
    }

    public function test_participant_is_a_company(): void
    {
        $this->expectRejected(fn () => $this->resource(Tickets::class)->update('ticket-uuid', [
            'participant' => ['customer' => ['type' => 'contact', 'id' => 'c']],
        ]), 'participant.customer.type');
    }

    public function test_info_takes_no_includes(): void
    {
        $this->expectRejected(fn () => $this->resource(Tickets::class)->info('ticket-uuid', 'messages'));
    }

    // -- messages --------------------------------------------------------------

    public function test_list_messages_requests_the_pagination_meta(): void
    {
        $this->resource(Tickets::class)->listMessages('ticket-uuid', ['type' => 'internal'], ['page_size' => 10]);

        $this->assertLastEndpoint('tickets.listMessages');
        $this->assertLastBody([
            'id' => 'ticket-uuid',
            'filter' => ['type' => 'internal'],
            'page' => ['size' => 10, 'number' => 1],
            'includes' => 'pagination',
        ]);
    }

    public function test_list_messages_rejects_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(Tickets::class)->listMessages('ticket-uuid', ['sent_by' => 'u']), 'sent_by');
    }

    public function test_import_message_checks_sent_at(): void
    {
        $this->expectRejected(
            fn () => $this->resource(Tickets::class)->importMessage('ticket-uuid', '<p>Hi</p>', 'user', 'user-uuid', '29/02/2024'),
            'sent_at'
        );
    }

    // -- statuses --------------------------------------------------------------

    public function test_status_list_wraps_a_string_id(): void
    {
        $this->resource(TicketStatus::class)->list(['ids' => 'status-uuid']);

        $this->assertLastBody(['filter' => ['ids' => ['status-uuid']]]);
    }

    public function test_status_list_rejects_unknown_filters(): void
    {
        $this->expectRejected(fn () => $this->resource(TicketStatus::class)->list(['status' => 'open']), 'status');
    }
}
