<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\CRM;

use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\Resources\CRM\Contacts;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * The write-side constants on Contacts and Companies, compared against the
 * specification fixture.
 *
 * SpecParityTest covers the list side (filters, sort, includes) for every
 * resource. It does not yet cover create/update bodies, so the field lists
 * and enums the CRM resources validate against are pinned here. Values come
 * from contract.json, never from literals in this file — when the
 * specification changes an enum, this fails until the constant follows.
 */
#[Group('spec-contract')]
final class CrmSpecContractTest extends TestCase
{
    private static ?SpecContract $spec = null;

    private static function spec(): SpecContract
    {
        return self::$spec ??= new SpecContract;
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function enums(): array
    {
        return [
            'contacts.add emails[].type' => ['contacts.add', 'emails[].type', Contacts::EMAIL_TYPES],
            'contacts.add telephones[].type' => ['contacts.add', 'telephones[].type', Contacts::TELEPHONE_TYPES],
            'contacts.add addresses[].type' => ['contacts.add', 'addresses[].type', Contacts::ADDRESS_TYPES],
            'contacts.add gender' => ['contacts.add', 'gender', Contacts::GENDERS],
            'contacts.update gender' => ['contacts.update', 'gender', Contacts::GENDERS],
            'contacts.list filter.email.type' => ['contacts.list', 'filter.email.type', Contacts::FILTER_EMAIL_TYPES],
            'contacts.list filter.status' => ['contacts.list', 'filter.status', Contacts::STATUSES],
            'companies.add emails[].type' => ['companies.add', 'emails[].type', Companies::EMAIL_TYPES],
            'companies.add telephones[].type' => ['companies.add', 'telephones[].type', Companies::TELEPHONE_TYPES],
            'companies.add addresses[].type' => ['companies.add', 'addresses[].type', Companies::ADDRESS_TYPES],
            'companies.add preferred_currency' => ['companies.add', 'preferred_currency', Companies::CURRENCIES],
            'companies.update preferred_currency' => ['companies.update', 'preferred_currency', Companies::CURRENCIES],
            'companies.list filter.email.type' => ['companies.list', 'filter.email.type', Companies::FILTER_EMAIL_TYPES],
            'companies.list filter.status' => ['companies.list', 'filter.status', Companies::STATUSES],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");

        sort($declared);
        sort($constant);

        $this->assertSame($declared, $constant, "The SDK's {$path} values for {$endpoint} have drifted from the specification.");
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function writeFields(): array
    {
        return [
            'contacts.add' => ['contacts.add', Contacts::WRITE_FIELDS],
            'contacts.update' => ['contacts.update', [...Contacts::WRITE_FIELDS, 'id']],
            'companies.add' => ['companies.add', Companies::WRITE_FIELDS],
            'companies.update' => ['companies.update', [...Companies::WRITE_FIELDS, 'id']],
        ];
    }

    #[DataProvider('writeFields')]
    public function test_write_fields_match_the_specification(string $endpoint, array $fields): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['properties'];

        sort($declared);
        sort($fields);

        $this->assertSame(
            $declared,
            $fields,
            "{$endpoint} fields have drifted. A field missing here is rejected client-side; "
            .'a field the specification dropped would be sent and silently ignored.'
        );
    }

    public function test_required_fields_match_the_specification(): void
    {
        $this->assertSame(['last_name'], self::spec()->endpoint('contacts.add')['request']['required']);
        $this->assertSame(['name'], self::spec()->endpoint('companies.add')['request']['required']);
    }

    public function test_contacts_company_id_filter_is_nullable(): void
    {
        $this->assertTrue(
            self::spec()->endpoint('contacts.list')['request']['filters']['company_id']['nullable'] ?? false,
            'contacts.list no longer declares company_id as nullable; Contacts::withoutCompany() relies on it.'
        );
    }
}
