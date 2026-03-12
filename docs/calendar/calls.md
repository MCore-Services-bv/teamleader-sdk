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
    - [complete()](#complete)
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
- `participant` (object): The participant in the call
    - `customer` (object): Related customer entity
        - `type` (string): `'company'`
        - `id` (string): Company UUID
- `due_at` (string): Scheduled datetime in ISO 8601 format
- `assignee` (object): The user responsible for the call
    - `type` (string): `'user'`
    - `id` (string): User UUID

**Optional fields:**
- `description` (string): Call description/notes
- `call_outcome_id` (string): Call outcome UUID (for completed calls)

**Example:**
```php
$call = Teamleader::calls()->create([
    'participant' => [
        'customer' => [
            'type' => 'company',
            'id'   => 'company-uuid'
        ]
    ],
    'due_at'      => '2025-02-20T14:00:00+00:00',
    'assignee'    => [
        'type' => 'user',
        'id'   => 'user-uuid'
    ],
    'description' => 'Follow-up on proposal — discuss pricing and timeline'
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
    'description'     => 'Updated notes',
    'call_outcome_id' => 'outcome-uuid'
]);
```

### `complete()`

Mark a call as complete, optionally recording an outcome.

**Parameters:**
- `id` (string): The call UUID
- `outcomeId` (string|null): Optional call outcome UUID
- `outcomeSummary` (string|null): Optional summary of the call outcome

**Example:**
```php
// Mark as complete
Teamleader::calls()->complete('call-uuid');

// Mark as complete with outcome
Teamleader::calls()->complete(
    'call-uuid',
    'outcome-uuid',
    'Client confirmed interest — sending proposal next week'
);
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

### `upcoming()`

Get calls scheduled after today.

```php
$upcomingCalls = Teamleader::calls()->upcoming();
```

### `overdue()`

Get calls scheduled before today (not yet completed).

```php
$overdueCalls = Teamleader::calls()->overdue();
```

### `today()`

Get calls scheduled for today.

```php
$todayCalls = Teamleader::calls()->today();
```

### `thisWeek()`

Get calls scheduled for the current week.

```php
$weekCalls = Teamleader::calls()->thisWeek();
```

### `schedule()`

Schedule a new call (alias for `create()`).

```php
$call = Teamleader::calls()->schedule([
    'participant' => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid']
    ],
    'due_at'   => '2025-02-20T14:00:00+00:00',
    'assignee' => ['type' => 'user', 'id' => 'user-uuid']
]);
```

### `reschedule()`

Update only the `due_at` field of an existing call.

```php
Teamleader::calls()->reschedule('call-uuid', '2025-02-25T10:00:00+00:00');
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
    'id'          => 'call-uuid',
    'description' => 'Follow-up on proposal',
    'due_at'      => '2025-02-20T14:00:00+00:00',
    'completed'   => false,
    'participant' => [
        'customer' => [
            'type' => 'company',
            'id'   => 'company-uuid'
        ]
    ],
    'assignee' => [
        'type' => 'user',
        'id'   => 'user-uuid'
    ],
    'call_outcome'  => null, // or ['id' => 'outcome-uuid', 'name' => 'Successful']
    'created_at'    => '2025-01-15T10:30:00+00:00',
    'updated_at'    => '2025-02-20T14:35:00+00:00'
]
```

## Usage Examples

### Schedule a Sales Call

```php
$call = Teamleader::calls()->create([
    'participant' => [
        'customer' => [
            'type' => 'company',
            'id'   => 'prospect-company-uuid'
        ]
    ],
    'due_at'      => '2025-02-22T10:00:00+00:00',
    'assignee'    => [
        'type' => 'user',
        'id'   => 'sales-rep-uuid'
    ],
    'description' => 'Introduce our services and understand client needs'
]);

echo "Call scheduled: {$call['data']['id']}";
```

### Log a Completed Call

```php
// Create call and mark complete with outcome
$call = Teamleader::calls()->create([
    'participant' => [
        'customer' => [
            'type' => 'company',
            'id'   => 'client-company-uuid'
        ]
    ],
    'due_at'      => now()->toIso8601String(),
    'assignee'    => ['type' => 'user', 'id' => 'user-uuid'],
    'description' => 'Discussed contract terms and next steps'
]);

