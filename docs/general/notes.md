# Notes

Manage notes in Teamleader Focus. Notes can be attached to various entities like companies, contacts, deals, projects, and more, providing a flexible way to add context and track communications.

## Endpoint

`notes`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of notes with filtering support.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination options

**Example:**
```php
$notes = $teamleader->notes()->list(['subject' => ['type' => 'contact', 'id' => 'contact-uuid']]);
```

### `create()`

Create a new note attached to a subject entity.

**Parameters:**
- `data` (array): Note data including subject, content, and optional notifications

**Example:**
```php
$note = $teamleader->notes()->create([
    'subject' => ['type' => 'contact', 'id' => 'contact-uuid'],
    'content' => 'Meeting notes from today'
]);
```

### `update()`

Update an existing note's content.

**Parameters:**
- `id` (string): Note UUID
- `data` (array): Data to update (typically just content)

**Example:**
```php
$note = $teamleader->notes()->update('note-uuid', [
    'content' => 'Updated note content'
]);
```

### Subject-Specific Methods

#### `forSubject()`

Get notes for a specific subject entity.

**Parameters:**
- `subjectType` (string): Type of subject entity
- `subjectId` (string): UUID of the subject entity
- `options` (array): Pagination options

**Example:**
```php
$notes = $teamleader->notes()->forSubject('company', 'company-uuid');
```

#### `createForSubject()`

Create a note for a specific subject entity.

**Parameters:**
- `subjectType` (string): Type of subject entity
- `subjectId` (string): UUID of the subject entity
- `content` (string): Note content
- `notify` (array): Optional array of users to notify

**Example:**
```php
$note = $teamleader->notes()->createForSubject(
    'deal', 
    'deal-uuid', 
    'Important client feedback',
    [['type' => 'user', 'id' => 'user-uuid']]
);
```

### Convenience Methods

The SDK provides convenience methods for common subject types:

#### Company Notes
```php
// Get notes for a company
$notes = $teamleader->notes()->forCompany('company-uuid');

// Create a note for a company
$note = $teamleader->notes()->createForCompany(
    'company-uuid', 
    'Quarterly review completed'
);
```

#### Contact Notes
```php
// Get notes for a contact
$notes = $teamleader->notes()->forContact('contact-uuid');

// Create a note for a contact
$note = $teamleader->notes()->createForContact(
    'contact-uuid', 
    'Follow-up call scheduled'
);
```

#### Deal Notes
```php
// Get notes for a deal
$notes = $teamleader->notes()->forDeal('deal-uuid');

// Create a note for a deal
$note = $teamleader->notes()->createForDeal(
    'deal-uuid', 
    'Client showed strong interest'
);
```

## Subject Types

Notes can be attached to the following entity types:

- **`company`** - Company records
- **`contact`** - Individual contacts
- **`deal`** - Sales deals/opportunities
- **`invoice`** - Invoices
- **`quotation`** - Quotations
- **`creditNote`** - Credit notes
- **`product`** - Products
- **`project`** - Legacy projects
- **`nextgenProject`** - New generation projects
- **`subscription`** - Subscriptions

## Filtering

### Available Filters

- **`subject`**: Filter notes by the subject entity they're attached to
    - `subject.type` (string): Type of subject entity
    - `subject.id` (string): UUID of the subject entity

### Filter Examples

```php
// Get all notes for a specific contact
$contactNotes = $teamleader->notes()->list([
    'subject' => [
        'type' => 'contact',
        'id' => '36386b05-936e-4cc0-9523-bd20d797ebf5'
    ]
]);

// Get all notes for a company
$companyNotes = $teamleader->notes()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471'
    ]
]);

// Get notes for a deal with pagination
$dealNotes = $teamleader->notes()->list([
    'subject' => [
        'type' => 'deal',
        'id' => 'deal-uuid-here'
    ]
], [
    'page_size' => 50,
    'page_number' => 1
]);
```

## Creating Notes

### Basic Note Creation

```php
$note = $teamleader->notes()->create([
    'subject' => [
        'type' => 'contact',
        'id' => '36386b05-936e-4cc0-9523-bd20d797ebf5'
    ],
    'content' => 'Had a great conversation about their upcoming project needs.'
]);
```

### Note Creation with Notifications

```php
$note = $teamleader->notes()->create([
    'subject' => [
        'type' => 'deal',
        'id' => 'deal-uuid-here'
    ],
    'content' => 'Client confirmed budget approval. Moving to next phase.',
    'notify' => [
        [
            'type' => 'user',
            'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
        ],
        [
            'type' => 'user', 
            'id' => 'another-user-uuid'
        ]
    ]
]);
```

### Using Convenience Methods

```php
// Simple note creation for a company
$note = $teamleader->notes()->createForCompany(
    'company-uuid',
    'Annual contract renewal discussion completed'
);

// Note creation with user notification
$note = $teamleader->notes()->createForContact(
    'contact-uuid',
    'Requested product demo for next week',
    [['type' => 'user', 'id' => 'sales-manager-uuid']]
);
```

## Updating Notes

