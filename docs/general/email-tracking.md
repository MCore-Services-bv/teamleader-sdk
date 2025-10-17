# Email Tracking

Manage email tracking in Teamleader Focus.

## Overview

The Email Tracking resource allows you to track emails sent to various entities in Teamleader (contacts, companies, deals, etc.). This helps maintain a complete communication history with your customers and prospects.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [create()](#create)
- [Helper Methods](#helper-methods)
- [Available Subject Types](#available-subject-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`emailTracking`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported (by subject)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get email tracking records for a specific subject.

**Parameters:**
- `filters` (array): Must include subject filter
    - `subject.id` (string): UUID of the subject entity
    - `subject.type` (string): Type of subject entity
- `options` (array): Pagination options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get emails for a contact
$emails = Teamleader::emailTracking()->list([
    'subject.id' => 'contact-uuid',
    'subject.type' => 'contact'
]);

// With pagination
$emails = Teamleader::emailTracking()->list(
    [
        'subject.id' => 'company-uuid',
        'subject.type' => 'company'
    ],
    [
        'page_size' => 50,
        'page_number' => 1
    ]
);
```

### `create()`

Create an email tracking record.

**Parameters:**
- `data` (array): Email data
    - `subject` (array, required): Subject entity
        - `type` (string): Subject type
        - `id` (string): Subject UUID
    - `title` (string, required): Email subject line
    - `content` (string, required): Email body content
    - `attachments` (array, optional): Array of file UUIDs

**Example:**
```php
// Create email tracking for a contact
$email = Teamleader::emailTracking()->create([
    'subject' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ],
    'title' => 'Follow-up on meeting',
    'content' => 'Thank you for meeting with us today...'
]);

// With attachments
$email = Teamleader::emailTracking()->create([
    'subject' => [
        'type' => 'deal',
        'id' => 'deal-uuid'
    ],
    'title' => 'Proposal document',
    'content' => 'Please find attached our proposal...',
    'attachments' => ['file-uuid-1', 'file-uuid-2']
]);
```

## Helper Methods

### Subject-Specific List Methods

```php
// Get emails for a contact
$emails = Teamleader::emailTracking()->forSubject('contact', 'contact-uuid');

// Get emails for a company
$emails = Teamleader::emailTracking()->forCompany('company-uuid');

// Get emails for a deal
$emails = Teamleader::emailTracking()->forDeal('deal-uuid');

// Get emails for an invoice
$emails = Teamleader::emailTracking()->forInvoice('invoice-uuid');
```

### Subject-Specific Create Methods

```php
// Create email for a contact
$email = Teamleader::emailTracking()->createForContact(
    'contact-uuid',
    'Email Subject',
    'Email content...'
);

// Create email for a company
$email = Teamleader::emailTracking()->createForCompany(
    'company-uuid',
    'Email Subject',
    'Email content...'
);

// Create email for a deal
$email = Teamleader::emailTracking()->createForDeal(
    'deal-uuid',
    'Email Subject',
    'Email content...'
);
```

## Available Subject Types

Email tracking can be attached to the following resource types:

- `contact` - Contact records
- `company` - Company records
- `deal` - Deal/opportunity records
- `invoice` - Invoice records
- `creditNote` - Credit note records
- `subscription` - Subscription records
- `product` - Product records
- `quotation` - Quotation records
- `nextgenProject` - Project records

Get the list programmatically:

```php
$types = Teamleader::emailTracking()->getAvailableSubjectTypes();
```

## Response Structure

### Email Tracking Object

```php
[
    'id' => 'email-tracking-uuid',
    'subject' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ],
    'title' => 'Follow-up email',
    'content' => 'Email body content...',
    'sent_at' => '2024-01-15T10:30:00+00:00',
    'attachments' => [
        [
            'type' => 'file',
            'id' => 'file-uuid'
        ]
    ]
]
```

## Usage Examples

### Track Sent Email

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// After sending an email, track it in Teamleader
$email = Teamleader::emailTracking()->createForContact(
    $contactId,
    $emailSubject,
    $emailBody
);
```

### Get Email History for Contact

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$contactId = 'contact-uuid';
$emailHistory = Teamleader::emailTracking()->forContact($contactId);

foreach ($emailHistory['data'] as $email) {
    echo "[{$email['sent_at']}] {$email['title']}\n";
}
```

### Track Email with Attachments

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Upload file first (using Files resource)
$file = Teamleader::files()->upload($filePath);

// Track email with attachment
$email = Teamleader::emailTracking()->create([
    'subject' => [
        'type' => 'deal',
        'id' => 'deal-uuid'
    ],
    'title' => 'Contract documents',
    'content' => 'Please review the attached contract.',
    'attachments' => [$file['data']['id']]
]);
```

### Paginate Through Email History

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$subjectId = 'company-uuid';
$allEmails = [];
$page = 1;

do {
    $response = Teamleader::emailTracking()->list(
        [
            'subject.id' => $subjectId,
            'subject.type' => 'company'
        ],
        [
            'page_size' => 100,
            'page_number' => $page
        ]
    );
    
    $allEmails = array_merge($allEmails, $response['data']);
    $hasMore = count($response['data']) === 100;
    $page++;
    
} while ($hasMore);
```

## Common Use Cases

### Email Activity Logger

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class EmailActivityLogger
{
    public function logSentEmail($subjectType, $subjectId, $emailData)
    {
        return Teamleader::emailTracking()->create([
            'subject' => [
                'type' => $subjectType,
                'id' => $subjectId
            ],
            'title' => $emailData['subject'],
            'content' => $emailData['body']
        ]);
    }
    
    public function getActivityLog($subjectType, $subjectId)
    {
        return Teamleader::emailTracking()->forSubject($subjectType, $subjectId);
    }
}
```

### CRM Integration

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class CRMEmailIntegration
{
    public function syncEmailToTeamleader($email, $recipientType, $recipientId)
    {
        // Extract email data
        $subject = $email->getSubject();
        $body = $email->getBody();
        
        // Track in Teamleader
        return Teamleader::emailTracking()->create([
            'subject' => [
                'type' => $recipientType,
                'id' => $recipientId
            ],
            'title' => $subject,
            'content' => $body
        ]);
    }
}
```

### Communication Timeline

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class CommunicationTimeline
{
    public function getTimeline($entityType, $entityId)
    {
        // Get email history
        $emails = Teamleader::emailTracking()->forSubject($entityType, $entityId);
        
        // Get notes
        $notes = Teamleader::notes()->forSubject($entityType, $entityId);
        
        // Combine and sort by date
        $timeline = array_merge(
            $this->formatEmails($emails['data']),
            $this->formatNotes($notes['data'])
        );
        
        usort($timeline, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $timeline;
    }
    
    private function formatEmails($emails)
    {
        return array_map(function($email) {
            return [
                'type' => 'email',
                'date' => $email['sent_at'],
                'title' => $email['title'],
                'content' => $email['content']
            ];
        }, $emails);
    }
    
    private function formatNotes($notes)
    {
        return array_map(function($note) {
            return [
                'type' => 'note',
                'date' => $note['created_at'],
                'title' => 'Note',
                'content' => $note['content']
            ];
        }, $notes);
    }
}
```

### Email Campaign Tracking

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class EmailCampaignTracker
{
    public function trackCampaignEmail($campaignName, $recipients, $subject, $body)
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            try {
                $email = Teamleader::emailTracking()->create([
                    'subject' => [
                        'type' => $recipient['type'],
                        'id' => $recipient['id']
                    ],
                    'title' => "[{$campaignName}] {$subject}",
                    'content' => $body
                ]);
                
                $results[] = [
                    'success' => true,
                    'recipient' => $recipient['id'],
                    'email_id' => $email['data']['id']
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'recipient' => $recipient['id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
```

### Automated Follow-up Tracker

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class FollowUpTracker
{
    public function trackFollowUp($dealId, $followUpNumber, $emailSubject, $emailBody)
    {
        return Teamleader::emailTracking()->createForDeal(
            $dealId,
            "Follow-up #{$followUpNumber}: {$emailSubject}",
            $emailBody
        );
    }
    
    public function getFollowUpCount($dealId)
    {
        $emails = Teamleader::emailTracking()->forDeal($dealId);
        
        $followUps = array_filter($emails['data'], function($email) {
            return stripos($email['title'], 'follow-up') !== false;
        });
        
        return count($followUps);
    }
}
```

## Best Practices

### 1. Always Specify Subject

```php
// Good: Clear subject specified
$email = Teamleader::emailTracking()->create([
    'subject' => ['type' => 'contact', 'id' => $contactId],
    'title' => 'Meeting follow-up',
    'content' => $body
]);

// Bad: Missing subject
$email = Teamleader::emailTracking()->create([
    'title' => 'Meeting follow-up',
    'content' => $body
]);
```

### 2. Use Descriptive Titles

```php
// Good: Descriptive title
'title' => 'Follow-up: Q1 Budget Discussion - Action Items'

// Bad: Vague title
'title' => 'Follow-up'
```

### 3. Track All Customer Communications

```php
// Good: Track all outbound emails
class EmailService
{
    public function sendEmail($to, $subject, $body)
    {
        // Send email via mail service
        $this->mailService->send($to, $subject, $body);
        
        // Track in Teamleader
        $recipient = $this->findRecipientInTeamleader($to);
        if ($recipient) {
            Teamleader::emailTracking()->create([
                'subject' => [
                    'type' => $recipient['type'],
                    'id' => $recipient['id']
                ],
                'title' => $subject,
                'content' => $body
            ]);
        }
    }
}
```

### 4. Include Relevant Context

```php
// Good: Full context in content
$content = "Hi {$name},\n\n";
$content .= "Following up on our meeting yesterday...\n\n";
$content .= "Action items:\n";
$content .= "- Item 1\n- Item 2\n\n";
$content .= "Best regards";

// Bad: Minimal context
$content = "Follow up";
```

### 5. Handle Attachments Properly

```php
// Good: Upload files first, then reference
$attachmentIds = [];
foreach ($files as $file) {
    $uploaded = Teamleader::files()->upload($file);
    $attachmentIds[] = $uploaded['data']['id'];
}

$email = Teamleader::emailTracking()->create([
    'subject' => ['type' => 'deal', 'id' => $dealId],
    'title' => $subject,
    'content' => $body,
    'attachments' => $attachmentIds
]);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $email = Teamleader::emailTracking()->createForContact(
        $contactId,
        $subject,
        $body
    );
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Invalid email tracking data', [
            'contact_id' => $contactId,
            'error' => $e->getMessage()
        ]);
    } else {
        Log::error('Failed to create email tracking', [
            'error' => $e->getMessage()
        ]);
    }
}
```

## Subject Type Validation

Always validate subject types before creating email tracking:

```php
$validTypes = Teamleader::emailTracking()->getAvailableSubjectTypes();

if (!in_array($subjectType, $validTypes)) {
    throw new \InvalidArgumentException("Invalid subject type: {$subjectType}");
}
```

## Limitations

1. **No Update**: Email tracking records cannot be updated after creation
2. **No Delete**: Email tracking records cannot be deleted
3. **Subject Required**: All emails must be linked to a subject entity
4. **No Individual Info**: Cannot fetch a single email by ID without knowing its subject

## Related Resources

- [Contacts](../crm/contacts.md) - Track emails sent to contacts
- [Companies](../crm/companies.md) - Track emails sent to companies
- [Deals](../deals/deals.md) - Track emails sent to deals
- [Notes](notes.md) - Related communication tracking
- [Files](../files/files.md) - Upload files for email attachments

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
