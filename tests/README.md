# Testing Guide

## Running Tests

### Basic Test Execution

```bash
# Run all tests
composer test

# Run tests with coverage report
vendor/bin/phpunit --coverage-html coverage

# Run specific test suite
vendor/bin/phpunit tests/Feature
vendor/bin/phpunit tests/Unit

# Run specific test file
vendor/bin/phpunit tests/Feature/CompaniesResourceTest.php

# Run specific test method
vendor/bin/phpunit --filter=it_can_list_companies

# Run with verbose output
vendor/bin/phpunit --verbose
```

### Code Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html build/coverage

# Generate text coverage summary
vendor/bin/phpunit --coverage-text

# Generate Clover XML (for CI)
vendor/bin/phpunit --coverage-clover build/logs/clover.xml

# View HTML report (after generation)
open build/coverage/index.html
```

## Test Structure

```
tests/
├── Feature/                      # Integration tests
│   ├── AuthenticationTest.php    # OAuth flow testing
│   ├── CompaniesResourceTest.php # Companies resource tests
│   └── ConfigurationValidatorTest.php # Config validation tests
│
├── Unit/                         # Unit tests
│   └── Services/
│       ├── ErrorHandlerTest.php      # Error handling tests
│       ├── RateLimiterTest.php       # Rate limiter tests
│       └── TokenServiceTest.php      # Token service tests
│
└── TestCase.php                  # Base test class with helpers
```

## Test Categories

### Feature Tests
Integration tests that test complete workflows and resource interactions:
- Authentication flows
- Resource CRUD operations
- Configuration validation
- End-to-end scenarios

### Unit Tests
Isolated tests for individual components:
- Service classes
- Utility functions
- Error handlers
- Rate limiters

## Writing Tests

### Feature Test Example

```php
<?php

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CompaniesResourceTest extends TestCase
{
    private TeamleaderSDK $sdk;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sdk = new TeamleaderSDK();
        $this->sdk->setAccessToken('test_token');
    }

    /** @test */
    public function it_can_list_companies(): void
    {
        $companies = $this->sdk->companies()->list();
        
        $this->assertIsArray($companies);
        $this->assertArrayHasKey('data', $companies);
    }

    /** @test */
    public function it_can_get_company_info(): void
    {
        $companyId = 'test-uuid';
        $company = $this->sdk->companies()->info($companyId);
        
        $this->assertIsArray($company);
        $this->assertArrayHasKey('data', $company);
    }
}
```

### Unit Test Example

```php
<?php

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use Psr\Log\NullLogger;

