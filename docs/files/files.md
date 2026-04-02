# Files

Manage file uploads and downloads in Teamleader Focus.

## Overview

The Files resource handles attaching files to Teamleader entities. Upload and download both work via **temporary signed
URLs** — the SDK requests the URL from the API, then you transfer the file content to/from that URL yourself using any
HTTP client.

Access via `Teamleader::files()`.

> **No `update()` method** — files cannot be renamed or moved after upload.
>
> **Sort uses direction-prefix format** — `['-updated_at']` for descending, `['updated_at']` for ascending. This is
> handled automatically when you pass `sort` + `sort_order` options.
>
> **Projects use subject type `nextgenProject`** — not `project`.
>
> **`temporary` uploads do not require a `subjectId`** — all other subject types throw without one.

## Endpoint

`files`

## Capabilities

| Capability  | Supported                    |
|-------------|------------------------------|
| Pagination  | ✅ Supported                  |
| Filtering   | ✅ Supported (`subject`)      |
| Sorting     | ✅ Supported (`updated_at`)   |
| Sideloading | ❌ Not supported              |
| Creation    | ✅ Supported (via `upload()`) |
| Update      | ❌ Not supported              |
| Deletion    | ✅ Supported                  |

---

## Methods

### `list(array $filters = [], array $options = [])`

Lists files for a given subject. The `subject` filter is validated before the request — both `type` and `id` are
required and `type` must be one of the valid values.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$files = Teamleader::files()->list([
    'subject' => ['type' => 'company', 'id' => 'company-uuid'],
]);

$files = Teamleader::files()->list(
    ['subject' => ['type' => 'deal', 'id' => 'deal-uuid']],
    ['sort' => 'updated_at', 'sort_order' => 'desc', 'page_size' => 50]
);
```

---

### `info(string $id)`

Throws if `$id` is empty.

```php
$file = Teamleader::files()->info('file-uuid');
```

---

### `upload(string $name, string $subjectType, ?string $subjectId = null, ?string $folder = null)`

Requests a signed upload URL from the API. **Does not upload the file** — you must then `PUT` the file content to the
returned `location` URL yourself.

- `$name` — filename with extension (e.g. `contract.pdf`)
- `$subjectType` — validated against the list below
- `$subjectId` — required for all types **except** `temporary`
- `$folder` — optional; defaults to the account's "General" folder

Returns `data.location` (upload URL) and `data.expires_at`.

```php
// Attach a file to a company
$upload = Teamleader::files()->upload('contract.pdf', 'company', 'company-uuid', 'Contracts');
$uploadUrl = $upload['data']['location'];

// Now PUT the file content to that URL
$fileContent = file_get_contents('/path/to/contract.pdf');
$ch = curl_init($uploadUrl);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS    => $fileContent,
    CURLOPT_HTTPHEADER    => ['Content-Type: application/pdf', 'Content-Length: ' . strlen($fileContent)],
]);
curl_exec($ch);
curl_close($ch);
```

### Temporary uploads

When `$subjectType` is `temporary`, no `$subjectId` is needed. The file UUID can then be passed to other resources (
e.g. `file_id` on an incoming invoice) before the 24-hour expiry.

```php
$upload = Teamleader::files()->upload('receipt.jpg', 'temporary');
$uploadUrl = $upload['data']['location'];
// ... PUT file content to $uploadUrl ...

$fileId = $upload['data']['id']; // use this UUID wherever file_id is accepted
```

> Temporary files expire after **24 hours** if not linked to an entity. They do not appear in any file overview and are
> not included in external syncs.

---

### `download(string $id)`

Requests a signed download URL. **Does not download the file** — you must then fetch the file content from the
returned `location` URL yourself.

Returns `data.location` (download URL) and `data.expires_at`.

```php
$download = Teamleader::files()->download('file-uuid');
$downloadUrl = $download['data']['location'];

