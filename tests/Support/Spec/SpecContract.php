<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support\Spec;

use RuntimeException;

/**
 * Read access to tests/Fixtures/specification/contract.json.
 *
 * The fixture is a mechanical extract of @teamleader/focus-api-specification,
 * produced by generate-spec-fixtures.mjs. Nothing here interprets it — the
 * judgement lives in SpecAuditor, so the two can be reviewed separately.
 */
final class SpecContract
{
    private array $contract;

    public function __construct(?string $path = null)
    {
        $path ??= self::defaultPath();

        if (! is_file($path)) {
            throw new RuntimeException(
                "Specification contract not found at {$path}. Regenerate it with: npm install && npm run spec:fixtures"
            );
        }

        $this->contract = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function defaultPath(): string
    {
        return dirname(__DIR__, 2).'/Fixtures/specification/contract.json';
    }

    public function version(): string
    {
        return (string) $this->contract['specification_version'];
    }

    public function has(string $endpoint): bool
    {
        return isset($this->contract['endpoints'][$endpoint]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function endpoint(string $endpoint): ?array
    {
        return $this->contract['endpoints'][$endpoint] ?? null;
    }

    /**
     * @return list<string>
     */
    public function endpointNames(): array
    {
        return array_keys($this->contract['endpoints']);
    }

    public function isDeprecated(string $endpoint): bool
    {
        return (bool) ($this->contract['endpoints'][$endpoint]['deprecated'] ?? false);
    }

    /**
     * The resource prefix of an endpoint: everything before the final dot.
     *
     * `projects-v2/materials.assign` → `projects-v2/materials`
     */
    public static function prefixOf(string $endpoint): string
    {
        $position = strrpos($endpoint, '.');

        return $position === false ? $endpoint : substr($endpoint, 0, $position);
    }

    /**
     * Every endpoint sharing the given prefix.
     *
     * @return list<string>
     */
    public function endpointsFor(string $prefix): array
    {
        return array_values(array_filter(
            $this->endpointNames(),
            fn (string $endpoint) => self::prefixOf($endpoint) === $prefix
        ));
    }
}
