# Teamleader Focus SDK for Laravel

[![Latest Version](https://img.shields.io/github/v/release/MCore-Services-bv/teamleader-sdk)](https://github.com/MCore-Services-bv/teamleader-sdk/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/mcore-services/teamleader-sdk)](https://packagist.org/packages/mcore-services/teamleader-sdk)
[![PHP Version](https://img.shields.io/packagist/php-v/mcore-services/teamleader-sdk)](https://packagist.org/packages/mcore-services/teamleader-sdk)
[![Laravel Version](https://img.shields.io/badge/Laravel-12%20%7C%2013-blue)](https://laravel.com)
[![License](https://img.shields.io/github/license/MCore-Services-bv/teamleader-sdk)](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/LICENSE.md)

A Laravel package for the Teamleader Focus API. Handles OAuth token management,
rate limiting, and around 70 API resources behind a consistent interface.

**Quick Links:**
- 📦 **Packagist:** [packagist.org/packages/mcore-services/teamleader-sdk](https://packagist.org/packages/mcore-services/teamleader-sdk)
- 💻 **GitHub:** [github.com/MCore-Services-bv/teamleader-sdk](https://github.com/MCore-Services-bv/teamleader-sdk)
- 📖 **Documentation:** [Wiki](https://github.com/MCore-Services-bv/teamleader-sdk/wiki)

---

## ✨ Key Features

### 🔐 Authentication & Security
- **Complete OAuth 2.0 Flow** — Authorization URL generation and secure callback handling
- **Automatic Token Management** — Smart token refresh with database and cache layers
- **Concurrent Request Safety** — Distributed locking prevents token refresh race conditions

### 🚀 Performance & Reliability
- **Proactive Rate Limiting** — Redis-backed sliding window that waits for a free slot rather than letting you hit the 200 req/min limit
- **Response Caching** — Configurable caching for static data endpoints
- **Retry Logic** — Automatic retry with backoff for transient failures

### 📦 Developer Experience
- **Resource-Based Architecture** — Organised access to all API endpoints
- **Fluent Sideloading** — Reduce API calls by including related resources
- **Fail-Fast Validation** — Unsupported filters, sort fields and includes throw before the request is sent, rather than being silently ignored by the API
- **Rich Error Handling** — Typed exceptions with actionable messages
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

- **PHP**: 8.2 or higher (tested on 8.2 – 8.5)
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

### 2. Publish the Configuration

```bash
php artisan vendor:publish --provider="McoreServices\TeamleaderSDK\TeamleaderServiceProvider"
```

### 3. Configure Environment Variables

```env
TEAMLEADER_CLIENT_ID=your_client_id
TEAMLEADER_CLIENT_SECRET=your_client_secret
TEAMLEADER_REDIRECT_URI=https://your-app.com/teamleader/callback
```

### 4. Set Up OAuth Routes

```php
// routes/web.php
use Illuminate\Http\Request;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

Route::get('/teamleader/auth', function () {
    return redirect(Teamleader::getAuthorizationUrl());
});

Route::get('/teamleader/callback', function (Request $request) {
    Teamleader::handleCallback($request->code, $request->state);

    return redirect('/dashboard')->with('success', 'Connected to Teamleader!');
});
```

> **Note:** No `php artisan migrate` is required. The SDK creates the
> `teamleader_tokens` table on first use.

---

## 🔑 Authentication

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// 1. Redirect the user to Teamleader for authorization
return redirect(Teamleader::getAuthorizationUrl());

// 2. Handle the callback — tokens are stored automatically
Teamleader::handleCallback($code, $state);

// 3. Check authentication status
if (Teamleader::isAuthenticated()) {
    // Ready to make API calls
}
```

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
> [Invoices wiki page](https://github.com/MCore-Services-bv/teamleader-sdk/wiki/Invoices).

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

**The API returns no total count.** Most endpoints send no `meta` block, so the
only end-of-list signal is a page shorter than the requested page size — meaning
a full final page costs one extra empty request.

```php
$all  = [];
$page = 1;

do {
    $response = Teamleader::companies()->list(
        ['status' => 'active'],
        ['page_size' => 100, 'page_number' => $page]
    );

    $all = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

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
    ['include' => 'lead.customer,responsible_user,current_phase']
);

$deals = Teamleader::deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->list(['status' => ['open']]);

// What does this resource actually accept?
$capabilities = Teamleader::companies()->getCapabilities();
```

> Includes are **per endpoint**. `list` and `info` frequently accept different
> sets, and some endpoints accept none at all. An unsupported include throws
> rather than being silently ignored.

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

All API exceptions extend `TeamleaderException`.

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
php artisan teamleader:status            # Connection and token status
php artisan teamleader:config:validate   # Validate configuration
php artisan teamleader:health            # Health check
php artisan teamleader:export-uuids      # Export reference UUIDs
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

- **Documentation**: [Wiki](https://github.com/MCore-Services-bv/teamleader-sdk/wiki)
- **Email**: help@mcore-services.be
- **Issues**: [GitHub Issues](https://github.com/MCore-Services-bv/teamleader-sdk/issues)
- **Discussions**: [GitHub Discussions](https://github.com/MCore-Services-bv/teamleader-sdk/discussions)
- **Teamleader API**: [developer.focus.teamleader.eu](https://developer.focus.teamleader.eu/)

## 🗺️ Roadmap

- [ ] Spec-derived filter parity across every resource, enforced by a test
- [ ] Payload tests for all resources
- [ ] Bulk operations helper
- [ ] Enhanced caching strategies with tag-based invalidation
- [ ] Laravel Pulse integration

---

**Made with ❤️ by [MCore Services](https://mcore-services.be)**
