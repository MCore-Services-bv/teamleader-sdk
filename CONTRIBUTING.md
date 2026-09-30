# Contributing to Teamleader SDK

Thank you for considering contributing! This document covers how the project
works and what makes a change easy to accept.

## 🤝 Code of Conduct

This project follows the principles of respect, inclusivity, and professionalism.
Please be considerate and constructive in all interactions.

## 📋 How Can I Contribute?

### Reporting Bugs

Bug reports have been the single most valuable contribution to this project.
Several fixes in v2.2.0 came directly from reports that explained what was
expected, what happened, and how the reporter worked around it — that last part
in particular often reveals more than a stack trace.

Before filing, please check existing issues to avoid duplicates.

**Use the issue template**, or include:

```markdown
**Description:**
Brief description of the bug

**Steps to Reproduce:**
1. Step one
2. Step two

**Expected Behavior:**
What you expected to happen

**Actual Behavior:**
What actually happened

**Environment:**
- PHP Version: 8.4.x
- Laravel Version: 12.x / 13.x
- Package Version: 2.2.x
- Teamleader Account Type: Focus (projects-v2 / legacy)

**Additional Context:**
Workarounds tried, related endpoints, anything that narrowed it down
```

**A note on silent bugs.** The Teamleader API answers `200` to filters, includes
and sort fields it does not recognise, returning the complete unfiltered set. If
a call returns *more* than you expected, or ignores something you passed, that is
worth reporting even without an error message — it is the most common defect
class in this SDK's history.

### Suggesting Features

Feature suggestions are welcome. Please:
- Check whether it already exists or is on the roadmap
- Explain the use case, not just the API surface
- Note whether the Teamleader API supports it — a link to the endpoint helps
- Consider backward compatibility

### Submitting Pull Requests

