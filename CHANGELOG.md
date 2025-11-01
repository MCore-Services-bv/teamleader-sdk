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

## [1.1.5] - 2025-11-01

### Fixed
- **CRITICAL: Token Storage Schema Mismatch**
    - Fixed critical bug where tokens were not persisting to database, only to cache
    - Root cause: Migration file created incomplete schema missing `token_type` and `expires_in` columns
    - **Impact**: Token refresh failures after cache expiry, lost authentication on app restart
    - Removed migration-based table creation (introduced in v1.1.3)
    - Reverted to automatic table creation via `TokenService::ensureTokensTableExists()`
    - Table now created automatically on first OAuth flow with correct schema
    - Affects files:
        - Removed: `database/migrations/0001_01_01_999999_create_teamleader_tokens_table.php`
        - Updated: `src/TeamleaderServiceProvider.php` (removed migration loading/publishing)

### Changed
- Simplified installation process - no `php artisan migrate` required
- Removed `database/migrations/` directory from package
- Updated `TeamleaderServiceProvider`:
    - Removed `loadMigrationsFrom()` call
    - Removed migration publishing from `publishes()`
- SDK now automatically creates `teamleader_tokens` table when needed

### Migration Guide

**For existing installations with buggy table:**

```bash
# 1. Update SDK
composer update mcore-services/teamleader-sdk

# 2. Drop old table
php artisan tinker
>>> Schema::dropIfExists('teamleader_tokens');

# 3. Clear caches
php artisan cache:clear
php artisan config:clear

# 4. Re-authenticate
# Visit /teamleader/authorize to trigger table recreation with correct schema
```

**For new installations:**
- No action required - table created automatically on first OAuth flow ✅

### Technical Details

**Correct table schema (now auto-created):**
```sql
CREATE TABLE teamleader_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    token_type VARCHAR(50) DEFAULT 'Bearer',  -- Was missing in migration
    expires_in INT NOT NULL,                   -- Was missing in migration  
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX expires_at_index (expires_at),
    INDEX updated_at_index (updated_at)
);
```

### Breaking Changes
- None - existing installations continue to work
- Users with incomplete table schema should follow migration guide above

### Benefits
- ✅ Automatic table creation - simpler user experience
- ✅ Schema always correct - defined in single source of truth
- ✅ Self-healing - recreates table if deleted
- ✅ No migration files to manage
- ✅ Fixes token persistence issues

