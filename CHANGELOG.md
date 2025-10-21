# Changelog

All notable changes to the Teamleader Focus SDK for Laravel will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- GraphQL support when available from Teamleader API
- Bulk operations helper for processing large datasets
- Enhanced caching strategies with tag-based invalidation
- WebSocket support for real-time updates
- Laravel Pulse integration for monitoring
- CLI tool for quick API exploration

## [1.1.2] - 2025-10-21

### Fixed
- **Method Signature Compatibility in Subscriptions Resource**
    - Fixed `buildSort()` method signature in `Subscriptions` resource to match parent `Resource` class
    - Changed from `buildSort(array $sort): array` to `buildSort($sort, string $order = 'desc'): array`
    - Removed strict `array` type hint to support flexible parameter types (string, array, or structured array)
    - Added `$order` parameter with default value `'desc'` to maintain compatibility with parent class
    - Prevents fatal error: "Declaration of buildSort() must be compatible with parent class"

### Changed
- Updated `Subscriptions::buildSort()` to handle multiple input formats:
    - String sort field: `'field_name'`
    - Simple array: `['field' => 'name', 'order' => 'asc']`
    - Structured sort array: `[['field' => 'name', 'order' => 'asc']]`

## [1.1.1] - 2025-10-21

### Fixed
- **Method Visibility for Inheritance Compatibility**
    - Changed `buildFilters()` and `buildSort()` methods from `private` to `protected` visibility in multiple resource classes
    - Ensures proper inheritance hierarchy and prevents PHP access level violations
    - Fixes fatal error: "Access level to [Resource]::buildSort() must be protected (as in class Resource) or weaker"

  **Affected files:**
    - `src/Resources/General/Departments.php`
    - `src/Resources/General/Users.php`
    - `src/Resources/General/Teams.php`
    - `src/Resources/General/WorkTypes.php`
    - `src/Resources/Projects/LegacyMilestones.php`
    - `src/Resources/Deals/LostReasons.php`
    - `src/Resources/Deals/Sources.php`
    - `src/Resources/Invoicing/Invoices.php`

### Changed
- Standardized method visibility across all resource classes for consistent inheritance behavior
- All helper methods used by parent class now use `protected` visibility instead of `private`

## [1.1.0-alpha] - 2024-10-16

### 🎉 Initial Alpha Release

This is the first public release of the Teamleader Focus SDK for Laravel. While labeled as alpha, the SDK is production-ready and feature-complete with comprehensive coverage of the Teamleader Focus API.

### Added

#### Core Features
- **Complete OAuth 2.0 Implementation**
    - Authorization URL generation with state validation
    - Secure callback handling with CSRF protection
    - Automatic token refresh with distributed locking
    - Database-backed token storage with cache layer
    - Concurrent request safety to prevent token conflicts

- **Intelligent Rate Limiting**
    - Sliding window rate limiter (200 requests/minute)
    - Automatic throttling when approaching limits
    - Configurable throttling thresholds
    - Retry logic with exponential backoff
    - Rate limit statistics and monitoring
    - Respects 429 response headers

- **Resource Sideloading**
    - Fluent interface for including related resources
    - Validation of include parameters
    - Pre-configured common relationship sets
    - Reduces API calls significantly
    - Support for nested includes (e.g., 'lead.customer')

- **Comprehensive Error Handling**
    - Teamleader-specific error parsing
    - Structured error responses with details
    - HTTP status code preservation
    - Extensive logging with configurable levels
    - User-friendly error messages

#### API Resources - Complete Coverage

**CRM**
- Companies (full CRUD, search, link, tag operations)
- Contacts (full CRUD, company linking, tag operations)
- Business Types (list, info)
- Tags (list, info, create, update, delete)
- Addresses (info)

**Deals & Sales**
- Deals (full CRUD, move, win, lose operations)
- Quotations (create, update, info, send, download)
- Orders (create, update, info)
- Deal Phases (list, info)
- Deal Pipelines (list, info)
- Deal Sources (list, info)
- Lost Reasons (list, info)

**Invoicing**
- Invoices (full CRUD, draft, book, send, download, payments)
- Credit Notes (create, draft, book, download)
- Payment Methods (list, info)
- Payment Terms (list, info)
- Tax Rates (list, info)
- Withholding Tax Rates (list, info)
- Commercial Discounts (list, info)
- Subscriptions (create, update, activate, deactivate)

**Projects & Time Tracking**
- Projects (full support for both v1 and v2)
    - Full CRUD operations
    - Close/open operations
    - Assign/unassign users and teams
    - Status-based filtering (open, closed, running, overdue)
- Project Tasks (create, update, complete, delete)
- Milestones (create, update, complete)
- Time Tracking (create, update, delete)
- Timers (start, stop, list running timers)

**Calendar & Activities**
- Meetings (create, update, cancel, schedule)
- Calls (create, update, complete, cancel)
- Call Outcomes (list, info)
- Calendar Events (list, info, create)
- Activity Types (list, info, create)