class ErrorHandlerTest extends TestCase
{
    private TeamleaderErrorHandler $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandler = new TeamleaderErrorHandler(new NullLogger(), true);
    }

    /** @test */
    public function it_throws_validation_exception_for_422(): void
    {
        $this->expectException(ValidationException::class);

        $result = [
            'error' => true,
            'status_code' => 422,
            'message' => 'Validation failed',
            'errors' => ['Field is required']
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    /** @test */
    public function it_identifies_retryable_errors(): void
    {
        $serverError = new ServerException('Server error', 500);
        
        $this->assertTrue($this->errorHandler->isRetryableError($serverError));
    }
}
```

### Testing Best Practices

#### 1. Use Descriptive Test Names
```php
// Good - clear what's being tested
/** @test */
public function it_refreshes_expired_tokens(): void

// Bad - unclear purpose
/** @test */
public function test_tokens(): void
```

#### 2. Follow Arrange-Act-Assert Pattern
```php
/** @test */
public function it_filters_companies_by_status(): void
{
    // Arrange - set up test data
    $filters = ['status' => 'active'];
    
    // Act - perform the action
    $result = $this->sdk->companies()->list($filters);
    
    // Assert - verify the outcome
    $this->assertArrayHasKey('data', $result);
}
```

#### 3. Test Both Success and Failure Cases
```php
/** @test */
public function it_creates_company_successfully(): void
{
    $data = ['name' => 'Test Company'];
    $result = $this->sdk->companies()->create($data);
    
    $this->assertFalse($result['error']);
}

/** @test */
public function it_fails_to_create_company_without_name(): void
{
    $this->expectException(ValidationException::class);
    
    $this->sdk->companies()->create([]);
}
```

#### 4. Use Data Providers for Similar Tests
```php
/**
 * @test
 * @dataProvider invalidEmailProvider
 */
public function it_rejects_invalid_email_formats(string $email): void
{
    $this->expectException(ValidationException::class);
    
    $this->sdk->contacts()->create([
        'first_name' => 'John',
        'email' => $email
    ]);
}

public static function invalidEmailProvider(): array
{
    return [
        ['not-an-email'],
        ['missing@domain'],
        ['@nodomain.com'],
    ];
}
```

## Test Environment Setup

### Configuration

Tests use the `TestCase` base class which automatically:
- Sets up SQLite in-memory database
- Configures test Teamleader credentials
- Enables exception throwing
- Disables rate limiting and caching for tests

### Custom Test Environment

```php
// In your test class
protected function getEnvironmentSetUp($app): void
{
    parent::getEnvironmentSetUp($app);
    
    // Override config for this test
    $app['config']->set('teamleader.api.timeout', 60);
    $app['config']->set('teamleader.caching.enabled', false);
}
```

### Mocking HTTP Responses

```php
use Illuminate\Support\Facades\Http;

/** @test */
public function it_handles_api_errors_gracefully(): void
{
    Http::fake([
        'api.focus.teamleader.eu/*' => Http::response([
            'errors' => [['title' => 'Not found']]
        ], 404)
    ]);
    
    $result = $this->sdk->companies()->info('invalid-id');
    
    $this->assertTrue($result['error']);
    $this->assertEquals(404, $result['status_code']);
}
```

## Coverage Goals

### Current Coverage
- ✅ Feature tests for core functionality
- ✅ Unit tests for critical services
- ⚠️ Resource tests in progress

### Target Coverage (Before 1.0.0 Stable)
- **Overall:** 80% code coverage
- **Services:** 90% coverage
- **Resources:** 70% coverage (CRUD operations)
- **Error Handling:** 100% coverage

### Priority Areas
1. **High Priority** (Needed before 1.0.0):
    - All service classes (TokenService, RateLimiter, ErrorHandler)
    - OAuth authentication flow
    - Rate limiting behavior
    - Error handling and retries

2. **Medium Priority**:
    - Core resources (Companies, Contacts, Deals, Invoices)
    - Filtering and pagination
    - Sideloading functionality

3. **Lower Priority**:
    - Less-used resources
    - Edge cases
    - Legacy migration utilities

## Contributing Tests

When adding features or fixing bugs:

### 1. Write Tests First (TDD)
```php
// 1. Write failing test
/** @test */
public function it_can_filter_by_custom_field(): void
{
    $result = $this->sdk->companies()->filterByCustomField('field-id', 'value');
    $this->assertNotEmpty($result['data']);
}

// 2. Implement feature
// 3. Test passes ✅
```

### 2. Test Requirements for PRs
- [ ] All existing tests pass
- [ ] New tests added for new features
- [ ] Tests added for bug fixes
- [ ] Edge cases covered
- [ ] Documentation updated

### 3. Running Tests Before Committing
```bash
# Quick check
composer test

# Full check with coverage
vendor/bin/phpunit --coverage-text

# Ensure no breaking changes
vendor/bin/phpunit --testsuite=Feature
```

## Continuous Integration

### GitHub Actions (Recommended)

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.2', '8.3']
        laravel: ['10.*', '11.*']
    
    steps:
    - uses: actions/checkout@v3
    - uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php }}
    - run: composer install
    - run: composer test
```

## Debugging Tests

### View Detailed Output
```bash
# Show all output
vendor/bin/phpunit --verbose

# Show debug information
vendor/bin/phpunit --debug

# Stop on first failure
vendor/bin/phpunit --stop-on-failure
```

### Using dd() in Tests
```php
/** @test */
public function it_debugs_response(): void
{
    $result = $this->sdk->companies()->list();
    
    // Dump and die to inspect
    dd($result);
    
    $this->assertNotEmpty($result);
}
```

### Test-Specific Logging
```php
/** @test */
public function it_logs_for_debugging(): void
{
    Log::info('Test starting', ['context' => 'debug']);
    
    $result = $this->sdk->companies()->list();
    
    Log::info('Result received', $result);
    
    $this->assertNotEmpty($result);
}
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

---

**Happy Testing!** 🧪
