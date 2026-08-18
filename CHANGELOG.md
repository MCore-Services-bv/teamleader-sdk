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

## [2.2.0] - 2026-08-18

Patch release, and an unusually large one. It began as four issues raised while
integrating the SDK into a client project and grew as each was verified against
`@teamleader/focus-api-specification` v1.197.0 rather than against the code.

Almost everything here shares one failure mode: **the SDK sent something the API
silently ignores.** Teamleader answers 200 to an unrecognised filter key, an
unknown include, a wrong-shaped sort object and a singular `include` parameter.
Nothing errors, plausible data comes back, and the mistake surfaces days later in
a component with no reason to suspect the SDK. Six such defects were found in
code nobody had reported a problem with — three of them fatal errors on code
paths the SDK's own documentation recommended.

Three fixes were prompted by Teamleader's July 2026 changelog: `price_list` on
`contacts.info` and `companies.info`, `price_list_id` on the add and update
endpoints, and the new `id` field on `commercialDiscounts.list`.

### Fixed

**Silently ignored parameters**

- **`Deals::list()`**: sideloads are sent as `includes` (plural). The API ignores
  the singular form, so `deals()->list([], ['include' => 'custom_fields'])`
  returned deals with no custom fields and no error, while the fluent
  `->withCustomFields()` form worked. The same class gave two different answers
  depending on which syntax was used, and the documented one was the broken one.
  This is the defect fixed SDK-wide in v1.2.3; `Deals::list()` hand-rolled the
  parameter instead of calling `FilterTrait::applyIncludes()` and never received
  the fix.
- **`ClosingDays::list()`**: same defect, third instance. Pagination metadata is
  now requested on every call rather than through an `include_pagination` option
  that never worked.
- **`Files`**: the sort parameter is built as an array of objects rather than a
  string array. `['-updated_at']` is ignored by the API, so sorting has never
  worked on this resource.
- **`Contacts`, `Companies`**: `price_list` is no longer requested as an include.
  Teamleader returns it automatically whenever the account has access to price
  lists, and `null` when none is set on the customer. `Companies` additionally
  declared six includes that do not exist — `addresses`, `business_type`,
  `responsible_user`, `added_by`, `tags` and `price_list` — and sent
  `responsible_user` and `addresses` as *defaults on every request*. Those fields
  are returned by default, so requesting them appeared to work. **No response
  data changes; only a no-op parameter is removed.**
- **`Contacts::info()`**: rejects includes. `contacts.info` declares no includes
  parameter at all.
- **`Companies::info()`**: validates against `related_companies` and
  `related_contacts`, the includes this endpoint actually accepts — a different
  set from `companies.list`.

**Fatal errors on documented code paths**

- **`FilterTrait`**: added the missing `with()` method. `applyPendingIncludes()`
  consumed `$pendingIncludes`, but nothing ever populated it and no `with()`
  existed anywhere in the SDK, so every fluent sideload wrapper raised
  `Error: Call to undefined method` — 23 call sites across `Meetings`,
  `Products`, `Users`, `Companies`, `Contacts`, `Deals` and `TimeTracking`. That
  includes the `->withCustomer()->withResponsibleUser()->list()` example
  published in the Deals resource's own usage examples. The fluent interface has
  never worked in any released version.
- **`Deals::list()`**: sorting no longer raises
  `Error: Call to undefined method Deals::buildSort()`. `list()` called
  `buildSort()`, which fifteen other resources define but this one did not, and
  which is on neither `Resource` nor `FilterTrait`. Any call passing a `sort`
  option was fatal. The method now validates the field against the two the API
  accepts and builds the object shape it expects.
- **`Resource`**: added the missing `validateData()` hook. Fourteen resources
  declare an override of it and `ClosingDays` calls `parent::validateData()`,
  which was a fatal error waiting for the first caller.

**Arguments accepted and discarded**

- **`UnitOfMeasure::list()`**: rejects filters, sorting and pagination instead of
  discarding them. `unitsOfMeasure.list` takes no request body, so every call
  returned the complete list regardless of what was requested — `paginate(5, 2)`
  returned page 1. A pager trusting the signature re-processes the same records
  indefinitely once an account grows past the page size.
