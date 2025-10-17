# Contributing to Teamleader SDK

Thank you for considering contributing to the Teamleader SDK! This document outlines the process and guidelines for contributing.

## 🤝 Code of Conduct

This project follows the principles of respect, inclusivity, and professionalism. Please be considerate and constructive in all interactions.

## 📋 How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates.

**When filing a bug report, include:**
- Clear, descriptive title
- Steps to reproduce the issue
- Expected vs actual behavior
- PHP, Laravel, and package versions
- Relevant code samples or error messages
- Stack trace if available

**Use this template:**
```markdown
**Description:**
Brief description of the bug

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
What you expected to happen

**Actual Behavior:**
What actually happened

**Environment:**
- PHP Version: 8.2.x
- Laravel Version: 10.x / 11.x / 12.x
- Package Version: 1.0.x
- OS: macOS / Linux / Windows

**Additional Context:**
Any other relevant information
```

### Suggesting Features

Feature suggestions are welcome! Please:
- Check if the feature already exists or is planned
- Explain the use case and benefit
- Provide examples of how it would work
- Consider backward compatibility

### Submitting Pull Requests

1. **Fork** the repository
2. **Create a branch** from `main`: `git checkout -b feature/my-feature`
3. **Make your changes** following our coding standards
4. **Write/update tests** for your changes
5. **Update documentation** if needed
6. **Commit** with clear, descriptive messages
7. **Push** to your fork: `git push origin feature/my-feature`
8. **Open a Pull Request** against `main`

## 🔧 Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Laravel 10, 11, or 12

### Installation

```bash
# Clone your fork
git clone https://github.com/your-username/teamleader-sdk.git
cd teamleader-sdk

# Install dependencies
composer install

# Copy .env.example if running tests
cp .env.example .env
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/Services/TokenServiceTest.php

# Run specific test method
vendor/bin/phpunit --filter testMethodName
```

### Code Style

We follow PSR-12 coding standards with Laravel conventions.

```bash
# Check code style
composer format

# Auto-fix code style issues
./vendor/bin/pint
```

## 📝 Coding Guidelines

### PHP Standards

- **PSR-12** for code style
- **PSR-4** for autoloading
- **Type hints** for all parameters and return types
- **Strict types** declaration in all files
- **Docblocks** for all public/protected methods

### Code Structure

**Method Organization:**
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

- **Classes**: `PascalCase` (e.g., `TokenService`)
- **Methods**: `camelCase` (e.g., `getValidAccessToken()`)
- **Variables**: `camelCase` (e.g., `$accessToken`)
- **Constants**: `SCREAMING_SNAKE_CASE` (e.g., `DEFAULT_TIMEOUT`)
- **Test methods**: `snake_case` with `test_` prefix or `@test` annotation

### Documentation

**Method Docblocks:**
```php
/**
 * Retrieve a valid access token, refreshing if necessary
 *
 * This method checks if the current access token is valid and
 * automatically refreshes it if expired.
 *
 * @return string|null The access token or null if unavailable
 * @throws AuthenticationException If token refresh fails
 */
public function getValidAccessToken(): ?string
{
    // Implementation
}
```

**Inline Comments:**
- Use `//` for single-line comments
- Explain "why", not "what"
- Keep comments concise and updated

### Testing

**Test Structure:**
```php
/** @test */
public function it_refreshes_expired_tokens(): void
{
    // Arrange
    $expiredToken = $this->createExpiredToken();
    
    // Act
    $result = $this->tokenService->refreshToken($expiredToken);
    
    // Assert
    $this->assertNotNull($result);
    $this->assertNotEquals($expiredToken, $result);
}
```

**Testing Guidelines:**
- Write tests for new features
- Update tests for bug fixes
- Aim for high code coverage
- Use descriptive test names
- Follow Arrange-Act-Assert pattern
- Mock external dependencies

## 🎯 Pull Request Guidelines

### PR Checklist

Before submitting, ensure:
- [ ] Code follows PSR-12 standards
- [ ] All tests pass
- [ ] New features have tests
- [ ] Documentation is updated
- [ ] CHANGELOG.md is updated (for notable changes)
- [ ] No breaking changes (or clearly documented)
- [ ] Commit messages are clear and descriptive

### Commit Message Format

Use conventional commit format:

```
type(scope): subject

body (optional)

footer (optional)
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding/updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(resources): add support for Projects v2 API

fix(auth): handle token refresh race condition

docs(readme): update installation instructions

test(services): add rate limiter tests
```

### PR Title Format

Use the same conventional commit format for PR titles:
```
feat: Add webhook management resource
fix: Resolve rate limit throttling issue
docs: Improve sideloading documentation
```

### PR Description Template

```markdown
## Description
Brief description of changes

## Motivation
Why are these changes needed?

## Changes Made
- Change 1
- Change 2
- Change 3

## Breaking Changes
None / List any breaking changes

## Related Issues
Fixes #123
Relates to #456

## Testing
How was this tested?

## Screenshots (if applicable)
Before/after screenshots

## Checklist
- [ ] Tests pass
- [ ] Documentation updated
- [ ] CHANGELOG updated
```

## 🏗️ Architecture Guidelines

### Resource Structure

When adding new resources:

```php
<?php

namespace McoreServices\TeamleaderSDK\Resources\YourCategory;

use McoreServices\TeamleaderSDK\Resources\Resource;

class YourResource extends Resource
{
    protected string $description = 'Manage [resources] in Teamleader';
    
    // Capabilities
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsPagination = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = true;
    
    // Configuration
    protected array $availableIncludes = ['relation1', 'relation2'];
    protected array $commonFilters = [
        'status' => 'Filter by status',
        'updated_since' => 'Filter by update date'
    ];
    
    protected function getBasePath(): string
    {
        return 'your-endpoint';
    }
}
```

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

### Adding Documentation

When adding features:
1. Update relevant `.md` files in `/docs`
2. Add usage examples
3. Document any breaking changes
4. Update README if necessary

### Documentation Structure

```markdown
# Resource Name

Brief description

## Available Methods

### methodName()

Description of what the method does

**Parameters:**
- `param1` (type): Description
- `param2` (type): Description

**Returns:** Description of return value

**Example:**
\```php
// Clear, working example
$result = Teamleader::resource()->methodName($param1, $param2);
\```
```

## 🐛 Debugging Tips

### Enable Debug Mode

```php
// In your .env
TEAMLEADER_DEBUG_MODE=true
TEAMLEADER_LOG_ALL_REQUESTS=true
```

### Check Logs

```php
// Laravel logs
tail -f storage/logs/laravel.log

// Check specific log channel
Log::channel('teamleader')->info('Debug info');
```

### Using SDK Commands

```bash
# Check SDK status
php artisan teamleader:status

# Validate configuration
php artisan teamleader:config:validate

# Run health check
php artisan teamleader:health
```

## 🤔 Questions?

- **Documentation**: Check `/docs` folder
- **Issues**: Search [existing issues](https://github.com/mcore-services/teamleader-sdk/issues)
- **Discussions**: Start a [discussion](https://github.com/mcore-services/teamleader-sdk/discussions)
- **Email**: help@mcore-services.be

## 📜 License

By contributing, you agree that your contributions will be licensed under the MIT License.

## 🙏 Thank You!

Every contribution, no matter how small, helps make this SDK better. Thank you for taking the time to contribute!

---

**Happy Coding!** 🚀
