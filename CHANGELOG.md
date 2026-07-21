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

## [2.0.0] - 2026-07-20

### Removed

- **BREAKING — dropped support for Laravel 10 and Laravel 11.** Both have reached end-of-life
  (Laravel 10 security support ended Feb 2025; Laravel 11 in March 2026), and every released
  10.x/11.x version now carries unpatched security advisories — Composer refuses to install them.
  The SDK now targets Laravel 12.

### Changed

- **Requirement:** `illuminate/support` is now `^12.0` (was `^10.0|^11.0|^12.0`).
- **Dev/test:** `orchestra/testbench` `^10.0`, `phpunit/phpunit` `^11.5.1`.
- **CI:** the test matrix is now Laravel 12 across PHP 8.2, 8.3 and 8.4; upgraded
  `actions/checkout` and `actions/cache` to v4.

### Added

- **PHP 8.4 support** — added to the test matrix (`"php": "^8.2"` already permitted it).

### Upgrading

- **On Laravel 12:** no changes required.
- **Still on Laravel 10 or 11:** upgrade to Laravel 12 (recommended), or pin the SDK to `^1.2`
  until you can. Note that Laravel 10/11 themselves have unpatched security advisories.

---

## [1.2.8] - 2026-07-20

Catches the SDK up with the Teamleader Focus API changelog additions from late March
through June 2026. Adds two new endpoints and one new resource, plus field/enum additions
and validation across existing resources.

### Added

#### Calendar
- **`Calls`** (`src/Resources/Calendar/Calls.php`): Added `delete()` targeting the new
  `calls.delete` endpoint (Teamleader 2026-05-08) and set `$supportsDeletion = true`.
- **`Meetings`** (`src/Resources/Calendar/Meetings.php`): `list()` now supports the `group_id`
  filter (nextgen project group, 2026-04-29). It cannot be combined with `milestone_id` — a guard
  throws `InvalidArgumentException` when both are provided.

#### General
- **`Notes`** (`src/Resources/General/Notes.php`): Added `delete()` targeting the new
  `notes.delete` endpoint (2026-06-09) and set `$supportsDeletion = true`.
- **`Notes`**: Added `meeting` as a valid subject type for `create()` and the `list()` subject
  filter (2026-04-01).
- **`UserSchedules`** (`src/Resources/General/UserSchedules.php`): New resource wrapping the new
  `userSchedules.list` endpoint (2026-06-19). Returns per-day working schedules for one or more
  users over a date range of at most 7 days, with `forUser()` / `forUsers()` helpers. Registered
  as `Teamleader::userSchedules()`. Teamleader has deprecated `users.getWeekSchedule` in favour of
  this endpoint.

#### Projects
- **`Materials`** (`src/Resources/Projects/Materials.php`): Added `parent_fixed_price` as a valid
  `billing_method` for `create()`/`update()`, and as a documented response value for
  `info()`/`list()` (2026-04-30). `parent_fixed_price` is only accepted by the API when the parent
  is fixed-price.

#### Invoicing
- **`Invoices`** (`src/Resources/Invoicing/Invoices.php`): Added `expected_payment_method`
  validation used by `create()`, `update()` and `updateBooked()` — validates the `method` enum
  (`direct_debit`, `credit_card`, `cash`, `cheque`, `bankers_draft`, `bank_transfer`,
  `payment_card`, `sepa_direct_debit`) and requires `reference` when `method` is
  `sepa_direct_debit` (added to `updateBooked` 2026-04-23).
- **`Invoices`**: Added the `ubl/xrechnung` download format to `download()`.
- **`Invoices`**: Added `listDrafts()`, `status` filter validation, and `getValidPeppolStatuses()`.

#### Tickets
- **`Tickets`** (`src/Resources/Tickets/Tickets.php`): Added `project_id` support to
  `create()`/`update()` (2026-06-09). A ticket links to either a legacy `milestone_id` or a
  new-projects `project_id`, but not both — a guard enforces this.

### Changed

