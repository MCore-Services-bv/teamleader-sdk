# Teamleader Focus SDK for Laravel

[![Latest Version](https://img.shields.io/github/v/release/MCore-Services-bv/teamleader-sdk)](https://github.com/MCore-Services-bv/teamleader-sdk/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/mcore-services/teamleader-sdk)](https://packagist.org/packages/mcore-services/teamleader-sdk)
[![PHP Version](https://img.shields.io/packagist/php-v/mcore-services/teamleader-sdk)](https://packagist.org/packages/mcore-services/teamleader-sdk)
[![Laravel Version](https://img.shields.io/badge/Laravel-12%20%7C%2013-blue)](https://laravel.com)
[![License](https://img.shields.io/github/license/MCore-Services-bv/teamleader-sdk)](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/LICENSE.md)
[![Tests](https://github.com/MCore-Services-bv/teamleader-sdk/actions/workflows/tests.yml/badge.svg)](https://github.com/MCore-Services-bv/teamleader-sdk/actions/workflows/tests.yml)
[![Docs](https://img.shields.io/badge/docs-teamleader--sdk.mcore--services.dev-blue)](https://teamleader-sdk.mcore-services.dev/)
[![Teamleader API spec](https://img.shields.io/badge/Teamleader%20API%20spec-1.221.0-2ea44f)](#-specification-parity)

A Laravel package for the Teamleader Focus API. Handles OAuth, encrypted token
storage and renewal, rate limiting, and around 70 API resources behind a
consistent interface — for one Teamleader account or many.

**Quick Links:**
- 📦 **Packagist:** [packagist.org/packages/mcore-services/teamleader-sdk](https://packagist.org/packages/mcore-services/teamleader-sdk)
- 💻 **GitHub:** [github.com/MCore-Services-bv/teamleader-sdk](https://github.com/MCore-Services-bv/teamleader-sdk)
- 📖 **Documentation:** [teamleader-sdk.mcore-services.dev](https://teamleader-sdk.mcore-services.dev/) — guides, and an API reference generated from the code

---

## ✨ Key Features

### 🔐 Authentication & Security
- **Complete OAuth 2.0 Flow** — `Teamleader::authorize()` and one callback route; the `state` is generated and checked for you
- **Encrypted Token Storage** — tokens encrypted with `APP_KEY` in the database and the cache
- **Scheduled Token Renewal** — tokens are renewed every ten minutes before they expire, so an idle connection stays connected
- **Right-Account Check** — `expected_account_id` refuses a callback that connects the wrong Teamleader account
- **Concurrent Request Safety** — a per-connection lock prevents token refresh race conditions

### 🏢 Multiple Accounts
- **Connections** — one application, several Teamleader accounts, each with its own credentials, tokens and rate-limit window
- **Stored in config or the database** — add an account with `php artisan teamleader:connections:add`, no deploy needed

### 🚀 Performance & Reliability
- **Proactive Rate Limiting** — Redis-backed sliding window that waits for a free slot rather than letting you hit the 200 req/min limit
- **Retry Logic** — Automatic retry with backoff for transient failures
- **Lazy Pagination** — `lazy()` and `cursor()` page through any list with flat memory use

### 📦 Bulk & Command Line
- **Bulk Export** — any list to CSV or JSON Lines, safe against spreadsheet formula injection
- **Bulk Writes** — create, update, delete or call any method for many rows: validated first, resumable, with a dry run, in-process or on the queue
- **Artisan CLI** — explore, list, export, import and call the API from the terminal; nothing writes without `--write`

### 🧰 Developer Experience
- **Resource-Based Architecture** — Organised access to all API endpoints
- **Fluent Sideloading** — Reduce API calls by including related resources
- **Fail-Fast Validation** — Unsupported filters, sort fields and includes throw before the request is sent, rather than being silently ignored by the API
- **Typed Exceptions by Default** — a failed request throws an exception with an actionable message
- **Events** — for every request, response, failure, rate-limit wait and token refresh, plus optional request logging
- **Resource Introspection** — Query any resource's capabilities programmatically

### 🎯 API Coverage

**CRM** — Companies, Contacts, Business Types, Tags, Addresses

**Deals & Sales** — Deals, Quotations, Orders, Pipelines, Phases, Sources, Lost Reasons

**Invoicing** — Invoices, Credit Notes, Payment Methods, Payment Terms, Tax Rates, Withholding Tax Rates, Commercial Discounts, Subscriptions

**Expenses** — Expenses, Incoming Invoices, Incoming Credit Notes, Receipts, Bookkeeping Submissions

**Projects & Time Tracking** — Projects (both the current and legacy systems), Project Tasks, Groups, Materials, Project Lines, External Parties, Time Tracking, Timers

**Planning** — Reservations, User Availability, Plannable Items

**Calendar & Activities** — Meetings, Calls, Call Outcomes, Calendar Events, Activity Types

**Products & Services** — Products, Product Categories, Units of Measure, Work Types, Price Lists

**General** — Users, Teams, Departments, Custom Fields, Currencies, Notes, Files, Document Templates, User Schedules, Closing Days, Days Off, Day Off Types, Email Tracking

**System** — Webhooks, Cloud Platforms, Accounts, Migration Utilities

---

## 📋 Requirements

- **PHP**: 8.4 or higher (tested on 8.4 – 8.5)
- **Laravel**: 12.x or 13.x
- **Extensions**: `ext-json`, `ext-mbstring`
- **Database**: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+
- **Redis**: required only if rate limiting is enabled — it is, by default

> Laravel 10 and 11 were dropped in v2.0. Both are EOL with unpatched CVEs, and
> Composer's security advisories block installing them.

---

## 🚀 Installation

### 1. Install via Composer

```bash
composer require mcore-services/teamleader-sdk
```

### 2. Publish the Configuration and Run the Migrations

```bash
php artisan vendor:publish --tag=teamleader-config
php artisan migrate
```

### 3. Configure Environment Variables

```env
TEAMLEADER_CLIENT_ID=your_client_id
TEAMLEADER_CLIENT_SECRET=your_client_secret
TEAMLEADER_REDIRECT_URI="${APP_URL}/teamleader/callback"
```

The redirect URI must match the one registered on your integration in the
[Teamleader developer portal](https://developer.focus.teamleader.eu/) exactly —
`http` versus `https` included.

### 4. Set Up OAuth Routes

```php
// routes/web.php
use Illuminate\Http\Request;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

Route::get('/teamleader/connect', function () {
    return Teamleader::authorize();
})->middleware('auth');

Route::get('/teamleader/callback', function (Request $request) {
    if (Teamleader::handleCallback($request->query('code'), $request->query('state'))) {
        return redirect('/dashboard')->with('success', 'Connected to Teamleader!');
    }

    return redirect('/settings')->with('error', 'Connecting to Teamleader failed.');
})->middleware('auth');
```

### 5. Run the Scheduler

The package schedules `teamleader:tokens:refresh` every ten minutes. Run
`php artisan schedule:work` locally, and a cron entry for
`php artisan schedule:run` in production (Laravel Forge: *Scheduler*).

> Upgrading from 2.x? See the
> [upgrade guide](https://teamleader-sdk.mcore-services.dev/project/upgrading).

---

## 🔑 Authentication

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// 1. Redirect the user to Teamleader — a state is generated and remembered
return Teamleader::authorize();

// 2. Handle the callback — the state is checked, tokens are encrypted and stored
Teamleader::handleCallback($code, $state);

// 3. Check authentication status
if (Teamleader::isAuthenticated()) {
    // Ready to make API calls
}
```

```bash
php artisan teamleader:status --all   # every connection, its account and token expiry
```

When Teamleader refuses a refresh token, the connection is marked
`needs_reauthorization` and its requests throw
`ConnectionNeedsReauthorizationException` until it is connected again.

---

## 📖 Basic Usage

Every resource follows the same shape:

```php
$resource->list(array $filters = [], array $options = []);
$resource->info(string $id);
$resource->create(array $data);
$resource->update(string $id, array $data);
$resource->delete(string $id);
```

`$filters` maps to the API's `filter` object. `$options` carries `page_size`,
`page_number`, `sort`, `sort_order` and `include`.

### Companies

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// List with filters, pagination and sideloading
$companies = Teamleader::companies()->list(
    ['status' => 'active'],
    ['page_size' => 50, 'sort' => 'name', 'include' => 'custom_fields']
);

// Get a single company
$company = Teamleader::companies()->info('company-uuid');

// Create
$company = Teamleader::companies()->create([
    'name'                    => 'Acme Corp',
    'vat_number'              => 'BE0123456789',
    'emails'                  => [['type' => 'primary', 'email' => 'info@acme.be']],
    'marketing_mails_consent' => true,
]);

// Upload a logo — base64 data URI, or null to remove
$logo = 'data:image/png;base64,'.base64_encode(file_get_contents('/path/to/logo.png'));
Teamleader::companies()->uploadLogo('company-uuid', $logo);
```

### Contacts

```php
$contacts = Teamleader::contacts()->list(
    ['marketing_mails_consent' => true],
    ['include' => 'custom_fields']
);

// Link to a price list — or pass null to remove it
Teamleader::contacts()->update('contact-uuid', ['price_list_id' => 'price-list-uuid']);

$avatar = 'data:image/jpeg;base64,'.base64_encode(file_get_contents('/path/to/avatar.jpg'));
Teamleader::contacts()->uploadAvatar('contact-uuid', $avatar);
```

### Deals

```php
$deals = Teamleader::deals()->list(
    ['status' => ['open']],
    ['sort' => 'weighted_value', 'sort_order' => 'desc']
);

$deal = Teamleader::deals()->create([
    'title'           => 'New Business Deal',
    'lead'            => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
    'estimated_value' => ['amount' => 10000, 'currency' => 'EUR'],
]);

Teamleader::deals()->win($deal['data']['id']);
```

### Invoices

```php
// create() drafts an invoice. listDrafts() lists existing drafts.
$invoice = Teamleader::invoices()->create([
    'department_id' => 'dept-uuid',
    'invoicee'      => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
    'payment_term'  => ['type' => 'after_invoice_date', 'days' => 30],
    'grouped_lines' => [[
        'line_items' => [[
            'quantity'    => 5,
            'description' => 'Consulting',
            'unit_price'  => ['amount' => 150.0, 'tax' => 'excluding'],
            'tax_rate_id' => 'tax-rate-uuid',
        ]],
    ]],
]);

$outstanding = Teamleader::invoices()->list(['status' => ['outstanding']]);
```

> A grouped line with no section title must **omit** the `section` key entirely —
> Teamleader rejects both `null` and `''`. See the
> [Invoices reference](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/docs/reference/invoicing/invoices.md).

### Time Tracking

```php
Teamleader::timeTracking()->create([
    'started_at'   => now()->toIso8601String(),
    'duration'     => 3600,
    'subject'      => ['type' => 'ticket', 'id' => 'ticket-uuid'],
    'work_type_id' => 'work-type-uuid',
]);

$entries = Teamleader::timeTracking()->betweenDates(
    '2026-08-01T00:00:00+02:00',
    '2026-08-31T23:59:59+02:00'
);
```

### Files

```php
// upload() returns a signed URL — you POST the file content to it yourself
$upload = Teamleader::files()->upload('contract.pdf', 'deal', 'deal-uuid');

$ch = curl_init($upload['data']['location']);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => file_get_contents('/local/contract.pdf'),
    CURLOPT_RETURNTRANSFER => true,
]);
curl_exec($ch);
curl_close($ch);

$files = Teamleader::files()->forDeal('deal-uuid');
```

### Custom Fields

```php
$field = Teamleader::customFields()->create([
    'label'   => 'VAT Number',
    'type'    => 'single_line',
    'context' => 'company',
]);

// Every definition, paging handled for you
$all = Teamleader::customFields()->all();
```

---

## 📄 Pagination

`lazy()` pages through any list for you and returns a `LazyCollection`: pages
of 100 are fetched only as you consume them.

```php
Teamleader::companies()
    ->lazy(['status' => 'active'])
    ->each(function (array $company) {
        // one record at a time
    });

Teamleader::deals()->lazy(['status' => 'open'])->take(10)->all();   // one request
```

**The API returns no total count** on most endpoints, so the end of a list is
a page shorter than the page size. `cursor()` gives you the pager itself —
`total()` where the endpoint reports one, and `lastPage()` to resume an
interrupted export.

---

## ⚡ Rate Limiting

Teamleader allows 200 requests per sliding minute. The SDK tracks usage in Redis,
throttles progressively as the window fills, and waits for a free slot rather
than firing a request that can only come back as a 429.

```php
$stats = Teamleader::getRateLimitStats();
echo "Remaining: {$stats['remaining']} / {$stats['rate_limit']}";
```

The wait is capped by `teamleader.rate_limiting.max_wait_ms` (default 5000). Once
the cap is reached a `RateLimitExceededException` is thrown and the decision
returns to you — which in a queue worker usually means releasing the job:

```php
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;

try {
    Teamleader::deals()->list();
} catch (RateLimitExceededException $e) {
    $this->release($e->getRetryAfter());
}
```

Set `max_wait_ms` to `65000` if you would rather the SDK sit out a full window
itself — sensible for a CLI import, less so for a web request.

---

## 🔗 Sideloading

Related records can be requested in the same call, via `$options['include']` or
the fluent methods:

```php
$deals = Teamleader::deals()->list(
    ['status' => ['open']],
    ['include' => 'custom_fields']
);

$company = Teamleader::companies()
    ->withRelatedContacts()
    ->info('company-uuid');

// What does this resource actually accept?
$capabilities = Teamleader::companies()->getCapabilities();
```

> Includes are **per endpoint**. `list` and `info` frequently accept different
> sets, and some endpoints accept none at all. An unsupported include throws
> rather than being silently ignored.

---

## 🏢 Multiple Connections

```php
// config/teamleader.php
'connections' => [
    'antwerp' => [
        'client_id' => env('TEAMLEADER_ANTWERP_CLIENT_ID'),
        'client_secret' => env('TEAMLEADER_ANTWERP_CLIENT_SECRET'),
    ],
],
```

```php
Teamleader::connection('antwerp')->companies()->list();
```

Or store a connection in the database, without a deploy:

```bash
php artisan teamleader:connections:add antwerp
```

Each connection has its own integration, tokens, refresh lock and rate-limit
window; one callback route serves them all.

---

## 📦 Bulk Operations

```php
// Any list to a file
Teamleader::bulk()
    ->export('contacts', ['tags' => ['customer']])
    ->toCsv(storage_path('contacts.csv'), ['id', 'first_name', 'last_name', 'emails.0.email']);

// Many writes: every row validated before the first is sent
$result = Teamleader::bulk()
    ->create('companies', $rows)
    ->continueOnError()
    ->run();

$result->succeeded();   // [row key => API response]
$result->failed();      // [row key => BulkFailure]

// Or on the queue, in chunks
Teamleader::bulk()->update('deals', $rows)->dispatch(chunk: 50);
```

`dryRun()` shows exactly what would be sent, and `resumeFrom($result)` skips
the rows that already succeeded. `call('deals', 'win', $rows)` runs any
method once per row.

---

## 💻 Command Line

```bash
php artisan teamleader:describe deals
php artisan teamleader:list deals --filter=status[]=open --sort=created_at:desc --fields=id,title
php artisan teamleader:export contacts --filter=tags[]=customer --output=storage/customers.csv
php artisan teamleader:import companies companies.csv --dry-run
php artisan teamleader:call users.me
```

Every command validates filters, sort fields and includes the same way your
code does, and takes `--connection=`. **Nothing writes without `--write`**,
and in production `--force` is required as well.

---

## 📡 Events

```php
use Illuminate\Support\Facades\Event;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;

Event::listen(function (TokenRefreshFailed $event) {
    if ($event->reauthorizationRequired) {
        // tell someone to reconnect $event->connection
    }
});
```

`RequestSending`, `ResponseReceived`, `RequestFailed`, `RateLimitWaited`,
`TokenRefreshed`, `TokenRefreshFailed`, `ConnectionAuthorized` and
`BulkBatchFinished` are fired only when something listens. Set
`TEAMLEADER_LOG_REQUESTS=true` to log every request to a channel of your
choice.

---

## 🪝 Webhooks

```php
Teamleader::webhooks()->register('https://your-app.com/webhooks/teamleader', [
    'invoice.booked',
    'invoice.peppolSubmissionSucceeded',
    'invoice.peppolSubmissionFailed',
    'deal.won',
]);

// Or a whole category at once
$types = Teamleader::webhooks()->getInvoiceEventTypes();
Teamleader::webhooks()->register('https://your-app.com/webhooks/teamleader', $types);
```

The payload carries the entity id at `subject.id`, not `data.id`:

```php
Route::post('/webhooks/teamleader', function (Request $request) {
    ProcessTeamleaderEvent::dispatch(
        $request->input('type'),
        $request->input('subject.id')
    );

    return response()->json(['status' => 'received']);
});
```

---

## 🛠️ Error Handling

A failed request throws a typed exception — the default since v3.0. Set
`TEAMLEADER_THROW_EXCEPTIONS=false` for the 2.x behaviour of returning an
array with `error => true`. All API exceptions extend `TeamleaderException`.

```php
use McoreServices\TeamleaderSDK\Exceptions\AuthenticationException;
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;

try {
    $company = Teamleader::companies()->info('company-uuid');
} catch (NotFoundException $e) {
    // 404 — the record does not exist
} catch (ValidationException $e) {
    // 422 — the API rejected the payload
} catch (RateLimitExceededException $e) {
    // 429 — always thrown, never swallowed
    $this->release($e->getRetryAfter());
} catch (AuthenticationException $e) {
    // Token expired and refresh failed — re-authenticate
    return redirect('/teamleader/auth');
} catch (TeamleaderException $e) {
    logger()->error('Teamleader API error', ['message' => $e->getMessage()]);
}
```

Client-side validation throws `InvalidArgumentException` **before** the request is
sent — for unsupported filter keys, sort fields, includes and subject types:

```php
Teamleader::timeTracking()->list(['updated_since' => '2026-08-01T00:00:00+02:00']);
// InvalidArgumentException: Invalid filter key 'updated_since' for
// timeTracking.list. Supported filters: ids, user_id, started_after, ...
```

This is deliberate. The API answers `200` to filters it does not recognise and
returns the complete unfiltered set, so silence is the more dangerous outcome.

---

## 🖥️ Artisan Commands

```bash
php artisan teamleader:status --all          # Every connection: account, status, token expiry
php artisan teamleader:health                # Health check
php artisan teamleader:config:validate       # Validate configuration
php artisan teamleader:tokens:refresh        # Renew tokens that expire soon (scheduled)
php artisan teamleader:connections:add       # Store a connection in the database
php artisan teamleader:connections:list
php artisan teamleader:connections:remove
php artisan teamleader:resources             # Every resource and what it supports
php artisan teamleader:export-uuids          # Export reference UUIDs
```

Plus `describe`, `list`, `info`, `export`, `import` and `call` — see
[Command Line](#-command-line).

---

## ✅ Specification Parity

Every resource is checked against Teamleader's machine-readable API
specification, [`@teamleader/focus-api-specification`](https://www.npmjs.com/package/@teamleader/focus-api-specification),
currently pinned at **1.221.0**.

The Teamleader API answers `200 OK` to a filter, sort field, include or body
field it does not recognise, and simply ignores it. A typo therefore looks like
success: a mistyped filter returns every record, and a mistyped field on an
update changes nothing. The SDK checks those names against the specification
and throws before the request is sent.

How that is kept true:

- **Spec contract tests.** Field lists, required fields and enums on each
  resource are asserted against a fixture generated from the specification.
- **Spec audit.** `composer spec:audit` compares every resource's filters,
  sort fields, includes, pagination and endpoints with the specification;
  `php bin/spec-audit --check` fails CI on anything not recorded in the
  baseline. The one recorded divergence is `users.getWeekSchedule`, which
  Teamleader deprecated and the SDK deliberately does not wrap.
- **Weekly watch.** A scheduled workflow audits the SDK against the newest
  published specification and opens an issue when it finds a difference, so
  API changes surface before they surface as bug reports.

```bash
composer spec:audit -- --summary     # counts per category
composer spec:audit -- --new         # anything not in the baseline
composer spec:check                  # the CI gate
```

---

## 🤝 Contributing

Contributions are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md).

Bug reports are especially useful when they include what you expected, what
happened, and how you worked around it. Several fixes in v2.2.0 came directly
from reports written that way.

---

## 🔒 Security

If you discover a security issue, email **help@mcore-services.be** rather than
using the issue tracker. See [SECURITY.md](SECURITY.md).

---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## 📜 License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

---

## 🙏 Credits

- **MCore Services** — [https://mcore-services.be](https://mcore-services.be)
- Built with ❤️ for the Laravel and Teamleader communities

## 💬 Support

- **Documentation**: [teamleader-sdk.mcore-services.dev](https://teamleader-sdk.mcore-services.dev/)
- **Email**: help@mcore-services.be
- **Issues**: [GitHub Issues](https://github.com/MCore-Services-bv/teamleader-sdk/issues)
- **Discussions**: [GitHub Discussions](https://github.com/MCore-Services-bv/teamleader-sdk/discussions)
- **Teamleader API**: [developer.focus.teamleader.eu](https://developer.focus.teamleader.eu/)

## 🗺️ Roadmap

- [x] Spec-derived filter parity across every resource, enforced by a test (v2.3.0)
- [x] Payload tests for all resources (v2.3.0)
- [x] Multiple connections, encrypted token storage and scheduled renewal (v3.0)
- [x] Bulk operations: export, writes and queued bulk (v3.0)
- [x] CLI for exploring and querying the API (v3.0)
- [x] Events and request logging (v3.0)
- [ ] Laravel Pulse recorder (v3.1)
- [ ] Upsert helper for bulk writes (v3.1)

---

**Made with ❤️ by [MCore Services](https://mcore-services.be)**
