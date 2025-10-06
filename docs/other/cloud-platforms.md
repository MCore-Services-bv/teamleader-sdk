# Cloud Platforms

Fetch cloud platform URLs for invoices, quotations, and tickets. These URLs provide direct access to resources in the Teamleader cloud platform interface.

## Endpoint

`cloudPlatforms`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ⚠️ Partial (via `batchUrls()` helper method)

## Available Methods

### `url()`

Fetch the cloud platform URL for a specific resource type and ID.

**Parameters:**
- `type` (string, required): Resource type - must be one of: `invoice`, `quotation`, or `ticket`
- `id` (string, required): Resource UUID

**Returns:** Array with cloud platform URL

**Example:**
```php
$result = $teamleader->cloudPlatforms()->url('invoice', 'b7023c11-455e-4fa5-bb96-87f37dbc7d07');

// Returns:
// [
//     'data' => [
//         'url' => 'https://teamleader.cloud/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
//     ]
// ]

$cloudUrl = $result['data']['url'];
```

### `getUrl()`

Extract just the URL string from the API response (convenience method).

**Parameters:**
- `type` (string, required): Resource type
- `id` (string, required): Resource UUID

**Returns:** String containing the cloud platform URL

**Example:**
```php
$url = $teamleader->cloudPlatforms()->getUrl('invoice', 'b7023c11-455e-4fa5-bb96-87f37dbc7d07');

// Returns: "https://teamleader.cloud/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

### Type-Specific Convenience Methods

#### `invoiceUrl()` / `getInvoiceUrl()`

Get cloud platform URL specifically for an invoice.

**Example:**
```php
// Get full response
$result = $teamleader->cloudPlatforms()->invoiceUrl('invoice-uuid');
$url = $result['data']['url'];

// Get just the URL string
$url = $teamleader->cloudPlatforms()->getInvoiceUrl('invoice-uuid');
```

#### `quotationUrl()` / `getQuotationUrl()`

Get cloud platform URL specifically for a quotation.

**Example:**
```php
// Get full response
$result = $teamleader->cloudPlatforms()->quotationUrl('quotation-uuid');
$url = $result['data']['url'];

// Get just the URL string
$url = $teamleader->cloudPlatforms()->getQuotationUrl('quotation-uuid');
```

#### `ticketUrl()` / `getTicketUrl()`

Get cloud platform URL specifically for a ticket.

**Example:**
```php
// Get full response
$result = $teamleader->cloudPlatforms()->ticketUrl('ticket-uuid');
$url = $result['data']['url'];

// Get just the URL string
$url = $teamleader->cloudPlatforms()->getTicketUrl('ticket-uuid');
```

### `batchUrls()`

Get cloud platform URLs for multiple resources of the same type. This is a convenience method that calls the API multiple times.

**Parameters:**
- `type` (string, required): Resource type
- `ids` (array, required): Array of resource UUIDs

**Returns:** Associative array mapping IDs to URLs

**Example:**
```php
$invoiceIds = [
    'b7023c11-455e-4fa5-bb96-87f37dbc7d07',
    'c8134d22-566f-5ga6-cc07-98g48ecd8e18',
    'd9245e33-677g-6hb7-dd18-09h59fde9f29'
];

$urls = $teamleader->cloudPlatforms()->batchUrls('invoice', $invoiceIds);

// Returns:
// [
//     'b7023c11-455e-4fa5-bb96-87f37dbc7d07' => 'https://teamleader.cloud/...',
//     'c8134d22-566f-5ga6-cc07-98g48ecd8e18' => 'https://teamleader.cloud/...',
//     'd9245e33-677g-6hb7-dd18-09h59fde9f29' => 'https://teamleader.cloud/...'
// ]
```

## Helper Methods

### `getSupportedTypes()`

Get all supported resource types.

**Example:**
```php
$types = $teamleader->cloudPlatforms()->getSupportedTypes();
// Returns: ['invoice', 'quotation', 'ticket']
```

### `isTypeSupported()`

Check if a resource type is supported.

**Example:**
```php
if ($teamleader->cloudPlatforms()->isTypeSupported('invoice')) {
    // This type is supported
}
```

## Supported Resource Types

The following resource types support cloud platform URLs:

- `invoice` - View invoices in the cloud platform
- `quotation` - View quotations in the cloud platform
- `ticket` - View tickets in the cloud platform

## Common Usage Patterns

### Redirect User to Invoice

```php
use Illuminate\Support\Facades\Redirect;