$fileContent = file_get_contents($downloadUrl);
file_put_contents('/local/path/file.pdf', $fileContent);
```

---

### `delete(mixed $id)`

Throws if `$id` is empty.

```php
Teamleader::files()->delete('file-uuid');
```

---

## Helper Methods

All helpers call `forSubject()` internally with the appropriate type.

| Method                                 | Subject type sent  |
|----------------------------------------|--------------------|
| `forCompany(string $id)`               | `company`          |
| `forContact(string $id)`               | `contact`          |
| `forDeal(string $id)`                  | `deal`             |
| `forInvoice(string $id)`               | `invoice`          |
| `forProject(string $id)`               | `nextgenProject`   |
| `forTicket(string $id)`                | `ticket`           |
| `forSubject(string $type, string $id)` | any validated type |

All accept an optional `$options` array for pagination and sorting:

```php
$files = Teamleader::files()->forCompany('company-uuid');
$files = Teamleader::files()->forDeal('deal-uuid', ['page_size' => 50, 'sort_order' => 'desc']);
$files = Teamleader::files()->forProject('project-uuid');
```

---

## Valid Subject Types

| Type             | Notes                                                   |
|------------------|---------------------------------------------------------|
| `company`        |                                                         |
| `contact`        |                                                         |
| `deal`           |                                                         |
| `invoice`        |                                                         |
| `creditNote`     |                                                         |
| `nextgenProject` | Note: `project` is **not** valid — use `nextgenProject` |
| `ticket`         |                                                         |
| `temporary`      | No `subjectId` required; expires in 24 h                |

---

## Filters

| Filter    | Type   | Description                                                       |
|-----------|--------|-------------------------------------------------------------------|
| `subject` | object | Required in practice. `{type: string, id: uuid}` — both validated |

---

## Sorting

| Field        | Description            |
|--------------|------------------------|
| `updated_at` | Last modification date |

```php
$files = Teamleader::files()->forCompany('company-uuid', [
    'sort'       => 'updated_at',
    'sort_order' => 'desc',
]);
```

---

## Usage Examples

### Attach a contract to a deal

```php
// 1. Request upload URL
$upload = Teamleader::files()->upload('contract.pdf', 'deal', 'deal-uuid', 'Contracts');

// 2. PUT the file to the signed URL
$content = file_get_contents('/local/contract.pdf');
$ch = curl_init($upload['data']['location']);
curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => $content]);
curl_exec($ch);
curl_close($ch);

// 3. List files on the deal to confirm
$files = Teamleader::files()->forDeal('deal-uuid');
```

### Stage a receipt before creating an incoming invoice

```php
// Upload temporarily
$upload = Teamleader::files()->upload('receipt.jpg', 'temporary');
// PUT file content to $upload['data']['location'] ...

// Use the returned file ID when creating the receipt
Teamleader::receipts()->add([
    'title'    => 'Team lunch',
    'currency' => ['code' => 'EUR'],
    'total'    => ['tax_inclusive' => ['amount' => 45.0]],
    'file_id'  => $upload['data']['id'],
]);
```

### Download all files for a company

```php
$files = Teamleader::files()->forCompany('company-uuid');

foreach ($files['data'] as $file) {
    $download = Teamleader::files()->download($file['id']);
    $content  = file_get_contents($download['data']['location']);
    file_put_contents("/exports/{$file['name']}", $content);
}
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing subject id on list
try {
    Teamleader::files()->list([
        'subject' => ['type' => 'company'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'subject filter must contain both type and id'
}

// Invalid subject type on upload
try {
    Teamleader::files()->upload('file.pdf', 'project', 'project-uuid');
} catch (InvalidArgumentException $e) {
    // 'Invalid subject type. Must be one of: company, contact, deal, invoice, creditNote, nextgenProject, ticket, temporary'
}

// Missing subjectId for non-temporary type
try {
    Teamleader::files()->upload('file.pdf', 'company');
} catch (InvalidArgumentException $e) {
    // 'Subject ID is required for subject type: company'
}
```

---

## Related Resources

- [[Companies]] — Files can be attached to companies
- [[Contacts]] — Files can be attached to contacts
- [[Deals]] — Files can be attached to deals
- [[Invoices]] — Files can be attached to invoices
- [[Receipts]] — Receipts accept a `file_id` on create
- [[Incoming-Invoices]] — Incoming invoices accept a `file_id` on create
