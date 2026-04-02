# Usage

Getting started with the Teamleader SDK for Laravel.

## Installation

```bash
composer require mcore-services/teamleader-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=teamleader-config
```

> No `php artisan migrate` is required. The SDK automatically creates the `teamleader_tokens` table on first use.

---

## Configuration

Add your credentials to `.env`:

```env
TEAMLEADER_CLIENT_ID=your_client_id
TEAMLEADER_CLIENT_SECRET=your_client_secret
TEAMLEADER_REDIRECT_URI=${APP_URL}/teamleader/callback
```

Key options in `config/teamleader.php`:

| Option                            | Description                                     |
|-----------------------------------|-------------------------------------------------|
| `api_version`                     | Teamleader API version                          |
| `timeouts.connection`             | Connection timeout in seconds                   |
| `timeouts.read`                   | Read timeout in seconds                         |
| `retry.attempts`                  | Number of automatic retry attempts              |
| `retry.delay`                     | Delay between retries in milliseconds           |
| `error_handling.throw_exceptions` | Whether to throw exceptions (429 always throws) |

---

## Authentication

The SDK uses OAuth 2.0. Set up two routes in your application.

### Step 1 — Redirect to Teamleader

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

public function redirectToTeamleader()
{
    return Teamleader::authorize();
}
```

### Step 2 — Handle the Callback

```php
public function handleCallback(Request $request)
{
    if (Teamleader::handleCallback($request->get('code'), $request->get('state'))) {
        return redirect('/dashboard')->with('success', 'Connected to Teamleader!');
    }

    return redirect('/settings')->with('error', 'Connection failed.');
}
```

### Checking Auth Status

```php
if (Teamleader::isAuthenticated()) {
    // Ready to make API calls
}
```

### Logging Out

```php
Teamleader::logout();
```

---

## Basic Usage

### Facade

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// List resources
$companies = Teamleader::companies()->list();

// Get a single resource
$contact = Teamleader::contacts()->info('contact-uuid');

// Create a resource
$deal = Teamleader::deals()->create([
    'title' => 'New Opportunity',
    'lead'  => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid'],
    ],
]);

// Update a resource
Teamleader::companies()->update('company-uuid', ['name' => 'Acme Corp Ltd']);

// Delete a resource
Teamleader::companies()->delete('company-uuid');
```

### Dependency Injection

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CompanyService
{
    public function __construct(private TeamleaderSDK $teamleader) {}

    public function getActive(): array
    {
        return $this->teamleader->companies()->list(['status' => 'active']);
    }
}
```

---

## Centralising UUIDs

Teamleader UUIDs are environment-specific. Store them in `config/teamleader.php` rather than scattering raw UUID strings
through your codebase.

### Export UUIDs via Artisan

```bash
# All resources
php artisan teamleader:export-uuids

# Specific resources
php artisan teamleader:export-uuids --resource=departments
php artisan teamleader:export-uuids --resource=users
php artisan teamleader:export-uuids --resource=deal-phases
php artisan teamleader:export-uuids --resource=custom-fields
```

The command outputs ready-to-paste configuration for `config/teamleader.php`.

### Manual Retrieval via Tinker

```bash
php artisan tinker
```

```php
Teamleader::departments()->list();
Teamleader::workTypes()->list();
Teamleader::customFields()->list();
```

### Using Config UUIDs

```php
Teamleader::deals()->create([
    'title'         => 'Enterprise Deal',
    'phase_id'      => config('teamleader.deal_phases.qualified'),
    'source_id'     => config('teamleader.deal_sources.website'),
    'department_id' => config('teamleader.departments.sales'),
]);
```

---

## Debugging

Enable request logging in `.env`:

```env
TEAMLEADER_DEBUG_MODE=true
TEAMLEADER_LOG_ALL_REQUESTS=true
```

Inspect API call statistics:

```php
$count = Teamleader::getApiCallCount();
$calls = Teamleader::getApiCalls();

Teamleader::resetApiCallStats();
```

---

## Artisan Commands

```bash
# Check SDK and auth status
php artisan teamleader:status

# Validate configuration
php artisan teamleader:config:validate

# Run health check
php artisan teamleader:health

# Export UUIDs
php artisan teamleader:export-uuids
```

---

## Related Resources

- [[Resources]] — Full resource reference and capability matrix
- [[Filtering]] — Filters, sorting, pagination
- [[Sideloading]] — Loading related data
- [[Errors]] — Exception reference
