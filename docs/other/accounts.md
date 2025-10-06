# Accounts

Fetch account information such as which version of Projects the account is using. This is useful for determining whether to use the new Projects v2 API or the legacy Projects API.

## Endpoint

`accounts`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `projectsV2Status()`

Fetch which version of Projects the account is using.

**Parameters:**
- None

**Returns:** Array containing status and optional auto-switch date

**Example:**
```php
$status = $teamleader->accounts()->projectsV2Status();

// Returns:
// [
//     'data' => [
//         'status' => 'projects-v2',  // or 'legacy'
//         'will_be_automatically_switched_on' => '2024-12-31'  // optional
//     ]
// ]

$version = $status['data']['status'];
$autoSwitchDate = $status['data']['will_be_automatically_switched_on'] ?? null;
```

### `isUsingProjectsV2()`

Check if the account is using Projects v2.

**Returns:** Boolean indicating if account uses Projects v2

**Example:**
```php
if ($teamleader->accounts()->isUsingProjectsV2()) {
    // Account is using Projects v2
    $projects = $teamleader->projects()->list();
} else {
    // Account is using legacy projects
    echo "Please upgrade to Projects v2";
}
```

### `isUsingLegacyProjects()`

Check if the account is using legacy Projects.

**Returns:** Boolean indicating if account uses legacy Projects

**Example:**
```php
if ($teamleader->accounts()->isUsingLegacyProjects()) {
    // Show migration notice
    $switchDate = $teamleader->accounts()->getAutoSwitchDate();
    
    if ($switchDate) {
        echo "Your account will be migrated on {$switchDate}";
    }
}
```

### `getProjectsVersion()`

Get the current Projects version as a string.

**Returns:** String ("projects-v2" or "legacy")

**Example:**
```php
$version = $teamleader->accounts()->getProjectsVersion();

match($version) {
    'projects-v2' => $this->handleProjectsV2(),
    'legacy' => $this->handleLegacyProjects(),
};
```

### `getAutoSwitchDate()`

Get the date when the account will be automatically switched to Projects v2.

**Returns:** String with date (YYYY-MM-DD) or null if not scheduled

**Example:**
```php
$switchDate = $teamleader->accounts()->getAutoSwitchDate();

if ($switchDate) {
    echo "Automatic switch scheduled for: {$switchDate}";
} else {
    echo "No automatic switch scheduled";
}
```

### `hasScheduledAutoSwitch()`

Check if the account has a scheduled automatic switch to Projects v2.

**Returns:** Boolean indicating if auto-switch is scheduled

**Example:**
```php
if ($teamleader->accounts()->hasScheduledAutoSwitch()) {
    $date = $teamleader->accounts()->getAutoSwitchDate();
    $days = $teamleader->accounts()->getDaysUntilAutoSwitch();
    
    echo "Migration scheduled in {$days} days ({$date})";
}
```

### `getAccountStatus()`

Get complete account status information in a formatted array.

**Returns:** Array with all status information

**Example:**
```php
$status = $teamleader->accounts()->getAccountStatus();

// Returns:
// [
//     'version' => 'projects-v2',
//     'is_projects_v2' => true,
//     'is_legacy' => false,
//     'auto_switch_date' => '2024-12-31',
//     'has_scheduled_switch' => true
// ]
```

### `getDaysUntilAutoSwitch()`

Calculate the number of days until the automatic switch (if scheduled).

**Returns:** Integer with days remaining, or null if not scheduled

**Example:**
```php
$days = $teamleader->accounts()->getDaysUntilAutoSwitch();

if ($days !== null) {
    if ($days > 0) {
        echo "Migration in {$days} days";
    } else if ($days === 0) {
        echo "Migration is today!";
    } else {
        echo "Migration date has passed";
    }
}
```

### `isAutoSwitchApproaching()`

Check if the auto-switch date is approaching within specified days.

**Parameters:**
- `days` (int, optional): Number of days to consider as "approaching" (default: 30)

**Returns:** Boolean indicating if switch is approaching

**Example:**
```php
// Check if switch is within 30 days
if ($teamleader->accounts()->isAutoSwitchApproaching()) {
    // Show urgent migration warning
    $days = $teamleader->accounts()->getDaysUntilAutoSwitch();
    echo "URGENT: Migration in {$days} days!";
}

// Check if switch is within 7 days
if ($teamleader->accounts()->isAutoSwitchApproaching(7)) {
    // Send immediate notification
    Mail::to($admin)->send(new UrgentMigrationNotice());
}
```

## Helper Methods

### `getProjectVersions()`

Get all valid project version statuses.

**Example:**
```php
$versions = $teamleader->accounts()->getProjectVersions();
// Returns: ['projects-v2', 'legacy']
```

