# Security Policy

## Supported Versions

| Version | Supported | Notes |
| ------- | --------- | ----- |
| 3.0.x | ✅ Active | Current release — receives every fix |
| 2.3.x | ⚠️ Security fixes only | Until 1 January 2027. See the upgrade guide |
| 2.0.x – 2.2.x | ❌ Unsupported | Upgrade to 2.3.x — same requirements, no breaking changes |
| < 2.0 | ❌ Unsupported | Requires Laravel 10 or 11 — both EOL with unpatched CVEs |

Only the latest minor of each supported major is supported. 3.0 needs PHP
8.4 or higher and Laravel 12 or 13; the
[upgrade guide](docs/project/upgrading.md) takes most applications four steps.

2.3.x receives security fixes only, on the `2.x` branch, until
**1 January 2027**, and is unsupported after that. Stay on `^2.3` until you
are on PHP 8.4 — but no longer than that.

Versions below 2.0 depend on Laravel 10 or 11. Composer's security advisories
block installing those, so there is no supported upgrade path that keeps them.

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.**

Email **help@mcore-services.be** with:

- A description of the issue and its impact
- Steps to reproduce, or a proof of concept
- The SDK, PHP and Laravel versions affected
- Any suggested fix, if you have one

You will get an acknowledgement within 48 hours and an assessment within five
working days. If the report is valid, you will be told when a fix is released and
credited in the changelog unless you would rather not be.

This is a community package maintained alongside client work, not a funded
security programme. Response times are best-effort, and there is no bounty.

## Scope

**In scope:** token handling and storage, the OAuth flow, credential leakage
through logs or exceptions, and any path where SDK code could be made to send
data somewhere it should not.

**Out of scope:** vulnerabilities in the Teamleader Focus API itself. Report
those to Teamleader. Also out of scope: issues that require an attacker to
already control your application's configuration or database.

## Security Best Practices

When using this SDK:

- Never commit `.env` files with real credentials
- Use HTTPS for all redirect URIs in production
- Rotate API credentials periodically
- Enable token encryption in production
- Monitor logs for unexpected authentication attempts
- Keep the SDK updated — see [CHANGELOG.md](CHANGELOG.md)

A note on logging: the SDK sanitises tokens and credentials before writing to
logs, but `TEAMLEADER_LOG_ALL_REQUESTS=true` produces verbose output including
request payloads. Those payloads can contain personal data from your Teamleader
account. Keep it off in production, and treat any logs captured with it on as
sensitive.

## Security Features

The SDK includes:

- ✅ Token encryption support
- ✅ State parameter CSRF protection on the OAuth flow
- ✅ Distributed locking for token refresh, preventing race conditions
- ✅ Rate limiting, Redis-backed and shared across workers
- ✅ Token storage in the database rather than in session or cache alone
- ✅ Input validation before API calls
- ✅ Sensitive data sanitisation in logs (`SanitizesLogData`)
