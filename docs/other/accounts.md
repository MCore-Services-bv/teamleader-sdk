# Accounts

Read account-level settings in Teamleader Focus.

## Overview

The Accounts resource exposes the Projects version status for the connected Teamleader account. This determines whether
the account is on the new Projects v2 or the legacy Projects system — which in turn determines which project-related SDK
resources are available.

Access via `Teamleader::accounts()`.

> **`list()` and `info()` throw** `InvalidArgumentException`. Only `projectsV2Status()` and its convenience wrappers are
> supported.
>
> **Every convenience method calls the API.** `isUsingProjectsV2()`, `getProjectsVersion()`, etc. all
> call `projectsV2Status()` internally. Cache the result if you need to call multiple helpers.

## Endpoint

`accounts`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ❌ Not supported |
| Filtering   | ❌ Not supported |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ❌ Not supported |
| Update      | ❌ Not supported |
| Deletion    | ❌ Not supported |

---

## Methods

### `projectsV2Status()`

The only real API call in this resource. Posts to `accounts.projects-v2-status`.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$status = Teamleader::accounts()->projectsV2Status();

// $status['data']['status'] === 'projects-v2' or 'legacy'
// $status['data']['will_be_automatically_switched_on'] — date string or absent
```

**Response:**

```php
[
    'data' => [
        'status'                           => 'legacy',           // or 'projects-v2'
        'will_be_automatically_switched_on'=> '2025-12-31',       // only present for legacy accounts scheduled to migrate
    ],
]
```

---

## Helper Methods

All helpers call `projectsV2Status()` internally. Cache the result to avoid redundant API calls.

| Method                                          | Returns                                                 |
|-------------------------------------------------|---------------------------------------------------------|
| `isUsingProjectsV2(): bool`                     | `true` if status is `projects-v2`                       |
| `isUsingLegacyProjects(): bool`                 | `true` if status is `legacy`                            |
| `getProjectsVersion(): string`                  | `'projects-v2'` or `'legacy'`                           |
| `getAutoSwitchDate(): ?string`                  | YYYY-MM-DD or `null`                                    |
| `getDefaultId(): ?string`                       | Alias for `getAutoSwitchDate()`                         |
| `hasScheduledAutoSwitch(): bool`                | `true` if auto-switch date is set                       |
| `getDaysUntilAutoSwitch(): ?int`                | Days remaining (negative if date has passed), or `null` |
| `isAutoSwitchApproaching(int $days = 30): bool` | `true` if switch is within `$days` days                 |
| `getAccountStatus(): array`                     | Formatted summary of all status fields                  |
| `getProjectVersions(): array`                   | `['projects-v2', 'legacy']`                             |

---

## Resource availability by version

| Resource                         | Projects v2 | Legacy |
|----------------------------------|-------------|--------|
| `Teamleader::projects()`         | ✅           | ❌      |
| `Teamleader::projectLines()`     | ✅           | ❌      |
| `Teamleader::projectTasks()`     | ✅           | ❌      |
| `Teamleader::legacyProjects()`   | ❌           | ✅      |
| `Teamleader::legacyMilestones()` | ❌           | ✅      |

---

## Usage Examples

### Route project API calls by version

```php
if (Teamleader::accounts()->isUsingProjectsV2()) {
    $projects = Teamleader::projects()->list();
} else {
    $projects = Teamleader::legacyProjects()->list();
}
```

### Cache the status and derive all checks from it

```php
// One API call — derive everything locally
$status   = Teamleader::accounts()->projectsV2Status();
$isV2     = $status['data']['status'] === 'projects-v2';
$switchOn = $status['data']['will_be_automatically_switched_on'] ?? null;

if (!$isV2 && $switchOn) {
    $days = (new DateTime)->diff(new DateTime($switchOn))->days;
    Log::warning("Account migrates to Projects v2 in {$days} days ({$switchOn})");
}
```

### Display a migration warning

```php
if (Teamleader::accounts()->isAutoSwitchApproaching(30)) {
    $days = Teamleader::accounts()->getDaysUntilAutoSwitch();
    $date = Teamleader::accounts()->getAutoSwitchDate();
    // show banner: "Your account migrates to Projects v2 in $days days ($date)."
}
```

---

## Error Handling

```php
use InvalidArgumentException;

// list() not supported
try {
    Teamleader::accounts()->list();
} catch (InvalidArgumentException $e) {
    // 'The accounts resource does not support list operations. Use projectsV2Status() method instead.'
}

// info() not supported
try {
    Teamleader::accounts()->info('uuid');
} catch (InvalidArgumentException $e) {
    // 'The accounts resource does not support info operations. Use projectsV2Status() method instead.'
}
```

---

## Related Resources

- [[Migrate]] — Translate legacy numeric IDs to new UUIDs
