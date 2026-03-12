# Changelog

All notable changes to the Teamleader Focus SDK for Laravel will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Bulk operations helper for processing large datasets
- Enhanced caching strategies with tag-based invalidation
- Laravel Pulse integration for monitoring
- CLI tool for quick API exploration

---

## [1.2.2] - 2026-03-12

### Fixed

#### General — Custom Fields Pagination Bug
- **`CustomFields`**: Fixed `list()` silently ignoring the `$options` parameter — `page_size` and `page_number` were accepted by the method signature but never forwarded to the API request, causing the Teamleader API to always return its default page of 20 records regardless of how many custom fields exist
- **`CustomFields`**: Corrected `$supportsPagination` capability flag from `false` to `true` to accurately reflect that the `customFieldDefinitions.list` endpoint does paginate
- **Impact**: Any account with more than 20 custom fields would silently receive an incomplete sync. The `SyncReferenceDataJob` already implemented correct pagination logic (100 per page, loop until exhausted) — it now works as intended

---

## [1.2.1] - 2026-03-12

### Fixed

#### Products — Product Categories Ledger Response Correction
- **`Categories`**: Corrected `getResponseStructure()` to reflect the actual Teamleader API response — each ledger entry contains a flat `ledger_account_number` (string) alongside the `department` reference object
- **`Categories`** (docs): Updated `docs/products/categories.md` response structure JSON example, Category Object Properties, and all usage examples (`Get All Categories`, `Get Ledger Accounts for Category`, `Map Categories to Departments`) to remove fabricated `sales_account` and `purchase_account` nested objects that were never returned by the API

---

## [1.2.0] - 2026-03-11

This release completes coverage of the Teamleader Focus API changelog from October 2025 through March 2026,
adding full payment management to the Expenses module, three new Planning resources, PEPPOL support across
Invoicing, avatar/logo upload for CRM entities, and a wide range of field additions across existing resources.

### Added

#### Expenses — Full Payment Management
- **`IncomingInvoices`**: Added `listPayments()`, `registerPayment()`, `removePayment()`, `updatePayment()`, and `getValidPaymentStatuses()` — full payment lifecycle management for incoming invoices
- **`IncomingCreditnotes`**: Added `listPayments()`, `registerPayment()`, `removePayment()`, `updatePayment()`, and `getValidPaymentStatuses()` — full payment lifecycle management for incoming credit notes
- **`Receipts`**: Added `listPayments()`, `registerPayment()`, `removePayment()`, `updatePayment()`, and `getValidPaymentStatuses()` — full payment lifecycle management for receipts
- All three resources now expose `$validPaymentStatuses` property: `['unknown', 'paid', 'partially_paid', 'not_paid']`
- `payment_status` field now returned in `info()` responses for all three resources

#### Planning — Three New Resources
- **`Reservations`** (`src/Resources/Planning/Reservations.php`): New resource with `list()`, `create()`, `update()`, `delete()` — manage planning reservations
- **`UserAvailability`** (`src/Resources/Planning/UserAvailability.php`): New resource with `daily()` and `total()` — query user availability for planning
- **`PlannableItems`** (`src/Resources/Planning/PlannableItems.php`): New resource with `list()` and `info()` — browse plannable items for scheduling
- All three Planning resources registered in `TeamleaderSDK.php`

#### CRM — Avatar & Logo Uploads
- **`Contacts`**: Added `uploadAvatar(string $id, string $fileId): array` — attach a file as a contact's avatar
- **`Companies`**: Added `uploadLogo(string $id, string $fileId): array` — attach a file as a company's logo

#### General — Custom Field Creation
- **`CustomFieldDefinitions`**: Added `create(array $data): array` — new endpoint added by Teamleader in January 2026

#### Webhooks — PEPPOL Events
- Added support for four new webhook event types:
    - `invoice.peppolSubmissionSucceeded`
    - `invoice.peppolSubmissionFailed`
    - `creditNote.peppolSubmissionSucceeded`
    - `creditNote.peppolSubmissionFailed`

### Changed

#### Expenses
- **`Expenses`**: `list()` now returns `payment_status`, `payment_amount`, and `paid_at` per expense item
- **`Expenses`**: `list()` accepts four new filters: `department_ids` (array), `supplier` (object with `type`/`id`), `paid_at` (date range), `payment_statuses` (array)
- **`Expenses`**: `list()` supports three new sort options: `document_date`, `due_date`, `supplier_name`

#### Deals & Sales
- **`Orders`**: `list()` and `info()` responses now include `order_number` field
- **`Orders`**: `info()` line items now include `project` (object), `group` (object), and `purchase_price` (object)
- **`Quotations`**: `info()` response now includes `text` field (rich text content of the quotation)

#### CRM
- **`Companies`**: `list()` filter now supports `national_identification_number` (string)
- **`Companies`**: `list()` includes now supports `price_list` as a valid sideload option
- **`Companies`**: `list()` filter and response now include `marketing_mails_consent` (boolean)
- **`Contacts`**: `list()` includes now supports `price_list` as a valid sideload option
- **`Contacts`**: `list()` filter and response now include `marketing_mails_consent` (boolean)

#### Invoicing
- **`Invoices`**: `draft()` and `update()` now accept `delivery_date` field
- **`Invoices`**: `list()` and `info()` responses now include `delivery_date`, `peppol_status`, and `subscription` (object with `type`/`id`, list only)
- **`CreditNotes`**: `info()` response now includes `peppol_status`
- **`Subscriptions`**: `create()` and `update()` now accept `peppol` as a valid `sending_methods` value
- **`Subscriptions`**: `list()` and `info()` responses now include `created_at`