- **`Invoices`**: `send()` now treats `recipients` as optional, matching the API (when omitted,
  the invoice is sent to the invoicee's email).
- **`Invoices`**: Documented pass-through `quotation_id` on `create()` — links the invoice to a
  source quotation and its deal, and marks the deal as won (2026-06-24).
- **`Deals`** (`src/Resources/Deals/Deals.php`): Documented pass-through `purchase_order_number`
  on `create()`/`update()` (2026-05-26).
- **`Projects`** (`src/Resources/Projects/Projects.php`): Documented the nullable pass-through
  `initial_time_tracked`, `initial_price`, `initial_cost`, `initial_amount_billed` and
  `initial_amount_paid` seeding fields on `update()` (2026-05-06).

### Deprecated

- **`Invoices`**: `draft()` (which lists draft invoices) is deprecated in favour of `listDrafts()`,
  to avoid confusion with `create()`, which posts to `invoices.draft` to create a draft.

### Fixed

- **`Invoices`**: Removed a superseded `expected_payment_method.method` check that validated
  against a 3-value list and ran before the full validator, causing valid methods (`cash`,
  `cheque`, `bankers_draft`, `bank_transfer`, `payment_card`) to be rejected on `create()`. Removed
  the now-unused `$validPaymentMethods` property.
- **`Invoices`**: `download()` previously rejected the valid `ubl/xrechnung` format.

### Notes

- Response-only additions that require no code change (they flow through untouched and are
  documented on the wiki): **`Meetings`** `created_by` and the `online_meeting_room` →
  `customer_meeting_room` rename; **`TimeTracking`** `nextgenTask` in the `relates_to` sideload;
  **`Users`** `teams` on `users.me`.

---

## [1.2.7] - 2026-05-12

### Fixed

#### New Projects — `Groups` Resource Hits Wrong Endpoint (404)
- **`Groups`** (`src/Resources/Projects/Groups.php`): Corrected `getBasePath()` to return
  `'projects-v2/projectGroups'` instead of `'projectGroups'`. All eight methods on the resource
  (`list`, `info`, `create`, `update`, `delete`, `duplicate`, `assign`, `unassign`) were posting
  to `https://api.focus.teamleader.eu/projectGroups.list` (and equivalents), which returns a
  generic 404 — the path is missing the required `/projects-v2/` prefix used by the new Projects
  module.
- **Root cause**: The resource was registered under the SDK alias `groups` but the underlying
  path was missing the `projects-v2/` module prefix that Teamleader requires for all new Projects
  endpoints. The 404 came back from Teamleader's edge router without an `X-Api-Version` header,
  confirming the URL never resolved to a known route.
- **Impact**: Every call against `Teamleader::groups()` was failing with 404 since the resource
  was introduced. Integrations relying on milestone (project group) data could not list, create,
  update, or otherwise interact with groups via the SDK.

```php
// Before (broken): URL resolved to /projectGroups.list — 404
protected function getBasePath(): string
{
    return 'projectGroups';
}

// After (fixed): URL resolves to /projects-v2/projectGroups.list — 200
protected function getBasePath(): string
{
    return 'projects-v2/projectGroups';
}
```

### Verified

- `Teamleader::groups()->list(['project_id' => $uuid])` now returns the expected
  `data` array of project groups for the given project.
- `Teamleader::groups()->info($uuid)` resolves correctly.
- Other operations (`create`, `update`, `delete`, `duplicate`, `assign`, `unassign`) all share
  the same base path and benefit automatically from the fix.

---

## [1.2.6] - 2026-03-30

### Fixed

#### CRM — `update()` Silently Drops `null` Field Values
- **`Contacts`** (`src/Resources/CRM/Contacts.php`): Fixed `validateContactData()` stripping `null`
  values from the payload before sending to the API. Any intentional field clear (`iban`, `bic`,
  `birthdate`, etc. set to `null`) was silently removed, causing the API to never receive the
  clear instruction. Empty strings and empty arrays are still stripped as before.
- **`Companies`** (`src/Resources/CRM/Companies.php`): Same fix applied to `validateCompanyData()`
  — identical bug, identical root cause.
- **Root cause**: `array_filter()` was called without `ARRAY_FILTER_USE_BOTH`, using a closure
  that rejected `null`. The fix switches to `ARRAY_FILTER_USE_BOTH` and explicitly preserves `null`
  and the `id` key while continuing to strip `''` and `[]`.

### Changed

#### Invoicing
- **`Subscriptions`**: `list()` and `info()` responses now include `purchase_order_number` (string|null) and `delivery_information` (object|null)
- **`Subscriptions`**: `create()` and `update()` now accept `purchase_order_number` (string|null) and `delivery_information` (object|null)
- **`Invoices`**: `list()` response `subscription` field officially confirmed in Teamleader March 2026 changelog — already supported in SDK since v1.2.0

---

## [1.2.5] - 2026-03-16

### Fixed

#### Critical: `RateLimitExceededException` Constructor Throws `TypeError` When `X-RateLimit-Reset` Header Is Present

- **`TeamleaderErrorHandler`** (`src/Services/TeamleaderErrorHandler.php`): Corrected the return
  type of `extractResetTime()` from `?string` to `?int` and added an explicit `(int)` cast before
  returning the header value. HTTP response headers are always strings, but
  `RateLimitExceededException::__construct()` declares `?int $resetTime`. Under PHP's
  `strict_types=1`, passing an uncast string caused a `TypeError` before the exception object was
  ever constructed — callers never received a `RateLimitExceededException`, they received an
  uncaught `TypeError` instead. This made the v1.2.4 fix (always throw on 429) non-functional in
  practice for any API response that included the `X-RateLimit-Reset` header.
- **Impact**: Any 429 response from Teamleader that included an `X-RateLimit-Reset` header was
  surfacing as an uncaught `TypeError` rather than a catchable `RateLimitExceededException`.
  Queue jobs using `$this->release($e->getRetryAfter())` were instead crashing with a `TypeError`.

```php
// Before (broken): extractResetTime() returned ?string, causing TypeError
private function extractResetTime(array $context): ?string { ... }

// After (fixed): cast to int before returning
private function extractResetTime(array $context): ?int
{
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'x-ratelimit-reset') {
            return (int) (is_array($value) ? $value[0] : $value);
        }
    }
    return null;
}
```

### Tests

- **`tests/Unit/Services/ErrorHandlerTest.php`**: Added
  `test_throws_rate_limit_exception_for429_with_reset_time_header` — asserts that a 429 response
  carrying both `Retry-After` and `X-RateLimit-Reset` headers throws `RateLimitExceededException`
  and not a `TypeError`. Added `test_rate_limit_exception_carries_reset_time_as_int` — asserts
  that `getResetTime()` returns an `int` with the correct value and that `getRetryAfter()` is
  unaffected.

---

## [1.2.4] - 2026-03-16

### Fixed

#### Critical: Rate Limiter Not Shared Across Queue Workers

- **`ApiRateLimiterService`** (`src/Services/ApiRateLimiterService.php`): Replaced the
  in-memory static array with a Redis sorted set (`teamleader_sdk:rate_limit`). Each recorded
  request is stored as a scored member (`ZADD`) with a `microtime(true)` timestamp as its score.
  Expired entries are pruned via `ZREMRANGEBYSCORE` and the current window count is read with
  `ZCARD`. This makes the sliding window shared across all processes and hosts, so multiple
  Horizon workers collectively respect the 200 requests/minute limit rather than each enforcing
  it independently.
- **`ApiRateLimiterService`**: Header-derived `remaining` and `reset_time` values are now stored
  in Redis string keys with TTLs, so all workers benefit from rate limit signals received by any
  single worker.
- **`ApiRateLimiterService`**: `handle429Response()` now clears the shared sorted set and writes
  a cross-process reset time to Redis, ensuring all workers back off when any one of them receives
  a 429.
- **`config/teamleader.php`**: Added `rate_limiting.redis_connection` key (env:
  `TEAMLEADER_RATE_LIMIT_REDIS_CONNECTION`, default: `default`) to allow configuring which Redis
  connection the rate limiter uses.
- **Impact**: In environments with multiple queue workers (e.g. Laravel Horizon), workers were
  each maintaining independent counters starting at 0, allowing combined request rates of up to
  `200 × worker_count` per minute before any single worker triggered throttling.

#### Critical: 429 Response Silently Swallowed When `throw_exceptions` Is Disabled

- **`TeamleaderErrorHandler`** (`src/Services/TeamleaderErrorHandler.php`): `handleApiError()`
  now always throws `RateLimitExceededException` when the API responds with a 429, regardless of
  the `throw_exceptions` configuration flag. Previously, with the default `throw_exceptions=false`,
  a 429 was logged and silently returned as an error array — callers received an empty result with
  no reliable way to distinguish it from any other failure without string-matching the error
  message.
- **`TeamleaderErrorHandler`**: `withRetry()` no longer catches and retries
  `RateLimitExceededException`. It now re-throws immediately so queue jobs can call
  `$this->release($e->getRetryAfter())` to return the job to the queue rather than sleeping
  inside the worker thread for up to 60 seconds per attempt.
- **Impact**: Calling code can now catch `RateLimitExceededException` with a typed catch block
  and access `$e->getRetryAfter()` without fragile error message inspection:

```php
try {
    $response = Teamleader::invoices()->info($invoiceId);
} catch (RateLimitExceededException $e) {
    $this->release($e->getRetryAfter());
}
```

### Changed

#### Test Infrastructure

- **`tests/Unit/Services/RateLimiterTest.php`**: Updated for Redis-backed service. Added
  `#[Group('redis')]` PHP attribute (replaces deprecated `@group` docblock annotation) so the
  suite is excluded from the default `composer test` run and only executes when Redis is available.
  Added `test_sliding_window_tracks_requests_in_redis`, `test_reset_clears_redis_state`,
  `test_multiple_instances_share_state_via_redis`, and `test_handle_429_clears_window_and_stores_reset_time`.
- **`tests/Unit/Services/ErrorHandlerTest.php`**: Added
  `test_throws_rate_limit_exception_for429_even_when_exceptions_disabled`,
  `test_with_retry_rethrows_rate_limit_exception_immediately`,
  `test_with_retry_retries_server_exceptions`, and
  `test_rate_limit_exception_carries_retry_after`. Renamed
  `test_does_not_throw_when_disabled` to `test_does_not_throw_when_disabled_for_non_429_errors`
  to reflect that 429 is now exempt from this behaviour.
- **`phpunit.xml`**: Added Redis env vars (`REDIS_HOST`, `REDIS_PORT`, `REDIS_DB=15`,
  `TEAMLEADER_RATE_LIMIT_REDIS_CONNECTION`). Added `<groups><exclude>redis</exclude></groups>`
  so the Redis test group is skipped in environments without Redis.
- **`.github/workflows/tests.yml`**: Added `redis:7-alpine` service with health check. Added
  `redis` to the PHP extensions list. Split test execution into two steps: standard run
  (Redis group excluded) and a dedicated Redis group run with explicit connection env vars.

---

## [1.2.3] - 2026-03-12

### Fixed

#### Critical: Sideloading Broken for All Resources — `includes` Parameter Name

- **`FilterTrait`** (`src/Traits/FilterTrait.php`): Corrected `applyIncludes()` to send `includes`
  (plural) instead of `include` (singular) in the POST body, aligning with the Teamleader API
  specification for all `.list` and `.info` endpoints.
- **Impact**: All resources supporting sideloading were silently receiving no included data.
  The API ignores unknown body keys without returning an error, so responses appeared successful
  but `custom_fields`, `price_list`, `responsible_user`, and all other sideloaded relationships
  were always absent.
- **Affected resources**: All resources using `FilterTrait::applyIncludes()`, which includes
  Companies, Contacts, Deals, Invoices, Products, and every other resource supporting sideloading.
- **Note**: A CHANGELOG entry in v1.1.6 incorrectly claimed this fix had been applied. That code
  change was never committed. This release contains the actual fix.

### Changed

#### Documentation — Filtering and Sideloading

- **`docs/filtering.md`**: Added a dedicated _Sideloading Related Data (Includes)_ section
  documenting the `include` option key, the `with()` fluent interface, per-resource include
  tables, and a custom fields usage example. Clarified the SDK's internal translation from
  `include` (options key) to `includes` (API body parameter).
- **`docs/sideloading.md`**: Added a prominent _How the API Parameter Works_ section at the top
  explaining the `includes` (plural) API requirement and how the SDK handles it transparently.
  Added a custom fields structure reference, a dedicated _Avoid Per-Record Info Calls_ example,
  and a _Syncing with Custom Fields_ pattern.

---

## [1.2.2] - 2026-03-12

### Fixed

#### General — Custom Fields Pagination Bug
- **`CustomFields`**: Fixed `list()` silently ignoring the `$options` parameter — `page_size` and `page_number` were accepted by the method signature but never forwarded to the API request, causing the Teamleader API to always return its default page of 20 records regardless of how many custom fields exist
- **`CustomFields`**: Corrected `$supportsPagination` capability flag from `false` to `true` to accurately reflect that the `customFieldDefinitions.list` endpoint does paginate

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

## [1.1.6] - 2025-11-01

### Fixed
- **Critical: Includes Parameter Name** *(note: this fix was not correctly applied in this release — see v1.2.3)*
    - Identified that `FilterTrait::applyIncludes()` was sending `include` (singular) instead
      of `includes` (plural), causing sideloading to silently return no data for all resources.

## [1.1.5] - 2025-11-01

### Fixed
- **CRITICAL: Token Storage Schema Mismatch**
    - Fixed critical bug where tokens were not persisting to database, only to cache
    - Root cause: Migration file created incomplete schema missing `token_type` and `expires_in` columns
    - **Impact**: Token refresh failures after cache expiry, lost authentication on app restart
    - Removed migration-based table creation (introduced in v1.1.3)
    - Reverted to automatic table creation via `TokenService::ensureTokensTableExists()`
    - Table now created automatically on first OAuth flow with correct schema

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

**[Unreleased]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.2.4...HEAD
**[1.2.4]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.2.3...v1.2.4
**[1.2.3]**: https://github.com/mcore-services-bv/teamleader-sdk/compare/v1.2.2...v1.2.3
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