Teamleader::calls()->complete(
    $call['data']['id'],
    'successful-outcome-uuid',
    'Client agreed to proceed. Contract sent.'
);

echo "Call logged with outcome";
```

### Get Company Call History

```php
$companyId = 'company-uuid';

$callHistory = Teamleader::calls()->forCompany($companyId);

echo "Total calls: " . count($callHistory['data']) . "\n";

foreach ($callHistory['data'] as $call) {
    $status = $call['completed'] ? 'Completed' : 'Scheduled';
    echo "- {$call['description']} ({$status}) - {$call['due_at']}\n";
}
```

### Update Call with Outcome

```php
// After completing the call — use complete() to record outcome
Teamleader::calls()->complete(
    'call-uuid',
    'positive-outcome-uuid',
    'Client is interested. Sending proposal next week.'
);

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
            'participant' => [
                'customer' => ['type' => 'company', 'id' => $companyId]
            ],
            'due_at'      => $details['due_at'],
            'assignee'    => ['type' => 'user', 'id' => $userId],
            'description' => $details['description'] ?? ''
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
        return array_filter($calls['data'], function ($call) use ($userId) {
            return isset($call['assignee']['id']) &&
                   $call['assignee']['id'] === $userId;
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

        $totalCalls     = count($calls['data']);
        $completedCalls = count(array_filter(
            $calls['data'],
            fn($c) => $c['completed']
        ));

        return [
            'total_calls'     => $totalCalls,
            'completed_calls' => $completedCalls,
            'pending_calls'   => $totalCalls - $completedCalls,
        ];
    }

    public function getCallsByOutcome($startDate, $endDate)
    {
        $calls    = Teamleader::calls()->betweenDates($startDate, $endDate);
        $byOutcome = [];

        foreach ($calls['data'] as $call) {
            if (isset($call['call_outcome']['id'])) {
                $outcomeId             = $call['call_outcome']['id'];
                $byOutcome[$outcomeId] = ($byOutcome[$outcomeId] ?? 0) + 1;
            }
        }

        return $byOutcome;
    }

    public function getCallSuccessRate($startDate, $endDate, $successOutcomeIds)
    {
        $calls          = Teamleader::calls()->betweenDates($startDate, $endDate);
        $completedCalls = array_filter($calls['data'], fn($c) => $c['completed']);
        $total          = count($completedCalls);

        $successful = count(array_filter($completedCalls, function ($call) use ($successOutcomeIds) {
            return isset($call['call_outcome']['id']) &&
                   in_array($call['call_outcome']['id'], $successOutcomeIds);
        }));

        return [
            'total_calls'     => $total,
            'successful_calls' => $successful,
            'success_rate'    => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
        ];
    }
}
```

### Sales Pipeline Integration

```php
class SalesCallManager
{
    public function scheduleFollowUpCall($companyId, $userId, $daysAhead = 7)
    {
        $scheduledTime = now()->addDays($daysAhead)->setTime(10, 0);

        return Teamleader::calls()->create([
            'participant' => [
                'customer' => ['type' => 'company', 'id' => $companyId]
            ],
            'due_at'      => $scheduledTime->toIso8601String(),
            'assignee'    => ['type' => 'user', 'id' => $userId],
            'description' => 'Check in on proposal and answer questions'
        ]);
    }

    public function getCallsNeedingAttention()
    {
        return Teamleader::calls()->overdue()['data'];
    }
    
