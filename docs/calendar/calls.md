# Calls

Manage call activities in Teamleader Focus Calendar.

## Overview

The Calls resource provides management of call activities in your Teamleader account. Calls represent phone conversations with clients, prospects, or other contacts, and can be linked to companies and tracked with outcomes.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`calls`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of calls with optional filtering and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for pagination

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all calls
$calls = Teamleader::calls()->list();

// Get calls with filters
$calls = Teamleader::calls()->list([
    'scheduled_after' => '2025-02-01',
    'scheduled_before' => '2025-02-28'
]);

// With pagination
$calls = Teamleader::calls()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

### `info()`

Get detailed information about a specific call.

**Parameters:**
- `id` (string): The call UUID

**Example:**
```php
$call = Teamleader::calls()->info('call-uuid');
```

### `create()`

Create a new call record.

**Required fields:**
- `subject` (string): Call subject/title
- `relates_to` (object): Related entity
    - `type` (string): 'company'
    - `id` (string): Company UUID

**Optional fields:**
- `description` (string): Call description/notes
- `scheduled_at` (string): Scheduled datetime in ISO 8601 format
- `duration` (integer): Call duration in minutes
- `call_outcome_id` (string): Call outcome UUID (for completed calls)
- `assigned_to` (string): User UUID assigned to the call

**Example:**
```php
$call = Teamleader::calls()->create([
    'subject' => 'Follow-up on proposal',
    'description' => 'Discuss pricing and timeline',
    'scheduled_at' => '2025-02-20T14:00:00+00:00',
    'duration' => 30,
    'relates_to' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'assigned_to' => 'user-uuid'
]);
```

### `update()`

Update an existing call.

**Parameters:**
- `id` (string): The call UUID
- `data` (array): Fields to update (all optional except id)

**Example:**
```php
$call = Teamleader::calls()->update('call-uuid', [
    'subject' => 'Updated call subject',
    'scheduled_at' => '2025-02-20T15:00:00+00:00',
    'call_outcome_id' => 'outcome-uuid'
]);
```

## Helper Methods

### `forCompany()`

Get calls for a specific company.

```php
$calls = Teamleader::calls()->forCompany('company-uuid');

// With date range
$calls = Teamleader::calls()->forCompany('company-uuid', [
    'filters' => [
        'scheduled_after' => '2025-02-01',
        'scheduled_before' => '2025-02-28'
    ]
]);
```

### `scheduledAfter()`

Get calls scheduled after a specific date.

```php
$calls = Teamleader::calls()->scheduledAfter('2025-02-01');
```

### `scheduledBefore()`

Get calls scheduled before a specific date.

```php
$calls = Teamleader::calls()->scheduledBefore('2025-02-28');
```

### `betweenDates()`

Get calls scheduled within a specific date range.

```php
$calls = Teamleader::calls()->betweenDates(
    '2025-02-01',
    '2025-02-28'
);
```

### `withOutcome()`

Get calls with a specific outcome.

```php
$successfulCalls = Teamleader::calls()->withOutcome('outcome-uuid');
```

### `scheduled()`

Get all scheduled (upcoming) calls.

```php
$upcomingCalls = Teamleader::calls()->scheduled();

// For specific company
$upcomingCalls = Teamleader::calls()->scheduled('company-uuid');
```

### `completed()`

Get all completed calls.

```php
$completedCalls = Teamleader::calls()->completed();

// For specific company
$completedCalls = Teamleader::calls()->completed('company-uuid');
```

## Filtering

Available filters:

- `scheduled_after` (string): Filter calls occurring on or after date (YYYY-MM-DD)
- `scheduled_before` (string): Filter calls occurring on or before date (YYYY-MM-DD)
- `relates_to` (object): Filter by related entity
    - `type` (string): 'company'
    - `id` (string): Company UUID
- `call_outcome_id` (string): Filter completed calls by outcome UUID

**Filter Examples:**
```php
// Filter by date range
$calls = Teamleader::calls()->list([
    'scheduled_after' => '2025-02-01',
    'scheduled_before' => '2025-02-28'
]);

// Filter by company
$calls = Teamleader::calls()->list([
    'relates_to' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Filter by outcome
$calls = Teamleader::calls()->list([
    'call_outcome_id' => 'outcome-uuid'
]);

// Multiple filters
$calls = Teamleader::calls()->list([
    'scheduled_after' => '2025-02-01',
    'relates_to' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'call_outcome_id' => 'successful-outcome-uuid'
]);
```