```php
// Update note content
$updatedNote = $teamleader->notes()->update('note-uuid-here', [
    'content' => 'Updated: Client confirmed they want to proceed with the proposal.'
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "a344c251-2494-0013-b433-ccee8e8435e5",
            "content": "Had a productive meeting discussing their Q4 requirements.",
            "subject": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "company"
            },
            "added_at": "2016-01-01T00:00:00+00:00"
        }
    ]
}
```

### Create Response

```json
{
    "data": {
        "id": "a344c251-2494-0013-b433-ccee8e8435e5",
        "type": "note"
    }
}
```

### Update Response

Returns HTTP 204 No Content on successful update.

## Data Fields

### Note Fields

- **`id`**: Note UUID
- **`content`**: The note content/text
- **`subject`**: Object containing the subject entity details
    - `id`: UUID of the subject entity
    - `type`: Type of subject entity
- **`added_at`**: ISO 8601 timestamp when the note was created

### Create Request Fields

- **`subject`** (required): Object specifying what entity the note is attached to
    - `type` (required): Subject entity type
    - `id` (required): Subject entity UUID
- **`content`** (required): Note content/text
- **`notify`** (optional): Array of users to notify about the new note
    - Each notification object must have `type: "user"` and `id` (user UUID)

### Update Request Fields

- **`id`** (required): Note UUID
- **`content`** (optional): Updated note content

## Usage Examples

### Comprehensive Note Management

```php
// Get all notes for a company
$companyNotes = $teamleader->notes()->forCompany('company-uuid');

// Add a new note to the company
$newNote = $teamleader->notes()->createForCompany(
    'company-uuid',
    'Initial consultation completed. Client interested in our enterprise package.'
);

// Update the note with additional information
$updatedNote = $teamleader->notes()->update($newNote['data']['id'], [
    'content' => 'Initial consultation completed. Client interested in our enterprise package. Follow-up scheduled for next week.'
]);

// Get all notes for a deal with pagination
$dealNotes = $teamleader->notes()->forDeal('deal-uuid', [
    'page_size' => 25,
    'page_number' => 1
]);
```

### Working with Different Entity Types

```php
// Create notes for different entity types
$invoiceNote = $teamleader->notes()->createForSubject(
    'invoice',
    'invoice-uuid',
    'Payment terms extended by 30 days per client request'
);

$projectNote = $teamleader->notes()->createForSubject(
    'nextgenProject',
    'project-uuid',
    'Milestone 2 completed ahead of schedule'
);

$productNote = $teamleader->notes()->createForSubject(
    'product',
    'product-uuid',
    'Updated pricing structure based on market analysis'
);
```

### Team Collaboration with Notifications

```php
// Create a note and notify multiple team members
$importantNote = $teamleader->notes()->create([
    'subject' => [
        'type' => 'deal',
        'id' => 'high-value-deal-uuid'
    ],
    'content' => 'URGENT: Client needs proposal by tomorrow. All hands on deck.',
    'notify' => [
        ['type' => 'user', 'id' => 'sales-manager-uuid'],
        ['type' => 'user', 'id' => 'technical-lead-uuid'],
        ['type' => 'user', 'id' => 'project-manager-uuid']
    ]
]);
```

## Error Handling

```php
try {
    $note = $teamleader->notes()->create([
        'subject' => ['type' => 'contact', 'id' => 'contact-uuid'],
        'content' => 'Meeting notes'
    ]);
} catch (InvalidArgumentException $e) {
    // Handle validation errors (missing required fields, invalid subject type, etc.)
    Log::error('Note creation validation error: ' . $e->getMessage());
} catch (Exception $e) {
    // Handle API errors
    if (isset($result['error']) && $result['error']) {
        Log::error('Note creation API error: ' . $result['message']);
    }
}
```

## Rate Limiting

Notes API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call

## Validation

The SDK automatically validates:

- **Subject type**: Must be one of the supported entity types
- **Required fields**: Subject and content for creation
- **Notification format**: Proper structure for notify arrays
- **Empty content**: Content cannot be empty when provided

## Best Practices

1. **Use specific subject types**: Always specify the correct entity type when creating notes
2. **Meaningful content**: Write clear, actionable note content
3. **Strategic notifications**: Only notify relevant team members to avoid notification fatigue
4. **Batch operations**: When adding multiple notes, consider rate limiting
5. **Error handling**: Always handle both validation and API errors appropriately

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class NotesController extends Controller
{
    public function storeCompanyNote(Request $request, TeamleaderSDK $teamleader, string $companyId)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'notify_users' => 'sometimes|array',
            'notify_users.*' => 'uuid'
        ]);

        $notify = collect($request->input('notify_users', []))
            ->map(fn($userId) => ['type' => 'user', 'id' => $userId])
            ->toArray();

        $note = $teamleader->notes()->createForCompany(
            $companyId,
            $request->input('content'),
            $notify
        );

        return response()->json($note);
    }
}
```

## Notes vs Comments

Notes in Teamleader are:
- **Persistent**: Stored permanently with the entity
- **Searchable**: Can be found through the notes API
- **Notifiable**: Can trigger notifications to team members
- **Timestamped**: Include creation timestamps
- **Entity-specific**: Must be attached to a specific entity

For real-time communication, consider using other Teamleader features or integrating with chat systems.

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
