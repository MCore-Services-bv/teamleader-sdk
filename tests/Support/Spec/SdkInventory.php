<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support\Spec;

use McoreServices\TeamleaderSDK\Support\ResourceCatalog;

/**
 * The SDK's resource catalog, under the name the audit and the reference
 * generator have always used.
 *
 * The implementation moved to src/Support/ResourceCatalog in v3.0 so the CLI
 * can use it at runtime. This subclass keeps SpecAuditor, ReferenceGenerator,
 * bin/spec-audit, bin/docs and the tests unchanged.
 */
final class SdkInventory extends ResourceCatalog {}