## Response Structure

### Call Object

```php
[
    'id' => 'call-uuid',
    'subject' => 'Follow-up on proposal',
    'description' => 'Discuss pricing and timeline',
    'scheduled_at' => '2025-02-20T14:00:00+00:00',
    'duration' => 30,
    'relates_to' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'assigned_to' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ],
    'call_outcome' => [
        'type' => 'callOutcome',
        'id' => 'outcome-uuid'
    ],
    'completed' => true,
    'created_at' => '2025-01-15T10:30:00+00:00',
    'updated_at' => '2025-02-20T14:35:00+00:00'
]
```

## Usage Examples

### Schedule a Sales Call

```php
$call = Teamleader::calls()->create([
    'subject' => 'Initial Sales Discussion',
    'description' => 'Introduce our services and understand client needs',
    'scheduled_at' => '2025-02-22T10:00:00+00:00',
    'duration' => 45,
    'relates_to' => [
        'type' => 'company',
        'id' => 'prospect-company-uuid'
    ],
    'assigned_to' => 'sales-rep-uuid'
]);

echo "Call scheduled: {$call['data']['id']}";
```

### Log a Completed Call

```php
// Create call with outcome
$call = Teamleader::calls()->create([
    'subject' => 'Follow-up Call',
    'description' => 'Discussed contract terms and next steps',
    'scheduled_at' => now()->toIso8601String(),
    'duration' => 20,
    'relates_to' => [
        'type' => 'company',
        'id' => 'client-company-uuid'
    ],
    'call_outcome_id' => 'successful-outcome-uuid',
    'assigned_to' => 'user-uuid'
]);

echo "Call logged with outcome";
```

### Get Company Call History

```php
$companyId = 'company-uuid';

$callHistory = Teamleader::calls()->forCompany($companyId);

echo "Total calls: " . count($callHistory['data']) . "\n";

foreach ($callHistory['data'] as $call) {
    $status = $call['completed'] ? 'Completed' : 'Scheduled';
    echo "- {$call['subject']} ({$status}) - {$call['scheduled_at']}\n";
}
```

### Update Call with Outcome

```php
// After completing the call
$call = Teamleader::calls()->update('call-uuid', [
    'call_outcome_id' => 'positive-outcome-uuid',
    'description' => 'Client is interested. Sending proposal next week.'
]);

echo "Call outcome recorded";
```

### Get This Week's Calls

```php
$startOfWeek = now()->startOfWeek()->format('Y-m-d');
$endOfWeek = now()->endOfWeek()->format('Y-m-d');

$weekCalls = Teamleader::calls()->betweenDates($startOfWeek, $endOfWeek);

echo "Calls this week: " . count($weekCalls['data']);
```

## Common Use Cases

### Call Tracking Service

```php
class CallTracker
{
    public function scheduleCall($companyId, $userId, $details)
    {
        return Teamleader::calls()->create([
            'subject' => $details['subject'],
            'description' => $details['description'] ?? '',
            'scheduled_at' => $details['scheduled_at'],
            'duration' => $details['duration'] ?? 30,
            'relates_to' => [
                'type' => 'company',
                'id' => $companyId
            ],
            'assigned_to' => $userId
        ]);
    }
    
    public function logCallOutcome($callId, $outcomeId, $notes = '')
    {
        return Teamleader::calls()->update($callId, [
            'call_outcome_id' => $outcomeId,
            'description' => $notes
        ]);
    }
    
    public function getUpcomingCalls($userId)
    {
        $today = now()->format('Y-m-d');
        $nextWeek = now()->addWeek()->format('Y-m-d');
        
        $calls = Teamleader::calls()->betweenDates($today, $nextWeek);
        
        // Filter by assigned user
        return array_filter($calls['data'], function($call) use ($userId) {
            return isset($call['assigned_to']['id']) && 
                   $call['assigned_to']['id'] === $userId;
        });
    }
}
```

### Call Analytics

