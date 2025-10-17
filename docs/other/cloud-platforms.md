# Cloud Platforms

Fetch cloud platform URLs for resources in Teamleader Focus.

## Overview

The Cloud Platforms resource provides methods to generate secure URLs to view resources (invoices, quotations, tickets) in the Teamleader cloud platform. These URLs allow you to redirect users directly to specific resources in the Teamleader interface, useful for deep linking and integrations.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [url()](#url)
    - [invoiceUrl()](#invoiceurl)
    - [quotationUrl()](#quotationurl)
    - [ticketUrl()](#ticketurl)
- [Helper Methods](#helper-methods)
- [Supported Resource Types](#supported-resource-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`cloudPlatforms`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `url()`

Fetch cloud platform URL for a specific resource type and ID.

**Parameters:**
- `type` (string): Resource type (invoice, quotation, or ticket)
- `id` (string): Resource UUID

**Returns:** Array with cloud platform URL

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get URL for invoice
$result = Teamleader::cloudPlatforms()->url('invoice', 'invoice-uuid');
$url = $result['data']['url'];

// Redirect user
return redirect($url);
```

### `invoiceUrl()`

Get cloud platform URL for an invoice.

**Parameters:**
- `invoiceId` (string): Invoice UUID

**Example:**
```php
// Get invoice URL
$result = Teamleader::cloudPlatforms()->invoiceUrl('invoice-uuid');
$url = $result['data']['url'];
```

### `quotationUrl()`

Get cloud platform URL for a quotation.

**Parameters:**
- `quotationId` (string): Quotation UUID

**Example:**
```php
// Get quotation URL
$result = Teamleader::cloudPlatforms()->quotationUrl('quotation-uuid');
$url = $result['data']['url'];
```

### `ticketUrl()`

Get cloud platform URL for a ticket.

**Parameters:**
- `ticketId` (string): Ticket UUID

**Example:**
```php
// Get ticket URL
$result = Teamleader::cloudPlatforms()->ticketUrl('ticket-uuid');
$url = $result['data']['url'];
```

## Helper Methods

### Get URL String Directly

```php
// Get just the URL string (without the full response array)
$url = Teamleader::cloudPlatforms()->getUrl('invoice', 'invoice-uuid');

// Type-specific helpers
$invoiceUrl = Teamleader::cloudPlatforms()->getInvoiceUrl('invoice-uuid');
$quotationUrl = Teamleader::cloudPlatforms()->getQuotationUrl('quotation-uuid');
$ticketUrl = Teamleader::cloudPlatforms()->getTicketUrl('ticket-uuid');
```

### Batch URLs

```php
// Get URLs for multiple resources of the same type
$urls = Teamleader::cloudPlatforms()->batchUrls('invoice', [
    'invoice-uuid-1',
    'invoice-uuid-2',
    'invoice-uuid-3'
]);

// Returns: ['invoice-uuid-1' => 'https://...', 'invoice-uuid-2' => 'https://...', ...]
```

## Supported Resource Types

Valid resource types for cloud platform URLs:

| Type | Description |
|------|-------------|
| `invoice` | Invoice resources |
| `quotation` | Quotation resources |
| `ticket` | Support ticket resources |

## Response Structure

### URL Response

```php
[
    'data' => [
        'url' => 'https://focus.teamleader.eu/invoice_detail.php?id=12345'
    ]
]
```

## Usage Examples

### Redirect to Invoice

```php
// Get invoice URL and redirect
$result = Teamleader::cloudPlatforms()->invoiceUrl('invoice-uuid');
$url = $result['data']['url'];

return redirect($url);

// Or use the helper
$url = Teamleader::cloudPlatforms()->getInvoiceUrl('invoice-uuid');
return redirect($url);
```

### Generate View Links

```php
// Create "View in Teamleader" links
$invoices = Teamleader::invoices()->list();

foreach ($invoices['data'] as $invoice) {
    $url = Teamleader::cloudPlatforms()->getInvoiceUrl($invoice['id']);
    
    echo '<tr>';
    echo "<td>{$invoice['invoice_number']}</td>";
    echo "<td><a href='{$url}' target='_blank'>View in Teamleader</a></td>";
    echo '</tr>';
}
```

### Email Notification with Links

```php
// Send email with link to quotation
$quotation = Teamleader::quotations()->info('quotation-uuid');
$url = Teamleader::cloudPlatforms()->getQuotationUrl($quotation['data']['id']);

$emailBody = "
    <p>Your quotation is ready for review.</p>
    <p><a href='{$url}'>View Quotation</a></p>
";

Mail::send($emailBody);
```

### Support Portal Integration

```php
// Show customer their tickets with links
$tickets = Teamleader::tickets()->forCustomer('company', 'company-uuid');

echo '<h3>Your Support Tickets</h3>';
echo '<ul>';

foreach ($tickets['data'] as $ticket) {
    $url = Teamleader::cloudPlatforms()->getTicketUrl($ticket['id']);
    
    echo '<li>';
    echo "{$ticket['subject']} - ";
    echo "<a href='{$url}' target='_blank'>View Details</a>";
    echo '</li>';
}

echo '</ul>';
```

### Batch URL Generation

```php
// Get URLs for multiple invoices
$invoiceIds = ['uuid1', 'uuid2', 'uuid3'];
$urls = Teamleader::cloudPlatforms()->batchUrls('invoice', $invoiceIds);

foreach ($urls as $id => $url) {
    echo "Invoice {$id}: <a href='{$url}'>View</a><br>";
}
```

### Dashboard Links

```php
// Create dashboard with direct links
function getDashboardData(): array
{
    $recentInvoices = Teamleader::invoices()->list([], ['page_size' => 5]);
    $openTickets = Teamleader::tickets()->list([], ['page_size' => 5]);
    
    $data = [
        'invoices' => [],
        'tickets' => []
    ];
    
    // Add URLs to invoices
    foreach ($recentInvoices['data'] as $invoice) {
        $data['invoices'][] = [
            'id' => $invoice['id'],
            'number' => $invoice['invoice_number'],
            'url' => Teamleader::cloudPlatforms()->getInvoiceUrl($invoice['id'])
        ];
    }
    
    // Add URLs to tickets
    foreach ($openTickets['data'] as $ticket) {
        $data['tickets'][] = [
            'id' => $ticket['id'],
            'subject' => $ticket['subject'],
            'url' => Teamleader::cloudPlatforms()->getTicketUrl($ticket['id'])
        ];
    }
    
    return $data;
}
```

## Common Use Cases

### 1. External Application Integration

```php
class TeamleaderDeepLinks
{
    public function getResourceUrl(string $type, string $id): string
    {
        return Teamleader::cloudPlatforms()->getUrl($type, $id);
    }
    
    public function createViewButton(string $type, string $id, string $label = 'View'): string
    {
        $url = $this->getResourceUrl($type, $id);
        return "<a href='{$url}' target='_blank' class='btn btn-primary'>{$label}</a>";
    }
    
    public function createQuickAccessLinks(array $resources): array
    {
        $links = [];
        
        foreach ($resources as $type => $ids) {
            foreach ($ids as $id) {
                $url = $this->getResourceUrl($type, $id);
                $links[] = [
                    'type' => $type,
                    'id' => $id,
                    'url' => $url
                ];
            }
        }
        
        return $links;
    }
}

// Usage
$deepLinks = new TeamleaderDeepLinks();
$button = $deepLinks->createViewButton('invoice', 'invoice-uuid', 'View Invoice');
```

### 2. Notification System

```php
class TeamleaderNotifications
{
    public function notifyInvoiceCreated(string $invoiceId, string $userEmail): void
    {
        $invoice = Teamleader::invoices()->info($invoiceId);
        $url = Teamleader::cloudPlatforms()->getInvoiceUrl($invoiceId);
        
        $message = "New invoice #{$invoice['data']['invoice_number']} has been created.";
        $link = "<a href='{$url}'>View Invoice</a>";
        
        // Send notification
        $this->sendEmail($userEmail, $message, $link);
    }
    
    public function notifyTicketUpdated(string $ticketId, string $userEmail): void
    {
        $ticket = Teamleader::tickets()->info($ticketId);
        $url = Teamleader::cloudPlatforms()->getTicketUrl($ticketId);
        
        $message = "Ticket '{$ticket['data']['subject']}' has been updated.";
        $link = "<a href='{$url}'>View Ticket</a>";
        
        $this->sendEmail($userEmail, $message, $link);
    }
    
    private function sendEmail(string $to, string $message, string $link): void
    {
        // Email sending logic
    }
}
```

### 3. Customer Portal

```php
class CustomerPortal
{
    public function getCustomerInvoices(string $companyId): array
    {
        $invoices = Teamleader::invoices()->list([
            'customer' => [
                'type' => 'company',
                'id' => $companyId
            ]
        ]);
        
        $invoicesWithUrls = [];
        
        foreach ($invoices['data'] as $invoice) {
            $invoicesWithUrls[] = [
                'id' => $invoice['id'],
                'number' => $invoice['invoice_number'],
                'date' => $invoice['invoice_date'],
                'total' => $invoice['total'],
                'view_url' => Teamleader::cloudPlatforms()->getInvoiceUrl($invoice['id'])
            ];
        }
        
        return $invoicesWithUrls;
    }
    
    public function getCustomerQuotations(string $companyId): array
    {
        $quotations = Teamleader::quotations()->list([
            'customer' => [
                'type' => 'company',
                'id' => $companyId
            ]
        ]);
        
        $quotationsWithUrls = [];
        
        foreach ($quotations['data'] as $quotation) {
            $quotationsWithUrls[] = [
                'id' => $quotation['id'],
                'number' => $quotation['quotation_number'],
                'view_url' => Teamleader::cloudPlatforms()->getQuotationUrl($quotation['id'])
            ];
        }
        
        return $quotationsWithUrls;
    }
}
```

### 4. Reporting Dashboard

```php
function generateReportWithLinks(): array
{
    $report = [
        'recent_invoices' => [],
        'open_quotations' => [],
        'active_tickets' => []
    ];
    
    // Get recent invoices with URLs
    $invoices = Teamleader::invoices()->list([], ['page_size' => 10]);
    foreach ($invoices['data'] as $invoice) {
        $report['recent_invoices'][] = [
            'number' => $invoice['invoice_number'],
            'total' => $invoice['total'],
            'teamleader_url' => Teamleader::cloudPlatforms()->getInvoiceUrl($invoice['id'])
        ];
    }
    
    // Get open quotations with URLs
    $quotations = Teamleader::quotations()->list([], ['page_size' => 10]);
    foreach ($quotations['data'] as $quotation) {
        $report['open_quotations'][] = [
            'number' => $quotation['quotation_number'],
            'teamleader_url' => Teamleader::cloudPlatforms()->getQuotationUrl($quotation['id'])
        ];
    }
    
    // Get active tickets with URLs
    $tickets = Teamleader::tickets()->list();
    foreach ($tickets['data'] as $ticket) {
        $report['active_tickets'][] = [
            'subject' => $ticket['subject'],
            'teamleader_url' => Teamleader::cloudPlatforms()->getTicketUrl($ticket['id'])
        ];
    }
    
    return $report;
}
```

## Best Practices

### 1. Cache URLs When Appropriate

```php
// URLs don't change often, cache them
function getCachedUrl(string $type, string $id): string
{
    $cacheKey = "teamleader_url_{$type}_{$id}";
    
    return Cache::remember($cacheKey, 3600, function() use ($type, $id) {
        return Teamleader::cloudPlatforms()->getUrl($type, $id);
    });
}
```

### 2. Open Links in New Tab

```php
// Always open Teamleader links in new tab
$url = Teamleader::cloudPlatforms()->getInvoiceUrl('invoice-uuid');
echo "<a href='{$url}' target='_blank' rel='noopener'>View Invoice</a>";
```

### 3. Handle Invalid Resource Types

```php
function getSafeUrl(string $type, string $id): ?string
{
    $validTypes = ['invoice', 'quotation', 'ticket'];
    
    if (!in_array($type, $validTypes)) {
        Log::warning('Invalid resource type for cloud platform URL', ['type' => $type]);
        return null;
    }
    
    try {
        return Teamleader::cloudPlatforms()->getUrl($type, $id);
    } catch (Exception $e) {
        Log::error('Failed to get cloud platform URL', [
            'type' => $type,
            'id' => $id,
            'error' => $e->getMessage()
        ]);
        
        return null;
    }
}
```

### 4. Validate IDs Before Getting URLs

```php
function getUrlIfExists(string $type, string $id): ?string
{
    // Verify resource exists first
    try {
        if ($type === 'invoice') {
            Teamleader::invoices()->info($id);
        } elseif ($type === 'quotation') {
            Teamleader::quotations()->info($id);
        } elseif ($type === 'ticket') {
            Teamleader::tickets()->info($id);
        }
        
        // Resource exists, get URL
        return Teamleader::cloudPlatforms()->getUrl($type, $id);
        
    } catch (Exception $e) {
        Log::warning('Resource not found, cannot generate URL', [
            'type' => $type,
            'id' => $id
        ]);
        
        return null;
    }
}
```

### 5. Provide Fallback for Errors

```php
function getUrlWithFallback(string $type, string $id, string $fallbackUrl = '/'): string
{
    try {
        return Teamleader::cloudPlatforms()->getUrl($type, $id);
    } catch (Exception $e) {
        Log::error('Cloud platform URL generation failed', [
            'type' => $type,
            'id' => $id
        ]);
        
        return $fallbackUrl;
    }
}

// Usage
$url = getUrlWithFallback('invoice', 'invoice-uuid', '/invoices');
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $url = Teamleader::cloudPlatforms()->url('invoice', 'invoice-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        // Resource not found
        Log::error('Invoice not found', ['id' => 'invoice-uuid']);
    } elseif ($e->getCode() === 422) {
        // Invalid type
        Log::error('Invalid resource type');
    }
}

// Handle missing URL gracefully
try {
    $url = Teamleader::cloudPlatforms()->getInvoiceUrl('invoice-uuid');
    echo "<a href='{$url}'>View</a>";
} catch (Exception $e) {
    // Don't show link if URL generation fails
    echo "Unable to generate link";
}
```

## Important Notes

### 1. URL Format

The URLs returned are direct links to the Teamleader Focus interface. They require the user to be logged into Teamleader to access.

### 2. Resource Must Exist

You can only generate URLs for resources that exist. The API will return an error if the resource ID is invalid.

### 3. Limited Resource Types

Only invoices, quotations, and tickets support cloud platform URLs. Other resource types are not supported.

### 4. No URL Customization

The generated URLs point to Teamleader's standard interface. You cannot customize the URL structure or destination.

## Related Resources

- [Invoices](../invoicing/invoices.md) - Invoice management
- [Quotations](../deals/quotations.md) - Quotation management
- [Tickets](../tickets/tickets.md) - Ticket management

## See Also

- [Usage Guide](../usage.md) - General SDK usage
