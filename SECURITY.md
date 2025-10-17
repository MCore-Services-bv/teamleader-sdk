# Security Policy

## Supported Versions

| Version | Supported |
| ------- |-----------|
| 1.0.x-alpha | ✅         |

## Reporting a Vulnerability

**Please do not create public GitHub issues for security vulnerabilities.**

Email security concerns to: **help@mcore-services.be**

We will respond within 48 hours and work with you to address any issues.

## Security Best Practices

When using this SDK:
- Never commit `.env` files with real credentials
- Use HTTPS for all redirect URIs in production
- Regularly rotate API credentials
- Enable token encryption in production
- Monitor logs for suspicious authentication attempts
- Keep the SDK updated for security patches

## Security Features

The SDK includes:
- ✅ Token encryption support
- ✅ State parameter CSRF protection
- ✅ Distributed locking for token refresh
- ✅ Rate limiting to prevent abuse
- ✅ Secure token storage in database
- ✅ Input validation before API calls
- ✅ Sensitive data sanitization in logs