```php
class CallAnalytics
{
    public function getCompanyCallStats($companyId)
    {
        $calls = Teamleader::calls()->forCompany($companyId);
        
        $totalCalls = count($calls['data']);
        $completedCalls = count(array_filter(
            $calls['data'],
            fn($c) => $c['completed']
        ));
        
        $totalDuration = array_reduce(
            $calls['data'],
            fn($sum, $c) => $sum + ($c['duration'] ?? 0),
            0
        );
        
        return [
            'total_calls' => $totalCalls,
            'completed_calls' => $completedCalls,
            'pending_calls' => $totalCalls - $completedCalls,
            'total_duration_minutes' => $totalDuration,
            'average_duration' => $totalCalls > 0 
                ? round($totalDuration / $totalCalls, 2) 
                : 0
        ];
    }
    
    public function getCallsByOutcome($startDate, $endDate)
    {
        $calls = Teamleader::calls()->betweenDates($startDate, $endDate);
        
        $byOutcome = [];
        
        foreach ($calls['data'] as $call) {
            if (isset($call['call_outcome']['id'])) {
                $outcomeId = $call['call_outcome']['id'];
                $byOutcome[$outcomeId] = ($byOutcome[$outcomeId] ?? 0) + 1;
            }
        }
        
        return $byOutcome;
    }
    
    public function getCallSuccessRate($startDate, $endDate, $successOutcomeIds)
    {
        $calls = Teamleader::calls()->betweenDates($startDate, $endDate);
        
        $completedCalls = array_filter($calls['data'], fn($c) => $c['completed']);
        $total = count($completedCalls);
        
        $successful = count(array_filter($completedCalls, function($call) use ($successOutcomeIds) {
            return isset($call['call_outcome']['id']) && 
                   in_array($call['call_outcome']['id'], $successOutcomeIds);
        }));
        
        return [
            'total_calls' => $total,
            'successful_calls' => $successful,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0
        ];
    }
}
```

### Sales Pipeline Integration

```php
class SalesCallManager
{
    public function scheduleFollowUpCall($companyId, $daysAhead = 7)
    {
        $scheduledTime = now()->addDays($daysAhead)->setTime(10, 0);
        
        return Teamleader::calls()->create([
            'subject' => 'Follow-up Call',
            'description' => 'Check in on proposal and answer questions',
            'scheduled_at' => $scheduledTime->toIso8601String(),
            'duration' => 30,
            'relates_to' => [
                'type' => 'company',
                'id' => $companyId
            ]
        ]);
    }
    
    public function getCallsNeedingAttention()
    {
        // Get overdue calls (scheduled in past, not completed)
        $today = now()->format('Y-m-d');
        
        $calls = Teamleader::calls()->scheduledBefore($today);
        
        return array_filter($calls['data'], function($call) {
            return !$call['completed'];
        });
    }
    
    public function assignCallsToRep($companyId, $repId)
    {
        $companyCalls = Teamleader::calls()->forCompany($companyId);
        
        foreach ($companyCalls['data'] as $call) {
            if (!$call['completed']) {
                Teamleader::calls()->update($call['id'], [
                    'assigned_to' => $repId
                ]);
            }
        }
    }
}
```

### Call Reminder System

```php
class CallReminderSystem
{
    public function getTodayCalls($userId)
    {
        $today = now()->format('Y-m-d');
        
        $calls = Teamleader::calls()->list([
            'scheduled_after' => $today,
            'scheduled_before' => $today
        ]);
        
        // Filter by assigned user
        return array_filter($calls['data'], function($call) use ($userId) {
            return isset($call['assigned_to']['id']) && 
                   $call['assigned_to']['id'] === $userId &&
                   !$call['completed'];
        });
    }
    
    public function getUpcomingReminders($userId, $hoursAhead = 24)
    {
        $now = now();
        $future = now()->addHours($hoursAhead);
        
        $calls = Teamleader::calls()->betweenDates(
            $now->format('Y-m-d'),
            $future->format('Y-m-d')
        );
        
        // Filter and sort
        $upcoming = array_filter($calls['data'], function($call) use ($userId, $now, $future) {
            if ($call['completed']) return false;
            if (!isset($call['assigned_to']['id'])) return false;
            if ($call['assigned_to']['id'] !== $userId) return false;
            
            $scheduledTime = strtotime($call['scheduled_at']);
            return $scheduledTime >= $now->timestamp && 
                   $scheduledTime <= $future->timestamp;
        });
        
        usort($upcoming, function($a, $b) {
            return strtotime($a['scheduled_at']) - strtotime($b['scheduled_at']);
        });
        
        return $upcoming;
    }
}
```