- **`PaymentTerms`, `DayOffTypes`, `Webhooks`**: same guard, same reason.
- **`Tags::list()`**: rejects filters, which `tags.list` does not support, and
  rejects unsupported sort values rather than silently rewriting them. Asking for
  descending order previously returned ascending with no indication.
- **`TimeTracking::applyFilters()`**: unknown filter keys throw instead of being
  forwarded. `updated_since`, `invoiced` and `invoiceable` are the confirmed
  cases, none of which the endpoint supports — a two-day query returned all
  32,985 entries in the account.
- **`Deals::list()`**: filters route through `buildFilters()`, which existed on
  the class and was never called.
- **`CommercialDiscounts`**: filters are whitelisted.

**Validation stricter or looser than the API**

- **`Invoices::validateGroupedLines()`**: `section` is optional, matching the
  API's `InvoicesGroupedLinesRequest` structure. Requiring `section.title` made
  `invoices.draft`, `invoices.update` and `invoices.updateBooked` unreachable for
  any invoice with an untitled section — a shape Teamleader's own UI produces by
  default. When a group has no title the `section` key must be omitted entirely;
  the API rejects both a null and an empty-string title with HTTP 400.
- **`Files`**: `files.list` and `files.upload` validate against separate subject
  type lists. `meeting`, `product` and `project` are accepted for listing and
  were previously all rejected; `temporary` is upload-only and is now rejected
  for listing.
- **`Files::list()`**: requires the subject filter the API requires, rather than
  sending a request that can only return 400.
- **`TimeTracking`**: subject types are split between the wider set accepted for
  writing and the narrower set accepted as a list filter — `nextgenTask` can
  carry tracked time but is not a valid filter value.
- **`DayOffTypes::validateData()`**: preserves `null`, so `date_validity` can be
  cleared. The API declares it nullable on update; stripping the null dropped the
  clear silently. Same defect fixed for `Contacts` and `Companies` in v1.2.6.
- **`CustomFields::forContext()`**: validates against the API's context enum.

**Documentation that described a different SDK**

- **`Resource::getResponseFormat()`**: describes the response the SDK actually
  returns. It claimed every `list` response carried `pagination`, `included` and
  `meta`. None is returned by default; `meta` appears only when a resource sends
  `includes=pagination`, and **`included` does not exist anywhere in the
  Teamleader API** — sideloaded data is embedded in each record in `data`.
  Meanwhile `headers`, which the SDK adds to every successful response and which
  carries the rate-limit budget, was undocumented. The format is now derived from
  each resource's capability flags. A consumer following the old documentation
  would write a pager keyed on `$response['pagination']`, which is always absent,
  and silently see only the first page of every entity.
- **`Resource::getDocumentation()`**: added a `pagination` block stating that the
  API returns no total count, so the end of a list must be inferred from a page
  shorter than the requested page size — meaning a full final page costs one
  extra empty request.
- **`Resource::generateMarkdownDocs()`**: renders response formats, sort fields
  and the pagination note, none of which it previously output.

**Rate limiting**

- **`TeamleaderSDK::request()`**: waits until the limiter confirms a free slot.
  It previously checked, slept, rechecked, and dispatched regardless of what the
  recheck said — so a still-full window produced the 429 the proactive limiter
  exists to prevent. The wait was also `sleep((int) $ms / 1000)`, truncating any
  sub-second delay to zero.
- **`TeamleaderSDK::request()`**: records every request, not only successful ones.
  Teamleader counts 4xx responses and the 429s themselves against the budget, so
  recording only successes made the window drift optimistic precisely when errors
  were already occurring.
- **`ApiRateLimiterService::checkAndThrottle()`**: gates on the API's own
  `X-RateLimit-Remaining` as well as the local window, taking whichever is more
  conservative. The header value was stored in Redis and never read, which made
  the most authoritative number available purely decorative — and meant that
  after a 429, the cleared window read as full headroom.
- **`TeamleaderSDK::request()`**: honours `teamleader.rate_limiting.enabled`.
  Redis was contacted on every request even with the flag off.

**Custom field contexts**

- **`CustomFields`**: `context: sale` in `list()` and `info()` responses is
  normalised to `context: deal`. The API accepts `deal` as a filter but returns
  `sale` in the body — a known defect on Teamleader's side — so any code
  comparing a stored definition's context against `deal` silently matched
  nothing. The mapping lives in `$contextResponseAliases` and can be removed once
  the API is corrected. Normalisation is one-directional: `sale` is not accepted
  as an inbound filter value.
