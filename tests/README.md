# Testing Guide

## Running Tests

```bash
# Run everything (the redis group is excluded by phpunit.xml)
composer test

# Run a single suite
vendor/bin/phpunit tests/Feature
vendor/bin/phpunit tests/Unit

# Run a single file or method
vendor/bin/phpunit tests/Unit/Resources/FilesResourceTest.php
vendor/bin/phpunit --filter=test_for_product_filters_by_product_subject

# Run the Redis-dependent tests (requires a running Redis)
vendor/bin/phpunit --group=redis
```

### Coverage

```bash
vendor/bin/phpunit --coverage-html build/coverage
vendor/bin/phpunit --coverage-text
vendor/bin/phpunit --coverage-clover build/logs/clover.xml
```

## Test Structure

```
tests/
├── Feature/                            # Integration tests
│   ├── AuthenticationTest.php          # OAuth URL generation, token set/clear
│   ├── CompaniesResourceTest.php       # Resource wiring and capability flags
│   └── ConfigurationValidatorTest.php  # Config validation and reporting
│
├── Unit/
│   ├── Resources/
│   │   └── FilesResourceTest.php       # Request payload assertions
│   └── Services/
│       ├── ErrorHandlerTest.php        # Exception mapping, retry behaviour
│       ├── RateLimiterTest.php         # Sliding window (group: redis)
│       ├── TokenServiceTest.php        # Store, read, expire, clear
│       └── TokenServiceCacheIntegrityTest.php  # v2.1.1 cache poisoning regression
│
├── Support/
│   └── RecordingApiClient.php          # TeamleaderSDK double that records requests
│
├── ResourceTestCase.php                # Base class for payload assertions
└── TestCase.php                        # Base class (Orchestra Testbench)
```

## Conventions

**Method naming.** Test methods are prefixed `test_` and use snake_case:
`test_throws_rate_limit_exception_for429`. The `/** @test */` annotation is not
used anywhere in this suite — don't introduce it.

**Attributes over annotations.** PHP 8 attributes are used for PHPUnit metadata:

```php
use PHPUnit\Framework\Attributes\Group;

#[Group('redis')]
class RateLimiterTest extends TestCase { /* ... */ }
```

**Which base class.** Extend `TestCase` for services and anything that doesn't
build a request. Extend `ResourceTestCase` when the thing under test is the
payload a resource produces.

## Testing Resources

Most bugs this SDK has shipped were in the payload, not the response: a body key
the API silently ignores (`include` where it wants `includes`), a wrong shape (a
sort string array instead of objects), or a filter that was dropped without
warning. The API answers `200` to all of these, so the only way to catch them is
to assert on what the SDK *builds*.

`ResourceTestCase` wires up a `RecordingApiClient` — a `TeamleaderSDK` subclass
that overrides `request()` to record the call and return a canned response. No
network, no token, no Redis.

```php
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

final class FilesResourceTest extends ResourceTestCase
{
    public function test_for_product_filters_by_product_subject(): void
    {
        $this->resource(Files::class)->forProduct('product-uuid');

        $this->assertLastEndpoint('files.list');
        $this->assertLastBodyHas('filter.subject.type', 'product');
    }
}
```

### Available assertions

| Assertion | Purpose |
|---|---|
| `assertLastEndpoint(string)` | The endpoint of the most recent call |
| `assertEndpointCalled(string)` | An endpoint was called at some point |
| `assertLastBodyHas(string $path, mixed $value = null)` | Dot-notation path exists, optionally with a value |
| `assertLastBodyMissing(string $path)` | Dot-notation path is absent |
| `assertLastBody(array)` | Exact body match |
| `assertLastBodyKeys(array)` | Top-level keys, order-insensitive |
| `assertRequestCount(int)` | Number of requests made |
| `assertNoRequestMade()` | Nothing reached the API |

Prefer `assertLastBodyHas()` over `assertLastBody()` — an exact match couples the
test to every default the SDK applies, so a change to pagination defaults breaks
a test about sorting.

`assertNoRequestMade()` is the one to reach for when testing client-side
validation. Pair it with `try`/`finally` so it still runs when the expected
exception is thrown:

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

### Queueing responses

The recording client returns `['data' => [], 'headers' => []]` by default. Queue
something specific when the code under test reads the response:

```php
$this->api->queueListResponse([
    ['id' => 'file-uuid', 'name' => 'datasheet.pdf'],
]);

$response = $this->resource(Files::class)->forProduct('product-uuid');
```

`queueResponse(array)`, `queueResponses(array)` and `setDefaultResponse(array)`
are also available. Queued responses are consumed in order; once exhausted, the
default is returned again.

## Test Environment

`TestCase::getEnvironmentSetUp()` sets:

- `teamleader.client_id`, `client_secret`, `redirect_uri` — test values
- `teamleader.caching.enabled` — `false`
- `teamleader.rate_limiting.enabled` — `false`
- `teamleader.error_handling.throw_exceptions` — `true`
- `cache.default` — `array`

It does **not** set up a database. Anything needing one has to configure it
itself:

```php
protected function getEnvironmentSetUp($app): void
{
    parent::getEnvironmentSetUp($app);

    $app['config']->set('database.default', 'testing');
    $app['config']->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
}
```

### Redis tests

`RateLimiterTest` needs a real Redis and is tagged `#[Group('redis')]`, which
`phpunit.xml` excludes by default. It uses DB 15 to avoid colliding with
application data, and calls `reset()` in `setUp()` so tests stay isolated
despite sharing an instance.

Locally, run Redis on `127.0.0.1:6379` or set `REDIS_HOST` in `.env.testing`.

## Continuous Integration

The workflow lives in `.github/workflows/tests.yml` and runs the suite twice:
once with the `redis` group excluded, once with only that group and a
`redis:7-alpine` service wired in.

**Keep the matrix in sync with `composer.json`.** Laravel 10 and 11 were dropped
at v2.0 — both carry unpatched CVEs and Composer's security advisories block
installation — so they must not appear here.

```yaml
strategy:
  matrix:
    php: ['8.4', '8.5']
    laravel: ['12.*', '13.*']
```

## Coverage Goals

Current state:

- ✅ Services: token handling, error mapping, rate limiting
- ✅ Payload assertions for resources (infrastructure in place)
- ⚠️ Only one resource has payload tests — the rest are uncovered

Targets:

| Area | Target |
|---|---|
| Error handling | 100% |
| Services | 90% |
| Resources | 70% (CRUD paths) |
| Overall | 80% |

Priority order: services first, then the resources most used in production
(Companies, Contacts, Deals, Invoices), then the long tail.

## Contributing Tests

When fixing a bug, write the test that would have caught it before writing the
fix. For payload bugs that means asserting on the body — a test that only checks
the method returns an array would have passed against every one of the defects
listed at the top of this file.

Before committing:

```bash
vendor/bin/pint
composer test
```

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing Guide](https://laravel.com/docs/testing)
- [Orchestra Testbench](https://github.com/orchestral/testbench)
- [Contributing Guidelines](../CONTRIBUTING.md)

## Questions?

- **Issues:** [GitHub Issues](https://github.com/mcore-services-bv/teamleader-sdk/issues)
- **Discussions:** [GitHub Discussions](https://github.com/mcore-services-bv/teamleader-sdk/discussions)
- **Email:** help@mcore-services.be
