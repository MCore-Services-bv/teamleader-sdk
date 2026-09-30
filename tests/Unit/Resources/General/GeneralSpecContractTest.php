<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\General;

use McoreServices\TeamleaderSDK\Resources\General\Currencies;
use McoreServices\TeamleaderSDK\Resources\General\CustomFields;
use McoreServices\TeamleaderSDK\Resources\General\DayOffTypes;
use McoreServices\TeamleaderSDK\Resources\General\Departments;
use McoreServices\TeamleaderSDK\Resources\General\DocumentTemplates;
use McoreServices\TeamleaderSDK\Resources\General\EmailTracking;
use McoreServices\TeamleaderSDK\Resources\General\Notes;
use McoreServices\TeamleaderSDK\Resources\General\Users;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Write-side field lists and enums of the General category, compared against
 * the specification fixture.
 */
#[Group('spec-contract')]
final class GeneralSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function writeFields(): array
    {
        return [
            'customFieldDefinitions.create' => ['customFieldDefinitions.create', CustomFields::CREATE_FIELDS],
            'dayOffTypes.create' => ['dayOffTypes.create', DayOffTypes::WRITE_FIELDS],
            'dayOffTypes.update' => ['dayOffTypes.update', [...DayOffTypes::WRITE_FIELDS, 'id']],
            'emailTracking.create' => ['emailTracking.create', EmailTracking::CREATE_FIELDS],
            'notes.create' => ['notes.create', Notes::CREATE_FIELDS],
            'notes.update' => ['notes.update', [...Notes::UPDATE_FIELDS, 'id']],
        ];
    }

    #[DataProvider('writeFields')]
    public function test_write_fields_match_the_specification(string $endpoint, array $fields): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint($endpoint)['request']['properties'], $fields);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function enums(): array
    {
        return [
            'currencies base' => ['currencies.exchangeRates', 'base', Currencies::CURRENCIES],
            'departments status' => ['departments.list', 'filter.status[]', Departments::STATUSES],
            'document types' => ['documentTemplates.list', 'filter.document_type', DocumentTemplates::DOCUMENT_TYPES],
            'document statuses' => ['documentTemplates.list', 'filter.status[]', DocumentTemplates::STATUSES],
            'email tracking subject' => ['emailTracking.create', 'subject.type', EmailTracking::SUBJECT_TYPES],
            'email tracking filter' => ['emailTracking.list', 'filter.subject.type', EmailTracking::SUBJECT_TYPES],
            'notes create subject' => ['notes.create', 'subject.type', Notes::CREATE_SUBJECT_TYPES],
            'notes list subject' => ['notes.list', 'filter.subject.type', Notes::LIST_SUBJECT_TYPES],
            'users status' => ['users.list', 'filter.status[]', Users::STATUSES],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");
        $this->assertEqualsCanonicalizing($declared, $constant);
    }

    public function test_user_filters_and_includes_match(): void
    {
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('users.info')['request']['includes'], Users::INFO_INCLUDES);
        $this->assertEqualsCanonicalizing(self::spec()->endpoint('users.listDaysOff')['request']['filter_keys'], Users::DAYS_OFF_FILTERS);
    }
}
