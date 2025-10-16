# Notes

Manage notes in Teamleader Focus.

## Overview

The Notes resource allows you to create, update, and list notes attached to various resources in Teamleader. Notes can be added to contacts, companies, deals, meetings, and more. This is useful for documenting interactions, decisions, and important information.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [create()](#create)
    - [update()](#update)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Available Subject Types](#available-subject-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`notes`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported (by subject only)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get notes for a specific subject (resource).

**Parameters:**
- `filters` (array): Must include subject filter
- `options` (array): Pagination options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get notes for a company
$notes = Teamleader::notes()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// With pagination
$notes = Teamleader::notes()->list(
    ['subject' => ['type' => 'contact', 'id' => 'contact-uuid']],
    ['page_size' => 50, 'page_number' => 1]
);
```

### `create()`

Create a new note.

**Parameters:**
- `data` (array): Note data including subject, content, and optional notifications

**Example:**
```php
// Create a note for a company
$note = Teamleader::notes()->create([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'content' => 'Had a great meeting today about the new contract.',
]);

// Create a note with user notifications
$note = Teamleader::notes()->create([
    'subject' => [
        'type' => 'deal',
        'id' => 'deal-uuid'
    ],
    'content' => 'Deal is moving to the next phase.',
    'notify' => [
        ['type' => 'user', 'id' => 'user-uuid-1'],
        ['type' => 'user', 'id' => 'user-uuid-2']
    ]
]);
```

### `update()`

Update an existing note.

**Parameters:**
- `id` (string): Note UUID
- `data` (array): Updated note data

**Example:**
```php
// Update note content
$note = Teamleader::notes()->update('note-uuid', [
    'content' => 'Updated: Deal is now confirmed and moving forward.'
]);
```

## Helper Methods

The Notes resource provides convenient helper methods for working with specific resource types:

### List Notes for Specific Resources

```php
// Get notes for a company
$notes = Teamleader::notes()->forCompany('company-uuid');

// Get notes for a contact
$notes = Teamleader::notes()->forContact('contact-uuid');

// Get notes for a deal
$notes = Teamleader::notes()->forDeal('deal-uuid');

// Get notes for any subject type
$notes = Teamleader::notes()->forSubject('meeting', 'meeting-uuid');
```

### Create Notes for Specific Resources

```php
// Create note for a company
$note = Teamleader::notes()->createForCompany(
    'company-uuid',
    'Meeting scheduled for next week'
);

// Create note for a contact with notifications
$note = Teamleader::notes()->createForContact(
    'contact-uuid',
    'Follow-up required',
    [
        ['type' => 'user', 'id' => 'user-uuid']
    ]
);

// Create note for a deal
$note = Teamleader::notes()->createForDeal(
    'deal-uuid',
    'Contract signed!'
);

// Create note for any subject type
$note = Teamleader::notes()->createForSubject(
    'project',
    'project-uuid',
    'Project kickoff meeting completed'
);
```

## Filters

### Available Filters

#### `subject` (Required)
Notes must be filtered by a specific subject. The subject filter requires both a type and an ID.

```php
$notes = Teamleader::notes()->list([
    'subject' => [
        'type' => 'company',  // Subject type
        'id' => 'company-uuid' // Subject UUID
    ]
]);
```

## Available Subject Types

Notes can be attached to the following resource types:

- `contact` - Contact notes
- `company` - Company notes
- `deal` - Deal notes
- `project` - Project notes
- `milestone` - Milestone notes
- `ticket` - Ticket notes
- `invoice` - Invoice notes
- `meeting` - Meeting notes

Get the list programmatically:

```php
$subjectTypes = Teamleader::notes()->getAvailableSubjectTypes();
```

## Response Structure

### Note Object

```php
[
    'id' => 'note-uuid',
    'author' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ],
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'content' => 'Had a great meeting today about the new contract.',
    'created_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-15T10:30:00+00:00'
]
```

## Usage Examples

### Get All Notes for a Company

```php
$companyId = 'company-uuid';
$notes = Teamleader::notes()->forCompany($companyId);

foreach ($notes['data'] as $note) {
    echo "[" . $note['created_at'] . "] " . $note['content'] . PHP_EOL;
}
```

### Add a Note to a Contact

```php
$note = Teamleader::notes()->createForContact(
    'contact-uuid',
    'Customer requested a callback next week.'
);

if ($note) {
    echo "Note created successfully!";
}
```

### Create a Note with Team Notifications

```php
// Notify multiple team members about important information
$note = Teamleader::notes()->create([
    'subject' => [
        'type' => 'deal',
        'id' => 'deal-uuid'
    ],
    'content' => 'URGENT: Customer needs proposal by Friday!',
    'notify' => [
        ['type' => 'user', 'id' => 'sales-manager-uuid'],
        ['type' => 'user', 'id' => 'account-manager-uuid'],
        ['type' => 'user', 'id' => 'team-lead-uuid']
    ]
]);
```

### Update an Existing Note

```php
$noteId = 'note-uuid';

$updatedNote = Teamleader::notes()->update($noteId, [
    'content' => 'Updated information: Contract signed and payment terms agreed.'
]);
```

### Paginate Through All Notes

```php
$subjectType = 'company';
$subjectId = 'company-uuid';
$allNotes = [];
$page = 1;

do {
    $response = Teamleader::notes()->list(
        ['subject' => ['type' => $subjectType, 'id' => $subjectId]],
        ['page_size' => 100, 'page_number' => $page]
    );
    
    $allNotes = array_merge($allNotes, $response['data']);
    $hasMore = count($response['data']) === 100;
    $page++;
    
} while ($hasMore);
```

## Common Use Cases

### Activity Log for Resources

```php
class ActivityLogger
{
    public function logActivity($resourceType, $resourceId, $message)
    {
        return Teamleader::notes()->createForSubject(
            $resourceType,
            $resourceId,
            '[' . now()->toDateTimeString() . '] ' . $message
        );
    }
    
    public function getActivityLog($resourceType, $resourceId)
    {
        return Teamleader::notes()->forSubject($resourceType, $resourceId);
    }
}

// Usage
$logger = new ActivityLogger();
$logger->logActivity('deal', 'deal-uuid', 'Customer requested pricing update');
```

### Add Notes During Deal Updates

```php
class DealService
{
    public function updateDealStatus($dealId, $newStatus, $reason)
    {
        // Update the deal
        $deal = Teamleader::deals()->update($dealId, [
            'status' => $newStatus
        ]);
        
        // Add a note documenting the change
        Teamleader::notes()->createForDeal(
            $dealId,
            "Status changed to {$newStatus}. Reason: {$reason}"
        );
        
        return $deal;
    }
}
```

### Customer Interaction Tracking

```php
class CustomerInteractionTracker
{
    public function logInteraction($companyId, $interactionType, $details, $notify = [])
    {
        $content = "Interaction Type: {$interactionType}\n";
        $content .= "Details: {$details}\n";
        $content .= "Date: " . now()->toDateTimeString();
        
        return Teamleader::notes()->createForCompany(
            $companyId,
            $content,
            $notify
        );
    }
    
    public function getInteractionHistory($companyId)
    {
        return Teamleader::notes()->forCompany($companyId);
    }
}

// Usage
$tracker = new CustomerInteractionTracker();
$tracker->logInteraction(
    'company-uuid',
    'Phone Call',
    'Discussed renewal options for annual contract',
    [['type' => 'user', 'id' => 'account-manager-uuid']]
);
```

### Meeting Follow-up System

```php
class MeetingFollowUp
{
    public function addMeetingNotes($meetingId, $notes, $actionItems = [])
    {
        $content = "Meeting Notes:\n{$notes}\n\n";
        
        if (!empty($actionItems)) {
            $content .= "Action Items:\n";
            foreach ($actionItems as $item) {
                $content .= "- {$item}\n";
            }
        }
        
        return Teamleader::notes()->createForSubject(
            'meeting',
            $meetingId,
            $content
        );
    }
}

// Usage
$followUp = new MeetingFollowUp();
$followUp->addMeetingNotes(
    'meeting-uuid',
    'Discussed Q1 objectives and budget allocation',
    [
        'Send proposal by Friday',
        'Schedule follow-up for next week',
        'Prepare cost breakdown'
    ]
);
```

### Automated Note Generation

```php
class AutomatedNoteGenerator
{
    public function generateDealNote($dealId, $eventType, $details)
    {
        $templates = [
            'created' => "Deal created: {$details['title']}",
            'updated' => "Deal updated: {$details['changes']}",
            'phase_changed' => "Deal moved to phase: {$details['new_phase']}",
            'won' => "Deal won! Value: {$details['value']}"
        ];
        
        $content = $templates[$eventType] ?? "Deal event: {$eventType}";
        
        return Teamleader::notes()->createForDeal($dealId, $content);
    }
}
```

### Timeline View

```php
class TimelineController extends Controller
{
    public function show($resourceType, $resourceId)
    {
        $notes = Teamleader::notes()->forSubject($resourceType, $resourceId);
        
        // Sort by date (newest first)
        $timeline = collect($notes['data'])
            ->sortByDesc('created_at')
            ->map(function($note) {
                $author = Teamleader::users()->info($note['author']['id']);
                return [
                    'id' => $note['id'],
                    'content' => $note['content'],
                    'author' => $author['data']['first_name'] . ' ' . $author['data']['last_name'],
                    'date' => $note['created_at']
                ];
            });
        
        return view('timeline.show', [
            'timeline' => $timeline
        ]);
    }
}
```

## Best Practices

### 1. Include Context in Note Content

```php
// Good: Clear context
$note = Teamleader::notes()->createForDeal(
    $dealId,
    "Phone call with John Smith: Discussed pricing for Enterprise plan. Customer requested 15% discount. Follow-up scheduled for Monday."
);

// Bad: Vague note
$note = Teamleader::notes()->createForDeal($dealId, "Had a call");
```

### 2. Use Notifications Wisely

```php
// Good: Notify relevant people
$note = Teamleader::notes()->create([
    'subject' => ['type' => 'deal', 'id' => $dealId],
    'content' => 'Customer requested urgent meeting',
    'notify' => [
        ['type' => 'user', 'id' => $accountManagerId],
        ['type' => 'user', 'id' => $teamLeadId']
    ]
]);

// Bad: Spamming everyone
// Don't notify people unnecessarily
```

### 3. Structure Multi-Line Notes

```php
// Good: Well-structured
$content = "Meeting Summary:\n\n";
$content .= "Attendees: John, Sarah, Mike\n";
$content .= "Topics Discussed:\n";
$content .= "- Budget approval\n";
$content .= "- Timeline adjustments\n";
$content .= "- Resource allocation\n\n";
$content .= "Next Steps:\n";
$content .= "- Send updated proposal\n";
$content .= "- Schedule follow-up\n";

$note = Teamleader::notes()->createForCompany($companyId, $content);
```

### 4. Handle Note Updates Carefully

```php
// Good: Preserve history by appending
$existingNote = Teamleader::notes()->info($noteId);
$updatedContent = $existingNote['data']['content'] . "\n\n---UPDATE---\n" . $newInfo;

Teamleader::notes()->update($noteId, ['content' => $updatedContent]);

// Consider: Creating a new note instead of updating
// to maintain a clear activity trail
```

### 5. Validate Subject Types

```php
// Good: Validate before creating
$validTypes = Teamleader::notes()->getAvailableSubjectTypes();

if (!in_array($subjectType, $validTypes)) {
    throw new \InvalidArgumentException("Invalid subject type: {$subjectType}");
}

$note = Teamleader::notes()->createForSubject($subjectType, $subjectId, $content);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $note = Teamleader::notes()->createForCompany(
        $companyId,
        $noteContent
    );
} catch (TeamleaderException $e) {
    Log::error('Failed to create note', [
        'company_id' => $companyId,
        'error' => $e->getMessage()
    ]);
    
    return response()->json([
        'error' => 'Failed to create note: ' . $e->getMessage()
    ], 500);
}
```

## Limitations

1. **No Delete Method**: Notes cannot be deleted via the API
2. **Subject Required**: You must specify a subject when listing notes
3. **No Individual Info**: Cannot fetch a single note by ID without knowing its subject

```php
// To get a specific note, you need to list all notes for a subject
// and filter by note ID
$allNotes = Teamleader::notes()->forCompany($companyId);
$specificNote = collect($allNotes['data'])->firstWhere('id', $noteId);
```

## Related Resources

- [Companies](../crm/companies.md) - Add notes to companies
- [Contacts](../crm/contacts.md) - Add notes to contacts
- [Deals](../deals/deals.md) - Add notes to deals
- [Projects](../projects/projects.md) - Add notes to projects
- [Users](users.md) - Note authors are users

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
