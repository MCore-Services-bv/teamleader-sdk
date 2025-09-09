# Email Tracking

Manage email tracking in Teamleader Focus. Track emails sent to various entities including contacts, companies, deals, and other business objects.

## Endpoint

`emailTracking`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of email tracking entries with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination options

**Example:**
```php
$emails = $teamleader->emailTracking()->list(['subject' => ['type' => 'contact', 'id' => 'contact-uuid']]);
```

### `create()`

Create a new email tracking entry.

**Parameters:**
- `data` (array): Email tracking data including subject, title, content, and optional attachments

**Example:**
```php
$email = $teamleader->emailTracking()->create([
    'subject' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ],
    'title' => 'Email Subject',
    'content' => '<p>Email content</p>',
    'attachments' => ['file-uuid-1', 'file-uuid-2']
]);
```

### `forSubject()`

Get email tracking entries for a specific subject.

**Parameters:**
- `subjectType` (string): Type of subject entity
- `subjectId` (string): UUID of the subject entity
- `options` (array): Pagination options

**Example:**
```php
$contactEmails = $teamleader->emailTracking()->forSubject('contact', 'contact-uuid');
```

### `createForContact()`

Convenience method to create email tracking for a contact.

**Parameters:**
- `contactId` (string): Contact UUID
- `title` (string): Email subject
- `content` (string): Email content
- `attachments` (array, optional): Array of attachment file UUIDs

**Example:**
```php
$email = $teamleader->emailTracking()->createForContact(
    'contact-uuid',
    'Meeting Follow-up',
    '<p>Thank you for the meeting today.</p>'
);
```

### `createForCompany()`

Convenience method to create email tracking for a company.

**Parameters:**
- `companyId` (string): Company UUID
- `title` (string): Email subject
- `content` (string): Email content
- `attachments` (array, optional): Array of attachment file UUIDs

**Example:**
```php
$email = $teamleader->emailTracking()->createForCompany(
    'company-uuid',
    'Proposal Update',
    '<p>Please find the updated proposal attached.</p>',
    ['proposal-file-uuid']
);
```

### `createForDeal()`

Convenience method to create email tracking for a deal.

**Parameters:**
- `dealId` (string): Deal UUID
- `title` (string): Email subject
- `content` (string): Email content
- `attachments` (array, optional): Array of attachment file UUIDs

**Example:**
```php
$email = $teamleader->emailTracking()->createForDeal(
    'deal-uuid',
    'Deal Progress Update',
    '<p>The deal is progressing well.</p>'
);
```

### `getAvailableSubjectTypes()`

Get the list of available subject types for email tracking.

**Example:**
```php
$types = $teamleader->emailTracking()->getAvailableSubjectTypes();
// Returns: ['contact', 'company', 'deal', 'invoice', 'creditNote', 'subscription', 'product', 'quotation', 'nextgenProject']
```

## Subject Types

Email tracking supports the following subject types:

- **`contact`**: Individual contacts/people
- **`company`**: Companies/organizations  
- **`deal`**: Sales deals and opportunities
- **`invoice`**: Invoices
- **`creditNote`**: Credit notes
- **`subscription`**: Subscription records
- **`product`**: Products
- **`quotation`**: Quotes and quotations
- **`nextgenProject`**: New generation projects

## Filtering

### Available Filters

- **`subject`**: Object with `type` and `id` properties to filter by specific entity
- **`subject_type`** + **`subject_id`**: Alternative way to specify subject (automatically combined into subject object)

### Filter Examples

```php
// Filter by specific contact
$contactEmails = $teamleader->emailTracking()->list([
    'subject' => [
        'type' => 'contact',
        'id' => '36386b05-936e-4cc0-9523-bd20d797ebf5'
    ]
]);

// Alternative syntax
$contactEmails = $teamleader->emailTracking()->list([
    'subject_type' => 'contact',
    'subject_id' => '36386b05-936e-4cc0-9523-bd20d797ebf5'
]);

// Filter by company
$companyEmails = $teamleader->emailTracking()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471'
    ]
]);
```

## Pagination

```php
// Get first page with 10 items
$emails = $teamleader->emailTracking()->forSubject('contact', 'contact-uuid', [
    'page_size' => 10,
    'page_number' => 1
]);

// Get second page
$emails = $teamleader->emailTracking()->forSubject('contact', 'contact-uuid', [
    'page_size' => 10,
    'page_number' => 2
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "a344c251-2494-0013-b433-ccee8e8435e5",
            "title": "Meeting Follow-up",
            "content": "<p>Thank you for the meeting today.</p>",
            "subject": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "contact"
            },
            "added_at": "2016-01-01T00:00:00+00:00",
            "attachments": []
        }
    ],
    "meta": {
        "page": {
            "size": 20,
            "number": 1
        },
        "matches": 1
    }
}
```

### Create Response

```json
{
    "data": {
        "id": "a344c251-2494-0013-b433-ccee8e8435e5"
    }
}
```

## Data Fields

### Email Tracking Fields