public function viewInvoiceInCloud($invoiceId)
{
    $url = $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);
    
    return Redirect::away($url);
}
```

### Generate Links for Email Notifications

```php
$invoice = $teamleader->invoices()->info($invoiceId);
$cloudUrl = $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);

Mail::to($customer->email)->send(new InvoiceNotification($invoice, $cloudUrl));
```

### Add Cloud Links to Admin Panel

```php
// In your controller
$invoices = $teamleader->invoices()->list();

foreach ($invoices['data'] as &$invoice) {
    $invoice['cloud_url'] = $teamleader->cloudPlatforms()
        ->getInvoiceUrl($invoice['id']);
}

return view('admin.invoices', compact('invoices'));
```

### Batch Generate URLs for Reports

```php
$invoiceIds = DB::table('invoices')
    ->where('status', 'sent')
    ->pluck('teamleader_id')
    ->toArray();

$cloudUrls = $teamleader->cloudPlatforms()->batchUrls('invoice', $invoiceIds);

// Store URLs in database for quick access
foreach ($cloudUrls as $invoiceId => $url) {
    DB::table('invoices')
        ->where('teamleader_id', $invoiceId)
        ->update(['cloud_url' => $url]);
}
```

### Create "View in Teamleader" Button

```php
// In your Blade template
@foreach($invoices as $invoice)
    <tr>
        <td>{{ $invoice['invoice_number'] }}</td>
        <td>{{ $invoice['total'] }}</td>
        <td>
            <a href="{{ $teamleader->cloudPlatforms()->getInvoiceUrl($invoice['id']) }}" 
               target="_blank" 
               class="btn btn-primary">
                View in Teamleader
            </a>
        </td>
    </tr>
@endforeach
```

### Handle Different Resource Types

```php
public function getCloudUrl(string $type, string $id)
{
    $cloudPlatforms = $teamleader->cloudPlatforms();
    
    if (!$cloudPlatforms->isTypeSupported($type)) {
        throw new \InvalidArgumentException("Type {$type} is not supported");
    }
    
    return match($type) {
        'invoice' => $cloudPlatforms->getInvoiceUrl($id),
        'quotation' => $cloudPlatforms->getQuotationUrl($id),
        'ticket' => $cloudPlatforms->getTicketUrl($id),
    };
}
```

### Generate QR Codes for Cloud URLs

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

public function generateInvoiceQrCode($invoiceId)
{
    $cloudUrl = $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);
    
    return QrCode::size(300)->generate($cloudUrl);
}
```

## Response Structure

### url() / invoiceUrl() / quotationUrl() / ticketUrl()

```php
[
    'data' => [
        'url' => 'https://teamleader.cloud/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.yUmR5yNZ45P_jHDbjAzuk4kRA8YNoM9ckSZOZpMIJmU/'
    ]
]
```

### getUrl() / getInvoiceUrl() / getQuotationUrl() / getTicketUrl()

```php
'https://teamleader.cloud/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
```

### batchUrls()

```php
[
    'uuid-1' => 'https://teamleader.cloud/...',
    'uuid-2' => 'https://teamleader.cloud/...',
    'uuid-3' => 'https://teamleader.cloud/...'
]
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $url = $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);
} catch (ValidationException $e) {
    // Handle validation errors (invalid type, invalid UUID format)
    echo "Validation error: " . $e->getMessage();
} catch (NotFoundException $e) {
    // Handle case where resource doesn't exist
    echo "Resource not found: " . $e->getMessage();
} catch (TeamleaderException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage();
}
```

## Security Considerations

### URL Expiration

Cloud platform URLs are JWT-encoded and may have an expiration time:

```php
// Don't cache URLs indefinitely
$url = $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);

// Instead, generate fresh URLs when needed
Cache::remember("invoice_{$invoiceId}_url", now()->addHours(1), function() use ($invoiceId) {
    return $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);
});
```

### Permission Checks

Users need appropriate permissions to view resources:

```php
// Check if current user has access before generating URL
if (!Auth::user()->can('view-invoice', $invoice)) {
    abort(403, 'You do not have permission to view this invoice');
}

$url = $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);
```

### External Sharing

Be cautious when sharing cloud URLs externally:

