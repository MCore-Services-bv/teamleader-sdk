# Files Resource

The Files resource provides methods to manage files in Teamleader Focus. Files can be attached to various entities like companies, contacts, deals, invoices, projects, and tickets.

## Table of Contents

- [Basic Usage](#basic-usage)
- [List Files](#list-files)
- [File Information](#file-information)
- [Upload Files](#upload-files)
- [Download Files](#download-files)
- [Delete Files](#delete-files)
- [Helper Methods](#helper-methods)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)

## Basic Usage

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

$teamleader = new TeamleaderSDK($accessToken);
$files = $teamleader->files();
```

## List Files

### Get all files for a subject

```php
// Get files for a company
$files = $teamleader->files()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Get files for a contact
$files = $teamleader->files()->list([
    'subject' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ]
]);

// Get files for a deal
$files = $teamleader->files()->list([
    'subject' => [
        'type' => 'deal',
        'id' => 'deal-uuid'
    ]
]);
```

### With pagination and sorting

```php
$files = $teamleader->files()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
], [
    'page_size' => 50,
    'page_number' => 1,
    'sort' => [
        [
            'field' => 'updated_at',
            'order' => 'desc'
        ]
    ]
]);
```

## File Information

Get detailed information about a specific file:

```php
$file = $teamleader->files()->info('file-uuid');

// Access file properties
$fileName = $file['data']['name'];
$mimeType = $file['data']['mime_type'];
$fileSize = $file['data']['size'];
$uploadedBy = $file['data']['uploaded_by'];
$folder = $file['data']['folder'];
```

## Upload Files

Upload a file to a subject (two-step process):

### Step 1: Request upload link

```php
// Request upload link
$uploadResponse = $teamleader->files()->upload(
    'document.pdf',           // File name with extension
    'company',                // Subject type
    'company-uuid',           // Subject ID
    'Documents'               // Optional folder name
);

$uploadUrl = $uploadResponse['data']['location'];
$expiresAt = $uploadResponse['data']['expires_at'];
```

### Step 2: Upload file to the provided URL

```php
// Upload file contents to the temporary URL
$fileContents = file_get_contents('/path/to/document.pdf');

$ch = curl_init($uploadUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/pdf'
]);

$response = curl_exec($ch);
curl_close($ch);
```

### Valid subject types

- `company`
- `contact`
- `deal`
- `invoice`
- `creditNote`
- `nextgenProject`
- `ticket`

### Complete upload example

```php
// Step 1: Get upload URL
$uploadResponse = $teamleader->files()->upload(
    'meeting-notes.docx',
    'deal',
    'deal-uuid',
    'Meeting Notes'
);

if (!isset($uploadResponse['error'])) {
    $uploadUrl = $uploadResponse['data']['location'];
    
    // Step 2: Upload the actual file
    $fileContents = file_get_contents('/path/to/meeting-notes.docx');
    
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "File uploaded successfully!";
    }
}
```

## Download Files

Get a temporary download link for a file:

```php
$downloadResponse = $teamleader->files()->download('file-uuid');

if (!isset($downloadResponse['error'])) {
    $downloadUrl = $downloadResponse['data']['location'];
    $expiresAt = $downloadResponse['data']['expires_at'];
    
    // Redirect user to download URL or download programmatically
    header('Location: ' . $downloadUrl);
}
```

## Delete Files

Delete a file permanently:

```php
$result = $teamleader->files()->delete('file-uuid');

if (!isset($result['error'])) {
    echo "File deleted successfully!";
}
```

## Helper Methods

Convenient methods for accessing files by subject type:

```php
// Get files for a company
$files = $teamleader->files()->forCompany('company-uuid');

// Get files for a contact
$files = $teamleader->files()->forContact('contact-uuid');

// Get files for a deal
$files = $teamleader->files()->forDeal('deal-uuid');

// Get files for an invoice
$files = $teamleader->files()->forInvoice('invoice-uuid');

// Get files for a project
$files = $teamleader->files()->forProject('project-uuid');

// Get files for a ticket
$files = $teamleader->files()->forTicket('ticket-uuid');

// All helper methods support options
$files = $teamleader->files()->forCompany('company-uuid', [
    'page_size' => 100,
    'sort' => [['field' => 'updated_at', 'order' => 'desc']]
]);
```

## Error Handling

The files resource follows standard SDK error handling:

```php
$result = $teamleader->files()->upload('document.pdf', 'company', 'company-uuid');

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Files API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

File API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **Upload operations**: 1 request for getting upload URL + 1 request for actual upload
- **Download operations**: 1 request per call
- **Delete operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class FileController extends Controller
{
    public function index(TeamleaderSDK $teamleader, string $companyId)
    {
        $files = $teamleader->files()->forCompany($companyId);
        return view('files.index', compact('files'));
    }
    
    public function upload(Request $request, TeamleaderSDK $teamleader)
    {
        // Get upload URL
        $uploadResponse = $teamleader->files()->upload(
            $request->file('file')->getClientOriginalName(),
            $request->input('subject_type'),
            $request->input('subject_id'),
            $request->input('folder')
        );
        
        if (!isset($uploadResponse['error'])) {
            // Upload the file
            $uploadUrl = $uploadResponse['data']['location'];
            // ... perform upload ...
        }
        
        return redirect()->back()->with('success', 'File uploaded successfully!');
    }
    
    public function download(TeamleaderSDK $teamleader, string $fileId)
    {
        $downloadResponse = $teamleader->files()->download($fileId);
        
        if (!isset($downloadResponse['error'])) {
            return redirect($downloadResponse['data']['location']);
        }
        
        return redirect()->back()->withErrors(['error' => 'Failed to download file']);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).
