# Files

Manage files in Teamleader Focus.

## Overview

The Files resource provides operations for managing file uploads and downloads in Teamleader. Files can be attached to various subjects (companies, contacts, deals, invoices, projects, tickets) and organized into folders. The API handles secure upload/download through temporary URLs.

**Enhanced in v1.1.2**: The Files resource now includes custom filter validation and formatting to ensure compatibility with the Teamleader Files API's specific requirements. This includes strict validation of subject filters and automatic formatting of filter parameters.

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
- [API-Specific Behavior](#api-specific-behavior)
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
- **Filtering**: ✅ Supported (with custom validation)
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported (via upload())
- **Update**: ❌ Not Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all files with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply (subject filter required)
- `options` (array): Additional options (page_size, page_number, sort, include)

**Important**: The Files API requires filters to be in a specific format. The SDK automatically handles this formatting and validates the subject filter structure.

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get files for specific subject (required by API)
$files = Teamleader::files()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// With pagination and sorting
$files = Teamleader::files()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
], [
    'page_size' => 50,
    'page_number' => 1,
    'sort' => 'updated_at',
    'sort_order' => 'desc'
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
- `subjectType` (string): Subject type (validated against allowed types)
- `subjectId` (string): Subject UUID
- `folder` (string|null): Optional folder name

**Returns:** Array with `location` (upload URL) and `expires_at`

**Validation**: Subject type is automatically validated against the list of allowed types.

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

The Files resource provides convenient helper methods that automatically handle subject filter formatting and validation.

### forSubject()

Get files for any subject type.

```php
// Generic method for any subject type
$files = Teamleader::files()->forSubject('company', 'company-uuid');
$files = Teamleader::files()->forSubject('deal', 'deal-uuid');
```

### Subject-Specific Methods

Convenience methods for common subject types:

```php
// Get files for a company
$files = Teamleader::files()->forCompany('company-uuid');

// Get files for a contact
$files = Teamleader::files()->forContact('contact-uuid');

// Get files for a deal
$files = Teamleader::files()->forDeal('deal-uuid');

// Get files for an invoice
$files = Teamleader::files()->forInvoice('invoice-uuid');

// Get files for a project
$files = Teamleader::files()->forProject('project-uuid');

// Get files for a ticket
$files = Teamleader::files()->forTicket('ticket-uuid');
```

All helper methods support additional options:

```php
// With pagination
$files = Teamleader::files()->forCompany('company-uuid', [
    'page_size' => 100,
    'page_number' => 1
]);

// With sorting
$files = Teamleader::files()->forDeal('deal-uuid', [
    'sort' => 'updated_at',
    'sort_order' => 'desc'
]);
```

## Subject Types

Valid subject types for file attachments (automatically validated by the SDK):

- `company` - Attach to companies
- `contact` - Attach to contacts
- `deal` - Attach to deals
- `invoice` - Attach to invoices
- `creditNote` - Attach to credit notes
- `nextgenProject` - Attach to projects
- `ticket` - Attach to tickets

**Note**: The SDK validates subject types and will throw an `InvalidArgumentException` if an invalid type is provided.

## Filters

Available filters for the `list()` method:

| Filter | Type | Required | Description |
|--------|------|----------|-------------|
| `subject` | object | Yes* | Filter by subject (type and id) |

*The subject filter is effectively required by the Teamleader API for listing files.

### Subject Filter Structure

The subject filter must contain both `type` and `id`:

```php
[
    'subject' => [
        'type' => 'company', // Required: one of the valid subject types
        'id' => 'uuid-here'  // Required: UUID of the subject
    ]
]
```

**Automatic Validation** (v1.1.2+):
- Validates that both `type` and `id` are present
- Validates that `type` is one of the allowed subject types
- Throws descriptive `InvalidArgumentException` on validation failure
- Ensures proper formatting for the Teamleader API

## Sorting

Available sort fields:

| Field | Description |
|-------|-------------|
| `updated_at` | Sort by last update date |

**Example:**
```php
$files = Teamleader::files()->forCompany('company-uuid', [
    'sort' => 'updated_at',
    'sort_order' => 'desc' // or 'asc'
]);
```

## API-Specific Behavior

### Custom Filter Handling (v1.1.2+)

The Files resource implements custom `buildQueryParams()` logic to comply with the Teamleader Files API's specific requirements:

1. **Filter Object Structure**: Filters are wrapped in a `filter` object rather than being passed directly
2. **Subject Validation**: The subject filter structure is strictly validated
3. **Type Validation**: Subject types are validated against the allowed list
4. **Error Messages**: Provides clear error messages for invalid filter configurations

**Internal Implementation Example:**
```php
// The SDK automatically converts this:
$files = Teamleader::files()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Into this API request structure:
[
    'filter' => [
        'subject' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'page' => ['size' => 20, 'number' => 1],
    'sort' => ['-updated_at']
]
```

### Validation Exceptions

The Files resource throws `InvalidArgumentException` in these cases:

```php
// Missing type
Teamleader::files()->list([
    'subject' => ['id' => 'uuid']
]);
// Exception: subject filter must contain both type and id

// Missing id
Teamleader::files()->list([
    'subject' => ['type' => 'company']
]);
// Exception: subject filter must contain both type and id

// Invalid subject type
Teamleader::files()->upload('file.pdf', 'invalid_type', 'uuid');
// Exception: Invalid subject type. Must be one of: company, contact, deal, invoice, creditNote, nextgenProject, ticket
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
            'uploaded_at' => '2025-10-17T09:00:00+00:00',
            'updated_at' => '2025-10-17T09:00:00+00:00'
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
        'uploaded_at' => '2025-10-17T09:00:00+00:00',
        'updated_at' => '2025-10-17T09:00:00+00:00'
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
$uploadUrl = $uploadData['data']['location'];
$fileContent = file_get_contents('/path/to/proposal.pdf');

$ch = curl_init($uploadUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/pdf'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "File uploaded successfully!";
}
```

### Download File from Deal

```php
// Get all files for a deal
$files = Teamleader::files()->forDeal('deal-uuid');

// Download the first file
if (!empty($files['data'])) {
    $fileId = $files['data'][0]['id'];
    
    // Request download URL
    $downloadData = Teamleader::files()->download($fileId);
    $downloadUrl = $downloadData['data']['location'];
    
    // Download file
    $fileContent = file_get_contents($downloadUrl);
    $fileName = $files['data'][0]['name'];
    file_put_contents('/downloads/' . $fileName, $fileContent);
}
```

### List Files with Pagination

```php
$page = 1;
$allFiles = [];

do {
    $response = Teamleader::files()->forCompany('company-uuid', [
        'page_size' => 100,
        'page_number' => $page,
        'sort' => 'updated_at',
        'sort_order' => 'desc'
    ]);
    
    $allFiles = array_merge($allFiles, $response['data']);
    
    $hasMore = count($response['data']) === 100;
    $page++;
    
} while ($hasMore);

echo "Total files: " . count($allFiles);
```

### Delete Old Files

```php
// Get all files for a subject
$files = Teamleader::files()->forInvoice('invoice-uuid');

$oneYearAgo = new DateTime('-1 year');

foreach ($files['data'] as $file) {
    $uploadedAt = new DateTime($file['uploaded_at']);
    
    if ($uploadedAt < $oneYearAgo) {
        try {
            Teamleader::files()->delete($file['id']);
            echo "Deleted old file: {$file['name']}\n";
        } catch (Exception $e) {
            Log::warning("Failed to delete file", [
                'file_id' => $file['id'],
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## Common Use Cases

### 1. Document Management System

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;
use Illuminate\Support\Facades\Log;

class DocumentManager
{
    /**
     * Upload a document to a subject
     */
    public function uploadDocument(
        string $filePath,
        string $subjectType,
        string $subjectId,
        string $folder = null
    ): bool {
        try {
            $fileName = basename($filePath);
            
            // Validate file exists
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }
            
            // Request upload URL (subject type is automatically validated)
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
            
            if ($httpCode !== 200) {
                throw new \Exception("Upload failed with HTTP code: {$httpCode}");
            }
            
            Log::info("Document uploaded successfully", [
                'file' => $fileName,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId
            ]);
            
            return true;
            
        } catch (\InvalidArgumentException $e) {
            // Handle validation errors
            Log::error("Invalid upload parameters", [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error("Document upload failed", [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
            return false;
        }
    }
    
    /**
     * Get all documents for a subject
     */
    public function getDocuments(string $subjectType, string $subjectId): array
    {
        try {
            return Teamleader::files()->forSubject($subjectType, $subjectId);
        } catch (\Exception $e) {
            Log::error("Failed to retrieve documents", [
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'error' => $e->getMessage()
            ]);
            return ['data' => []];
        }
    }
    
    /**
     * Download a document
     */
    public function downloadDocument(string $fileId, string $savePath): bool
    {
        try {
            $downloadData = Teamleader::files()->download($fileId);
            $downloadUrl = $downloadData['data']['location'];
            
            $fileContent = file_get_contents($downloadUrl);
            
            if ($fileContent === false) {
                throw new \Exception("Failed to download file content");
            }
            
            file_put_contents($savePath, $fileContent);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Document download failed", [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Delete a document
     */
    public function deleteDocument(string $fileId): bool
    {
        try {
            Teamleader::files()->delete($fileId);
            
            Log::info("Document deleted", ['file_id' => $fileId]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to delete file", [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
```

### 2. Bulk File Sync Command

```php
use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class SyncTeamleaderFiles extends Command
{
    protected $signature = 'teamleader:sync-files 
                            {subject_type : Subject type (company, deal, etc.)} 
                            {subject_id : Subject UUID}';
    
    protected $description = 'Sync files from Teamleader for a specific subject';
    
    public function handle(): int
    {
        $subjectType = $this->argument('subject_type');
        $subjectId = $this->argument('subject_id');
        
        $this->info("Syncing files for {$subjectType}: {$subjectId}");
        
        try {
            // Get all files (automatically validated)
            $files = Teamleader::files()->forSubject($subjectType, $subjectId);
            
            $this->info("Found {$files['meta']['matches']} files");
            
            foreach ($files['data'] as $file) {
                $this->line("- {$file['name']} ({$file['size']} bytes)");
                
                // Store file metadata in database
                \App\Models\TeamleaderFile::updateOrCreate(
                    ['teamleader_id' => $file['id']],
                    [
                        'name' => $file['name'],
                        'size' => $file['size'],
                        'mime_type' => $file['mime_type'],
                        'folder' => $file['folder'] ?? null,
                        'subject_type' => $file['subject']['type'],
                        'subject_id' => $file['subject']['id'],
                        'uploaded_at' => $file['uploaded_at'],
                        'synced_at' => now(),
                    ]
                );
            }
            
            $this->info("✓ Files synced successfully");
            
            return Command::SUCCESS;
            
        } catch (\InvalidArgumentException $e) {
            $this->error("Invalid arguments: " . $e->getMessage());
            return Command::FAILURE;
            
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

### 3. Invoice Attachment Handler

```php
class InvoiceAttachmentService
{
    /**
     * Attach multiple files to an invoice
     */
    public function attachFiles(string $invoiceId, array $filePaths): array
    {
        $results = [];
        
        foreach ($filePaths as $filePath) {
            try {
                $fileName = basename($filePath);
                
                // Upload file (subject type validated automatically)
                $uploadData = Teamleader::files()->upload(
                    $fileName,
                    'invoice',
                    $invoiceId,
                    'Supporting Documents'
                );
                
                // Upload file content
                $fileContent = file_get_contents($filePath);
                $uploadUrl = $uploadData['data']['location'];
                
                $ch = curl_init($uploadUrl);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $results[] = [
                    'file' => $fileName,
                    'success' => $httpCode === 200,
                    'http_code' => $httpCode
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'file' => basename($filePath),
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get all files attached to an invoice
     */
    public function getInvoiceFiles(string $invoiceId): array
    {
        // Helper method handles validation automatically
        $files = Teamleader::files()->forInvoice($invoiceId);
        return $files['data'];
    }
}
```

## Best Practices

### 1. Always Use Helper Methods When Possible

```php
// ✅ Good: Use helper method (automatic validation)
$files = Teamleader::files()->forCompany('company-uuid');

// ⚠️ Works but verbose: Manual filter construction
$files = Teamleader::files()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

### 2. Handle Upload Expiration

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

### 3. Validate Before Upload

```php
function uploadWithValidation(string $filePath, string $subjectType, string $subjectId): bool
{
    // Check file exists
    if (!file_exists($filePath)) {
        throw new \Exception('File not found');
    }
    
    // Check file size (max 25MB)
    $fileSize = filesize($filePath);
    if ($fileSize > 25 * 1024 * 1024) {
        throw new \Exception('File too large (max 25MB)');
    }
    
    // Check file type
    $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'];
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        throw new \Exception('File type not allowed');
    }
    
    // Subject type is validated automatically by the SDK
    $fileName = basename($filePath);
    $uploadData = Teamleader::files()->upload($fileName, $subjectType, $subjectId);
    
    // ... upload logic
    
    return true;
}
```

### 4. Organize Files in Folders

```php
// Use meaningful folder structure
Teamleader::files()->upload(
    'contract_2025.pdf',
    'company',
    'company-uuid',
    'Contracts/2025' // Organize by year
);

Teamleader::files()->upload(
    'invoice_001.pdf',
    'company',
    'company-uuid',
    'Invoices/Q4-2025' // Organize by quarter
);
```

### 5. Implement Retry Logic

```php
function uploadWithRetry(
    string $filePath, 
    string $subjectType, 
    string $subjectId,
    int $maxRetries = 3
): bool {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            $fileName = basename($filePath);
            
            // Subject type validated automatically
            $uploadData = Teamleader::files()->upload(
                $fileName,
                $subjectType,
                $subjectId
            );
            
            $fileContent = file_get_contents($filePath);
            $uploadUrl = $uploadData['data']['location'];
            
            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return true;
            }
            
            throw new \Exception("Upload failed with HTTP code: {$httpCode}");
            
        } catch (\Exception $e) {
            $attempt++;
            
            if ($attempt >= $maxRetries) {
                Log::error("Upload failed after {$maxRetries} attempts", [
                    'file' => $filePath,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
            
            // Exponential backoff
            sleep(pow(2, $attempt));
        }
    }
    
    return false;
}
```

## Error Handling

### Common Exceptions

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use InvalidArgumentException;

// Validation errors (v1.1.2+)
try {
    // Missing subject id
    $files = Teamleader::files()->list([
        'subject' => ['type' => 'company']
    ]);
} catch (InvalidArgumentException $e) {
    // Message: "subject filter must contain both type and id"
    Log::error('Invalid filter structure', ['error' => $e->getMessage()]);
}

try {
    // Invalid subject type
    $uploadData = Teamleader::files()->upload(
        'file.pdf',
        'invalid_type',
        'uuid'
    );
} catch (InvalidArgumentException $e) {
    // Message: "Invalid subject type. Must be one of: company, contact, deal, invoice, creditNote, nextgenProject, ticket"
    Log::error('Invalid subject type', ['error' => $e->getMessage()]);
}

// API errors
try {
    $uploadData = Teamleader::files()->upload(
        'document.pdf',
        'company',
        'company-uuid'
    );
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error from API
        Log::error('API validation error', [
            'errors' => $e->getDetails()
        ]);
    } elseif ($e->getCode() === 404) {
        // Subject not found
        Log::error('Subject does not exist');
    }
}

// Download errors
try {
    $downloadData = Teamleader::files()->download('file-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::error('File not found');
    }
}

// Delete errors
try {
    Teamleader::files()->delete('file-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::warning('File already deleted or not found');
    } elseif ($e->getCode() === 403) {
        Log::error('Permission denied to delete file');
    }
}
```

### Graceful Error Handling

```php
class SafeFileManager
{
    public function safeUpload(
        string $filePath,
        string $subjectType,
        string $subjectId
    ): array {
        try {
            // Pre-validation
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found',
                    'code' => 'FILE_NOT_FOUND'
                ];
            }
            
            $fileName = basename($filePath);
            
            // Subject type validated automatically by SDK
            $uploadData = Teamleader::files()->upload(
                $fileName,
                $subjectType,
                $subjectId
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
            
            return [
                'success' => $httpCode === 200,
                'http_code' => $httpCode,
                'file' => $fileName
            ];
            
        } catch (InvalidArgumentException $e) {
            // SDK validation error
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'VALIDATION_ERROR'
            ];
            
        } catch (TeamleaderException $e) {
            // API error
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'API_ERROR',
                'http_code' => $e->getCode()
            ];
            
        } catch (\Exception $e) {
            // Generic error
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'UNKNOWN_ERROR'
            ];
        }
    }
}
```

## Important Notes

### 1. Two-Step Upload Process

File uploads require two steps:
1. Request upload URL from Teamleader API (using `upload()` method)
2. PUT the file to the temporary URL (using cURL, Guzzle, etc.)

### 2. Temporary URLs

Upload and download URLs are temporary and expire. Always check `expires_at` in responses and request new URLs if expired.

### 3. Filter Validation (v1.1.2+)

The Files resource now includes automatic validation:
- Subject filter structure is validated
- Subject types are validated against allowed types
- Clear error messages for invalid configurations
- Prevents API errors due to malformed requests

### 4. Subject Types

Not all entity types support file attachments. Only the documented subject types (`company`, `contact`, `deal`, `invoice`, `creditNote`, `nextgenProject`, `ticket`) are supported.

### 5. File Organization

Use folders to organize files logically. This makes files easier to find and manage through the Teamleader interface.

## Related Resources

- [Companies](../crm/companies.md) - Attach files to companies
- [Contacts](../crm/contacts.md) - Attach files to contacts
- [Deals](../deals/deals.md) - Attach files to deals
- [Invoices](../invoicing/invoices.md) - Attach files to invoices
- [Projects](../projects/projects.md) - Attach files to projects
- [Tickets](../tickets/tickets.md) - Attach files to tickets

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Error Handling](../error-handling.md) - Comprehensive error handling guide
- [Best Practices](../best-practices.md) - SDK best practices

---

**Version**: 1.1.2+  
**Last Updated**: October 2025  
**Status**: ✅ Stable with enhanced validation