```php
// For customer portals, consider creating a proxy
public function showInvoice($invoiceId)
{
    $invoice = $teamleader->invoices()->info($invoiceId);
    
    // Only share URL if invoice belongs to current customer
    if ($invoice['data']['customer']['id'] !== Auth::user()->teamleader_id) {
        abort(403);
    }
    
    $cloudUrl = $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);
    
    return view('invoices.view', compact('invoice', 'cloudUrl'));
}
```

## Best Practices

### 1. Use Type-Specific Methods

```php
// Prefer this:
$url = $teamleader->cloudPlatforms()->getInvoiceUrl($id);

// Over this:
$url = $teamleader->cloudPlatforms()->getUrl('invoice', $id);
```

### 2. Generate URLs On-Demand

```php
// Don't store URLs permanently in your database
// Generate them when needed

// Bad:
DB::table('invoices')->update(['cloud_url' => $url]);

// Good:
public function getCloudUrl($invoiceId) {
    return $teamleader->cloudPlatforms()->getInvoiceUrl($invoiceId);
}
```

### 3. Batch Processing for Performance

```php
// When you need multiple URLs, use batchUrls()
$urls = $teamleader->cloudPlatforms()->batchUrls('invoice', $invoiceIds);

// Instead of:
foreach ($invoiceIds as $id) {
    $urls[$id] = $teamleader->cloudPlatforms()->getInvoiceUrl($id);
}
```

### 4. Add Rate Limiting for Batch Operations

```php
use Illuminate\Support\Facades\RateLimiter;

public function batchGenerateUrls(array $invoiceIds)
{
    RateLimiter::attempt(
        'cloud-urls:' . Auth::id(),
        $perMinute = 60,
        function() use ($invoiceIds) {
            return $teamleader->cloudPlatforms()->batchUrls('invoice', $invoiceIds);
        }
    );
}
```

### 5. Handle URLs in Frontend Safely

```blade
{{-- Always open cloud URLs in new tab --}}
<a href="{{ $cloudUrl }}" target="_blank" rel="noopener noreferrer">
    View in Teamleader
</a>

{{-- Or use JavaScript --}}
<button onclick="window.open('{{ $cloudUrl }}', '_blank')">
    Open Invoice
</button>
```

## Important Notes

- Cloud platform URLs are JWT-encoded links to teamleader.cloud
- URLs may be time-limited and expire after a certain period
- Users need appropriate Teamleader permissions to view the resources
- Only three resource types are supported: invoice, quotation, and ticket
- IDs must be valid UUIDs (format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
- The `batchUrls()` method makes multiple API calls, so use with consideration for rate limits
- URLs should generally be generated on-demand rather than stored permanently
- Always validate user permissions before providing cloud URLs

## Integration Examples

### Laravel Livewire Component

```php
use Livewire\Component;

class InvoiceViewer extends Component
{
    public $invoiceId;
    public $cloudUrl;
    
    public function mount($invoiceId)
    {
        $this->invoiceId = $invoiceId;
        $this->loadCloudUrl();
    }
    
    public function loadCloudUrl()
    {
        $this->cloudUrl = app('teamleader')
            ->cloudPlatforms()
            ->getInvoiceUrl($this->invoiceId);
    }
    
    public function render()
    {
        return view('livewire.invoice-viewer');
    }
}
```

### API Endpoint

```php
Route::get('/api/invoices/{id}/cloud-url', function($id) {
    try {
        $url = app('teamleader')
            ->cloudPlatforms()
            ->getInvoiceUrl($id);
            
        return response()->json(['url' => $url]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
});
```

### Custom Artisan Command

```php
use Illuminate\Console\Command;

class GenerateCloudUrls extends Command
{
    protected $signature = 'teamleader:generate-urls {type} {--ids=*}';
    
    public function handle()
    {
        $type = $this->argument('type');
        $ids = $this->option('ids');
        
        $urls = app('teamleader')
            ->cloudPlatforms()
            ->batchUrls($type, $ids);
            
        foreach ($urls as $id => $url) {
            $this->info("{$id}: {$url}");
        }
    }
}
```

## See Also

- [Invoices](../invoicing/invoices.md) - Managing invoices
- [Quotations](../deals/quotations.md) - Managing quotations
- [Tickets](../tickets/tickets.md) - Managing tickets