## Projects Version Statuses

- **`projects-v2`**: Account is using the new Projects v2 API
- **`legacy`**: Account is using the legacy Projects API

## Common Usage Patterns

### Conditional API Usage

```php
$accounts = $teamleader->accounts();

if ($accounts->isUsingProjectsV2()) {
    // Use Projects v2 endpoints
    $projects = $teamleader->projects()->list();
    $tasks = $teamleader->tasks()->list();
    $materials = $teamleader->materials()->list();
} else {
    // Use legacy endpoints or show upgrade notice
    throw new \Exception('Please upgrade to Projects v2');
}
```

### Migration Warning System

```php
$accounts = $teamleader->accounts();

if ($accounts->isUsingLegacyProjects()) {
    $status = $accounts->getAccountStatus();
    
    if ($status['has_scheduled_switch']) {
        $days = $accounts->getDaysUntilAutoSwitch();
        
        if ($days !== null && $days <= 30) {
            // Show urgent warning
            session()->flash('warning', "Your account will be migrated to Projects v2 in {$days} days!");
        } else if ($days !== null) {
            // Show info notice
            session()->flash('info', "Your account is scheduled for Projects v2 migration on {$status['auto_switch_date']}");
        }
    } else {
        // No scheduled migration, but still on legacy
        session()->flash('info', 'Consider upgrading to Projects v2 for new features');
    }
}
```

### Dashboard Status Widget

```php
public function getProjectsStatus()
{
    $status = $teamleader->accounts()->getAccountStatus();
    
    return [
        'version' => $status['version'],
        'badge_color' => $status['is_projects_v2'] ? 'success' : 'warning',
        'badge_text' => $status['is_projects_v2'] ? 'Projects v2' : 'Legacy',
        'migration_info' => $this->getMigrationInfo($status),
    ];
}

private function getMigrationInfo($status)
{
    if (!$status['has_scheduled_switch']) {
        return null;
    }
    
    $days = $teamleader->accounts()->getDaysUntilAutoSwitch();
    
    return [
        'date' => $status['auto_switch_date'],
        'days_remaining' => $days,
        'is_urgent' => $days <= 30,
    ];
}
```

### Automatic Migration Notifications

```php
use Illuminate\Console\Command;

class CheckMigrationStatus extends Command
{
    protected $signature = 'teamleader:check-migration';
    
    public function handle()
    {
        $accounts = app('teamleader')->accounts();
        
        if (!$accounts->hasScheduledAutoSwitch()) {
            $this->info('No migration scheduled');
            return;
        }
        
        $days = $accounts->getDaysUntilAutoSwitch();
        
        if ($days === null) {
            return;
        }
        
        // Send notifications based on days remaining
        match(true) {
            $days === 1 => $this->sendUrgentNotification(),
            $days === 7 => $this->sendWeeklyNotification(),
            $days === 30 => $this->sendMonthlyNotification(),
            default => null,
        };
    }
}
```

### Feature Flag Implementation

```php
class ProjectsFeatureFlag
{
    public function isProjectsV2Enabled(): bool
    {
        try {
            return app('teamleader')->accounts()->isUsingProjectsV2();
        } catch (\Exception $e) {
            // Fallback to false if API call fails
            Log::error('Failed to check Projects version', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function shouldUseLegacyProjects(): bool
    {
        return !$this->isProjectsV2Enabled();
    }
}

// Usage in your code
if (app(ProjectsFeatureFlag::class)->isProjectsV2Enabled()) {
    // Use new features
}
```

### Cache Projects Version Status

```php
use Illuminate\Support\Facades\Cache;

class AccountStatusService
{
    public function getProjectsVersion(): string
    {
        return Cache::remember('teamleader_projects_version', now()->addHours(24), function() {
            return app('teamleader')->accounts()->getProjectsVersion();
        });
    }
    
    public function isUsingProjectsV2(): bool
    {
        return $this->getProjectsVersion() === 'projects-v2';
    }
    
    public function clearCache(): void
    {
        Cache::forget('teamleader_projects_version');
    }
}
```

### Migration Countdown Component

```php
// Laravel Livewire Component
namespace App\Http\Livewire;

use Livewire\Component;

class MigrationCountdown extends Component
{
    public $daysRemaining;
    public $switchDate;
    public $isScheduled;
    
    public function mount()
    {
        $accounts = app('teamleader')->accounts();
        $this->isScheduled = $accounts->hasScheduledAutoSwitch();
        
        if ($this->isScheduled) {
            $this->daysRemaining = $accounts->getDaysUntilAutoSwitch();
            $this->switchDate = $accounts->getAutoSwitchDate();
        }
    }
    
    public function render()
    {
        return view('livewire.migration-countdown');
    }
}
```