- **`CustomFields::byType()`**: filters client-side. The endpoint has no `type`
  filter, so the key was dropped and every call returned the entire catalogue.
- **`CustomFields`**: sorting is implemented. `$supportsSorting` defaulted to
  `true` while `list()` built no sort at all.

### Added

- **`Files::forProduct()`**, **`forMeeting()`**, **`forCreditNote()`** and
  **`forLegacyProject()`** — helpers for subject types the API accepts.
- **`Resource::rejectUnsupportedListArguments()`** — shared, capability-driven
  guard for endpoints that accept no filter, sort or page parameters.
- **`Resource::validateData()`** — the base hook fourteen resources override.
- **`Resource::$requestsPaginationMeta`** — declares whether a resource requests
  pagination metadata, so generated documentation matches the real response.
- **`FilterTrait::with()`** — the fluent include queue.
- **`CustomFields::all()`** — pages through every definition.
- **`CommercialDiscounts::find()`** — resolves a discount by the `id` Teamleader
  added to `commercialDiscounts.list` in July 2026.
- **`Companies::withRelatedCompanies()`** and **`withRelatedContacts()`**.
- **`Teamleader::nextgenProjects()`** — alias for `projects()`. Teamleader names
  the webhook family `nextgenProject` while naming the resource
  `projects-v2/projects`, so reasoning from the event names leads people to look
  for this method.
- **`teamleader.rate_limiting.max_wait_ms`** — caps how long the SDK waits for a
  rate limit slot before throwing `RateLimitExceededException`. Defaults to 5000.
- **Resource test harness** — `tests/Support/RecordingApiClient.php` and
  `tests/ResourceTestCase.php`, which assert on the request a resource builds
  rather than on the response. Every payload defect in this release was invisible
  to the previous suite.

### Changed

- **`Projects`, `LegacyProjects`**: class docblocks state the relationship
  between the SDK method name, the API path and the webhook event family.
  `projects()` is the current ("nextgen") system on `projects-v2/projects` with
  `nextgenProject.*` events; `legacyProjects()` is the old system on the bare
  `projects` path with `project.*` events — so the class names and the endpoint
  paths run in opposite directions. Both point at
  `Accounts::getProjectsVersion()`, since both endpoints answer and the system in
  use cannot be inferred from whether a list call returns rows. No runtime change.
- **`CommercialDiscounts::asOptions()`**: keyed by discount UUID rather than by
  name. The name was used only because the API returned no id; two discounts
  sharing a name across departments collapsed into one entry.
- **`Deals::$availableSortFields`**: now a keyed map with descriptions, matching
  the convention used by the other resources.
- **`Webhooks::$eventTypes`**: verified complete against the specification —
  95 types, exact match in both directions. No change to the list itself.

### Removed

- **`CustomFields::forQuotations()`** and **`forCreditnotes()`** — both passed
  context values (`quotation`, `creditnote`) that are not in Teamleader's context
  enum, so they returned empty results or a 422.
- **`Companies::withAddresses()`**, **`withBusinessType()`**,
  **`withResponsibleUser()`**, **`withAddedBy()`**, **`withCommonRelationships()`**
  and **`withPriceList()`**; **`Contacts::withPriceList()`** — all requested
  includes the API does not accept.

### Tests

Added `RecordingApiClient`, `ResourceTestCase`, and payload tests for `Files`,
`Deals`, `CustomFields`, `Invoices`, `TimeTracking`, `UnitOfMeasure` and
`Resource`, plus `RateLimiterGateTest`. `tests/TestCase.php` now configures the
Redis connection so the `redis` group can run without the phpredis extension;
`predis/predis` is a new dev dependency.

`CompaniesResourceTest::test_has_sideloading_options` asserted that `addresses`
and `responsible_user` were valid includes. They never were — the test had
locked in the defect. It is replaced by assertions derived from the specification.

### Upgrade notes

No migration. Three things to be aware of:

**Calls that were silently wrong now throw.** Unsupported filter keys, unknown
sort fields, invalid subject types and includes the API does not accept all raise
`InvalidArgumentException` before the request is sent. This is the intended
outcome — the previous behaviour returned plausible but wrong data — but code
that relied on it will surface at the call site on upgrade. `TimeTracking`,
`Deals`, `Tags`, `UnitOfMeasure`, `Files` and `CustomFields` are the resources
most likely to be affected.

