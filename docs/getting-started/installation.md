# Installation

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.4 or higher (tested on 8.4 – 8.5) |
| Laravel | 12.x or 13.x |
| Database | MySQL 5.7+, PostgreSQL 10+ or SQLite 3.8+ |
| Cache | Any Laravel store; Redis for production |
| Redis | Required while rate limiting is enabled (the default) |

Laravel 10 and 11 were dropped in v2.0: both are end of life, and Composer's
security advisories block installing them.

## Install the package

```bash
composer require mcore-services/teamleader-sdk
```

The service provider and the `Teamleader` facade are registered through package
discovery.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=teamleader-config
```

This creates `config/teamleader.php`. No migration is needed: the SDK creates
its `teamleader_tokens` table on first use.

## Create a Teamleader integration

The SDK authenticates with OAuth 2.0, so you need an integration in the
Teamleader developer portal for a client ID and secret.

1. Sign in at [developer.focus.teamleader.eu](https://developer.focus.teamleader.eu/)
   and open **Integrations**.
2. Choose **Create integration**, give it a name and continue.
3. Set the **Redirect URI** to your callback route, for example
   `https://your-app.com/teamleader/callback`. It must match
   `TEAMLEADER_REDIRECT_URI` exactly.
4. Copy the **Client ID** and **Client Secret**.
5. Fill in the remaining required fields and save. You only need to request
   publication if the integration is meant for other Teamleader accounts.

{% hint style="info" %}
Teamleader accepts `http://` redirect URIs for local development only.
{% endhint %}

## Add your credentials

```env
TEAMLEADER_CLIENT_ID=your_client_id
TEAMLEADER_CLIENT_SECRET=your_client_secret
TEAMLEADER_REDIRECT_URI="${APP_URL}/teamleader/callback"
```

These three are required. Every other setting has a default; see
[Configuration](configuration.md).

## Next

- [Authentication](authentication.md) — the two OAuth routes
- [Quick start](quick-start.md) — your first requests