**Products & Services**
- Products (full CRUD, search operations)
- Product Categories (list, info)
- Unit of Measures (list, info)
- Work Types (list, info)

**General**
- Users (list, info, me, invite, deactivate)
- Departments (list, info)
- Custom Fields (list, info, definitions by context)
- Currencies (list, info)
- Notes (create, update, list by subject)
- Files (upload, download, info, delete)

**System & Utilities**
- Webhooks (register, unregister, list)
- Cloud Platforms (integrations)
- Accounts (info, Projects v2 status detection)
- Migration (ID translation, activity type mapping, tax rate migration)

#### Advanced Features

- **Resource Capabilities Introspection**
    - Query what operations each resource supports
    - Get available includes for sideloading
    - View common filters and usage examples
    - Rate limit cost information per operation

- **Configuration Management**
    - Comprehensive configuration file with 100+ options
    - Environment variable support for all settings
    - Configuration validation with suggestions
    - Per-endpoint cache configuration

- **Filtering & Search**
    - Advanced filtering on list endpoints
    - Search term support across multiple fields
    - Date range filtering with ISO 8601 format
    - Status-based filtering
    - Tag-based filtering
    - UUID array filtering

- **Pagination & Sorting**
    - Configurable page sizes
    - Page number navigation
    - Sort by field with ascending/descending order
    - Pagination metadata in responses

- **Logging & Monitoring**
    - Request/response logging
    - Rate limit tracking
    - Token refresh logging
    - Configurable log channels
    - Debug mode for development

- **Caching Layer**
    - Response caching for static data
    - Configurable TTL per endpoint
    - Cache store configuration
    - Token caching for performance
    - Automatic cache invalidation

#### Laravel Integration

- **Service Provider**
    - Automatic registration of SDK services
    - Configuration publishing
    - Service container bindings
    - Singleton pattern for optimal performance

- **Artisan Commands**
    - `teamleader:status` - Check connection and rate limits
    - `teamleader:health` - Comprehensive health check
    - `teamleader:config-validate` - Validate configuration

- **Facade Support**
    - Convenient static access via `Teamleader` facade
    - Full IDE autocomplete support
    - Clean, readable syntax

- **Middleware**
    - API call counter middleware
    - Request tracking
    - Rate limit monitoring

#### Developer Experience

- **Fluent Interface**
    - Chainable methods for readable code
    - Method chaining for includes
    - Intuitive resource access

- **Validation**
    - Request data validation before API calls
    - Custom validation rules for Teamleader data types
    - UUID validation
    - Email/phone array validation
    - Address structure validation

- **Documentation**
    - Comprehensive README with examples
    - Inline code documentation
    - Resource-specific usage examples
    - Migration guides from legacy API

- **Type Safety**
    - PHP 8.2+ type declarations
    - Strict types enforcement
    - Return type hints
    - Parameter type hints

### Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- Guzzle HTTP Client 7.0+
- Database: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+
- PHP Extensions: ext-json, ext-mbstring

### Breaking Changes

N/A - Initial release

### Deprecated

N/A - Initial release

### Security

- State parameter validation in OAuth flow
- Secure token storage in database
- Token encryption support via Laravel's encryption
- Rate limiting to prevent abuse
- Input validation before API calls
- CSRF protection in authentication flow

### Known Issues

- None at this time

### Migration Guide

For users migrating from the deprecated Teamleader API, see the Migration resource:

```php
// Translate old activity types to new UUIDs
$result = $teamleader->migrate()->activityType('meeting');

// Migrate old tax rates
$result = $teamleader->migrate()->taxRate('department-uuid', 21.00, '2024-01-01');

// Translate old IDs to new UUIDs
$result = $teamleader->migrate()->id('company', 12345);
```

### Notes

This alpha release includes:
- ✅ All Teamleader Focus API endpoints implemented
- ✅ Production-ready code with extensive error handling
- ✅ Comprehensive test coverage
- ✅ Full Laravel integration (10.x, 11.x, 12.x)
- ✅ Complete documentation and examples
- ✅ Rate limiting and caching strategies
- ✅ OAuth 2.0 with automatic token management

We're calling this an "alpha" release to gather community feedback before the stable 1.0.0 release, but the codebase is production-ready and actively used in production environments.

### Feedback Welcome

We'd love to hear your feedback on:
- API design and developer experience
- Documentation clarity and completeness
- Feature requests and improvements
- Bug reports and issues
- Performance observations

Please open an issue on GitHub or contact us at help@mcore-services.be

---

## Release Notes Format

Each release will include:
- **Added**: New features and capabilities
- **Changed**: Changes to existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Security improvements and fixes

---

**[Unreleased]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.2...HEAD
**[1.1.2]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.1...v1.1.2
**[1.1.1]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.0-alpha...v1.1.1
**[1.1.0-alpha]**: https://github.com/mcore-services-bv/teamleader-sdk/releases/tag/v1.1.0-alpha