    public function assignCallsToRep($companyId, $repId)
    {
        $companyCalls = Teamleader::calls()->forCompany($companyId);
        
        foreach ($companyCalls['data'] as $call) {
            if (!$call['completed']) {
                Teamleader::calls()->update($call['id'], [
                    'assignee' => ['type' => 'user', 'id' => $repId]
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
        $calls = Teamleader::calls()->today();

        // Filter by assigned user
        return array_filter($calls['data'], function ($call) use ($userId) {
            return isset($call['assignee']['id']) &&
                   $call['assignee']['id'] === $userId &&
                   ! $call['completed'];
        });
    }

    public function getUpcomingReminders($userId, $hoursAhead = 24)
    {
        $now    = now();
        $future = now()->addHours($hoursAhead);

        $calls = Teamleader::calls()->betweenDates(
            $now->format('Y-m-d'),
            $future->format('Y-m-d')
        );

        $upcoming = array_filter($calls['data'], function ($call) use ($userId, $now, $future) {
            if ($call['completed']) {
                return false;
            }
            if (! isset($call['assignee']['id'])) {
                return false;
            }
            if ($call['assignee']['id'] !== $userId) {
                return false;
            }

            $dueAt = strtotime($call['due_at']);
            return $dueAt >= $now->timestamp && $dueAt <= $future->timestamp;
        });

        usort($upcoming, fn($a, $b) => strtotime($a['due_at']) - strtotime($b['due_at']));

        return $upcoming;
    }
}
```

## Best Practices

### 1. Always Link Calls to Companies

Calls require a participant with a customer reference:

```php
$call = Teamleader::calls()->create([
    'participant' => [
        'customer' => [
            'type' => 'company',
            'id'   => 'company-uuid' // Required
        ]
    ],
    'due_at'   => now()->addDays(2)->toIso8601String(),
    'assignee' => ['type' => 'user', 'id' => 'user-uuid']
]);
```

### 2. Use ISO 8601 Format for due_at

Always use proper datetime format with timezone:

```php
// Good
'due_at' => '2025-02-20T14:00:00+00:00'

// Using Carbon
'due_at' => now()->addDays(2)->toIso8601String()
```

### 3. Record Call Outcomes Using complete()

Always use `complete()` to mark calls done and record outcomes:

```php
Teamleader::calls()->complete(
    'call-uuid',
    'outcome-uuid',
    'Client interested. Sending proposal.'
);
```

### 4. Use Helper Methods for Common Queries

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

### 5. Use Helper Methods for Common Queries

```php
// Prefer
$calls = Teamleader::calls()->forCompany('company-uuid');
$calls = Teamleader::calls()->betweenDates($start, $end);
$calls = Teamleader::calls()->overdue();
$calls = Teamleader::calls()->today();
```

### 6. Assign Calls to Users

Always assign calls to specific users for accountability:

```php
$call = Teamleader::calls()->create([
    // ... other fields
    'assignee' => ['type' => 'user', 'id' => 'user-uuid'] // Clear ownership
]);
```

### 7. Add Descriptive Notes

Include meaningful descriptions to provide context:

```php
$call = Teamleader::calls()->create([
    // ... required fields
    'description' => 'Discuss Q1 performance, address concerns, ' .
                     'present Q2 strategy and renewal options'
]);
```

## Error Handling

### Common Errors and Solutions

**Missing Required Field:**
```php
try {
    $call = Teamleader::calls()->create([
        'participant' => ['customer' => ['type' => 'company', 'id' => 'uuid']],
        'assignee'    => ['type' => 'user', 'id' => 'user-uuid']
        // Missing: due_at
    ]);
} catch (\InvalidArgumentException $e) {
    // Handle: "Field 'due_at' is required for creating a call"
}
```

**Invalid Participant Structure:**
```php
try {
    $call = Teamleader::calls()->create([
        'participant' => [], // Missing customer
        'due_at'      => '2025-02-20T14:00:00+00:00',
        'assignee'    => ['type' => 'user', 'id' => 'uuid']
    ]);
} catch (\InvalidArgumentException $e) {
    // Handle: "Participant must have a customer object"
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
        if (empty($data['participant']['customer'])) {
            throw new \InvalidArgumentException('participant.customer is required');
        }

        if (empty($data['due_at'])) {
            throw new \InvalidArgumentException('due_at is required');
        }

        if (empty($data['assignee'])) {
            throw new \InvalidArgumentException('assignee is required');
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
