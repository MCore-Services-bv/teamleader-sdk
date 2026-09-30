<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Expenses;

use McoreServices\TeamleaderSDK\Resources\Expenses\BookkeepingSubmissions;
use McoreServices\TeamleaderSDK\Resources\Expenses\ExpenseDocument;
use McoreServices\TeamleaderSDK\Resources\Expenses\Expenses;
use McoreServices\TeamleaderSDK\Resources\Expenses\IncomingCreditNotes;
use McoreServices\TeamleaderSDK\Resources\Expenses\IncomingInvoices;
use McoreServices\TeamleaderSDK\Resources\Expenses\Receipts;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Field lists and enums of the Expenses category, compared against the
 * specification fixture.
 */
#[Group('spec-contract')]
final class ExpensesSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    /**
     * @return array<string, array{0: string, 1: list<string>, 2: list<string>}>
     */
    public static function documents(): array
    {
        return [
            'incomingInvoices' => ['incomingInvoices', IncomingInvoices::WRITE_FIELDS, IncomingInvoices::PAYMENT_STATUSES],
            'incomingCreditNotes' => ['incomingCreditNotes', IncomingCreditNotes::WRITE_FIELDS, IncomingCreditNotes::PAYMENT_STATUSES],
            'receipts' => ['receipts', Receipts::WRITE_FIELDS, Receipts::PAYMENT_STATUSES],
        ];
    }

    #[DataProvider('documents')]
    public function test_write_fields_match(string $base, array $fields, array $statuses): void
    {
        foreach (["{$base}.add" => $fields, "{$base}.update" => [...$fields, 'id']] as $endpoint => $expected) {
            $declared = self::spec()->endpoint($endpoint)['request']['properties'];

            sort($declared);
            sort($expected);

            $this->assertSame($declared, $expected, "{$endpoint} fields have drifted.");
        }

        $this->assertSame(['currency', 'title'], self::spec()->endpoint("{$base}.add")['request']['required']);
    }

    #[DataProvider('documents')]
    public function test_payment_statuses_match_the_info_response(string $base, array $fields, array $statuses): void
    {
        $this->assertSame(
            'string, enum: '.implode('|', $statuses),
            self::spec()->endpoint("{$base}.info")['response']['fields']['data.payment_status']
        );
    }

    #[DataProvider('documents')]
    public function test_payment_endpoint_fields_match(string $base, array $fields, array $statuses): void
    {
        foreach (["{$base}.registerPayment" => ExpenseDocument::REGISTER_PAYMENT_FIELDS, "{$base}.updatePayment" => ExpenseDocument::UPDATE_PAYMENT_FIELDS] as $endpoint => $expected) {
            $declared = self::spec()->endpoint($endpoint)['request']['properties'];
            $expected = [...$expected, 'id'];

            sort($declared);
            sort($expected);

            $this->assertSame($declared, $expected, "{$endpoint} fields have drifted.");
        }

        $this->assertSame(['id', 'payment_id'], self::spec()->endpoint("{$base}.updatePayment")['request']['required']);
    }

    #[DataProvider('documents')]
    public function test_currencies_match(string $base, array $fields, array $statuses): void
    {
        $declared = self::spec()->endpoint("{$base}.add")['request']['enums']['currency.code'];
        $constant = ExpenseDocument::CURRENCIES;

        sort($declared);
        sort($constant);

        $this->assertSame($declared, $constant);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function listEnums(): array
    {
        return [
            'source_types' => ['filter.source_types[]', Expenses::SOURCE_TYPES],
            'review_statuses' => ['filter.review_statuses[]', Expenses::REVIEW_STATUSES],
            'bookkeeping_statuses' => ['filter.bookkeeping_statuses[]', Expenses::BOOKKEEPING_STATUSES],
            'payment_statuses' => ['filter.payment_statuses[]', Expenses::PAYMENT_STATUSES],
            'supplier.type' => ['filter.supplier.type', Expenses::SUPPLIER_TYPES],
            'document_date.operator' => ['filter.document_date.operator', Expenses::DATE_OPERATORS],
            'paid_at.operator' => ['filter.paid_at.operator', Expenses::DATE_OPERATORS],
        ];
    }

    #[DataProvider('listEnums')]
    public function test_expenses_list_enums_match(string $path, array $constant): void
    {
        $declared = self::spec()->endpoint('expenses.list')['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "expenses.list no longer declares {$path}.");

        sort($declared);
        sort($constant);

        $this->assertSame($declared, $constant);
    }

    public function test_bookkeeping_subject_types_match(): void
    {
        $declared = self::spec()->endpoint('bookkeepingSubmissions.list')['request']['enums']['filter.subject.type'];
        $constant = BookkeepingSubmissions::SUBJECT_TYPES;

        sort($declared);
        sort($constant);

        $this->assertSame($declared, $constant);
    }
}