## Best Practices

### 1. Always Link Calls to Companies

Calls must be related to a company:

```php
// Required
$call = Teamleader::calls()->create([
    'subject' => 'Sales Call',
    'relates_to' => [ // Required
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

### 2. Use ISO 8601 Format for Scheduled Times

Always use proper datetime format with timezone:

```php
// Good
$call = Teamleader::calls()->create([
    'scheduled_at' => '2025-02-20T14:00:00+00:00'
]);

// Using Carbon
$call = Teamleader::calls()->create([
    'scheduled_at' => now()->addDays(2)->toIso8601String()
]);
```

### 3. Record Call Outcomes

Always log outcomes for completed calls:

```php
// After call is completed
Teamleader::calls()->update('call-uuid', [
    'call_outcome_id' => 'outcome-uuid',
    'description' => 'Client interested. Sending proposal.'
]);
```

### 4. Include Duration for Scheduling

Specify call duration to help with time management:

```php
$call = Teamleader::calls()->create([
    'subject' => 'Client Meeting',
    'duration' => 30, // minutes
    // ... other fields
]);
```

### 5. Use Helper Methods for Readability

Prefer helper methods for cleaner code:

```php
// Good
$calls = Teamleader::calls()->forCompany('company-uuid');
$calls = Teamleader::calls()->betweenDates($start, $end);

// Less readable
$calls = Teamleader::calls()->list([
    'relates_to' => ['type' => 'company', 'id' => 'company-uuid']
]);
```

### 6. Assign Calls to Users

Always assign calls to specific users for accountability:

```php
$call = Teamleader::calls()->create([
    // ... other fields
    'assigned_to' => 'user-uuid' // Clear ownership
]);
```

### 7. Add Descriptive Notes

Include meaningful descriptions to provide context:

```php
$call = Teamleader::calls()->create([
    'subject' => 'Q1 Review Call',
    'description' => 'Discuss Q1 performance, address concerns, ' .
                     'present Q2 strategy and renewal options'
]);
```

## Error Handling

### Common Errors and Solutions

**Missing Related Company:**
```php
try {
    $call = Teamleader::calls()->create([
        'subject' => 'Call',
        // Missing relates_to
    ]);
} catch (\Exception $e) {
    // Handle: "relates_to is required"
}
```

**Invalid Date Format:**
```php
try {
    $call = Teamleader::calls()->create([
        'scheduled_at' => '2025-02-20 14:00:00' // Wrong format
    ]);
} catch (\Exception $e) {
    // Handle: "Invalid datetime format. Use ISO 8601"
}
```

**Call Not Found:**
```php
try {
    $call = Teamleader::calls()->info('non-existent-uuid');
} catch (\Exception $e) {
    // Handle: Call not found error
}
```

### Robust Error Handling Example

```php
class CallManager
{
    public function createCallSafely(array $data)
    {
        try {
            // Validate required fields
            $this->validateCallData($data);
            
            // Create call
            $call = Teamleader::calls()->create($data);
            
            return [
                'success' => true,
                'call' => $call['data']
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => 'Validation error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create call', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to create call. Please try again.'
            ];
        }
    }
    
    private function validateCallData(array $data)
    {
        if (empty($data['subject'])) {
            throw new \InvalidArgumentException('Call subject is required');
        }
        
        if (empty($data['relates_to'])) {
            throw new \InvalidArgumentException('Company relation is required');
        }
        
        if (isset($data['scheduled_at'])) {
            // Validate datetime format
            $timestamp = strtotime($data['scheduled_at']);
            if ($timestamp === false) {
                throw new \InvalidArgumentException('Invalid scheduled_at format');
            }
        }
    }
}
```

## Related Resources

- [Call Outcomes](call-outcomes.md) - Define call results
- [Events](events.md) - General calendar events
- [Companies](../crm/companies.md) - Related companies
- [Contacts](../crm/contacts.md) - Contact information
- [Users](../users/users.md) - Assigned users
- [Activity Types](activity-types.md) - Activity categorization

## Rate Limiting

All call operations consume 1 API credit per request:

- `list()`: 1 credit
- `info()`: 1 credit
- `create()`: 1 credit
- `update()`: 1 credit

Monitor your API usage to stay within rate limits.
