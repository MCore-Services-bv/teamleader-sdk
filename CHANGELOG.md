# Changelog

All notable changes to the Teamleader Focus SDK for Laravel will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- v3.0: bulk operations helper for processing large datasets, CLI tool, and
  the removals deprecated during the audit
- Enhanced caching strategies with tag-based invalidation
- Laravel Pulse integration for monitoring

### Removed (3.x branch)

- **Everything deprecated during the 2.2.x audit.** 15 methods —
  `users()->getWeekSchedule()`, `plannableItems()->active()`,
  `invoices()->draft()`, `lostReasons()->search()`, `companies()->byName()`,
  `quotations()->byStatus()`, `creditNotes()->paid()` / `unpaid()`,
  `products()->withCustomFields()` and six no-op `deals()->with…()` methods —
  and the seven resource keys renamed in v2.2.6 (`calenderEvents`,
  `creditnotes`, `payment_methods`, `payment_terms`, `external_parties`,
  `plannable_items`, `user_availability`). An old key throws an exception
  naming its replacement. `TeamleaderSDK::getDeprecatedResourceAliases()` is
  removed with them. See *From 2.3 to 3.0* in the upgrade guide.
- `users.getWeekSchedule` is no longer wrapped. Teamleader deprecated it;
  `userSchedules()->forUser()` wraps its successor. The spec-audit baseline
  records this as an accepted `endpoint.unwrapped`.

- **Configuration keys that had no effect.** `sideloading.*`, `caching.*`,
  `development.*`, four `rate_limiting.*` keys, four `logging.*` keys and
  three `error_handling.*` keys. The upgrade guide lists each with its `.env`
  variable. With them: `Resource::invalidateCache()` / `clearCache()` /
  `getCacheKey()`, which nothing called.
- `teamleader:health --fix` no longer calls `Cache::flush()`. A failing cache
  check cleared the application's entire cache — sessions and other packages'
  data included, if they share the store.

- The `McoreServices\TeamleaderSDK\Constants` namespace:
  `TeamleaderConstants` and `ErrorMessages`. Nothing in the SDK used either.

### Added (3.x branch)

- **Events.** `RequestSending`, `ResponseReceived`, `RequestFailed`,
  `RateLimitWaited`, `TokenRefreshed` and `TokenRefreshFailed`, in
  `McoreServices\TeamleaderSDK\Events`. Bodies are sanitised and no event
  carries a token. An event with no listener is not built. See the new
  *Events and Logging* guide.
