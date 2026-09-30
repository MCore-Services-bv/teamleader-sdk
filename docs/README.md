# Teamleader Focus SDK for Laravel

A Laravel package for the [Teamleader Focus API](https://developer.focus.teamleader.eu/).
It handles the OAuth flow and token refresh, keeps you under the rate limit, and
puts around 70 API resources behind one consistent interface.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$companies = Teamleader::companies()->list(
    ['status' => 'active', 'tags' => ['customer']],
    ['sort' => 'name', 'page_size' => 50]
);
```

## Why this SDK

**It fails loudly where the API fails silently.** The Teamleader API answers
`200 OK` to a filter, sort field, include or body field it does not recognise,
and ignores it. A typo looks like success: a mistyped filter returns every
record, and a mistyped field on an update changes nothing. The SDK checks those
names against Teamleader's own machine-readable specification and throws before
the request is sent. See [Validation](guides/validation.md).

**It is checked against the specification in CI.** Every resource is audited
against `@teamleader/focus-api-specification`, and a weekly job reports new
API versions before they turn into bug reports. See
[Specification parity](project/specification-parity.md).

**It is built for queue workers.** Token refresh is guarded by a distributed
lock, the rate limiter waits for a free slot instead of spending requests on
429s, and a rate-limit error hands control back to your job so it can
`release()` rather than block the worker.

## Where to start

| | |
|---|---|
| New to the SDK | [Installation](getting-started/installation.md), then [Quick start](getting-started/quick-start.md) |
| Looking up a resource | [API reference](reference/README.md) — one page per resource, generated from the code |
| Upgrading | [Upgrading](project/upgrading.md) |
| Something went wrong | [Error handling](guides/error-handling.md) |

## Requirements

- PHP 8.2 or higher (tested on 8.2 – 8.5)
- Laravel 12.x or 13.x
- A database for the token table (MySQL, PostgreSQL or SQLite)
- Redis, when rate limiting is enabled — it is by default

## Links

- [Documentation site](https://teamleader-sdk.mcore-services.dev/)
- [Packagist](https://packagist.org/packages/mcore-services/teamleader-sdk)
- [GitHub](https://github.com/MCore-Services-bv/teamleader-sdk)
- [Changelog](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/CHANGELOG.md)
- [Teamleader developer portal](https://developer.focus.teamleader.eu/)