1. **Fork** the repository
2. **Create a branch** from `main`: `git checkout -b feature/my-feature`
3. **Make your changes** following the standards below
4. **Write tests** — see [Testing](#testing)
5. **Update the docs** — run `composer docs:build`, and edit the guides in `docs/` if behaviour changed
6. **Commit** with a clear message
7. **Push** and open a Pull Request against `main`

## 🔧 Development Setup

### Prerequisites

- PHP 8.4 or higher (CI tests 8.4 – 8.5)
- Composer
- Laravel 12 or 13
- Redis, for the rate limiter tests

### Installation

```bash
git clone https://github.com/your-username/teamleader-sdk.git
cd teamleader-sdk

composer install
```

### Running Tests

```bash
# Everything except the Redis-dependent tests
composer test

# The Redis group — needs a running Redis
vendor/bin/phpunit --group redis

# A single file or method
vendor/bin/phpunit tests/Unit/Resources/DealsResourceTest.php
vendor/bin/phpunit --filter=test_unknown_filter_keys_throw
```

Redis defaults to `127.0.0.1:6379`, database 15. Override with environment
variables if yours lives elsewhere — Laravel Herd, for example, serves it on
6380:

```bash
REDIS_PORT=6380 vendor/bin/phpunit --group redis
```

The suite uses `predis/predis` by default so the phpredis C extension is not
required. Set `REDIS_CLIENT=phpredis` if you have it and would rather use it.

### Code Style

Laravel Pint, PSR-12 with Laravel conventions.

```bash
# Auto-fix
composer format

# Check without fixing — this is what CI runs
vendor/bin/pint --test
```

CI gates style on **PHP 8.4**. If you have a different version as your default,
Pint output can differ on edge cases, so a locally clean commit can still fail
CI. The repository's `pre-commit` hook looks for an 8.4 binary and warns if it
cannot find one.

## 📝 Coding Guidelines

### PHP Standards

- **PSR-12** for code style, enforced by Pint
- **PSR-4** for autoloading
- **Type hints** on parameters and return types where the existing signature
  allows it
- **Docblocks** on public and protected methods, explaining *why* where the
  behaviour is surprising

> `declare(strict_types=1)` is used in the test suite but not consistently across
> `src/`. New files may add it; retrofitting existing resource classes is a
> separate change and would need its own testing.

### Code Structure

```php
<?php

class Example
{
    // 1. Constants
    public const DEFAULT_VALUE = 'value';

    // 2. Properties (public -> protected -> private)
    public string $publicProperty;
    protected array $protectedProperty = [];
    private int $privateProperty;

    // 3. Constructor
    public function __construct() {}

    // 4. Public methods
    public function publicMethod(): void {}

    // 5. Protected methods
    protected function protectedMethod(): void {}

    // 6. Private methods
    private function privateMethod(): void {}
}
```

### Naming Conventions

- **Classes**: `PascalCase` (e.g. `TokenService`)
- **Methods**: `camelCase` (e.g. `getValidAccessToken()`)
- **Variables**: `camelCase`
- **Constants**: `SCREAMING_SNAKE_CASE`
- **Test methods**: `snake_case` with a `test_` prefix

## 🧪 Testing

### Which base class

| Base class | Use for |
|---|---|
| `TestCase` | Services, and anything that does not build a request |
| `ResourceTestCase` | Anything where the thing under test is the payload a resource produces |

### Testing resources

Most defects this SDK has shipped were in the **payload**, not the response: a
body key the API silently ignores (`include` where it wants `includes`), a wrong
shape (a sort string array instead of objects), or a filter that was dropped
without warning. The API answers `200` to all of these, so the only way to catch
them is to assert on what the SDK *builds*.

`ResourceTestCase` wires up a `RecordingApiClient` — a `TeamleaderSDK` subclass
that records the call instead of sending it. No network, no token, no Redis.

```php
use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

final class DealsResourceTest extends ResourceTestCase
{
    public function test_include_option_is_sent_as_includes_plural(): void
    {
        $this->resource(Deals::class)->list([], ['include' => 'custom_fields']);

        $this->assertLastBodyHas('includes', 'custom_fields');
        $this->assertLastBodyMissing('include');
    }
}
```

Available assertions: `assertLastEndpoint()`, `assertEndpointCalled()`,
`assertLastBodyHas()`, `assertLastBodyMissing()`, `assertLastBody()`,
`assertLastBodyKeys()`, `assertRequestCount()`, `assertNoRequestMade()`.

Prefer `assertLastBodyHas()` over `assertLastBody()` — an exact match couples the
test to every default the SDK applies, so a change to pagination defaults breaks
a test about sorting.

For client-side validation, pair the expected exception with
`assertNoRequestMade()` in a `finally` block so it still runs:

```php
public function test_rejects_an_invalid_subject_type(): void
{
    $this->expectException(InvalidArgumentException::class);

    try {
        $this->resource(Files::class)->forSubject('banana', 'some-uuid');
    } finally {
        $this->assertNoRequestMade();
    }
}
```

### Write tests against the API, not against the code

This is the guideline that matters most here.

A test written from the current implementation locks in whatever that
implementation happens to do. `CompaniesResourceTest` once asserted that
`addresses` and `responsible_user` were valid sideload includes. They never were —
the API had never accepted them — and the test kept six phantom includes alive
until someone checked the specification.

So: verify against
[`@teamleader/focus-api-specification`](https://www.npmjs.com/package/@teamleader/focus-api-specification),
which is machine-readable and authoritative, rather than against the docs pages
or the existing code.

The version is pinned in `package.json`. Everything below reads from
`tests/Fixtures/specification/contract.json`, a mechanical extract of it:

```bash
npm install                 # installs the pinned specification
npm run spec:fixtures       # regenerates contract.json, orders.json, API-list-endpoint-contract.md
composer spec:audit         # Markdown report of every divergence, by category
composer spec:audit -- --category=CRM --severity=error
composer spec:audit -- --new  # only what is not in the baseline yet
composer spec:check         # the CI gate: exits 1 on anything new or stale
```

`SpecParityTest` gates the audit against
`tests/Fixtures/specification/baseline.json`:

- a divergence **not** in the baseline fails the suite — a regression, or a
  specification change that needs a decision
- a baseline entry that is **no longer found** fails too — it was fixed, so
  delete the entry, or the defect could return unnoticed

Divergences that are deliberate (an undeclared filter the API honours, verified
against a live account) move from `open` to `accepted` with a one-line reason.
After a specification bump: regenerate, read `composer spec:audit -- --new`,
fix or accept, then `composer spec:baseline`.

CI runs the same checks once per push, in the *Specification parity* job:
the committed fixtures must be exactly what `npm run spec:fixtures` produces
from the pinned version, and `composer spec:check` must pass. A weekly
workflow (`spec-watch.yml`) audits against the newest published specification
and opens an issue when it differs, so a bump starts from a list of findings.

The raw YAML is at `node_modules/@teamleader/focus-api-specification/dist/`.

When a test fails after you change something, the question to ask is: *which one
did I check against the API?*

### What to cover for a resource

- `list()` sends the expected filter, sort and page shapes
- Unknown filter keys throw
- Includes go out as `includes` (plural), never `include`
- `info()` / `create()` / `update()` / `delete()` hit the right endpoint with the
  right body
- Client-side validation throws **before** dispatch

## 🎯 Pull Request Guidelines

### PR Checklist

- [ ] `vendor/bin/pint --test` passes
- [ ] `composer test` passes
- [ ] New behaviour has tests
- [ ] Bug fixes have a regression test
- [ ] `composer docs:check` passes (run `composer docs:build` to fix)
- [ ] Guides in `docs/` updated if behaviour changed
- [ ] CHANGELOG.md updated
- [ ] Breaking changes clearly documented

### Commit Message Format

The repository history uses a `Type: subject` form:

```
Fixed: Deals - route filters through buildFilters, send includes plural
Added: nextgenProjects() alias for the projects-v2 resource
Docs: Projects/LegacyProjects - document the SDK/API/webhook naming split
```

Types: `Fixed`, `Added`, `Changed`, `Removed`, `Docs`, `Tests`, `Chore`.

Name the resource and say what changed — future you will be reading this while
bisecting.

### PR Description Template

```markdown
## Description
Brief description of changes

## Motivation
Why are these changes needed?

## Verified against
Link to the endpoint in the API specification, if this concerns API behaviour

## Changes Made
- Change 1
- Change 2

## Breaking Changes
None / List any breaking changes

## Related Issues
Fixes #123

## Checklist
- [ ] Tests pass
- [ ] Docs regenerated / updated
- [ ] CHANGELOG updated
```

## 🏗️ Architecture Guidelines

### Adding a resource

```php
<?php

namespace McoreServices\TeamleaderSDK\Resources\YourCategory;

use McoreServices\TeamleaderSDK\Resources\Resource;

class YourResource extends Resource
{
    protected string $description = 'Manage [resources] in Teamleader Focus';

    // Capabilities — these must match what the endpoint actually supports
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsPagination = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSorting = true;
    protected bool $supportsSideloading = false;

    // Filter keys, verified against the specification. buildFilters() uses the
    // keys of this array as its whitelist, so the two cannot drift apart.
    protected array $commonFilters = [
        'ids'           => 'Array of UUIDs',
        'updated_since' => 'ISO 8601 datetime',
    ];

    protected array $availableSortFields = [
        'created_at' => 'Sort by creation date',
    ];

    protected function getBasePath(): string
    {
        return 'yourEndpoint';
    }
}
```

Then register it in `TeamleaderSDK::$resources` **and** add a matching `@method`
annotation to `Facades\Teamleader`. The two are checked for parity — a resource
without an annotation gets no IDE completion.

### Three rules from experience

**Capability flags must match reality.** If `supportsSorting` is `true`, `list()`
must actually build a sort parameter. Several resources have declared a
capability they did not implement, and `getCapabilities()` is part of the public
API — people build on it.

**Reject what the endpoint cannot use.** If a resource takes no filters, call
`rejectUnsupportedListArguments()` rather than accepting and discarding them. A
signature that lies is worse than a signature that throws, because the failure
surfaces much later somewhere else.

**Check write shape against read shape.** They are often different. `day` in,
`date` out on Closing Days. Option strings in, option objects out on Custom
Fields. `price_list_id` in, `price_list` out on Contacts. Verify both directions.

### Service Classes

Keep services focused and single-purpose:

```php
<?php

namespace McoreServices\TeamleaderSDK\Services;

class YourService
{
    public function __construct(
        private DependencyOne $dependency1,
        private DependencyTwo $dependency2
    ) {}

    public function doSomething(): mixed
    {
        // Implementation
    }
}
```

## 📚 Documentation

Documentation lives in `docs/` and is published to GitBook from `main` through
Git Sync, at [teamleader-sdk.mcore-services.dev](https://teamleader-sdk.mcore-services.dev/). It has two parts:

- **Guides** (`docs/getting-started`, `docs/guides`, `docs/project`) are written
  by hand. Change them in the same PR as the behaviour they describe.
- **The API reference** (`docs/reference`) is generated from the resource
  classes by `bin/docs`: the facade method, endpoints, filters, sort fields,
  includes, public constants, public methods with their docblocks, and
  `$usageExamples`. Do not edit it by hand.

```bash
composer docs:build    # regenerate docs/reference and the reference part of docs/SUMMARY.md
composer docs:check    # exits 1 when the committed reference does not match the code
```

`ReferenceDocsTest` and the CI *Specification parity* job both fail when the
reference is out of date. So the way to improve a reference page is to improve
the code it is generated from: a filter description in `$commonFilters`, a
clearer docblock, a better usage example.

If the API behaves in a way that would surprise someone, say so in the method's
docblock and say what to do instead. That note then appears in the reference.

## 🐛 Debugging Tips

### Inspect what the SDK sent

Often faster than reading logs:

```php
$api = new \McoreServices\TeamleaderSDK\Tests\Support\RecordingApiClient;
$deals = new \McoreServices\TeamleaderSDK\Resources\Deals\Deals($api);

$deals->list(['status' => ['open']]);

dump($api->lastEndpoint(), $api->lastBody());
```

### SDK Commands

```bash
php artisan teamleader:status
php artisan teamleader:config:validate
php artisan teamleader:health
php artisan teamleader:export-uuids
```

## 🤔 Questions?

- **Documentation**: [teamleader-sdk.mcore-services.dev](https://teamleader-sdk.mcore-services.dev/)
- **Issues**: [Existing issues](https://github.com/MCore-Services-bv/teamleader-sdk/issues)
- **Discussions**: [Start a discussion](https://github.com/MCore-Services-bv/teamleader-sdk/discussions)
- **Email**: help@mcore-services.be

## 📜 License

By contributing, you agree that your contributions will be licensed under the MIT
License.

## 🙏 Thank You!

Every contribution helps, no matter how small.

---

**Happy Coding!** 🚀