- **`id`**: Email tracking UUID
- **`title`**: Email subject line
- **`content`**: Email body content (supports HTML)
- **`subject`**: Object containing the entity this email is tracked against
  - **`id`**: Subject entity UUID
  - **`type`**: Subject entity type
- **`added_at`**: Timestamp when the email tracking was created (ISO 8601)
- **`attachments`**: Array of attachment objects (if any)

### Required Fields for Creation

- **`subject`**: Object with `type` and `id`
  - **`subject.type`**: Must be one of the supported subject types
  - **`subject.id`**: Valid UUID of the subject entity
- **`content`**: Email content (HTML supported)

### Optional Fields for Creation

- **`title`**: Email subject line
- **`attachments`**: Array of file UUIDs (files must have same subject as email)

## Usage Examples

### Basic Email Tracking Creation

```php
// Create email tracking for a contact
$email = $teamleader->emailTracking()->create([
    'subject' => [
        'type' => 'contact',
        'id' => '36386b05-936e-4cc0-9523-bd20d797ebf5'
    ],
    'title' => 'Follow-up Meeting',
    'content' => '<p>Hi John, thanks for the productive meeting today. Looking forward to our next steps.</p>'
]);
```

### Email with Attachments

```php
// Create email tracking with attachments
$email = $teamleader->emailTracking()->create([
    'subject' => [
        'type' => 'company',
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471'
    ],
    'title' => 'Proposal and Contract',
    'content' => '<p>Please find attached the proposal and contract for your review.</p>',
    'attachments' => [
        '4f4288b2-c21b-4dac-87f6-a97511309079',  // Proposal PDF
        '5a2b8c3d-4e5f-6789-a012-b3456789cdef'   // Contract PDF
    ]
]);
```

### List All Emails for a Subject

```php
// Get all emails for a specific contact
$contactEmails = $teamleader->emailTracking()->forSubject(
    'contact',
    '36386b05-936e-4cc0-9523-bd20d797ebf5'
);

foreach ($contactEmails['data'] as $email) {
    echo "Email: {$email['title']} - {$email['added_at']}\n";
}
```

### Convenience Methods

```php
// Using convenience methods for different entity types
$contactEmail = $teamleader->emailTracking()->createForContact(
    'contact-uuid',
    'Thank you!',
    '<p>Thank you for your business!</p>'
);

$companyEmail = $teamleader->emailTracking()->createForCompany(
    'company-uuid',
    'Monthly Update',
    '<p>Here is your monthly business update.</p>'
);

$dealEmail = $teamleader->emailTracking()->createForDeal(
    'deal-uuid',
    'Deal Status Update',
    '<p>Your deal is progressing well.</p>'
);
```

### Paginated Results

```php
// Get paginated email tracking results
$page = 1;
do {
    $result = $teamleader->emailTracking()->forSubject('company', 'company-uuid', [
        'page_size' => 20,
        'page_number' => $page
    ]);
    
    foreach ($result['data'] as $email) {
        // Process each email
        processEmail($email);
    }
    
    $page++;
    $hasMore = count($result['data']) === 20; // Full page means potentially more data
    
} while ($hasMore);
```

## Error Handling

The email tracking resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->emailTracking()->create($emailData);

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Email tracking error: {$errorMessage}", [
        'status_code' => $statusCode,
        'email_data' => $emailData
    ]);
}
```

## Validation

The SDK performs validation on email tracking data:

```php
try {
    $email = $teamleader->emailTracking()->create([
        'subject' => [
            'type' => 'invalid_type',  // Will throw InvalidArgumentException
            'id' => 'not-a-uuid'       // Will throw InvalidArgumentException
        ],
        // 'content' => '',           // Missing required field - will throw InvalidArgumentException
    ]);
} catch (InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage();
}
```

## Rate Limiting

Email tracking API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Create operations**: 1 request per call
- **Convenience methods**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Email tracking is **append-only** - you can create but not update or delete entries
- All file attachments must be uploaded to Teamleader first and have the same subject as the email
- HTML content is supported in the email body
- The `title` field is optional but recommended for better organization
- Subject entities must exist in Teamleader before creating email tracking
- Email tracking provides a audit trail of communications with various entities

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class EmailTrackingController extends Controller
{
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $validated = $request->validate([
            'subject_type' => 'required|in:contact,company,deal,invoice',
            'subject_id' => 'required|uuid',
            'title' => 'sometimes|string|max:255',
            'content' => 'required|string',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'uuid'
        ]);
        
        $email = $teamleader->emailTracking()->create([
            'subject' => [
                'type' => $validated['subject_type'],
                'id' => $validated['subject_id']
            ],
            'title' => $validated['title'] ?? '',
            'content' => $validated['content'],
            'attachments' => $validated['attachments'] ?? []
        ]);
        
        return response()->json($email);
    }
    
    public function index(Request $request, TeamleaderSDK $teamleader)
    {
        $emails = $teamleader->emailTracking()->forSubject(
            $request->get('subject_type'),
            $request->get('subject_id'),
            [
                'page_size' => $request->get('page_size', 20),
                'page_number' => $request->get('page_number', 1)
            ]
        );
        
        return view('email-tracking.index', compact('emails'));
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
