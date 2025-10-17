# Accounts

Fetch account information in Teamleader Focus.

## Overview

The Accounts resource provides methods to check account-level settings and configurations, specifically the Projects version status. This helps determine whether an account is using the new Projects v2 or legacy Projects system, which affects which API endpoints should be used.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [projectsV2Status()](#projectsv2status)
    - [isUsingProjectsV2()](#isusingprojectsv2)
    - [isUsingLegacyProjects()](#isusinglegacyprojects)
    - [getProjectsVersion()](#getprojectsversion)
    - [getAutoSwitchDate()](#getautoswitchdate)
- [Helper Methods](#helper-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`accounts`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `projectsV2Status()`

Fetch which version of Projects the account is using.

**Returns:** Array with status and optional auto-switch date

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Check Projects version
$status = Teamleader::accounts()->projectsV2Status();

$version = $status['data']['status']; // 'projects-v2' or 'legacy'

if (isset($status['data']['will_be_automatically_switched_on'])) {
    $switchDate = $status['data']['will_be_automatically_switched_on'];
    echo "Will be switched to Projects v2 on: {$switchDate}";
}
```

### `isUsingProjectsV2()`

Check if the account is using Projects v2.

**Returns:** boolean

**Example:**
```php
if (Teamleader::accounts()->isUsingProjectsV2()) {
    // Use Projects v2 endpoints
    $projects = Teamleader::projects()->list();
} else {
    // Use legacy endpoints
    $projects = Teamleader::legacyProjects()->list();
}
```

### `isUsingLegacyProjects()`

Check if the account is using legacy Projects.

**Returns:** boolean

**Example:**
```php
if (Teamleader::accounts()->isUsingLegacyProjects()) {
    echo "Account is using legacy Projects";
}
```

### `getProjectsVersion()`

Get the current Projects version status.

**Returns:** string ('projects-v2' or 'legacy')

**Example:**
```php
$version = Teamleader::accounts()->getProjectsVersion();

echo "Account is using: {$version}";
```

### `getAutoSwitchDate()`

Get the date when account will be automatically switched to Projects v2.

**Returns:** string|null (date in YYYY-MM-DD format or null)

**Example:**
```php
$switchDate = Teamleader::accounts()->getAutoSwitchDate();

if ($switchDate) {
    echo "Account will be switched on: {$switchDate}";
} else {
    echo "No automatic switch scheduled";
}
```

## Helper Methods

### Days Until Auto Switch

```php
// Get days until automatic switch
$days = Teamleader::accounts()->getDaysUntilAutoSwitch();

if ($days !== null) {
    echo "Switching in {$days} days";
}
```

### Check if Switch is Approaching

```php
// Check if switch is within 30 days
$isApproaching = Teamleader::accounts()->isAutoSwitchApproaching(30);

if ($isApproaching) {
    echo "Projects v2 switch is approaching!";
}
```

### Get Available Versions

```php
// Get list of valid project versions
$versions = Teamleader::accounts()->getProjectVersions();

// Returns: ['projects-v2', 'legacy']
```

## Project Versions

Available project version statuses:

| Version | Description |
|---------|-------------|
| `projects-v2` | Using new Projects v2 system |
| `legacy` | Using legacy Projects system |

## Response Structure

### Projects V2 Status Response

```php
[
    'data' => [
        'status' => 'projects-v2', // or 'legacy'
        'will_be_automatically_switched_on' => '2025-12-31' // optional, only for legacy
    ]
]
```

**Fields:**
- `status` (string): Current Projects version
- `will_be_automatically_switched_on` (string, optional): Date when account will be migrated to Projects v2

## Usage Examples

### Check Projects Version

```php
// Check which version is active
$status = Teamleader::accounts()->projectsV2Status();

if ($status['data']['status'] === 'projects-v2') {
    echo "Using Projects v2";
} else {
    echo "Using Legacy Projects";
}
```

### Conditional Endpoint Usage

```php
// Use appropriate endpoints based on version
if (Teamleader::accounts()->isUsingProjectsV2()) {
    // Use Projects v2 endpoints
    $projects = Teamleader::projects()->list();
    $projectLines = Teamleader::projectLines()->list();
    $projectTasks = Teamleader::projectTasks()->list();
} else {
    // Use legacy endpoints
    $projects = Teamleader::legacyProjects()->list();
    $milestones = Teamleader::legacyMilestones()->list();
}
```

### Check Auto-Switch Date

```php
// Get auto-switch information
$status = Teamleader::accounts()->projectsV2Status();

if (isset($status['data']['will_be_automatically_switched_on'])) {
    $switchDate = $status['data']['will_be_automatically_switched_on'];
    $daysUntil = Teamleader::accounts()->getDaysUntilAutoSwitch();
    
    echo "Your account will be switched to Projects v2 in {$daysUntil} days.";
    echo "Switch date: {$switchDate}";
}
```

### Warn Users About Upcoming Switch

```php
// Check if switch is approaching
if (Teamleader::accounts()->isAutoSwitchApproaching(30)) {
    $days = Teamleader::accounts()->getDaysUntilAutoSwitch();
    
    echo '<div class="alert alert-warning">';
    echo "Important: Your account will be migrated to Projects v2 in {$days} days. ";
    echo 'Please prepare for the transition.';
    echo '</div>';
}
```

### Version-aware Project Fetching

```php
function getProjects(): array
{
    if (Teamleader::accounts()->isUsingProjectsV2()) {
        return Teamleader::projects()->list();
    } else {
        return Teamleader::legacyProjects()->list();
    }
}

// Usage
$projects = getProjects();
```

## Common Use Cases

### 1. Version Detection Wrapper

```php
class ProjectsVersionDetector
{
    private $version;
    private $switchDate;
    
    public function __construct()
    {
        $status = Teamleader::accounts()->projectsV2Status();
        $this->version = $status['data']['status'];
        $this->switchDate = $status['data']['will_be_automatically_switched_on'] ?? null;
    }
    
    public function isV2(): bool
    {
        return $this->version === 'projects-v2';
    }
    
    public function isLegacy(): bool
    {
        return $this->version === 'legacy';
    }
    
    public function getVersion(): string
    {
        return $this->version;
    }
    
    public function hasScheduledSwitch(): bool
    {
        return $this->switchDate !== null;
    }
    
    public function getSwitchDate(): ?string
    {
        return $this->switchDate;
    }
    
    public function getDaysUntilSwitch(): ?int
    {
        if (!$this->switchDate) {
            return null;
        }
        
        $switch = new DateTime($this->switchDate);
        $now = new DateTime();
        $diff = $now->diff($switch);
        
        return $diff->invert ? -$diff->days : $diff->days;
    }
}

// Usage
$detector = new ProjectsVersionDetector();

if ($detector->isV2()) {
    // Use v2 endpoints
}

if ($detector->hasScheduledSwitch()) {
    $days = $detector->getDaysUntilSwitch();
    echo "Switching in {$days} days";
}
```

### 2. Unified Projects Interface

```php
class ProjectsManager
{
    private $useV2;
    
    public function __construct()
    {
        $this->useV2 = Teamleader::accounts()->isUsingProjectsV2();
    }
    
    public function listProjects(array $filters = []): array
    {
        if ($this->useV2) {
            return Teamleader::projects()->list($filters);
        } else {
            return Teamleader::legacyProjects()->list($filters);
        }
    }
    
    public function getProject(string $id): array
    {
        if ($this->useV2) {
            return Teamleader::projects()->info($id);
        } else {
            return Teamleader::legacyProjects()->info($id);
        }
    }
    
    public function createProject(array $data): array
    {
        if ($this->useV2) {
            return Teamleader::projects()->create($data);
        } else {
            return Teamleader::legacyProjects()->create($data);
        }
    }
}

// Usage
$manager = new ProjectsManager();
$projects = $manager->listProjects();
```

### 3. Migration Notification System

```php
class MigrationNotifier
{
    public function shouldNotify(): bool
    {
        // Only notify legacy users with scheduled switch
        if (Teamleader::accounts()->isUsingProjectsV2()) {
            return false;
        }
        
        $switchDate = Teamleader::accounts()->getAutoSwitchDate();
        return $switchDate !== null;
    }
    
    public function getNotificationMessage(): ?string
    {
        if (!$this->shouldNotify()) {
            return null;
        }
        
        $days = Teamleader::accounts()->getDaysUntilAutoSwitch();
        $date = Teamleader::accounts()->getAutoSwitchDate();
        
        if ($days <= 7) {
            return "URGENT: Your account will be migrated to Projects v2 in {$days} days ({$date})";
        } elseif ($days <= 30) {
            return "Important: Your account will be migrated to Projects v2 on {$date}";
        } else {
            return "Your account is scheduled to migrate to Projects v2 on {$date}";
        }
    }
    
    public function getSeverity(): string
    {
        $days = Teamleader::accounts()->getDaysUntilAutoSwitch();
        
        if ($days === null) {
            return 'info';
        } elseif ($days <= 7) {
            return 'danger';
        } elseif ($days <= 30) {
            return 'warning';
        } else {
            return 'info';
        }
    }
}

// Usage
$notifier = new MigrationNotifier();

if ($notifier->shouldNotify()) {
    $message = $notifier->getNotificationMessage();
    $severity = $notifier->getSeverity();
    
    echo "<div class='alert alert-{$severity}'>{$message}</div>";
}
```

### 4. Version Compatibility Check

```php
function checkProjectsCompatibility(): array
{
    $version = Teamleader::accounts()->getProjectsVersion();
    $isV2 = $version === 'projects-v2';
    
    return [
        'version' => $version,
        'is_v2' => $isV2,
        'available_endpoints' => [
            'projects' => true,
            'projectLines' => $isV2,
            'projectTasks' => $isV2,
            'legacyProjects' => !$isV2,
            'legacyMilestones' => !$isV2,
        ],
        'migration_info' => [
            'scheduled' => Teamleader::accounts()->getAutoSwitchDate() !== null,
            'date' => Teamleader::accounts()->getAutoSwitchDate(),
            'days_until' => Teamleader::accounts()->getDaysUntilAutoSwitch(),
        ]
    ];
}

// Usage
$compatibility = checkProjectsCompatibility();

if (!$compatibility['available_endpoints']['projectLines']) {
    echo "Project Lines API is not available on legacy version";
}
```

## Best Practices

### 1. Cache Version Status

```php
// Cache version status to avoid repeated API calls
class AccountVersionCache
{
    private static $version = null;
    
    public static function getVersion(): string
    {
        if (self::$version === null) {
            self::$version = Teamleader::accounts()->getProjectsVersion();
        }
        
        return self::$version;
    }
    
    public static function isV2(): bool
    {
        return self::getVersion() === 'projects-v2';
    }
}
```

### 2. Handle Both Versions Gracefully

```php
// Support both versions in your code
function getProjectData(string $projectId): array
{
    try {
        if (Teamleader::accounts()->isUsingProjectsV2()) {
            return Teamleader::projects()->info($projectId);
        } else {
            return Teamleader::legacyProjects()->info($projectId);
        }
    } catch (Exception $e) {
        Log::error('Failed to fetch project', [
            'id' => $projectId,
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
```

### 3. Notify Users About Migration

```php
// Display migration notice to users
if (Teamleader::accounts()->isAutoSwitchApproaching(60)) {
    $days = Teamleader::accounts()->getDaysUntilAutoSwitch();
    $date = Teamleader::accounts()->getAutoSwitchDate();
    
    echo '<div class="migration-notice">';
    echo '<h4>Projects v2 Migration</h4>';
    echo "<p>Your account will be migrated in {$days} days ({$date}).</p>";
    echo '<a href="/migration-guide">Learn more</a>';
    echo '</div>';
}
```

### 4. Log Version Changes

```php
// Log when version changes (after migration)
function logVersionStatus(): void
{
    $currentVersion = Teamleader::accounts()->getProjectsVersion();
    $lastKnownVersion = Cache::get('teamleader_projects_version');
    
    if ($lastKnownVersion !== $currentVersion) {
        Log::info('Projects version changed', [
            'old_version' => $lastKnownVersion,
            'new_version' => $currentVersion
        ]);
        
        Cache::put('teamleader_projects_version', $currentVersion, 3600);
    }
}
```

### 5. Feature Flag Based on Version

```php
// Enable features based on Projects version
class FeatureFlags
{
    public static function hasProjectLines(): bool
    {
        return Teamleader::accounts()->isUsingProjectsV2();
    }
    
    public static function hasProjectTasks(): bool
    {
        return Teamleader::accounts()->isUsingProjectsV2();
    }
    
    public static function hasLegacyMilestones(): bool
    {
        return Teamleader::accounts()->isUsingLegacyProjects();
    }
}

// Usage
if (FeatureFlags::hasProjectLines()) {
    // Show project lines in UI
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $status = Teamleader::accounts()->projectsV2Status();
    
    $version = $status['data']['status'];
    
    // Use version info
    
} catch (TeamleaderException $e) {
    Log::error('Failed to check Projects version', [
        'error' => $e->getMessage()
    ]);
    
    // Provide safe default or handle gracefully
    // You might want to assume legacy by default
    $version = 'legacy';
}

// Check if version is valid
try {
    $isV2 = Teamleader::accounts()->isUsingProjectsV2();
} catch (Exception $e) {
    Log::error('Version check failed');
    // Default to legacy for safety
    $isV2 = false;
}
```

## Important Notes

### 1. Read-Only Resource

Account settings cannot be modified via the API. This resource is purely informational.

### 2. Migration Timeline

Accounts with legacy Projects may have a scheduled automatic migration date. Always check for this when using legacy endpoints.

### 3. Endpoint Compatibility

- Projects v2: Use `projects()`, `projectLines()`, `projectTasks()`
- Legacy: Use `legacyProjects()`, `legacyMilestones()`

### 4. No list() or info() Methods

The accounts resource doesn't support standard CRUD operations. Use `projectsV2Status()` instead.

## Migration Considerations

When an account migrates from legacy to Projects v2:

1. **Data Migration**: Projects data is automatically migrated by Teamleader
2. **Endpoint Changes**: Update your code to use v2 endpoints
3. **Data Structure**: Some fields may change structure
4. **Testing**: Test your integration with v2 before the migration date

## Related Resources

- [Projects v2](../projects/projects.md) - New Projects v2 API
- [Legacy Projects](../projects/legacy/projects.md) - Legacy Projects API
- [Project Lines](../projects/project-lines.md) - Project lines (v2 only)
- [Project Tasks](../projects/project-tasks.md) - Project tasks (v2 only)
- [Legacy Milestones](../projects/legacy/milestones.md) - Milestones (legacy only)

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Migration Guide](../migration.md) - Migrating to Projects v2