**`config/teamleader.php` gained a key.** Installations that published the config
will not receive `rate_limiting.max_wait_ms`; it falls back to 5000, so nothing
breaks, but the knob is invisible until added manually. Set it to 65000 to have
the SDK wait out a full rate limit window rather than throwing.

**Rate limiting is now slower and more accurate.** Counting failed requests and
consulting the API's own remaining count both make the limiter more
conservative, so a long-running sync may take longer than before. It will also
now block for up to five seconds when the window is full, where previously it
dispatched immediately and took the 429. Catch `RateLimitExceededException` and
`release($e->getRetryAfter())` in queue workers.

---

## [2.1.1] - 2026-07-23

Patch release. Fixes a `TypeError` that could break every SDK entry point after a
successful OAuth authorization, and completes the facade's IDE annotations.

### Fixed

- **`TokenService`**: `expires_at` is no longer written to the cache as a live
  `Carbon` instance. Cache stores serialize their payload, and when that payload
  could not be rehydrated PHP returned a `__PHP_Incomplete_Class`, which
  `Carbon::parse()` rejects. The result was an uncaught
  `Carbon\Carbon::parse(): Argument #1 ($time) must be of type
  DateTimeInterface|...|null, __PHP_Incomplete_Class given` on any code path
  that touched the token — including `php artisan teamleader:health`,
  `Teamleader::isAuthenticated()` and every API call. Both the cache and the
  database now store a plain string.
- **`TokenService`**: added `parseExpiresAt()`, which safely normalises strings,
  unix timestamps and `DateTimeInterface` instances and returns `null` for
  anything unreadable. Callers treat `null` as "expired / needs refresh"
  instead of throwing.
- **`TokenService`**: `getTokensFromCache()` is now self-healing. An expiry it
  cannot read is logged, the cached token entries are purged, and the database
  becomes the source of truth again — so existing installations recover on the
  next call without a manual `cache:clear`.
- **`TokenService`**: `shouldRefreshToken()` and `hasValidTokens()` no longer
  mutate `$expiresAt` when applying the refresh threshold and the five minute
  buffer. Carbon is mutable, so the previous code logged an `expires_at` that
  was shifted by 15 minutes (respectively 5 minutes).
- **`TokenService`**: `getTokenInfo()` and `shouldRefreshToken()` cast Carbon 3's
  float diffs to sensible values before returning or logging them.

### Changed

- **`Facades\Teamleader`**: the `@method` block now covers every resource
  registered in `TeamleaderSDK::$resources` and every public method on
  `TeamleaderSDK`, including `getAuthorizationUrl()`. IDEs previously reported
  "Method 'getAuthorizationUrl' not found" for the exact snippet published in
  the README, and gave no completion for most resources. No runtime behaviour
  changed — the annotations were incomplete, not the code.

### Added

- `tests/Unit/Services/TokenServiceCacheIntegrityTest.php` — regression coverage
  for a poisoned cache entry, scalar cache storage, self-healing, and a missing
  expiry.

### Upgrade notes

No configuration or migration changes. If an installation is currently stuck on
the `__PHP_Incomplete_Class` error, upgrading is enough; the cache repairs
itself on the next token read. `php artisan cache:clear` also resolves it.

---

## [2.1.0] - 2026-07-20

### Added

- **Laravel 13 support.** `illuminate/support` now allows `^13.0`; the test matrix runs
  Laravel 13 on PHP 8.3–8.5 with `orchestra/testbench` `^11.0`.
- **PHP 8.5 support.** Added to the test matrix for both Laravel 12 (PHP 8.2–8.5) and
  Laravel 13 (PHP 8.3–8.5).

### Changed

- **Requirement:** `illuminate/support` is now `^12.0|^13.0`.
- **Dev/test:** `orchestra/testbench` `^10.0|^11.0`, `phpunit/phpunit` `^11.5.50|^12.5.8`
  (the range accepted by both testbench 10 and 11).
- **CI:** matrix expanded to Laravel 12 + 13 across PHP 8.2–8.5. Laravel 13 requires PHP 8.3+,
  so the PHP 8.2 × Laravel 13 combination is excluded.

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
