<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Tickets;

use McoreServices\TeamleaderSDK\Resources\Tickets\Tickets;
use McoreServices\TeamleaderSDK\Resources\Tickets\TicketStatus;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of the Tickets category, compared against
 * the specification fixture.
 */
#[Group('spec-contract')]
final class TicketsSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    public function test_write_fields_match(): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('tickets.create')['request']['properties'], Tickets::CREATE_FIELDS);
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('tickets.update')['request']['properties'], [...Tickets::UPDATE_FIELDS, 'id']);
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('tickets.create')['request']['required'], Tickets::REQUIRED_ON_CREATE);
    }

    public function test_message_filters_match(): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('tickets.listMessages')['request']['filter_keys'], Tickets::MESSAGE_FILTERS);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function enums(): array
    {
        return [
            'create customer' => ['tickets.create', 'customer.type', Tickets::CUSTOMER_TYPES],
            'create initial_reply' => ['tickets.create', 'initial_reply', Tickets::INITIAL_REPLY_OPTIONS],
            'list relates_to' => ['tickets.list', 'filter.relates_to.type', Tickets::CUSTOMER_TYPES],
            'messages type' => ['tickets.listMessages', 'filter.type', Tickets::MESSAGE_TYPES],
            'import sent_by' => ['tickets.importMessage', 'sent_by.type', Tickets::SENT_BY_TYPES],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");
        $this->assertEqualsCanonicalizing($declared, $constant);
    }

    public function test_status_types_match_the_response_enum(): void
    {
        $field = self::spec()->endpoint('ticketStatus.list')['response']['fields']['data[].status'];

        $this->assertSame('string, enum: '.implode('|', TicketStatus::STATUS_TYPES), $field);
    }
}