#### Projects & Time Tracking
- **`Materials`**: `create()`, `update()`, `list()`, and `info()` now support `quantity_estimated` field
- **`TimeTracking`**: `list()` filter `relates_to` now accepts `nextgenProject` and `nextgenProjectGroup` as valid type values

#### Files
- **`Files`**: `upload()` subject type validation now accepts `temporary` as a valid subject type

#### Calendar
- **`Meetings`**: `list()` and `info()` responses now include `group` field

---

## [1.1.6] - 2025-11-01

### Fixed
- **Critical: Includes Parameter Name**
    - Changed FilterTrait to use `includes` (plural) instead of `include` (singular)
    - Fixes sideloading for all resources (companies.info, products.info, etc.)
    - Aligns with official Teamleader API specification
    - Affects: All resources supporting sideloading/includes
    - Impact: Critical bug fix - previous implementation caused API errors

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

# 4. Re-authenticate (visit your OAuth flow)
```

## [1.1.4] - 2025-10-31

### Fixed
- **Method Visibility for Inheritance in LegacyMilestones**
    - Changed validation methods from `private` to `protected` visibility in `LegacyMilestones` resource
    - Fixes fatal error where `compact()` operations would fail with access level errors

### Changed
- Standardized all validation methods in LegacyMilestones to use `protected` visibility for consistency with inheritance patterns

## [1.1.3] - 2025-10-29

### Fixed
- **Missing Migrations Directory Structure**
    - Added `database/migrations/` directory to package structure
    - Created `0001_01_01_999999_create_teamleader_tokens_table.php` migration file
    - Fixes error: "Can't locate path: `<vendor/mcore-services/teamleader-sdk/src/../database/migrations>`"

### Enhanced
- **Token Storage Schema**
    - Migration creates `teamleader_tokens` table with optimized schema
    - Added index on `expires_at` column for efficient token expiration queries

## [1.1.2] - 2025-10-24

### Fixed
- **Method Signature Compatibility in Subscriptions Resource**
    - Fixed `buildSort()` method signature to match parent `Resource` class
- **Missing Public `list()` Method in ActivityTypes Resource**
    - Added missing public `list()` method to `ActivityTypes` resource class
- **Resource.php Method Conflict with FilterTrait**
    - Removed deprecated `buildQueryParams()` and `buildSort()` methods from `Resource` base class
    - Now exclusively uses `FilterTrait` methods for query building

### Changed
- Updated `Subscriptions::buildSort()` to handle multiple input formats
- Standardized query building through `FilterTrait` across all resources

### Enhanced
- **Files Resource API Compatibility**
    - Added custom `buildQueryParams()` with strict validation for subject filter structure

## [1.1.1] - 2025-10-21

### Fixed
- **Method Visibility for Inheritance Compatibility**
    - Changed `buildFilters()` and `buildSort()` methods from `private` to `protected` visibility in multiple resource classes
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

## [1.1.0-alpha] - 2024-10-16

### 🎉 Initial Alpha Release

This is the first public release of the Teamleader Focus SDK for Laravel. While labeled as alpha, the SDK
is production-ready and feature-complete with comprehensive coverage of the Teamleader Focus API.

### Added

#### Core Features
- **Complete OAuth 2.0 Implementation** — Authorization URL generation, secure callback handling, automatic token refresh, distributed locking, database-backed storage
- **Intelligent Rate Limiting** — Sliding window rate limiter (200 requests/minute), automatic throttling, retry logic with exponential backoff
- **Resource Sideloading** — Fluent interface for including related resources, validation, pre-configured relationship sets
- **Comprehensive Error Handling** — Teamleader-specific error parsing, structured error responses, extensive logging

#### API Resources — Complete Coverage
- **CRM**: Companies, Contacts, Business Types, Tags, Addresses
- **Deals & Sales**: Deals, Quotations, Orders, Deal Phases, Deal Pipelines, Deal Sources, Lost Reasons
- **Invoicing**: Invoices, Credit Notes, Payment Methods, Payment Terms, Tax Rates, Withholding Tax Rates, Commercial Discounts, Subscriptions
- **Projects**: Projects (v1 & v2), Project Tasks, Milestones, Legacy Milestones, Materials, Time Tracking, Timers
- **Expenses**: Expenses, Incoming Invoices, Incoming Credit Notes, Receipts, Bookkeeping Submissions
- **Calendar**: Meetings, Calls, Call Outcomes, Calendar Events, Activity Types
- **Products**: Products, Product Categories, Unit of Measures, Work Types
- **General**: Users, Departments, Teams, Custom Field Definitions, Currencies, Notes, Files, Tags
- **System**: Webhooks, Cloud Platforms, Accounts, Migration Utilities

### Requirements
- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- Guzzle HTTP Client 7.0+
- Database: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+
- PHP Extensions: ext-json, ext-mbstring

---

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

**[Unreleased]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.2.2...HEAD
**[1.2.2]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.2.1...v1.2.2
**[1.2.1]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.2.0...v1.2.1
**[1.2.0]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.6...v1.2.0
**[1.1.6]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.5...v1.1.6
**[1.1.5]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.4...v1.1.5
**[1.1.4]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.3...v1.1.4
**[1.1.3]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.2...v1.1.3
**[1.1.2]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.1...v1.1.2
**[1.1.1]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.1.0-alpha...v1.1.1
**[1.1.0-alpha]**: https://github.com/mcore-services-bv/teamleader-sdk/releases/tag/v1.1.0-alpha
