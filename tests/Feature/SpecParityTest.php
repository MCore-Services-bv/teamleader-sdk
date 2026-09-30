<?php

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Tests\Support\Spec\Baseline;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SdkInventory;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecAuditor;
use McoreServices\TeamleaderSDK\Tests\Support\Spec\SpecContract;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Every registered resource, audited against @teamleader/focus-api-specification.
 *
 * SpecAuditor compares what each resource declares — endpoints it calls,
 * filters, sort fields, includes, capability flags, usage examples — against
 * contract.json, a mechanical extract of the specification. This test gates the
 * result against baseline.json:
 *
 *   - a finding not in the baseline fails: either a regression, or a
 *     specification change that needs a decision
 *   - a baseline entry no longer found fails: it has been fixed, so remove it,
 *     otherwise the defect could come back unnoticed
 *
 * The baseline is the audit's to-do list and should only ever shrink, apart
 * from specification bumps. Inspect it with `php bin/spec-audit`.
 */
#[Group('spec-contract')]
class SpecParityTest extends TestCase
{
    private SpecAuditor $auditor;

    private Baseline $baseline;

    private array $findings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditor = new SpecAuditor(new SpecContract, new SdkInventory);
        $this->baseline = new Baseline(Baseline::defaultPath());
        $this->findings = array_filter(
            $this->auditor->findings(),
            fn (array $finding) => $finding['severity'] !== 'info'
        );
    }

    public function test_contract_matches_the_pinned_specification_version(): void
    {
        $package = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/package.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            $package['devDependencies']['@teamleader/focus-api-specification'],
            $this->auditor->specificationVersion(),
            'contract.json was generated from a different specification version than '
            .'package.json pins. Run: npm install && npm run spec:fixtures'
        );
    }

    public function test_baseline_matches_the_contract_version(): void
    {
        $this->assertSame(
            $this->auditor->specificationVersion(),
            $this->baseline->version(),
            'baseline.json was written against a different specification version. '
            .'Review `php bin/spec-audit --new`, then run `php bin/spec-audit --write-baseline`.'
        );
    }

    public function test_every_registered_resource_resolves_its_endpoints(): void
    {
        $unresolved = [];

        foreach ((new SdkInventory)->resources() as $key => $resource) {
            foreach ($resource['dynamic_endpoints'] as $expression) {
                $unresolved[] = "{$key}: {$expression}";
            }
        }

        $this->assertSame(
            [],
            $unresolved,
            "The auditor could not resolve these request() endpoints, so they are not checked:\n"
            .implode("\n", $unresolved)
            ."\nUse \$this->getBasePath().'.action' or a string literal."
        );
    }

    public function test_no_divergence_outside_the_baseline(): void
    {
        $unexpected = $this->baseline->unexpected($this->findings);

        $this->assertSame(
            [],
            array_map(fn (array $finding) => $finding['message'], $unexpected),
            "New divergences from specification {$this->auditor->specificationVersion()}.\n"
            .'Fix them, or — if deliberate and verified against a live account — add '
            .'them to "accepted" in baseline.json with the reason.'
        );
    }

    public function test_baseline_has_no_stale_entries(): void
    {
        $stale = $this->baseline->stale($this->findings);

        $this->assertSame(
            [],
            $stale,
            'These baseline entries are no longer found — they have been fixed. '
            .'Remove them from baseline.json so the defect cannot return unnoticed.'
        );
    }
}
