<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Other;

use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Resources\Other\CloudPlatforms;
use McoreServices\TeamleaderSDK\Resources\Other\Migrate;
use McoreServices\TeamleaderSDK\Resources\Other\Webhooks;
use McoreServices\TeamleaderSDK\Resources\Templates\MailTemplates;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Enums of the Files, Templates and Other categories, compared against the
 * specification fixture. The webhook event list is the one most likely to
 * move: Teamleader adds event types regularly.
 */
#[Group('spec-contract')]
final class OtherSpecContractTest extends TestCase
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
            'webhooks.register types' => ['webhooks.register', 'types[]', Webhooks::EVENT_TYPES],
            'webhooks.unregister types' => ['webhooks.unregister', 'types[]', Webhooks::EVENT_TYPES],
            'cloudPlatforms.url type' => ['cloudPlatforms.url', 'type', CloudPlatforms::TYPES],
            'migrate.id type' => ['migrate.id', 'type', Migrate::RESOURCE_TYPES],
            'migrate.activityType type' => ['migrate.activityType', 'type', Migrate::ACTIVITY_TYPES],
            'mailTemplates.list type' => ['mailTemplates.list', 'filter.type', MailTemplates::TYPES],
            'files.list sort order' => ['files.list', 'sort[].order', Files::SORT_ORDERS],
        ];
    }

    #[DataProvider('enums')]
    public function test_enum_matches_the_specification(string $endpoint, string $path, array $constant): void
    {
        $declared = self::spec()->endpoint($endpoint)['request']['enums'][$path] ?? null;

        $this->assertNotNull($declared, "{$endpoint} no longer declares an enum at {$path}.");
        $this->assertEqualsCanonicalizing($declared, $constant, "{$path} on {$endpoint} has drifted from the specification.");
    }
}