- **`logging.channel`, `logging.log_requests` and `logging.log_responses`**
  work: the channel receives all SDK output (including `TokenService`'s), and
  the two flags register a listener that logs bodies at debug level.
- `McoreServices\TeamleaderSDK\Support\ResourceCatalog`: every registered
  resource with its endpoints, filters, sort fields, includes and
  capabilities, read by reflection without constructing anything. The spec
  audit and the generated API reference already used it (as the test-only
  `SdkInventory`); it moved to `src/` for the CLI.

### Fixed (3.x branch)

- **With rate limiting disabled, every successful request still called
  Redis** — to store the rate-limit headers and to put limiter statistics in a
  debug log line — so an application without Redis failed on every call. The
  headers are only stored when rate limiting is on, and the statistics are no
  longer computed per request (`getRateLimitStats()` still returns them).

### Security (3.x branch)

- The first 20 characters of the refresh token were logged at info level on
  every refresh. Nothing from the token is logged now.
- A token response without an access token was quoted in full in the
  exception message, which could include a refresh token. It now lists the
  keys received.

### Changed (3.x branch)

- `TeamleaderSDK::getApiCalls()` is bounded to the last 100 calls and no longer
  stores request bodies or response headers. It grew for the life of the
  process — without limit in a queue worker — and held personal data.
- The rate limiter logs through the SDK's logger when the SDK creates it;
  before, it logged nowhere.

- **`base_url`, `auth_url` and `api.retry_delay` are read.** They were in the
  published config and documented, but the hosts were hard-coded — the token
  refresh URL in `TokenService` separately — and the retry base was fixed at
  1000 ms.
- `teamleader:health` checks the application's default cache store, where
  tokens are cached, whatever the removed `caching.enabled` flag said.
- `ConfigurationValidator` checks for PHP 8.4 / Laravel 12, stopped warning
  "Laravel 11 detected" on every install, and validates `api.retry_delay`.
- `logging.channel` defaults to `null` rather than calling `config()` inside
  the config file.

- **Requires PHP 8.4 or higher.** PHP 8.2 and 8.3 are dropped; CI tests
  PHP 8.4 and 8.5 against Laravel 12 and 13, and code style is checked on
  PHP 8.4. PHPUnit 11 is dropped from the development dependencies.
- **One sort rule.** Deals, TimeTracking and CustomFields carried their own
  copies of `normaliseSort()` / `validateSortField()` / `normaliseSortOrder()`,
  which had drifted apart. They now delegate to `FilterTrait`, like every other
  sortable resource. Accepted inputs are unchanged, with two additions:
  TimeTracking now also takes a list of field names and a
  `['field' => 'order']` map, as the other resources already did.
- Sort validation messages name the endpoint on every resource:
  `Invalid sort field: title. deals.list accepts: created_at, weighted_value.`
  (previously `… Accepted: …` on most resources).

---

## [2.3.2] - 2026-09-30

### Fixed

- **`docs:check` failed on PHP 8.5 only.** The reference generator rendered
  method signatures from reflection, and PHP 8.5 reports a `self` return type
  differently from 8.2 – 8.4. Six pages with fluent `with…(): self` methods
  (contacts, deals, quotations, products, meetings, time tracking) therefore
  came out differently on 8.5, and `ReferenceDocsTest` failed in the 8.5 CI
  jobs while passing everywhere else. `self`, `static` and `parent` — and the
  declaring class's own name — are now rendered as `self` / `static` /
  `parent` on every version. A regression test covers it.

### Changed

- The documentation is live at
  [teamleader-sdk.mcore-services.dev](https://teamleader-sdk.mcore-services.dev/).
  README (plus a docs badge), CONTRIBUTING and `docs/README.md` link to it,
  and `composer.json` declares it as `homepage` and `support.docs`, so
  Packagist shows it too.

---

## [2.3.1] - 2026-09-30

**Documentation release.** The documentation moves from the GitHub wiki into
the repository, as `docs/`, published to GitBook through Git Sync. No runtime
behaviour changes.

### Added

- **`docs/` with hand-written guides**: installation, configuration,
  authentication, quick start, filtering and sorting, pagination, sideloading,
  validation, error handling, rate limiting, webhooks, token storage and
  security, Artisan commands, specification parity and upgrading. Ported from
  the wiki and the README, and checked against the code — see *Fixed* below.
- **An API reference generated from the code** — one page per resource under
  `docs/reference`, plus an index with a capability matrix, every include per
  endpoint, and the deprecated resource keys. Each page lists the facade
  method, the endpoints the resource calls (marked when Teamleader deprecates
  them), its filters, sort fields and includes, the public constants holding
  its accepted values, and every public method with its signature and
  docblock, deprecations included. It is built by `bin/docs`
  (`tests/Support/Docs/ReferenceGenerator.php`) with reflection only, so it
  describes exactly what the SDK does.
- **`composer docs:build`** and **`composer docs:check`**. The check exits 1
  when a reference page is missing, outdated or no longer belongs to a
  resource; it runs in the CI *Specification parity* job and as
  `ReferenceDocsTest`.
- `.gitbook.yaml`, pointing GitBook at `docs/`.

### Changed

- `Contacts` declares `$infoIncludes = []`. `contacts.info` has always
  rejected includes; the declaration makes the reference and the spec audit
  read the info endpoint separately instead of assuming it matches `list`.
- README and CONTRIBUTING link to `docs/` instead of the wiki. CONTRIBUTING
  describes the docs workflow, and the PR checklist asks for
  `composer docs:check`.
- README's sideloading example no longer uses the deprecated
  `deals()->withCustomer()` / `withResponsibleUser()` no-ops, or includes
  `deals.list` does not accept.

### Fixed — documentation that did not match the code

- **The wiki documented settings the SDK does not read.** Of the keys in
  `config/teamleader.php`, only the credentials, API version, timeouts, retry
  attempts, `throw_exceptions`, `validate_on_boot`, `rate_limiting.enabled`,
  `redis_connection`, `max_wait_ms` and `caching.enabled` have an effect. The
  logging, sideloading and development keys, the other rate-limiting keys, and
  `base_url` / `auth_url` are not read. The configuration guide now says so;
  wiring them up or removing them is a v3.0 item.
- **Error handling.** The wiki implied exceptions are thrown by default. They
  are not: `throw_exceptions` defaults to `false`, and a failed request is
  returned as an array with `error => true`. Server and connection errors are
  retried only when exceptions are on.
- **Token storage.** The wiki suggested adding encrypted casts to a model to
  encrypt stored tokens; the SDK reads and writes the table directly, so that
  has no effect. The guide now states that tokens are stored unencrypted and
  how to protect them. It also documented a `teamleader:token` command that
  does not exist.
- **OAuth `state`.** The SDK passes `state` through but does not verify it.
  The authentication guide now shows how to generate and check it.
- Wrong facade method names in the wiki's resource tables, a `Laravel 10.x`
  requirement, an `mcore-services-bv/teamleader-sdk` package name, and
  debugging helpers (`getApiCallCount()`, `getApiCalls()`) that do not exist.

### Upgrading

Nothing to do. To publish the docs, connect the repository to GitBook with
Git Sync (branch `main`); `.gitbook.yaml` points it at `docs/`.

---

## [2.3.0] - 2026-09-30

**Specification parity release.** Between v2.2.4 and v2.2.17, every resource
in the SDK was read against Teamleader's machine-readable API specification,
`@teamleader/focus-api-specification` **1.221.0**, one category per patch
release. This release adds no new API behaviour of its own: it marks the
audit as complete and puts the checks that hold it in place into CI.

### The audit in numbers

- **Baseline: 111 → 0 open divergences.** One is recorded as accepted: the
  deprecated `users.getWeekSchedule` wrapper, kept until v3.0.
- **Test methods: 227 → 608.** Every category now has a payload test (what
  the SDK sends) and a spec-contract test (field lists, required fields and
  enums compared with the specification).
- **13 categories, 17 releases.** These are CRM, Deals, Invoicing, Expenses,
  Projects, Tasks, Time Tracking, Calendar, Tickets, General, Products,
  Planning, and Files / Templates / Other, plus the resource-key cleanup in
  v2.2.6.

### What changed for users, in short

- **Silent no-ops now throw.** The API answers `200 OK` to a filter, sort
  field, include, option or body field it does not recognise, and ignores it.
  Across the SDK, these used to be passed through or dropped without a word,
  which meant unfiltered results or updates that changed nothing. They now
  throw an `InvalidArgumentException` naming the accepted values. This is the
  change most likely to surface in existing code; in every case the call was
  not doing what it appeared to.
- **Crashes fixed.** Among others:
  - `projectTasks()->list()` with any filter
  - `dealPhases()->delete()`
  - a field-name sort on tasks, events and subscriptions
  - an array sort on files
  - error responses in the accounts, cloud platforms, migrate and mail
    template helpers
- **Wrong answers fixed.** Among others:
  - `callOutcomes()->byIds()`, the plannable items status filter and
    `quotations()->byStatus()` returned everything
  - `projectLines()->unassigned()` returned every line
  - `accounts()->isUsingProjectsV2()` reported projects-v2 accounts as legacy
    when the call failed
  - `getCommonHolidays()` returned the wrong Belgian holidays
- **Endpoints and filters added** where the SDK was behind the
  specification. Examples: materials delete/duplicate/assign, full-day days
  off, ticket assignee filters, reservation filters, plannable item types,
  and cloud platform URLs for deals.
- **Stricter-than-API checks relaxed** where the SDK demanded fields the API
  does not: meeting customers, timer subjects, expense totals, task work
  types on update, and others.

The individual release notes below list every change with the reason for it.

### Added

- **`composer spec:check`** (`php bin/spec-audit --check`). It exits 1 on any
  error or warning not in the baseline, or any baseline entry that is no
  longer reported, and prints the summary either way.
- **CI: *Specification parity* job** in `tests.yml`. It checks that the
  committed fixtures are exactly what the generator produces from the pinned
  specification, then runs `spec:check`. The audit summary is written to the
  job page.
- **CI: weekly specification watch** (`spec-watch.yml`). It audits the SDK
  against the newest published specification and opens an issue listing the
  differences when it is newer than the pinned one. Nothing is committed
  automatically; bumping the pin stays a deliberate change.
- README: a *Specification Parity* section, a spec-version badge and a tests
  badge. CONTRIBUTING documents the CI checks.

### Changed

- **Supported versions: 2.3.x.** 2.2.x and older are unsupported; 2.3 runs on
  the same PHP and Laravel versions, with no breaking changes. See SECURITY.md.

### Deprecated — to be removed in v3.0

- `users()->getWeekSchedule()` — use `userSchedules()->forUser()`
- `plannableItems()->active()` — the endpoint has no status filter
- `products()->withCustomFields()`, and `deals()->withCustomer()`,
  `withResponsibleUser()`, `withDepartment()`, `withCurrentPhase()`,
  `withSource()` and `withAll()` — not includes, now no-ops
- `companies()->byName()`, `quotations()->byStatus()`,
  `creditNotes()->paid()` / `unpaid()` and `lostReasons()->search()` —
  the filters they implied do not exist
- `invoices()->draft()` — use `listDrafts()`
- The seven snake_case / misspelt resource keys aliased in v2.2.6 (for
  example `calenderEvents`) — use the camelCase names

---

## [2.2.17] - 2026-09-30

The last three categories — Files, Templates (Mail Templates) and Other
(Accounts, Cloud Platforms, Migrate, Webhooks) — brought in line with
`@teamleader/focus-api-specification` **1.221.0**. The audit reported nothing
for them; reading them against the spec found the following.

### Fixed

- **Files: an ascending sort was sent, and array sorts crashed.**
  files.list sorts `updated_at` in descending order only, but
  `sort_order => 'asc'` was sent anyway. A sort passed as
  `['field' => …, 'order' => …]` was a `TypeError`. Sorting now goes through
  `normaliseSort()`, and `asc` throws. Filter keys other than `subject` were
  dropped without a word; they now throw.
- **Accounts: a failed call reported the account as legacy.** When error
  responses are returned as arrays, `isUsingProjectsV2()` read a missing
  `data.status` and answered false. This affected every helper built on it
  (`getProjectsVersion()`, `getAutoSwitchDate()`, `getAccountStatus()` and
  the rest), and a projects-v2 account came back as legacy. They now throw a
  `TeamleaderException` carrying the API's message.
- **Cloud platforms: `deal` was missing.** cloudPlatforms.url returns the
  customer-facing link for deals too. `dealUrl()` and `getDealUrl()` are
  added. `getUrl()` and `batchUrls()` threw a `TypeError` on an error
  response; they now throw a `TeamleaderException`.
- **Migrate: the 0% tax rate could not be looked up.** `taxRate($dept, '0')`
  was rejected as empty. 0% is the rate for exports and reverse charge.
  `batchIds()` now throws with the mapping built so far when a lookup
  returns no UUID, instead of a `TypeError`.
- **Mail templates:** unknown filter keys were dropped without a word, and the
  `findBy*` / `asOptions()` / `groupedByLanguage()` helpers crashed on an
  error response. Unknown keys now throw, and the helpers return nothing
  found.

### Changed

- Constants `Webhooks::EVENT_TYPES` (all 95), `CloudPlatforms::TYPES`,
  `Migrate::RESOURCE_TYPES` / `ACTIVITY_TYPES`, `MailTemplates::TYPES` and
  `Files::SORT_ORDERS`, each checked against the spec. The webhook list is the
  one most likely to drift, and a new event type in the spec now fails the
  suite until it is added.
- New tests: `OtherPayloadTest` and `OtherSpecContractTest`. Baseline
  unchanged at 1 (accepted).

This completes the category-by-category audit. Every resource in the SDK has
now been read against spec 1.221.0.

---

## [2.2.16] - 2026-09-30

The Planning category — Plannable Items, Reservations and User Availability —
brought in line with `@teamleader/focus-api-specification` **1.221.0**. This
closes the last open error in the audit.

### Fixed

- **Plannable items: the `status` filter did not exist.** The SDK advertised
  and sent `status: [active|deactivated]`, which plannableItems.list does not
  have. The API ignored it, so the filter returned every item, and so did
  `active()`. The filter now throws with a pointer to the real filters.
  `active()` is deprecated (it raises `E_USER_DEPRECATED` once and goes in
  v3.0).
- **Plannable items: sorting was half-checked.** A list of sort objects was
  validated, but a plain field name or `"field:order"` string was not, and
  `sort_order` was ignored. All forms now go through `normaliseSort()`.
- **Unknown or mistyped filter keys were dropped without a word** on plannable
  items, reservations and user availability, and a string `ids` /
  `project_ids` was dropped too. They now throw, or are wrapped in the case
  of string ids. Assignees, sources and source types are checked, and dates
  must be real dates.

### Added

- Plannable items: the `types` filter (closingDay, dayOffType, meeting, task,
  call, externalEvent), with `ofTypes()` and `unassigned()` helpers.
- Reservations: the `project_ids`, `work_type_ids` and `term` filters.
- User availability: `page_size` / `page_number` shorthand alongside `page`.

### Changed

- **Unknown write fields throw** on reservations.create and
  reservations.update. For example, `plannable_item_id` cannot change on
  update.
- Unknown list options throw.
- Constants `TYPES`, `COMPLETION_STATUSES` and `PLANNED_TIME_STATUSES` on
  PlannableItems; `CREATE_FIELDS`, `UPDATE_FIELDS`, `SOURCE_TYPES` and
  `DURATION_UNITS` on Reservations. Each is checked against the spec.
- New tests: `PlanningPayloadTest` and `PlanningSpecContractTest`. Baseline
  6 → 1: nothing open, and the one accepted divergence
  (users.getWeekSchedule).

---

## [2.2.15] - 2026-09-30

The Products category — Products, Product Categories, Price Lists and Units
of Measure — brought in line with `@teamleader/focus-api-specification`
**1.221.0**.

### Fixed

- **Product validation never ran.** `validateProductData()` built a table of
  rules and then checked none of them, so every field, currency and shape
  was sent as given. It now checks:
  - unknown fields
  - the name-or-code requirement
  - price and currency shape on `purchase_price`, `selling_price` and
    `price_list_prices[]`
  - the stock threshold, which cannot be negative and has action `notify`
- **`custom_fields` is not a products include.** products.info returns custom
  fields on every call, and products.list takes no includes. Asking for it was
  a no-op; `withCustomFields()` is now documented as one (deprecated, removed
  in v3.0), and `info(…, 'custom_fields')` throws.
- **`withSuppliers()->list()` did nothing.** `suppliers` is an info-only
  include; list() now throws when given one, and the usage example uses
  `info()`.
- **Unknown filter keys were passed through** on products.list and dropped
  silently on productCategories.list and priceLists.list, so a mistyped key
  returned everything. They now throw. A string `ids` on priceLists.list was
  dropped; it is wrapped now. `search` / `general_search` keep working as
  aliases for `term`.

### Changed

- Includes are validated on products.info (`suppliers`). Unknown list options
  throw.
- Constants `ADD_FIELDS`, `UPDATE_FIELDS`, `INFO_INCLUDES`, `CURRENCIES` and
  `STOCK_THRESHOLD_ACTIONS` on Products, each checked against the spec.
- `bin/spec-audit --summary` no longer counts accepted findings under their
  severity as well, so a category with only accepted divergences reads
  0 | 0 | 0 | n.
- New tests: `ProductsPayloadTest` and `ProductsSpecContractTest`. Baseline
  7 → 6 (5 open, 1 accepted).

---

## [2.2.14] - 2026-09-30

The General category — Users, Teams, Departments, Work Types, Custom Fields,
Notes, Email Tracking, Document Templates, Closing Days, Days Off, Day Off
Types, User Schedules and Currencies — brought in line with
`@teamleader/focus-api-specification` **1.221.0**.

### Fixed

- **Sorting on users, teams and departments was passed through unchecked**,
  and none of their valid sort fields were advertised. Sorting now goes
  through `normaliseSort()`, which validates the field and order.
- **Work types: the sort sent was malformed and ignored.** workTypes.list
  takes no sort, and the SDK sent a single object where the API would expect
  a list. The `sort` option now throws. `sortedByName()` sorts the page
  client-side instead.
- **Email tracking and notes ignored their advertised filters.**
  `subject.type` and `subject.id` were listed but never read. Email tracking
  without a subject was sent with an empty filter, which the API refuses.
  Notes with an incomplete subject were sent with no filter at all. The
  dotted keys now work, and a missing subject throws.
- **Notes could be "created" on a legacy `project`.** notes.list accepts that
  subject type but notes.create does not; the two lists are now separate.
- **Unknown or mistyped filter keys were dropped without a word** on users,
  teams, departments, work types, document templates, user schedules and
  users.listDaysOff, so those calls returned unfiltered results. They now
  throw. A string `ids` is wrapped, and status and document-type values are
  checked.
- **Days off: full-day imports were impossible.** daysOff.import accepts
  `['date' => 'Y-m-d']` for a full day; the SDK accepted only timed days.
  There is a new `importFullDays()` helper, which chunks at the API's
  100-day limit. Imports over the limit throw, and `Z` datetimes are
  accepted.
- **Day off types rejected a single-day validity** (`until` equal to `from`).
- **`closingDays()->getCommonHolidays()` returned the wrong holidays.** It
  listed Boxing Day, which is not a Belgian public holiday, and missed six of
  the ten legal ones. It now returns all ten, with Easter, Ascension and Whit
  Monday computed by the SDK, so ext-calendar is no longer needed. A country
  other than BE throws instead of returning Belgian dates.
- `UserSchedules` could misjudge the seven-day range across a second
  boundary: dates were parsed with the current time attached.

### Changed

- **`Users::$availableIncludes` is a flat list.** It was the only
  name => description map. `external_rate` is now declared as an info-only
  include (`$infoIncludes`), and users.list rejects includes.
- **`users()->getWeekSchedule()` raises `E_USER_DEPRECATED`** once per
  process; Teamleader deprecates the endpoint. Use
  `userSchedules()->forUser()`. The method goes in v3.0.
- **Unknown write fields throw** on the create and update calls of custom
  fields, notes, email tracking, day off types and closing days. For
  example, notes.update takes `content` only.
- Includes are checked on users.info (`external_rate`); departments.info takes
  none. Unknown list options throw.
- `users.listDaysOff` and `userSchedules.list` send `includes=pagination`, so
  the response carries the total count in `meta`.
- Departments and document templates no longer claim paging, sorting or
  includes the API does not offer.
- `normaliseSort()` accepts a field => order map (`['name' => 'desc']`) on
  every resource that uses it.
- Constants for field lists and enums on Currencies, CustomFields,
  DayOffTypes, DaysOff, Departments, DocumentTemplates, EmailTracking, Notes
  and Users, each checked against the spec.
- New tests: `GeneralPayloadTest` and `GeneralSpecContractTest`. The
  deprecated users.getWeekSchedule wrapper is recorded in the baseline as
  accepted, with its reason. Baseline 21 → 7 (6 open, 1 accepted).

---

## [2.2.13] - 2026-09-30

The Tickets category — Tickets and Ticket Statuses — brought in line with
`@teamleader/focus-api-specification` **1.221.0**.

### Fixed

- **The advertised dotted filters did nothing.** `relates_to.type`,
  `relates_to.id` and `exclude.status_ids` were listed as filters but sent flat
  (`"relates_to.type": …`), which the API ignored, so those calls returned
  every ticket. They are now nested into the objects the API expects. The
  nested form keeps working.
- **Unknown filter keys were passed through** on tickets.list and dropped
  silently on tickets.listMessages and ticketStatus.list, so a mistyped key
  returned unfiltered results. They now throw. A string `ids` on
  ticketStatus.list was dropped; it is wrapped now.
- `update()` could not remove a third-party participant: `participant.customer`
  null was rejected. It is accepted now.

### Added

- **`assignee_ids` filter**, with `assignedTo([...])` and `unassigned()`
  helpers. A null entry matches unassigned tickets, as the API documents.
- `listMessages()` sends `includes=pagination`, so the response carries the
  total message count in `meta`.

### Changed

- **Unknown write fields throw** on tickets.create and tickets.update.
  `initial_reply`, for example, is create only.
- These are now checked: `relates_to.type`, the `exclude` shape, message
  filter types, and `importMessage()`'s `sent_at`, which must be ISO 8601
  with a timezone.
- `info()` throws when given includes; tickets.info takes none. Unknown list
  options throw too.
- Constants on Tickets (`CREATE_FIELDS`, `UPDATE_FIELDS`, `REQUIRED_ON_CREATE`,
  `CUSTOMER_TYPES`, `INITIAL_REPLY_OPTIONS`, `MESSAGE_TYPES`, `SENT_BY_TYPES`,
  `MESSAGE_FILTERS`) and `TicketStatus::STATUS_TYPES`, each checked against the
  spec.
- New tests: `TicketsPayloadTest` and `TicketsSpecContractTest`. Baseline
  22 → 21.

---

## [2.2.12] - 2026-09-30

The Calendar category — Events, Meetings, Calls, Call Outcomes and Activity
Types — brought in line with `@teamleader/focus-api-specification` **1.221.0**.

### Fixed

- **`callOutcomes()->byIds()` returned every outcome.** callOutcomes.list
  takes no filter, so the `ids` the SDK sent was ignored. `byIds()` now reads
  the list and filters it client-side. `findByName()` now searches every page
  rather than the first 20, and `list()` with a filter throws.
- **Events: sorting.** A field-name sort (`'sort' => 'starts_at'`) was a
  `TypeError`, and `sort_order` was ignored.
- **Events: filtering on user attendees did not work.** events.list filters
  attendees of type `contact` only, but `forAttendee('user', …)` was accepted
  and sent. It now throws; `forUser()` is the way to get a user's events.
- **Meetings required a `customer`**, which meetings.schedule does not
  require. Meetings' sort was passed through unchecked, `scheduled_at` was
  not advertised, and the `includes` option key was ignored.
- **Unknown filter keys were passed or dropped silently** on events,
  meetings, calls and activity types, and a string `ids` was dropped on
  activity types. Every one of these returned unfiltered results. They now
  throw, or are wrapped in the case of `ids`.
- Event create and update rejected UTC datetimes written with `Z`.

### Changed

- **Unknown write fields throw** on events.create/update,
  meetings.schedule/update/createReport and calls.add/update. Examples:
  `activity_type_id` on events.update (it cannot change) and `work_order_id`
  on meetings.update (schedule only).
- These are now checked:
  - calls are assigned to users only (`assignee.type`)
  - meetings: `project_id` and `milestone_id` are mutually exclusive, and
    `group_id` requires `project_id`
  - end times must follow start times
  - attendee, link, customer and report-target types
- `calls()->list()` sends `includes=pagination`, so the response carries
  the total match count in `meta`.
- Includes are checked on meetings (`tracked_time`, `estimated_time`);
  events.info and calls.info take none. Unknown list options throw.
- Field-list, required-field and enum constants on Events, Meetings and
  Calls, each checked against the spec.
- New tests: `CalendarPayloadTest` and `CalendarSpecContractTest`.
  Baseline 25 → 22.

---

## [2.2.11] - 2026-09-30

The TimeTracking category — Time Tracking and Timers — brought in line with
`@teamleader/focus-api-specification` **1.221.0**. The list side was
hardened in v2.1.2; this release covers the write side, includes and timers.

### Fixed

- **`timers()->update()` did not exist.** The usage example called it, and
  the method is `updateCurrent()`. `update()` is now an alias, and the example
  uses `updateCurrent()`.
- **`timers()->start()` was stricter than the API.** It required a subject and
  a `work_type_id`; timers.start requires nothing.
- **Updating a time entry without its timing was sent anyway.**
  timeTracking.update requires `duration` and exactly one of `started_at` or
  `started_on` on every call, so updating only the description failed at the
  API. It now throws before sending.
- **The `includes` option key was ignored** on `timeTracking()->list()`; only
  `include` was read. Both work now.
- The `subject_types` filter rejected `null`, which the API documents as the
  way to match tracked time with no subject.

### Changed

- **Unknown write fields throw** on timeTracking.add/update and
  timers.start/update. Examples: `ended_at` or `user_id` on an update (add
  only) and `invoiced` anywhere.
- Includes are checked on list and info: `materials` and `relates_to` only.
  Unknown list options throw too.
- Datetimes (`started_at`, `ended_at`, `resume()`'s start) must be ISO 8601
  with a timezone. `started_on` must be a real date, and `ended_at` must come
  after `started_at`.
- A new entry that mixes the three timing shapes (for example `started_on`
  plus `ended_at`) now throws.
- Constants `ADD_FIELDS`, `UPDATE_FIELDS`, `INCLUDES` and `RELATES_TO_TYPES`
  on TimeTracking; `WRITE_FIELDS` and `SUBJECT_TYPES` on Timers.
- New tests: `TimeTrackingPayloadTest` and `TimeTrackingSpecContractTest`.
  Baseline 26 → 25.

---

## [2.2.10] - 2026-09-30

The Tasks category (`tasks.*`) brought in line with
`@teamleader/focus-api-specification` **1.221.0**.

### Fixed

- **Sorting didn't work.** The resource advertised `name`, which tasks.list
  does not accept, and left out the two fields it does: `created_at` and
  `due_on`. A field-name sort (`'sort' => 'due_on'`) was a `TypeError`, and
  `sort_order` was ignored. Sorting now goes through `normaliseSort()`, which
  validates the field and the order.
- **Unknown filter keys passed straight through**, so a mistyped key such as
  `status` or `assignee_id` returned every task. They now throw. These are
  checked too:
  - `completed` and `scheduled` must be booleans
  - `due_by` and `due_from` must be real dates
  - `customer.type` must be contact or company
- `schedule()` rejected UTC datetimes written with `Z`. It accepts them now,
  plus fractional seconds, and throws when `ends_at` is not after `starts_at`.
- Dates are checked as real calendar dates, so `2026-02-30` is rejected.
  Before, only the shape was checked.

### Changed

- **Unknown write fields throw** on create and update. `priority`, which
  tasks.info returns but the API does not accept on writes, was the likeliest
  one to be sent by mistake.
- `estimated_duration.unit` must be `min`, the only unit the API accepts.
- `info()` throws when given includes; tasks.info takes none. Unknown list
  options also throw.
- Constants `CREATE_FIELDS`, `UPDATE_FIELDS`, `REQUIRED_ON_CREATE`,
  `ASSIGNEE_TYPES`, `CUSTOMER_TYPES`, `DURATION_UNITS` and `PRIORITIES`, each
  checked against the spec.
- New tests: `TasksPayloadTest` and `TasksSpecContractTest`. Baseline 29 → 26.

---

## [2.2.9] - 2026-09-30

The Projects category — Projects, Groups, Tasks, Materials, Project Lines,
External Parties, and the legacy Projects and Milestones — brought in line with
`@teamleader/focus-api-specification` **1.221.0**.

### Fixed

- **`projectTasks()->list()` with any filter was a fatal error.** It called a
  `buildFilters()` method the class did not have. A new test scans every
  resource for calls to methods that do not exist; this was the only one left.
- **`projectLines()->unassigned()` returned every line.** It sent
  `assignees: null`, which an `isset()` check then dropped, so no filter
  reached the API. It now sends `assignees: [null]`, the form the API
  documents; a null entry can be combined with real assignees.
- **Legacy milestones: sorting.** A plain field name (`'sort' => 'due_on'`)
  raised a PHP warning, and an unknown field was quietly replaced by `due_on`.
  Both forms are accepted now and an unknown field throws.
- **Legacy milestones: `billing_method`, `budget` and `price` looked unsupported.**
  The spec declares them in a oneOf beside the main properties, which the
  fixture generator missed (see Tooling). They are validated now:
  `fixed_price` needs a `price` and takes no `budget`.
- **Task updates were stricter than the API.** Switching a task to
  `work_type_rate` required a `work_type_id` in the same call; the API only
  forbids clearing it. On create, the deprecated `task_type_id` is accepted in
  its place.
- `projects()->info()` accepted `custom_fields`, which projects.info does not
  take; it accepts `legacy_project` only.
- Projects' sort fields were never validated; they are now.
- Legacy projects' sort order was not checked; `asc`/`desc` are enforced.

### Added

- **Materials: `delete()`, `duplicate()`, `assign()`, `unassign()`** and the
  `assignUser()`/`assignTeam()`/`unassignUser()`/`unassignTeam()` helpers.
  All four endpoints existed in the API but had no SDK method.
- **Paging** on materials and project groups (`page_size`, `page_number`),
  added to both endpoints in spec 1.221.0.
- `projects()->list()` sends `includes=pagination` by default, so the
  response carries the total match count in `meta`.
- Project groups and projects: `update()` accepts `billing_method` as a plain
  method name and sends it as `{value, update_strategy: none}`.
- Project lines take `types` and `assignees` as flat filter keys. The nested
  `filter` form still works.
- `assignUser()` and similar helpers on `projects()` as well.
- Field-list, enum and strategy constants on every resource in the category
  (`CREATE_FIELDS`, `UPDATE_FIELDS`, `BILLING_METHODS`, `STATUSES`,
  `DELETE_STRATEGIES` and so on), each checked against the spec.

### Changed

- **Unknown write fields throw** on every create and update in the category.
  The API ignores unknown fields and reports success. The check caught fields
  that belong to other endpoints: `status` on materials.create, `group_id` on
  tasks.update, `customers` on projects.update, and `milestones` on the legacy
  projects.update.
- **Unknown filter keys and options throw.** Before, groups, materials and
  milestones dropped them without a word. The same goes for sort or include
  options on endpoints that take none.
- These are now validated:
  - money objects (`{amount, currency}` with the 23 currencies)
  - durations (`{value, unit}`)
  - assignee lists, colours, and status and customer-type filters
  - milestone lists and participant roles on legacy projects.create
- Dates on groups are checked as `Y-m-d`. Before, anything `date_parse()`
  accepted got through.
- Projects, groups, tasks and materials extend a new abstract
  `ProjectsV2Resource`, which holds their shared assign/unassign endpoints
  and payload checks. Public methods and signatures are unchanged.

### Tooling

- **Fixture generator:** request bodies are resolved deep, so a oneOf beside
  the top-level properties now counts. This fixed `milestones.create` in this
  category. It also affects `products.add`, whose 13 fields were missing, and
  `timeTracking.add`/`update` (`started_at`, `started_on`, `ended_at`,
  `duration`). Those will be picked up in their own passes.
- **Auditor:** a top-level request parameter may be advertised as a filter
  even when the endpoint also has a filter object. projectLines.list's
  `project_id` is one.
- New tests: `ProjectsPayloadTest`, `ProjectsSpecContractTest` and
  `ResourceMethodCallsTest`. Baseline 42 → 29.

---

## [2.2.8] - 2026-09-30

The Expenses category — Expenses, Incoming Invoices, Incoming Credit Notes,
Receipts, Bookkeeping Submissions — brought in line with
`@teamleader/focus-api-specification` **1.221.0**. The audit reported three
sort warnings here; reading the code against the spec found considerably more.

### Fixed — Bookkeeping Submissions

- **`forInvoice()` and `forCreditNote()` could not work.** They sent subject
  types `incoming_invoice` and `incoming_credit_note`; the API accepts
  `incomingInvoice`, `incomingCreditNote` and `receipt`. Every status helper
  built on them (`confirmed()`, `failed()`, `sending()`, `latest()`,
  `hasConfirmed()`, `hasFailed()`, `statistics()`) was affected for those two
  document types. The camelCase values are now sent; the old snake_case
  spellings are still accepted and translated, so existing calls keep working.
- Paging and sorting options, and filter keys other than `subject`, now throw.

### Fixed — Expenses

- **`unpaid()` sent `payment_statuses: ["unpaid"]`**, which is not a payment
  status. It now sends `not_paid` and `partially_paid`. The documented status
  list was `paid, unpaid`; the API's is `unknown, paid, partially_paid,
  credited, not_paid`.
- **A field-name sort was dropped.** `['sort' => 'document_date']` was ignored
  because only sort objects were read; `sort_order` was ignored too.
- **Unknown filter keys were dropped without a word**, and no enum was checked:
  source types, review, bookkeeping and payment statuses, supplier type and
  date operators now are. A date filter with `between` but no `start` or `end`,
  or `equals`/`before`/`after` with no `value`, throws instead of being sent
  half-built.
- `includes=pagination` is sent by default, so the response carries a `meta`
  block with the total match count.

### Fixed — Incoming Invoices, Incoming Credit Notes, Receipts

- **One implementation instead of three copies.** The three resources were
  ~550-line near-duplicates that had drifted apart. They now extend a new
  abstract `ExpenseDocument`; each declares only its field list, the keys its
  `total` accepts and its payment statuses. Public methods and signatures are
  unchanged, except `updatePayment()`'s `$payment`, which is now nullable.
- **`add()` required a `total`**, and receipts required
  `total.tax_inclusive`. The API requires only `title` and `currency.code`.
- **`updatePayment()` required the payment amount** on every call. The API
  requires only the two IDs; pass `null` to change just the date, method or
  remark.
- **Receipts accepted `tax_exclusive`, `due_date`, `iban_number` and
  `payment_reference`**, none of which `receipts.add`/`update` have — the API
  dropped them. Unknown fields now throw on all three, and a receipt's `total`
  accepts `tax_inclusive` only.
- `total.*` entries must be `['amount' => number]` or `null`.
- `info()` rejects includes; the endpoint takes none.
- Incoming invoices' payment statuses gain `credited`. `listPayments()`
  documents `meta.total.currency` (spec 2026-09-21).

### Changed — tooling

- `SdkInventory` reads endpoints from parent classes too, so resources built on
  a shared base (`ExpenseDocument`) are audited correctly.
- New `pagination.meta` check: a resource claiming a meta block the endpoint
  does not document is a warning; an endpoint offering one the resource does
  not request is reported as info.
- Tests: `ExpenseDocumentsPayloadTest` (shared behaviour run against all three
  documents), `ExpensesSpecContractTest`. Baseline 45 → 42.

---

## [2.2.7] - 2026-09-30

The Invoicing category — Invoices, Credit Notes, Subscriptions, Tax Rates,
Withholding Tax Rates, Payment Methods, Payment Terms, Commercial Discounts —
brought in line with `@teamleader/focus-api-specification` **1.221.0**.

Two crashes, three methods that silently returned everything, and several
client-side checks that were **stricter** than the API and blocked valid
calls. As before, values the API would silently ignore now throw.

### Fixed — crashes

- **`Subscriptions::list()` with a field-name sort was a `TypeError`.**
  `['sort' => 'title']` reached `array_map()` as a string — the
  `Projects::buildSort()` fatal of v2.2.2, on another resource.
- **`Invoices::list()` and `TaxRates::list()` with a field-name sort** made
  `foreach()` iterate a string: a warning, and no sort sent (Invoices) or the
  string sent where the API expects objects (TaxRates). `sort_order` was
  ignored on all three. All now go through `normaliseSort()`, which accepts a
  name, a list of names or sort objects, and validates the field.

### Fixed — silently returned everything

- **`Creditnotes::paid()` and `unpaid()`** set an internal `_paid` flag that
  `buildFilters()` stripped, so both returned every credit note. There is no
  paid filter on `creditNotes.list`; both now throw pointing at client-side
  filtering on `data[].paid`. Deprecated, removed in v3.0.
- **`WithholdingTaxRates::list()` ignored its arguments.** The endpoint takes a
  `department_id` filter; it is now sent, and `forDepartment()` is new.
- **Unknown filter keys** were forwarded unchecked on Invoices, Credit Notes,
  Subscriptions, Tax Rates and Payment Methods — the API ignores them and
  returns every record. They now throw, via a new shared
  `rejectUnknownFilters()` on the `ValidatesWritePayload` trait.
- `status` given as a string was sent as a string on Subscriptions and Payment
  Methods, where the API expects an array. It is wrapped and validated.

### Fixed — checks stricter than the API

- **`unit_price` was required on line items** (invoices, credit-partially,
  subscriptions). The specification requires only `quantity`, `description`
  and `tax_rate_id`; a product line can take the product's price. When
  `unit_price` is given, `amount` and `tax: excluding` are still required.
- **`Creditnotes::download()`** accepted only `pdf` and `ubl/e-fff`, rejecting
  `ubl/peppol_bis_3` and `ubl/xrechnung` which the endpoint supports.

### Fixed — checks the API makes that the SDK did not

- **`Subscriptions::create()` did not require `department_id`**, which the API
  requires.
- **`sending_methods` must include `email`** when the action is
  `book_and_send` — it is the fallback when Peppol or postal sending fails
  (spec clarification, 2026-09-21). `sending_methods` is required for
  `book_and_send` and rejected for `draft` / `book`.
- **Billing cycle**: the allowed `period` depends on the unit (week 1–2; month
  1, 2, 3, 4, 6; year 1–10) and `days_in_advance` is 0, 7, 14, 21 or 28.
- **`Invoices::updateBooked()`** shared `update()`'s validator, so `currency`,
  `discounts`, `delivery_date`, `document_template_id` and
  `purchase_order_number` — which `invoices.updateBooked` does not accept — were
  sent and dropped. Each write method now checks its own field list.
- Unknown fields throw on `create()`, `update()` and `updateBooked()` for
  Invoices and Subscriptions.
- **`invoice_content`** (`goods`, `services`, `goods_and_services`, spec
  2026-09-02) is enum-checked on invoices and subscriptions; also
  `invoice_generation.payment_method` and `delivery_information.type`.

### Fixed — includes

- **Invoices** declared `$supportsSideloading = false` while `invoices.list` and
  `invoices.info` both take `late_fees`, `totals.due_incasso_inclusive`,
  `totals.fixed_late_fee` and `totals.interest`. All four are advertised and
  validated on both methods; fluent `with()` works on `info()` too.

### Changed — tooling

- **The fixture generator merged `oneOf` alternatives wrongly.** A property
  several alternatives declare kept only the last one's enum, so
  `billing_cycle.periodicity.unit` read as `year` only. Alternatives are now
  merged property by property (enums unioned, array items included), and a
  `oneOf` nested in an `allOf` is resolved too — which surfaced the
  `expected_payment_method.method` enum and two more on Meetings and
  Quotations. Response fields are unaffected.
- Tests: `InvoicingPayloadTest`, `InvoicingSpecContractTest`. Baseline 51 → 45.

---

## [2.2.6] - 2026-09-30

Seven resource keys renamed to camelCase, with the old keys kept as deprecated
aliases. No behaviour changes for existing code.

### Fixed

- **36 SDK usage examples could not run.** The resource map had one misspelling
  and six snake_case outliers, while the examples published by those same
  resources used the camelCase names — so `$teamleader->paymentMethods()` threw
  *"Method or resource 'paymentMethods' not found"* and only `payment_methods()`
  worked. The audit reported these as `example.resource` errors across
  Calendar, Invoicing, Planning and Projects.

| Canonical (new) | Deprecated alias |
|---|---|
| `calendarEvents()` | `calenderEvents()` |
| `creditNotes()` | `creditnotes()` |
| `paymentMethods()` | `payment_methods()` |
| `paymentTerms()` | `payment_terms()` |
| `externalParties()` | `external_parties()` |
| `plannableItems()` | `plannable_items()` |
| `userAvailability()` | `user_availability()` |

  Both spellings return the same instance. An old key raises one
  `E_USER_DEPRECATED` notice per process — Laravel logs it to the
  `deprecations` channel and never throws it. **The aliases are removed in
  v3.0**; the facade's `@method` block lists both, with the old ones marked
  deprecated, so IDE completion steers towards the new names.
- A resource an application registered itself under one of the old keys with
  `addResource()` keeps precedence over the alias.
- The Events and Creditnotes usage examples called `events()` and
  `creditnotes()`; they now use `calendarEvents()` and `creditNotes()`.

### Changed — tooling

- `SpecAuditor` resolves deprecated keys in usage examples and reports them as
  `example.deprecated_key` warnings. Baseline **87 → 51** (errors 44 → 8).
- `ResourceKeyAliasTest` covers every rename, the shared instance, the
  once-per-process notice and `addResource()` precedence.

---

## [2.2.5] - 2026-09-30

The Deals category — Deals, Quotations, Orders, Phases, Pipelines, Sources,
LostReasons — brought in line with `@teamleader/focus-api-specification`
**1.221.0**. Same theme as v2.2.4: values the API accepts with `200` and
ignores now throw before the request. **Code that relied on an ignored value
will now get an `InvalidArgumentException`** — most likely the five Deals
includes below, a descending sort on Sources or LostReasons, or `phase_id` on
`Deals::update()`.

### Fixed — Deals

- **Five phantom includes.** `lead.customer`, `responsible_user`, `department`,
  `current_phase` and `source` were advertised as includes. None is: all five
  are returned on every deal as a `{type, id}` reference. `deals.list` takes
  `custom_fields` and `second_responsible_user`; `deals.info` takes
  `second_responsible_user` only (custom fields come back without asking).
  Both are now validated. `withCustomer()`, `withResponsibleUser()`,
  `withDepartment()`, `withCurrentPhase()`, `withSource()` and `withAll()` are
  deprecated no-ops, removed in v3.0 — the data they appeared to fetch still
  arrives by default. **`withSecondResponsibleUser()`** added.
- **`update()` accepted `phase_id`.** `deals.update` has no such field; the API
  dropped it and reported success. A deal changes phase through `move()`.
  Unknown write fields now throw on create and update.
- **`second_responsible_user_id`** is accepted on create and update, and a
  **negative `estimated_value.amount`** is allowed (both spec 1.221.0).
- `filter.status[]` and `filter.customer.type` are checked against their enums.
- A custom field could not be cleared: `isset()` rejected `'value' => null`
  client-side.

### Fixed — Phases

- **`delete()` was a fatal error.** It called `parent::delete()`, which
  `Resource` does not define — the Pipelines defect fixed in v2.2.2, on the
  class next to it. It also demanded `new_phase_id`, which the spec makes
  optional. Both fixed.
- **`update()` did not require `requires_attention_after`**, which
  `dealPhases.update` requires on every call. It passed client-side and failed
  at the API. It also accepted `deal_pipeline_id`, which cannot change.
- Unknown filter keys were dropped silently; a string `ids` was dropped too.

### Fixed — Pipelines, Sources, LostReasons

- **Pipelines** and **Sources** gain the `term` filter (spec 1.221.0).
  `Pipelines::search()` is new; `Sources::search()` now uses it — before, it
  filtered the first page in PHP, so a source past the first 20 was never
  found.
- **Sources** and **LostReasons** silently rewrote any sort to `name`/`asc`, so
  asking for descending order returned ascending. Both endpoints accept only
  `name`, ascending; anything else now throws. The Tags defect fixed in
  v2.1.2, twice more.
- **`Sources::all()`** returned the first 20 sources and **`LostReasons::all()`**
  the first 100; both now walk every page, and `selectOptions()`,
  `getStatistics()`, `getStats()` and `getSelectOptions()` with them.
- Unknown filter keys throw on all three; pipeline `status` values are checked.
- `LostReasons::info()` no longer returns an `included` key the API never sends.
- `LostReasons::search()` is deprecated — it never searched by text.

### Fixed — Quotations

- **The `expiry` include is back.** v2.2.2 removed it because neither
  `quotations.list` nor `quotations.info` declares an includes request
  property. But both responses document the `expiry` field as *"returned if
  user has access to quotation expiry and `includes=expiry` is requested"*. The
  specification contradicts itself; the response documentation is the more
  specific of the two, and an unrecognised include is ignored rather than
  rejected, so offering it costs nothing. **`withExpiry()`** added.
- **Status values were wrong:** `rejected` and `closed` do not exist; `refused`
  was missing. The API returns `open`, `accepted`, `refused`, `expired`.
- `create()` no longer demands `grouped_lines` or `text` — the spec requires
  `deal_id` only. `send()` no longer demands `from`, which is optional.
- `name` is accepted on create and update (spec 1.221.0); unknown fields throw;
  `discounts[].type`, `expiry.action_after_expiry`, and on `send()` the
  `language`, sender type and recipient customer types are enum-checked.

### Fixed — Orders

- Includes on `list()` and `info()` are validated (`custom_fields` only). The
  comment describing pagination as undocumented is trimmed — spec 1.221.0
  declares it.

### Changed — tooling

- **The fixture generator reads includes from response documentation too.**
  Several endpoints name an include only in a response field description —
  *"Only included with request parameter `includes=…`"*. That is how the
  Quotations `expiry` contradiction surfaced. It also cleared a false positive:
  the Meetings `estimated_time` include is documented this way, so it was
  never phantom. `pagination` mentions are recorded separately as
  `declares_pagination_meta`.
- A sort field documented only as a `default` (`dealSources.list`) is now
  extracted.
- `API-list-endpoint-contract.md` shows both include sources.
- Tests: `DealsReferencePayloadTest`, `QuotationsOrdersPayloadTest`,
  `DealsSpecContractTest`; `DealsResourceTest` updated. Baseline 97 → 87.

---

## [2.2.4] - 2026-09-30

First release of the full specification audit: the tooling that runs it, and
the CRM category (Contacts, Companies, Addresses, BusinessTypes, Tags) brought
in line with `@teamleader/focus-api-specification` **1.221.0**.

The theme is the same as every release since 2.1.2: the API answers `200` to a
filter, sort field, include or body field it does not recognise, and ignores
it. Each fix below replaces one of those silent no-ops with an
`InvalidArgumentException` before the request is sent. **Code that passed an
unsupported value will now throw** where it previously got an unfiltered,
unsorted or unchanged result back.

### Fixed — Contacts

- **`company_id => null` was dropped.** `applyFilters()` skipped every null
  value, so filtering for contacts linked to no company — which the API
  supports since spec 1.221.0 — returned every contact. `null` is now sent for
  `company_id`, and **`withoutCompany()`** wraps it.
- **Unknown filter keys were dropped silently.** They now throw, as they have
  on Companies since v2.2.1.
- **`create()` required `first_name` or `last_name`.** `contacts.add` requires
  `last_name`, so a contact with only a first name passed client-side and was
  rejected by the API. `last_name` is now required.
- **Sort fields were not validated.** Only `added_at`, `name` and `updated_at`
  are accepted; anything else throws instead of being ignored.
- **Includes on `list()` were not validated.** Only `custom_fields` is accepted.

### Fixed — Companies

- **A `status` array was reduced to its first element.** `['active',
  'deactivated']` quietly returned active companies only. The API declares a
  single string, so an array now throws.
- **Sort fields were not validated.** Only `name`, `added_at` and `updated_at`.
- **Includes on `list()` were not validated.** Only `custom_fields`;
  `related_companies` / `related_contacts` remain `info()`-only, as before.
- **`ids` as a string was dropped.** It is now wrapped into an array (also on
  Contacts).

### Changed — Contacts and Companies write validation

- `create()` and `update()` reject top-level fields the endpoint does not
  declare, listing the accepted ones. The API would drop them and report
  success — `['vat' => ...]` for `vat_number`, or `company_id` on a contact.
- Enum checks, from the specification: `emails[].type` (`primary` on
  contacts; `primary`, `invoicing` on companies), `telephones[].type`
  (`phone`, `mobile`, `fax` on contacts; `phone`, `fax` on companies),
  `addresses[].type`, `gender` and `preferred_currency`.
- The `email` filter accepts only `type: primary`, the one value the list
  endpoints declare.
- New trait **`ValidatesWritePayload`** holds these checks, for reuse by the
  remaining categories. The field lists and enums live as public constants on
  each resource (`Contacts::GENDERS`, `Companies::CURRENCIES`, …).

### Fixed — Addresses, BusinessTypes, Tags

- **Addresses** and **BusinessTypes**: unknown parameter keys and any paging or
  sorting option now throw. `country` and `language` are documented as the
  top-level parameters they are, not filters.
- `isValidCountryCode()` / `isValidLanguageCode()` returned `1`/`0` from
  `preg_match()`; they return `bool`.
- **Tags**: the `tag` sort field is declared on `$availableSortFields`, where
  the validator reads it. No behaviour change.

### Added — specification audit tooling

- **Specification contract for every endpoint.**
  `generate-spec-fixtures.mjs` now extracts all 293 endpoints of
  `@teamleader/focus-api-specification` into
  `tests/Fixtures/specification/contract.json`: filters with type, enum and
  nullability; sort fields and orders; pagination; includes; required fields;
  every request enum; response fields; deprecation. The specification version
  is pinned in a new `package.json` (dev tooling only), currently **1.221.0**.
- **`SpecAuditor`**: compares every registered resource against the contract:
  endpoints called (unknown, deprecated, unwrapped, unmapped), filters, sort
  fields, includes, capability flags, and the methods and resource keys named
  in `$usageExamples`. It reads declarations by reflection and source scan, so
  it needs no Laravel boot.
- **`bin/spec-audit`**: the audit as a Markdown or JSON report, filterable by
  category and severity (`composer spec:audit`).
- **`SpecParityTest`**: gates the audit against
  `tests/Fixtures/specification/baseline.json`. Unknown divergences fail, and so
  do baseline entries that have since been fixed. The baseline starts at 111
  open entries: 47 errors and 64 warnings, 97 after this release.

### Changed — tooling and docs

- **`API-list-endpoint-contract.md`** is now generated by the fixture script
  from specification 1.221.0 (it was 1.197.0), with a column for pagination and
  deprecation.
- **`OrdersSpecContractTest`**: specification 1.221.0 declares `page` on
  `orders.list`, which the SDK has supported since 2.2.3 after a live check. The
  test that pinned this as a deliberate divergence now asserts the ordinary
  case.
- **`SECURITY.md`**: 2.0.x and 2.1.x are no longer supported. Fixes land on 2.2.x
  only, which has the same requirements. When 3.0.0 ships, 2.x gets security
  fixes only for three months.
- **`SpecAuditor`** understands two patterns found in the CRM pass: top-level
  list parameters on endpoints with no filter object (`levelTwoAreas.list`),
  and resources declaring a separate `$infoIncludes` set. Seven CRM findings
  were auditor false positives and are gone for that reason, not a code change.
- **`CrmSpecContractTest`** pins the write-side field lists, enums and
  required fields against the specification fixture.
- CRM payload tests: `ContactsPayloadTest`, `CompaniesPayloadTest`,
  `CrmReferencePayloadTest`.

---

## [2.2.3] - 2026-08-20

Patch release. `orders.list` could not be paginated, so every consumer received
the API's default of twenty records with nothing in the response to say more
existed. Reported from a nightly reconciliation that was silently wrong.

### Added

- **`Orders::all()`**: walks every page of `orders.list` and returns one `data`
  array. The endpoint returns no total count, so the end of the list is inferred
  from a page shorter than the one requested. A `$maxPages` guard (default 100
  pages of 100) throws when it is reached with records still pending, rather
  than returning a partial set that looks complete — which is the failure this
  release exists to fix. The sideload is resolved once and replayed on every
  page, because `applyPendingIncludes()` consumes the fluent state after the
  first request and `with('custom_fields')->all()` would otherwise have
  sideloaded page 1 only.

### Fixed

- **`Orders::list()`**: now accepts `page_size` and `page_number`.
  `supportsPagination` was `false` and no `page` key was ever sent, so there was
  no way to reach past the first twenty orders — and no `meta` block, no page
  count and no error to reveal it. An account with 1,100 orders synced cleanly
  and was wrong by 98%. Verified live on 2026-08-20 against an account holding
  30 orders: no page parameter returned 20, `size: 100` returned all 30, page 2
  returned 0, and `size: 5` returned five records on page 1 and five different
  records on page 2. Passing neither option still sends no `page` key, so
  existing calls are unchanged.

  Note that `@teamleader/focus-api-specification` (v1.198.0) does **not**
  declare `page` on `orders.list` — it declares `filter` and `includes` only,
  where 40 of the 58 `.list` endpoints declare `page`. The capability is
  undocumented rather than absent: an ignored parameter would have returned
  twenty records every time, not a result set that changes with page size and
  page number. The spec entry for this endpoint is thin elsewhere too, omitting
  `status` from a response that carries it.

- **`Orders::buildFilters()`**: filters are now whitelisted, matching `Deals`
  since v2.2.0. `orders.list` accepts `ids` and nothing else; `department_id`,
  `updated_since`, `order_date_after`, `term` and `status` were each confirmed
  accepted-and-ignored against the live API, returning the full unfiltered set
  with a 200. A deliberately invented key behaved identically, which is how the
  others were shown to be ignored rather than merely unhelpful. Unknown keys now
  throw. `ids` accepts a lone string and wraps it.

- **`Orders::list()`**: now rejects a sort option instead of discarding it. The
  API accepts `sort` on this endpoint and ignores it — sorting by `order_date`
  returns the same first record as no sort at all — so a caller passing one
  silently got none.

- **Guzzle deprecation notices on every request**: `timeout`, `connect_timeout`
  and `read_timeout` were passed to the client as whatever `config()` returned,
  which is a string when the value comes from `.env`. Guzzle 7.11 deprecates a
  string here and 8.0 will require `int|float`, so every API call emitted
  deprecation notices under a strict error handler. All three are now cast.

### Documentation

- `Orders` usage examples now cover pagination and `all()`. The block previously
  showed only unpaginated calls, which is part of how the gap stayed invisible.
- `Orders::getResponseStructure()`: **26 fields the specification declares were
  undocumented** — the `id` and `type` members of the `department`, `deal`,
  `project`, `assignee` and `product_category` references, and the whole
  `custom_fields[].definition` / `value` shape, on both the `list` and `info`
  maps. Found by diffing the field map against the specification rather than by
  reading it.
- `Orders::getResponseStructure()`: `order_number` is documented as an integer,
  and `status` is documented on both maps. `status` appears on live records but
  is absent from the API specification, so it is marked as observed rather than
  declared.

### Tests

- **`OrdersSpecContractTest`** — 17 tests asserting the resource's declarations
  against `@teamleader/focus-api-specification` rather than against runtime
  behaviour. The API answers 200 to filter keys, sort fields and includes it does
  not recognise, so a response-based test cannot tell a working capability from
  an ignored one; every assertion here compares a declaration in the class to a
  declaration in the specification. Covers the filter whitelist, the sorting and
  sideloading flags, the read-only flags, both response field maps in both
  directions, the `payment_term` enum, and that every published usage example
  names a method that exists.

  The pagination divergence is asserted rather than ignored: the suite fails if
  Teamleader ever declares `page` on `orders.list`, so the explanatory comments
  can be retired at that point instead of quietly going stale.

- **`tests/Fixtures/specification/`** — a mechanical extract of the specification
  for the endpoints under test, with `generate-spec-fixtures.mjs` to regenerate
  it. Committed so the suite needs no npm in CI. Adding a resource is one line in
  the script's `RESOURCES` map.

---

## [2.2.2] - 2026-08-18

Patch release. Ten findings from a source scan against
`@teamleader/focus-api-specification` — two fatals, three filters or includes
the API has never accepted, and five consistency defects. Every one of them
failed silently or on a code path the SDK's own documentation recommended.

### Fixed

- **`Projects::list()`**: sorting by a field name no longer raises
  `TypeError: array_map(): Argument #2 ($array) must be of type array, string
  given`. `buildSort()` tested `isset($sort['field'])`, which is false for a
  string in PHP 8, then passed the string to `array_map()` — so
  `projects()->list([], ['sort' => 'title'])` was fatal. It now accepts a field
  name, a list of names, a single `['field' => ..., 'order' => ...]` entry, or a
  list of those, and validates the field against the eighteen the API declares.
  Same defect class as the `Deals::buildSort()` fatal fixed in v2.2.0.
- **`Projects::buildFilters()`**: filters are now whitelisted. Both branches of
  its `if`/`else` assigned the same value, so every key passed through
  unchecked — and the API ignores filter keys it does not recognise, answering
  `200` with the full unfiltered set. `projects-v2/projects.list` accepts `ids`,
  `status`, `customers`, `deal_ids`, `quotation_ids` and `term`; anything else
  now throws. `ids`, `deal_ids` and `quotation_ids` accept a lone string and wrap
  it.
- **Sideload option key**: `Projects`, `Orders`, `Pipelines` and `Invoices` read
  `$options['includes']` while the rest of the SDK reads `$options['include']`.
  So `['include' => 'custom_fields']` was silently ignored on those four, and
  `['includes' => ...]` was silently ignored everywhere else — the caller-facing
  version of the body-key defect fixed in v1.2.3. **Both keys are now accepted
  everywhere**, resolved by `FilterTrait::resolveIncludesOption()`. `include`
  wins when both are given, since it is the documented one. No existing call
  breaks either way.
- **`Orders::$availableIncludes`**: was a keyed map
  (`['custom_fields' => 'Include custom field values...']`) where every other
  resource uses a flat list, so `getCapabilities()['available_includes']`
  returned a different structure for this one resource and generic iteration
  over capabilities broke. Now a list.
- **`Pipelines::delete()`**: called `parent::delete()`, but `Resource` defines
  no `delete()` method — so deleting a pipeline raised
  `Error: Call to undefined method`. The request is now built directly, sending
  `migrate_phases` only when migrations are supplied. `prepareDeleteData()` was
  written for that delegation, was never reached, and has been removed. Fourth
  member of the missing-base-method family, after `buildSort()`, `with()` and
  `validateData()`.
- **`Quotations`**: `supportsSideloading` was `true` and `$availableIncludes`
  advertised an `expiry` include. Neither `quotations.list` nor `quotations.info`
  declares an includes parameter at all, so the include was never accepted.
  Sideloading is now `false`, the include list is empty, and `info()` throws if
  includes are passed.
- **`Quotations`**: removed the `status` filter. `quotations.list` accepts `ids`
  and nothing else — the API ignored `status` and returned every quotation.
  `byStatus()` therefore never filtered anything and now throws, pointing at
  client-side filtering on `data[].status`. Deprecated, removed in v3.0. Same
  defect as `Companies::byName()` in v2.2.1.
- **`Quotations::list()`**: now accepts the SDK-wide `page_size` / `page_number`
  options. It previously understood only a nested `['page' => ['size' => ...]]`
  array, so the standard form was silently ignored and every call returned the
  API default of 20 records.
- **`Orders`** and **`Quotations`**: usage examples called
  `->include('custom_fields')` and `->include('expiry')`. No such method exists —
  the fluent method is `with()`, and Quotations has no includes to request at
  all. Second and third SDK-published examples found to be unrunnable, after
  `Deals::withCustomer()` in v2.2.0.
- **`Pipelines::list()`**: removed dead debug logging. It was guarded by
  `function_exists('Log')` — `Log` is a class, not a function, so the condition
  was always false and neither log line ever ran. The unused
  `Illuminate\Support\Facades\Log` import went with it.

### Added

- **`FilterTrait::resolveIncludesOption()`** — reads either option key and
  returns the value for `applyIncludes()`.
- **`FilterTrait::normaliseSort()`**, **`validateSortField()`** and
  **`normaliseSortOrder()`** — shared sort handling accepting all four input
  forms, validating against `$availableSortFields` when the resource declares it
  as a keyed map. Resources that define their own copies keep them, since a class
  method takes precedence over a trait method; consolidating the fifteen existing
  `buildSort()` implementations onto the trait is a v3.0 item.

### Documentation

- **`API-list-endpoint-contract.md`** — generated reference listing the real
  filters, sort fields and includes for all 58 `.list` endpoints, extracted from
  the specification. This is the cross-reference for the filter parity sweep
  planned for v2.3.0.

### Upgrade notes

`Quotations::byStatus()` now throws where it previously returned every
quotation. Replace it with a `list()` call and a client-side filter on
`data[].status`. The `status` filter key throws for the same reason.

`Projects::list()` now throws on unsupported filter keys where it previously
forwarded them. Those filters were never applied — the API dropped them and
returned every project — so any code relying on them was already getting the
wrong result set.

Sorting on `Projects` accepts more input shapes than before, and validates the
field. A sort field that is not one of the eighteen the API declares now throws
rather than being silently ignored.

---

## [2.2.1] - 2026-08-18

Patch release. Closes the findings from the post-release audit of the wiki and
root documentation — three phantom filters, two missing guards, and a method that
could fatal on PHP builds without `ext-calendar`.

### Fixed

- **`Companies`**: removed the `name` and `company_number` filters. Neither
  exists on `companies.list` — the API ignored them and returned every company
  with HTTP 200, so `byName('Acme')` silently produced the complete account list.
  Unknown filter keys now throw, as they already did on Deals, TimeTracking and
  CustomFields. `search()` / the `term` filter is the working equivalent: it
  searches name as well as VAT number, emails and telephones.
- **`ClosingDays`**: unknown filter keys throw instead of being dropped, and sort
  options are rejected rather than ignored — this endpoint has no sorting.
- **`ClosingDays::getCommonHolidays()`**: no longer raises
  `Error: Call to undefined function easter_date()` on PHP builds without
  `ext-calendar`. Easter Monday is omitted when the extension is absent; the
  three fixed dates are still returned.
- **`LegacyProjects`**: unknown filter keys throw instead of being dropped, and
  sort fields are validated against the three the API accepts (`due_on`, `title`,
  `created_at`) rather than being passed through unchecked.

### Deprecated

- **`Companies::byName()`** — throws `InvalidArgumentException` explaining that
  `companies.list` has no `name` filter, and pointing at `search()`. Removed in
  v3.0. It previously returned every company in the account.

### Changed

- **`LegacyProjects::$availableSortFields`**: now a keyed map with descriptions,
  matching the convention used by the other resources and surfacing in
  `getDocumentation()`.

### Documentation

- **README**: corrected roughly fifteen non-working code examples — a named
  `includes:` parameter that does not exist, `webhooks()->create()` (it is
  `register()`), `customFieldDefinitions()` (it is `customFields()`), wrong casing
  on `incomingCreditNotes()`, `user_availability()` and `plannable_items()`,
  `uploadLogo()` shown taking a file id rather than a base64 data URI,
  `invoices()->draft()` used for drafting rather than listing, `withCache()` and
  two exception classes that do not exist, and the webhook event
  `invoice.created`, which is not in the API. Laravel version corrected to 12/13,
  and the three dead `/docs` links point at the wiki.
- **SECURITY.md**: supported versions listed `1.0.x-alpha` only, which told
  researchers that no shipped version was supported. Now covers 2.2.x back to the
  2.0 cutoff, with scope boundaries and a note that
  `TEAMLEADER_LOG_ALL_REQUESTS` writes payloads containing personal data.
- **CONTRIBUTING.md**: aligned with the codebase — the `@test` annotation it
  recommended contradicts the test suite, `composer test-coverage` does not
  exist, and the `/docs` folder was removed. Adds guidance on writing tests
  against the API specification rather than against the current implementation,
  which is how six phantom includes survived in `CompaniesResourceTest`.
- **Wiki**: sixteen resource pages rewritten against the specification during the
  v2.2.0 cycle; twelve contained at least one factual error. `Accounts` no longer
  documents a `getDefaultId()` method — no such method exists.

### Upgrade notes

`Companies::byName()` now throws where it previously returned every company.
Anywhere it appears, replace it with `search()`. Any code passing `name` or
`company_number` as a `companies.list` filter will also now throw; those filters
were never applied.

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
