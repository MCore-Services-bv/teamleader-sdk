# Security Policy

## Supported Versions

| Version | Supported | Notes |
| ------- | --------- | ----- |
| 2.3.x | ✅ Active | Current release — receives every fix |
| 2.2.x | ❌ Unsupported | Upgrade to 2.3.x — same requirements, no breaking changes |
| 2.1.x | ❌ Unsupported | Upgrade to 2.3.x — same requirements, no breaking changes |
| 2.0.x | ❌ Unsupported | Upgrade to 2.3.x — same requirements, no breaking changes |
| < 2.0 | ❌ Unsupported | Requires Laravel 10 or 11 — both EOL with unpatched CVEs |

Only the latest minor of the current major is supported. Fixes land as patch
releases on 2.3.x, which runs on the same PHP and Laravel versions as 2.0, 2.1
and 2.2, so there is no reason to stay on an older minor.

When 3.0.0 is released, 2.x moves to security fixes only for **three months**
and is unsupported after that. The date will be stated here on release.

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