## Response Structure

### projectsV2Status()

```php
[
    'data' => [
        'status' => 'projects-v2',  // or 'legacy'
        'will_be_automatically_switched_on' => '2024-12-31'  // optional
    ]
]
```

### getAccountStatus()

```php
[
    'version' => 'projects-v2',
    'is_projects_v2' => true,
    'is_legacy' => false,
    'auto_switch_date' => '2024-12-31',
    'has_scheduled_switch' => true
]
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $version = $teamleader->accounts()->getProjectsVersion();
    
    if ($version === 'legacy') {
        // Handle legacy version
    }
} catch (TeamleaderException $e) {
    // Handle API errors
    Log::error('Failed to fetch account status', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Use fallback behavior
    $version = config('teamleader.fallback_projects_version', 'projects-v2');
}
```

## Best Practices

### 1. Cache the Version Status

Projects version doesn't change frequently, so cache the result:

```php
// Cache for 24 hours
$version = Cache::remember('projects_version', 86400, function() {
    return app('teamleader')->accounts()->getProjectsVersion();
});
```

### 2. Handle Migration Gracefully

Prepare your application for the transition:

```php
class ProjectsService
{
    public function listProjects()
    {
        $accounts = app('teamleader')->accounts();
        
        if ($accounts->isUsingProjectsV2()) {
            return $this->listProjectsV2();
        } else {
            // Provide legacy support or migration path
            if ($accounts->hasScheduledAutoSwitch()) {
                $this->notifyUpcomingMigration();
            }
            
            return $this->listLegacyProjects();
        }
    }
}
```

### 3. Monitor Migration Status

Set up monitoring for upcoming migrations:

```php
// In a scheduled task
if ($accounts->isAutoSwitchApproaching(7)) {
    // Alert administrators
    Notification::send($admins, new MigrationApproaching(
        $accounts->getDaysUntilAutoSwitch()
    ));
}
```

### 4. Create Migration Readiness Check

```php
public function checkMigrationReadiness(): array
{
    $accounts = app('teamleader')->accounts();
    
    return [
        'current_version' => $accounts->getProjectsVersion(),
        'is_ready_for_v2' => $this->checkV2Compatibility(),
        'has_scheduled_migration' => $accounts->hasScheduledAutoSwitch(),
        'migration_date' => $accounts->getAutoSwitchDate(),
        'days_remaining' => $accounts->getDaysUntilAutoSwitch(),
        'blocking_issues' => $this->getBlockingIssues(),
    ];
}
```

### 5. Version-Specific Routing

```php
// In your routes
Route::middleware(['auth'])->group(function() {
    $accounts = app('teamleader')->accounts();
    
    if ($accounts->isUsingProjectsV2()) {
        Route::resource('projects', ProjectsV2Controller::class);
    } else {
        Route::resource('projects', LegacyProjectsController::class);
    }
});
```

## Important Notes

- The account status check is a simple API call with no parameters
- The `will_be_automatically_switched_on` field is only present for legacy accounts scheduled for migration
- Projects v2 is the current and recommended version
- Legacy projects will eventually be migrated to Projects v2
- Consider implementing caching to avoid repeated API calls
- Monitor the migration date to prepare your integration
- The auto-switch date format is `YYYY-MM-DD`
- Negative days from `getDaysUntilAutoSwitch()` means the date has passed

## Migration Timeline

Understanding the migration process:

1. **Legacy Status**: Account is on legacy Projects
2. **Migration Scheduled**: `will_be_automatically_switched_on` date is set
3. **Approaching (30 days)**: Time to finalize migration preparations
4. **Urgent (7 days)**: Last chance to address any blockers
5. **Migration Day**: Account automatically switches to Projects v2
6. **Projects v2**: Account is fully migrated

## Testing Migration Scenarios

```php
// Create a service to simulate migration states
class MigrationSimulator
{
    public function simulateStatus(string $version, ?string $switchDate = null)
    {
        return [
            'data' => [
                'status' => $version,
                'will_be_automatically_switched_on' => $switchDate
            ]
        ];
    }
    
    public function testLegacyWithMigration()
    {
        $futureDate = now()->addDays(15)->format('Y-m-d');
        return $this->simulateStatus('legacy', $futureDate);
    }
    
    public function testProjectsV2()
    {
        return $this->simulateStatus('projects-v2');
    }
}
```

## See Also

- [Projects](../projects/projects.md) - Managing Projects v2
- [Tasks](../projects/tasks.md) - Managing tasks in Projects v2
- [Materials](../projects/materials.md) - Managing materials in Projects v2
- [Groups](../projects/groups.md) - Managing project groups in Projects v2