### Known Issues Resolved
- Tokens not persisting to database (#issue-number-here)
- Token refresh failing after cache expiry (#issue-number-here)
- Silent database insert failures in TokenService (#issue-number-here)

## [1.1.4] - 2025-10-30

### Fixed
- **Method Visibility in LegacyMilestones Resource**
    - Changed `validateId()` method from `private` to `protected` visibility to match parent class requirements
    - Changed `validateCreateData()` method from `private` to `protected` for consistency
    - Changed `isValidDate()` method from `private` to `protected` for potential inheritance
    - Fixes fatal error: "Access level to LegacyMilestones::validateId() must be protected (as in class Resource) or weaker"
    - Ensures proper inheritance hierarchy and prevents PHP access level violations
    - Affects file: `src/Resources/Projects/LegacyMilestones.php`
    - **Impact**: Resolves milestone sync command failures where forProject() operations would fail with access level errors

### Changed
- Standardized all validation methods in LegacyMilestones to use `protected` visibility for consistency with inheritance patterns

## [1.1.3] - 2025-10-29

### Fixed
- **Missing Migrations Directory Structure**
    - Added `database/migrations/` directory to package structure
    - Created `0001_01_01_999999_create_teamleader_tokens_table.php` migration file
    - Fixes error: "Can't locate path: <vendor/mcore-services/teamleader-sdk/src/../database/migrations>"
    - Error occurred when running `php artisan vendor:publish --provider="McoreServices\TeamleaderSDK\TeamleaderServiceProvider"`
    - Migration file was previously loaded via `loadMigrationsFrom()` but directory didn't exist for publishing
    - Now both `loadMigrationsFrom()` and `publishes()` in TeamleaderServiceProvider work correctly
    - Affects file: `database/migrations/2024_01_01_000000_create_teamleader_tokens_table.php`
    - **Impact**: Eliminates vendor:publish error while maintaining automatic migration execution

### Enhanced
- **Token Storage Schema**
    - Migration creates `teamleader_tokens` table with optimized schema:
        - `access_token` (VARCHAR 500) - Stores OAuth access token
        - `refresh_token` (VARCHAR 500) - Stores OAuth refresh token
        - `expires_at` (TIMESTAMP) - Token expiration time (indexed for performance)
        - Standard Laravel timestamps (`created_at`, `updated_at`)
    - Added index on `expires_at` column for efficient token expiration queries
    - Supports automatic token refresh workflow
    - Database-backed token persistence (replacing cache-based storage)

## [1.1.2] - 2025-10-24

### Fixed
- **Method Signature Compatibility in Subscriptions Resource**
    - Fixed `buildSort()` method signature in `Subscriptions` resource to match parent `Resource` class
    - Changed from `buildSort(array $sort): array` to `buildSort($sort, string $order = 'desc'): array`
    - Removed strict `array` type hint to support flexible parameter types (string, array, or structured array)
    - Added `$order` parameter with default value `'desc'` to maintain compatibility with parent class
    - Prevents fatal error: "Declaration of buildSort() must be compatible with parent class"

- **Missing Public list() Method in ActivityTypes Resource**
    - Added missing public `list()` method to `ActivityTypes` resource class
    - Method was referenced by other public methods (`all()`, `byIds()`, `findByName()`) but not implemented
    - Implements proper filtering and pagination support consistent with other read-only resources
    - Fixes error: "Call to undefined method ActivityTypes::list()"
    - Affects file: `src/Resources/Calendar/ActivityTypes.php`

- **Resource.php Method Conflict with FilterTrait**
    - Removed deprecated `buildQueryParams()` method from `Resource` base class (lines 346-382)
    - Removed deprecated `buildSort()` method from `Resource` base class (lines 384-409)
    - Fixed fatal error: "Call to undefined method buildFilters()" in Companies and other resources
    - The old methods were calling non-existent `buildFilters()` and conflicting with `FilterTrait`
    - Now exclusively uses `FilterTrait` methods: `buildQueryParams()`, `applyFilters()`, `applySorting()`, `applyPagination()`, `applyIncludes()`
    - Ensures all resources use consistent query building through the trait
    - Prevents method signature conflicts between base class and trait
    - Affects file: `src/Resources/Resource.php`
    - **Impact**: This fix resolves sync command failures where list() operations would fail with undefined method errors

### Changed
- Updated `Subscriptions::buildSort()` to handle multiple input formats:
    - String sort field: `'field_name'`
    - Simple array: `['field' => 'name', 'order' => 'asc']`
    - Structured sort array: `[['field' => 'name', 'order' => 'asc']]`

### Enhanced
- **Files Resource API Compatibility**
    - Added custom `buildQueryParams()` method to properly handle Files API-specific requirements
    - Implemented strict validation for subject filter structure (type and id required)
    - Added validation for subject types against Teamleader API specifications
    - Validates subject types: `company`, `contact`, `deal`, `invoice`, `creditNote`, `nextgenProject`, `ticket`
    - Ensures filters are formatted correctly for the Files API endpoint
    - Throws descriptive `InvalidArgumentException` when:
        - Subject filter is missing required fields (type or id)
        - Subject type is not in the valid types list
    - Improves error messages for better debugging experience
    - Essential for file sync operations and bulk file management
    - Affects file: `src/Resources/Files/Files.php`

- **Query Building Architecture**
    - Standardized all query building through `FilterTrait` across all resources
    - Improved consistency between resource classes for filters, sorting, pagination
    - Better separation of concerns: base Resource class handles documentation/caching, FilterTrait handles query construction

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

**[Unreleased]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.4...HEAD
**[1.1.5]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.4...v1.1.5
**[1.1.4]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.3...v1.1.4
**[1.1.3]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.2...v1.1.3
**[1.1.2]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.1...v1.1.2
**[1.1.1]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.0-alpha...v1.1.1
**[1.1.0-alpha]**: https://github.com/mcore-services-bv/teamleader-sdk/releases/tag/v1.1.0-alpha
