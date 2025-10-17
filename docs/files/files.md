# Files

Manage files in Teamleader Focus.

## Overview

The Files resource provides operations for managing file uploads and downloads in Teamleader. Files can be attached to various subjects (companies, contacts, deals, invoices, projects, tickets) and organized into folders. The API handles secure upload/download through temporary URLs.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [upload()](#upload)
    - [download()](#download)
    - [delete()](#delete)
- [Helper Methods](#helper-methods)
- [Subject Types](#subject-types)
- [Filters](#filters)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`files`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ✅ Supported
- **Creation**: ✅ Supported (via upload())
- **Update**: ❌ Not Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all files with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, sort, include)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all files
$files = Teamleader::files()->list();

// Get files for specific subject
$files = Teamleader::files()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// With pagination and sorting
$files = Teamleader::files()->list([], [
    'page_size' => 50,
    'page_number' => 1,
    'sort' => 'name',
    'sort_order' => 'asc'
]);
```

### `info()`

Get detailed information about a specific file.

**Parameters:**
- `id` (string): File UUID

**Example:**
```php
// Get file information
$file = Teamleader::files()->info('file-uuid');
```

### `upload()`

Request an upload link for a file. This returns a temporary URL where you can upload the file.

**Parameters:**
- `name` (string): File name with extension
- `subjectType` (string): Subject type
- `subjectId` (string): Subject UUID
- `folder` (string|null): Optional folder name

**Returns:** Array with `location` (upload URL) and `expires_at`

**Example:**
```php
// Request upload link
$uploadData = Teamleader::files()->upload(
    'document.pdf',
    'company',
    'company-uuid',
    'Contracts'
);

// Upload file to the returned URL
$uploadUrl = $uploadData['data']['location'];
$expiresAt = $uploadData['data']['expires_at'];

// Use cURL, Guzzle, or file_get_contents to PUT the file to $uploadUrl
$fileContent = file_get_contents('/path/to/document.pdf');

$ch = curl_init($uploadUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/pdf',
    'Content-Length: ' . strlen($fileContent)
]);
curl_exec($ch);
curl_close($ch);
```

### `download()`

Request a download link for a file. This returns a temporary URL from which you can download the file.

**Parameters:**
- `id` (string): File UUID

**Returns:** Array with `location` (download URL) and `expires_at`

**Example:**
```php
// Request download link
$downloadData = Teamleader::files()->download('file-uuid');

$downloadUrl = $downloadData['data']['location'];
$expiresAt = $downloadData['data']['expires_at'];

// Download file from URL
$fileContent = file_get_contents($downloadUrl);
file_put_contents('/path/to/save/file.pdf', $fileContent);
```

### `delete()`

Delete a file.

**Parameters:**
- `id` (string): File UUID

**Example:**
```php
$result = Teamleader::files()->delete('file-uuid');
```

## Helper Methods

### Subject Filtering

```php
// Get files for a company
$files = Teamleader::files()->forSubject('company', 'company-uuid');

// Get files for a deal
$files = Teamleader::files()->forSubject('deal', 'deal-uuid');

// Get files for a project
$files = Teamleader::files()->forSubject('nextgenProject', 'project-uuid');
```

### Folder Filtering

```php
// Get files in specific folder
$files = Teamleader::files()->inFolder('Invoices');

// Get files in root (no folder)
$files = Teamleader::files()->inFolder(null);
```

### ID Filtering

```php
// Get specific files by IDs
$files = Teamleader::files()->byIds(['uuid1', 'uuid2', 'uuid3']);
```

## Subject Types

Valid subject types for file attachments:

- `company`
- `contact`
- `deal`
- `invoice`
- `creditNote`
- `nextgenProject`
- `ticket`

## Filters

Available filters for the `list()` method:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of file UUIDs |
| `subject` | object | Filter by subject (type and id) |
| `folder` | string | Filter by folder name (null for root) |

### Subject Filter Structure

```php
[
    'subject' => [
        'type' => 'company', // or contact, deal, invoice, etc.
        'id' => 'uuid-here'
    ]
]
```

## Sorting

Available sort fields:

| Field | Description |
|-------|-------------|
| `name` | Sort by file name |
| `size` | Sort by file size |
| `created_at` | Sort by creation date |

**Example:**
```php
$files = Teamleader::files()->list([], [
    'sort' => 'created_at',
    'sort_order' => 'desc'
]);
```

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'file-uuid',
            'name' => 'contract.pdf',
            'size' => 524288,
            'mime_type' => 'application/pdf',
            'folder' => 'Contracts',
            'subject' => [
                'type' => 'company',
                'id' => 'company-uuid'
            ],
            'uploaded_by' => [
                'type' => 'user',
                'id' => 'user-uuid'
            ],
            'uploaded_at' => '2025-10-17T09:00:00+00:00'
        ]
    ],
    'meta' => [
        'page' => [
            'size' => 20,
            'number' => 1
        ],
        'matches' => 15
    ]
]
```

### Info Response

```php
[
    'data' => [
        'id' => 'file-uuid',
        'name' => 'contract.pdf',
        'size' => 524288,
        'mime_type' => 'application/pdf',
        'folder' => 'Contracts',
        'subject' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'uploaded_by' => [
            'type' => 'user',
            'id' => 'user-uuid'
        ],
        'uploaded_at' => '2025-10-17T09:00:00+00:00'
    ]
]
```

### Upload Response

```php
[
    'data' => [
        'location' => 'https://secure-upload-url.com/...',
        'expires_at' => '2025-10-17T10:00:00+00:00'
    ]
]
```

### Download Response

```php
[
    'data' => [
        'location' => 'https://secure-download-url.com/...',
        'expires_at' => '2025-10-17T10:00:00+00:00'
    ]
]
```

## Usage Examples

### Upload File to Company

```php
// Step 1: Request upload URL
$uploadData = Teamleader::files()->upload(
    'proposal.pdf',
    'company',
    'company-uuid',
    'Proposals'
);

// Step 2: Upload file to temporary URL
$fileContent = file_get_contents('/path/to/proposal.pdf');
$uploadUrl = $uploadData['data']['location'];

$ch = curl_init($uploadUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/pdf',
    'Content-Length: ' . strlen($fileContent)
]);
$success = curl_exec($ch);
curl_close($ch);

if ($success) {
    echo "File uploaded successfully!";
}
```

### Download File

```php
// Get download URL
$downloadData = Teamleader::files()->download('file-uuid');
$downloadUrl = $downloadData['data']['location'];

// Download file
$fileContent = file_get_contents($downloadUrl);

// Save to disk
file_put_contents('/downloads/document.pdf', $fileContent);

// Or stream to browser
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="document.pdf"');
echo $fileContent;
```

### List Files for Deal

```php
// Get all files attached to a deal
$files = Teamleader::files()->forSubject('deal', 'deal-uuid');

echo "<h3>Deal Files</h3><ul>";

foreach ($files['data'] as $file) {
    $size = round($file['size'] / 1024, 2);
    echo "<li>{$file['name']} ({$size} KB)</li>";
}

echo "</ul>";
```

### Upload Multiple Files

```php
function uploadFilesToCompany(string $companyId, array $filePaths): array
{
    $uploadedFiles = [];
    
    foreach ($filePaths as $filePath) {
        $fileName = basename($filePath);
        
        // Request upload URL
        $uploadData = Teamleader::files()->upload(
            $fileName,
            'company',
            $companyId,
            'Documents'
        );
        
        // Upload file
        $fileContent = file_get_contents($filePath);
        $uploadUrl = $uploadData['data']['location'];
        
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $uploadedFiles[] = [
            'name' => $fileName,
            'success' => $httpCode === 200,
            'http_code' => $httpCode
        ];
    }
    
    return $uploadedFiles;
}

// Usage
$results = uploadFilesToCompany('company-uuid', [
    '/path/to/file1.pdf',
    '/path/to/file2.docx',
    '/path/to/file3.jpg'
]);
```

### Organize Files by Folder

```php
// Get files organized by folder
$allFiles = Teamleader::files()->forSubject('company', 'company-uuid');

$byFolder = [];

foreach ($allFiles['data'] as $file) {
    $folder = $file['folder'] ?? 'Root';
    
    if (!isset($byFolder[$folder])) {
        $byFolder[$folder] = [];
    }
    
    $byFolder[$folder][] = $file;
}

// Display organized files
foreach ($byFolder as $folder => $files) {
    echo "<h3>📁 {$folder}</h3>";
    echo "<ul>";
    
    foreach ($files as $file) {
        echo "<li>{$file['name']}</li>";
    }
    
    echo "</ul>";
}
```

### Bulk Download Files

```php
function downloadFilesAsZip(array $fileIds, string $zipPath): bool
{
    $zip = new ZipArchive();
    
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        return false;
    }
    
    foreach ($fileIds as $fileId) {
        // Get file info
        $file = Teamleader::files()->info($fileId);
        
        // Get download URL
        $downloadData = Teamleader::files()->download($fileId);
        $downloadUrl = $downloadData['data']['location'];
        
        // Download content
        $content = file_get_contents($downloadUrl);
        
        // Add to zip
        $zip->addFromString($file['data']['name'], $content);
    }
    
    $zip->close();
    return true;
}

// Usage
$fileIds = ['uuid1', 'uuid2', 'uuid3'];
downloadFilesAsZip($fileIds, '/downloads/files.zip');
```

## Common Use Cases

### 1. Document Management System

```php
class DocumentManager
{
    public function uploadDocument(
        string $filePath,
        string $subjectType,
        string $subjectId,
        string $folder = null
    ): bool {
        $fileName = basename($filePath);
        
        // Request upload
        $uploadData = Teamleader::files()->upload(
            $fileName,
            $subjectType,
            $subjectId,
            $folder
        );
        
        // Upload file
        $fileContent = file_get_contents($filePath);
        $uploadUrl = $uploadData['data']['location'];
        
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    public function getDocuments(string $subjectType, string $subjectId): array
    {
        return Teamleader::files()->forSubject($subjectType, $subjectId);
    }
    
    public function deleteDocument(string $fileId): bool
    {
        try {
            Teamleader::files()->delete($fileId);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete file', ['id' => $fileId]);
            return false;
        }
    }
}
```

### 2. Invoice Attachment Handler

```php
// Attach files to invoice
function attachInvoiceDocuments(string $invoiceId, array $filePaths): array
{
    $results = [];
    
    foreach ($filePaths as $filePath) {
        $fileName = basename($filePath);
        
        $uploadData = Teamleader::files()->upload(
            $fileName,
            'invoice',
            $invoiceId,
            'Supporting Documents'
        );
        
        // Upload logic here
        $fileContent = file_get_contents($filePath);
        // ... (curl upload code)
        
        $results[] = [
            'file' => $fileName,
            'uploaded' => true
        ];
    }
    
    return $results;
}

// Get invoice files
function getInvoiceFiles(string $invoiceId): array
{
    return Teamleader::files()->forSubject('invoice', $invoiceId);
}
```

### 3. Ticket Attachment System

```php
// Handle ticket attachments
class TicketAttachments
{
    public function addAttachment(string $ticketId, string $filePath): bool
    {
        $fileName = basename($filePath);
        
        $uploadData = Teamleader::files()->upload(
            $fileName,
            'ticket',
            $ticketId
        );
        
        $fileContent = file_get_contents($filePath);
        $uploadUrl = $uploadData['data']['location'];
        
        // Upload file
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    public function getAttachments(string $ticketId): array
    {
        $files = Teamleader::files()->forSubject('ticket', $ticketId);
        return $files['data'];
    }
}
```

## Best Practices

### 1. Handle Upload Expiration

```php
// Check if upload URL is expired
$uploadData = Teamleader::files()->upload('file.pdf', 'company', 'company-uuid');

$expiresAt = new DateTime($uploadData['data']['expires_at']);
$now = new DateTime();

if ($now > $expiresAt) {
    // URL expired, request new one
    $uploadData = Teamleader::files()->upload('file.pdf', 'company', 'company-uuid');
}
```

### 2. Validate File Before Upload

```php
function uploadFileWithValidation(string $filePath, string $subjectType, string $subjectId): bool
{
    // Check file exists
    if (!file_exists($filePath)) {
        throw new Exception('File not found');
    }
    
    // Check file size (max 25MB)
    $fileSize = filesize($filePath);
    if ($fileSize > 25 * 1024 * 1024) {
        throw new Exception('File too large (max 25MB)');
    }
    
    // Get upload URL
    $fileName = basename($filePath);
    $uploadData = Teamleader::files()->upload($fileName, $subjectType, $subjectId);
    
    // Upload file
    // ... (upload logic)
    
    return true;
}
```

### 3. Organize Files in Folders

```php
// Use meaningful folder structure
$uploadData = Teamleader::files()->upload(
    'contract_2025.pdf',
    'company',
    'company-uuid',
    'Contracts/2025' // Organize by year
);

$uploadData = Teamleader::files()->upload(
    'invoice_001.pdf',
    'company',
    'company-uuid',
    'Invoices/Q4-2025' // Organize by quarter
);
```

### 4. Handle Upload Errors

```php
function safeUpload(string $filePath, string $subjectType, string $subjectId): array
{
    try {
        $fileName = basename($filePath);
        $uploadData = Teamleader::files()->upload($fileName, $subjectType, $subjectId);
        
        $fileContent = file_get_contents($filePath);
        $uploadUrl = $uploadData['data']['location'];
        
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'success' => $httpCode === 200,
            'http_code' => $httpCode,
            'file' => $fileName
        ];
        
    } catch (Exception $e) {
        Log::error('Upload failed', [
            'file' => $filePath,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
```

### 5. Clean Up Old Files

```php
// Delete files older than 1 year
function cleanUpOldFiles(string $subjectType, string $subjectId): int
{
    $files = Teamleader::files()->forSubject($subjectType, $subjectId);
    $oneYearAgo = new DateTime('-1 year');
    $deletedCount = 0;
    
    foreach ($files['data'] as $file) {
        $uploadedAt = new DateTime($file['uploaded_at']);
        
        if ($uploadedAt < $oneYearAgo) {
            try {
                Teamleader::files()->delete($file['id']);
                $deletedCount++;
            } catch (Exception $e) {
                Log::warning('Failed to delete old file', ['id' => $file['id']]);
            }
        }
    }
    
    return $deletedCount;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Upload file
try {
    $uploadData = Teamleader::files()->upload(
        'document.pdf',
        'company',
        'company-uuid'
    );
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Invalid upload parameters', [
            'errors' => $e->getDetails()
        ]);
    } elseif ($e->getCode() === 404) {
        // Subject not found
        Log::error('Subject does not exist');
    }
}

// Download file
try {
    $downloadData = Teamleader::files()->download('file-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::error('File not found');
    }
}

// Delete file
try {
    Teamleader::files()->delete('file-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::warning('File already deleted or not found');
    }
}
```

## Important Notes

### 1. Two-Step Upload Process

File uploads require two steps:
1. Request upload URL from Teamleader API
2. PUT the file to the temporary URL

### 2. Temporary URLs

Upload and download URLs are temporary and expire. Always check `expires_at` in responses.

### 3. File Size Limits

Check Teamleader's file size limits before uploading large files.

### 4. Supported Subject Types

Not all entity types support file attachments. Refer to the valid subject types list.

## Related Resources

- [Companies](../crm/companies.md) - Attach files to companies
- [Contacts](../crm/contacts.md) - Attach files to contacts
- [Deals](../deals/deals.md) - Attach files to deals
- [Invoices](../invoicing/invoices.md) - Attach files to invoices
- [Projects](../projects/projects.md) - Attach files to projects
- [Tickets](../tickets/tickets.md) - Attach files to tickets

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
