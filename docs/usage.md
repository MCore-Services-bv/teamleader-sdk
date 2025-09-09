# Usage Examples

This guide provides comprehensive examples of using the Teamleader SDK in real-world scenarios, from basic operations to advanced integration patterns.

## Table of Contents

- [Setup and Authentication](#setup-and-authentication)
- [Basic CRUD Operations](#basic-crud-operations)
- [Working with Deals](#working-with-deals)
- [Contact and Company Management](#contact-and-company-management)
- [Project Management](#project-management)
- [Invoice Handling](#invoice-handling)
- [Time Tracking](#time-tracking)
- [Bulk Operations](#bulk-operations)
- [Advanced Integration Patterns](#advanced-integration-patterns)
- [Error Handling](#error-handling)

## Setup and Authentication

### Laravel Controller Setup

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class TeamleaderController extends Controller
{
    public function __construct(
        private TeamleaderSDK $teamleader
    ) {}

    /**
     * Redirect to Teamleader for authorization
     */
    public function authorize()
    {
        $state = Str::random(32);
        session(['teamleader_state' => $state]);
        
        return $this->teamleader->authorize($state);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        
        // Validate state parameter (CSRF protection)
        if ($state !== session('teamleader_state')) {
            return redirect('/auth/error')->with('error', 'Invalid state parameter');
        }

        if ($this->teamleader->handleCallback($code, $state)) {
            session()->forget('teamleader_state');
            return redirect('/dashboard')->with('success', 'Connected to Teamleader!');
        }

        return redirect('/auth/error')->with('error', 'Authentication failed');
    }

    /**
     * Check connection status
     */
    public function status()
    {
        return response()->json([
            'connected' => $this->teamleader->isAuthenticated(),
            'api_version' => $this->teamleader->getApiVersion(),
            'rate_limit' => $this->teamleader->getRateLimitStats(),
            'token_info' => $this->teamleader->getTokenService()->getTokenInfo()
        ]);
    }
}
```

### Service Class Pattern

```php
<?php

namespace App\Services;

use McoreServices\TeamleaderSDK\TeamleaderSDK;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TeamleaderService
{
    public function __construct(
        private TeamleaderSDK $sdk
    ) {}

    /**
     * Ensure we're connected before making requests
     */
    protected function ensureConnected(): void
    {
        if (!$this->sdk->isAuthenticated()) {
            throw new \Exception('Not connected to Teamleader. Please authenticate first.');
        }
    }

    /**
     * Get current user information with caching
     */
    public function getCurrentUser(): array
    {
        $this->ensureConnected();
        
        return Cache::remember('teamleader_current_user', 300, function () {
            $result = $this->sdk->users()->me();
            
            if (isset($result['error'])) {
                Log::error('Failed to get current user', $result);
                throw new \Exception('Failed to get user information: ' . $result['message']);
            }
            
            return $result['data'];
        });
    }
}
```

## Basic CRUD Operations

### Companies

```php
class CompanyService extends TeamleaderService
{
    /**
     * Create a new company
     */
    public function createCompany(array $data): array
    {
        $this->ensureConnected();
        
        $result = $this->sdk->companies()->create([
            'name' => $data['name'],
            'business_type_id' => $data['business_type_id'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
            'national_identification_number' => $data['national_id'] ?? null,
            'emails' => $data['emails'] ?? [],
            'telephones' => $data['phones'] ?? [],
            'website' => $data['website'] ?? null,
            'addresses' => $data['addresses'] ?? [],
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'tags' => $data['tags'] ?? [],
            'custom_fields' => $data['custom_fields'] ?? []
        ]);

        if (isset($result['error'])) {
            throw new \Exception('Failed to create company: ' . $result['message']);
        }

        return $result['data'];
    }

    /**
     * Get company with related data
     */
    public function getCompany(string $id, bool $includeRelated = true): array
    {
        $this->ensureConnected();
        
        $query = $this->sdk->companies();
        
        if ($includeRelated) {
            $query->withResponsibleUser()
                  ->with(['addresses', 'business_type', 'tags']);
        }
        
        $result = $query->info($id);
        
        if (isset($result['error'])) {
            throw new \Exception('Company not found: ' . $result['message']);
        }
        
        return $result;
    }

    /**
     * Update company
     */
    public function updateCompany(string $id, array $data): array
    {
        $this->ensureConnected();
        
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'business_type_id' => $data['business_type_id'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
            'website' => $data['website'] ?? null,
            'emails' => $data['emails'] ?? null,
            'telephones' => $data['phones'] ?? null,
            'addresses' => $data['addresses'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'tags' => $data['tags'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null
        ], function ($value) {
            return $value !== null;
        });

        $result = $this->sdk->companies()->update($id, $updateData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to update company: ' . $result['message']);
        }
        
        return $result;
    }

    /**
     * Search companies with pagination
     */
    public function searchCompanies(
        array $filters = [], 
        int $pageSize = 20, 
        int $page = 1
    ): array {
        $this->ensureConnected();
        
        return $this->sdk->companies()
            ->withResponsibleUser()
            ->list($filters, [
                'page_size' => $pageSize,
                'page_number' => $page,
                'sort' => 'name',
                'sort_order' => 'asc'
            ]);
    }
}
```

### Contacts

```php
class ContactService extends TeamleaderService
{
    /**
     * Create contact with company relationship
     */
    public function createContact(array $data): array
    {
        $this->ensureConnected();
        
        $contactData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'salutation' => $data['salutation'] ?? null,
            'emails' => $data['emails'] ?? [],
            'telephones' => $data['phones'] ?? [],
            'addresses' => $data['addresses'] ?? [],
            'language' => $data['language'] ?? 'en',
            'gender' => $data['gender'] ?? null,
            'birthdate' => $data['birthdate'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'tags' => $data['tags'] ?? [],
            'custom_fields' => $data['custom_fields'] ?? []
        ];

        // Add company relationship if provided
        if (!empty($data['company_id'])) {
            $contactData['company_id'] = $data['company_id'];
        }

        $result = $this->sdk->contacts()->create($contactData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to create contact: ' . $result['message']);
        }
        
        return $result['data'];
    }

    /**
     * Get contacts by company
     */
    public function getContactsByCompany(string $companyId): array
    {
        $this->ensureConnected();
        
        return $this->sdk->contacts()
            ->withCompany()
            ->withResponsibleUser()
            ->list([
                'company_id' => $companyId
            ]);
    }

    /**
     * Import contacts from CSV data
     */
    public function importContacts(array $contactsData): array
    {
        $this->ensureConnected();
        
        $imported = [];
        $failed = [];
        
        foreach ($contactsData as $index => $data) {
            try {
                // Check rate limits
                $stats = $this->sdk->getRateLimitStats();
                if ($stats['remaining'] <= 10) {
                    Log::info('Rate limit approaching, waiting...');
                    sleep($stats['seconds_until_reset'] + 1);
                }
                
                $contact = $this->createContact($data);
                $imported[] = $contact;
                
                Log::info("Imported contact: {$contact['first_name']} {$contact['last_name']}");
                
            } catch (\Exception $e) {
                $failed[] = [
                    'index' => $index,
                    'data' => $data,
                    'error' => $e->getMessage()
                ];
                
                Log::error("Failed to import contact at index {$index}", [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
            }
        }
        
        return [
            'imported' => $imported,
            'failed' => $failed,
            'stats' => [
                'total' => count($contactsData),
                'imported' => count($imported),
                'failed' => count($failed)
            ]
        ];
    }
}
```

## Working with Deals

### Deal Management Service

```php
class DealService extends TeamleaderService
{
    /**
     * Create a deal with full relationship setup
     */
    public function createDeal(array $data): array
    {
        $this->ensureConnected();
        
        $dealData = [
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => 'active',
            'lead' => [
                'customer' => [
                    'type' => $data['customer_type'], // 'contact' or 'company'
                    'id' => $data['customer_id']
                ]
            ],
            'estimated_value' => $data['estimated_value'] ?? null,
            'estimated_probability' => $data['estimated_probability'] ?? null,
            'estimated_closing_date' => $data['estimated_closing_date'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'deal_phase_id' => $data['deal_phase_id'] ?? null,
            'deal_source_id' => $data['deal_source_id'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? []
        ];

        $result = $this->sdk->deals()->create($dealData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to create deal: ' . $result['message']);
        }
        
        return $result['data'];
    }

    /**
     * Get deals for dashboard with complete information
     */
    public function getDashboardDeals(int $limit = 10): array
    {
        $this->ensureConnected();
        
        return $this->sdk->deals()
            ->withCustomer()
            ->withResponsibleUser()
            ->withDepartment()
            ->with(['deal_phase', 'deal_source'])
            ->list([
                'status' => 'active'
            ], [
                'page_size' => $limit,
                'sort' => 'updated_at',
                'sort_order' => 'desc'
            ]);
    }

    /**
     * Move deal through pipeline
     */
    public function moveDealToPhase(string $dealId, string $phaseId): array
    {
        $this->ensureConnected();
        
        $result = $this->sdk->deals()->update($dealId, [
            'deal_phase_id' => $phaseId
        ]);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to move deal: ' . $result['message']);
        }
        
        // Log the phase change
        Log::info("Deal {$dealId} moved to phase {$phaseId}");
        
        return $result;
    }

    /**
     * Win a deal
     */
    public function winDeal(string $dealId, array $data = []): array
    {
        $this->ensureConnected();
        
        $updateData = [
            'status' => 'won',
            'closed_at' => now()->toISOString()
        ];
        
        if (isset($data['final_value'])) {
            $updateData['estimated_value'] = $data['final_value'];
        }
        
        if (isset($data['closing_date'])) {
            $updateData['estimated_closing_date'] = $data['closing_date'];
        }
        
        $result = $this->sdk->deals()->update($dealId, $updateData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to win deal: ' . $result['message']);
        }
        
        Log::info("Deal {$dealId} marked as won", $updateData);
        
        return $result;
    }

    /**
     * Get deal pipeline statistics
     */
    public function getPipelineStats(): array
    {
        $this->ensureConnected();
        
        // Get all active deals with phases
        $deals = $this->sdk->deals()
            ->with('deal_phase')
            ->list(['status' => 'active'], ['page_size' => 1000]);
        
        $stats = [
            'total_deals' => 0,
            'total_value' => 0,
            'phases' => []
        ];
        
        foreach ($deals['data'] ?? [] as $deal) {
            $stats['total_deals']++;
            $stats['total_value'] += $deal['estimated_value'] ?? 0;
            
            $phaseId = $deal['deal_phase_id'] ?? 'unknown';
            
            if (!isset($stats['phases'][$phaseId])) {
                $stats['phases'][$phaseId] = [
                    'count' => 0,
                    'value' => 0,
                    'name' => 'Unknown Phase'
                ];
            }
            
            $stats['phases'][$phaseId]['count']++;
            $stats['phases'][$phaseId]['value'] += $deal['estimated_value'] ?? 0;
            
            // Get phase name from included data if available
            if (isset($deals['included']['deal_phase'])) {
                foreach ($deals['included']['deal_phase'] as $phase) {
                    if ($phase['id'] === $phaseId) {
                        $stats['phases'][$phaseId]['name'] = $phase['name'];
                        break;
                    }
                }
            }
        }
        
        return $stats;
    }
}
```

## Contact and Company Management

### CRM Integration Service

```php
class CrmIntegrationService extends TeamleaderService
{
    /**
     * Sync customer data from external CRM
     */
    public function syncCustomerFromExternal(array $externalData): array
    {
        $this->ensureConnected();
        
        // Check if customer already exists
        $existingCustomer = $this->findExistingCustomer($externalData);
        
        if ($existingCustomer) {
            return $this->updateExistingCustomer($existingCustomer['id'], $externalData);
        } else {
            return $this->createNewCustomer($externalData);
        }
    }

    /**
     * Find existing customer by email or external ID
     */
    private function findExistingCustomer(array $externalData): ?array
    {
        // Search by custom field (external ID)
        if (!empty($externalData['external_id'])) {
            $companies = $this->sdk->companies()->list([
                'custom_fields' => [
                    config('teamleader.custom_fields.company.external_id') => $externalData['external_id']
                ]
            ]);
            
            if (!empty($companies['data'])) {
                return $companies['data'][0];
            }
        }
        
        // Search by email
        if (!empty($externalData['email'])) {
            $companies = $this->sdk->companies()->list([
                'email' => $externalData['email']
            ]);
            
            if (!empty($companies['data'])) {
                return $companies['data'][0];
            }
        }
        
        return null;
    }

    /**
     * Create customer with complete address and contact info
     */
    private function createNewCustomer(array $externalData): array
    {
        $customerData = [
            'name' => $externalData['name'],
            'emails' => $externalData['emails'] ?? [],
            'telephones' => $externalData['phones'] ?? [],
            'website' => $externalData['website'] ?? null,
            'vat_number' => $externalData['vat_number'] ?? null,
            'addresses' => $this->formatAddresses($externalData['addresses'] ?? []),
            'custom_fields' => [
                config('teamleader.custom_fields.company.external_id') => $externalData['external_id'] ?? null
            ]
        ];

        $result = $this->sdk->companies()->create($customerData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to create customer: ' . $result['message']);
        }
        
        // Create primary contact if provided
        if (!empty($externalData['primary_contact'])) {
            $this->createPrimaryContact($result['data']['id'], $externalData['primary_contact']);
        }
        
        return $result['data'];
    }

    /**
     * Format addresses for Teamleader API
     */
    private function formatAddresses(array $addresses): array
    {
        return array_map(function ($address) {
            return [
                'type' => $address['type'] ?? 'invoicing',
                'address_line_1' => $address['street'] ?? '',
                'address_line_2' => $address['street_2'] ?? null,
                'postal_code' => $address['postal_code'] ?? '',
                'city' => $address['city'] ?? '',
                'country' => $address['country'] ?? 'BE'
            ];
        }, $addresses);
    }

    /**
     * Create primary contact for company
     */
    private function createPrimaryContact(string $companyId, array $contactData): array
    {
        $result = $this->sdk->contacts()->create([
            'company_id' => $companyId,
            'first_name' => $contactData['first_name'],
            'last_name' => $contactData['last_name'],
            'emails' => $contactData['emails'] ?? [],
            'telephones' => $contactData['phones'] ?? []
        ]);
        
        if (isset($result['error'])) {
            Log::warning('Failed to create primary contact', $result);
        }
        
        return $result['data'] ?? [];
    }

    /**
     * Get complete customer view with all relationships
     */
    public function getCustomerOverview(string $customerId): array
    {
        $this->ensureConnected();
        
        // Get company with all related data
        $company = $this->sdk->companies()
            ->withResponsibleUser()
            ->with(['addresses', 'business_type', 'tags'])
            ->info($customerId);
        
        // Get all contacts for this company
        $contacts = $this->sdk->contacts()
            ->withResponsibleUser()
            ->list(['company_id' => $customerId]);
        
        // Get all deals for this customer
        $deals = $this->sdk->deals()
            ->withResponsibleUser()
            ->with('deal_phase')
            ->list([
                'customer_id' => $customerId
            ]);
        
        // Get recent invoices
        $invoices = $this->sdk->invoices()
            ->list([
                'customer_id' => $customerId
            ], [
                'page_size' => 10,
                'sort' => 'invoice_date',
                'sort_order' => 'desc'
            ]);
        
        // Get active projects
        $projects = $this->sdk->projects()
            ->withResponsibleUser()
            ->list([
                'customer_id' => $customerId,
                'status' => 'active'
            ]);
        
        return [
            'company' => $company,
            'contacts' => $contacts['data'] ?? [],
            'deals' => $deals['data'] ?? [],
            'invoices' => $invoices['data'] ?? [],
            'projects' => $projects['data'] ?? [],
            'summary' => [
                'total_contacts' => count($contacts['data'] ?? []),
                'active_deals' => count($deals['data'] ?? []),
                'total_deal_value' => array_sum(array_column($deals['data'] ?? [], 'estimated_value')),
                'recent_invoices' => count($invoices['data'] ?? []),
                'active_projects' => count($projects['data'] ?? [])
            ]
        ];
    }
}
```

## Project Management

### Project Service

```php
class ProjectService extends TeamleaderService
{
    /**
     * Create project with complete setup
     */
    public function createProject(array $data): array
    {
        $this->ensureConnected();
        
        $projectData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'active',
            'customer' => [
                'type' => $data['customer_type'],
                'id' => $data['customer_id']
            ],
            'starts_at' => $data['start_date'] ?? null,
            'ends_at' => $data['end_date'] ?? null,
            'budget' => $data['budget'] ?? null,
            'purchase_order_number' => $data['po_number'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? []
        ];

        $result = $this->sdk->projects()->create($projectData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to create project: ' . $result['message']);
        }
        
        $project = $result['data'];
        
        // Create initial milestones if provided
        if (!empty($data['milestones'])) {
            $this->createProjectMilestones($project['id'], $data['milestones']);
        }
        
        return $project;
    }

    /**
     * Get project dashboard with time tracking summary
     */
    public function getProjectDashboard(string $projectId): array
    {
        $this->ensureConnected();
        
        // Get project with full details
        $project = $this->sdk->projects()
            ->withCustomer()
            ->withResponsibleUser()
            ->withDepartment()
            ->info($projectId);
        
        // Get project tasks
        $tasks = $this->sdk->projectTasks()
            ->with('assignee')
            ->list(['project_id' => $projectId]);
        
        // Get time tracking entries
        $timeEntries = $this->sdk->timeTracking()
            ->with(['user', 'work_type'])
            ->list([
                'project_id' => $projectId,
                'started_after' => now()->subDays(30)->toISOString()
            ]);
        
        // Calculate time summary
        $totalHours = 0;
        $userHours = [];
        
        foreach ($timeEntries['data'] ?? [] as $entry) {
            $duration = $entry['duration'] ?? 0; // In seconds
            $hours = $duration / 3600;
            $totalHours += $hours;
            
            $userId = $entry['user_id'] ?? 'unknown';
            $userHours[$userId] = ($userHours[$userId] ?? 0) + $hours;
        }
        
        // Task summary
        $taskSummary = [
            'total' => count($tasks['data'] ?? []),
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0
        ];
        
        foreach ($tasks['data'] ?? [] as $task) {
            $status = $task['status'] ?? 'pending';
            if (isset($taskSummary[$status])) {
                $taskSummary[$status]++;
            }
        }
        
        return [
            'project' => $project,
            'tasks' => $tasks['data'] ?? [],
            'time_entries' => $timeEntries['data'] ?? [],
            'summary' => [
                'total_hours' => round($totalHours, 2),
                'user_hours' => $userHours,
                'tasks' => $taskSummary,
                'budget_used' => $project['data']['budget'] ? 
                    ($totalHours * 75) / $project['data']['budget'] * 100 : 0 // Assuming €75/hour
            ]
        ];
    }

    /**
     * Track time on project
     */
    public function trackTime(string $projectId, array $timeData): array
    {
        $this->ensureConnected();
        
        $result = $this->sdk->timeTracking()->create([
            'started_at' => $timeData['started_at'],
            'ended_at' => $timeData['ended_at'],
            'duration' => $timeData['duration'],
            'description' => $timeData['description'] ?? null,
            'project_id' => $projectId,
            'user_id' => $timeData['user_id'] ?? null,
            'work_type_id' => $timeData['work_type_id'] ?? null
        ]);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to track time: ' . $result['message']);
        }
        
        return $result['data'];
    }
}
```

## Invoice Handling

### Invoice Service

```php
class InvoiceService extends TeamleaderService
{
    /**
     * Create invoice from deal
     */
    public function createInvoiceFromDeal(string $dealId, array $options = []): array
    {
        $this->ensureConnected();
        
        // Get deal information
        $deal = $this->sdk->deals()
            ->withCustomer()
            ->info($dealId);
        
        if (isset($deal['error'])) {
            throw new \Exception('Deal not found: ' . $deal['message']);
        }
        
        $customer = $deal['data']['lead']['customer'];
        
        $invoiceData = [
            'customer' => [
                'type' => $customer['type'],
                'id' => $customer['id']
            ],
            'invoice_date' => $options['invoice_date'] ?? now()->toDateString(),
            'due_date' => $options['due_date'] ?? now()->addDays(30)->toDateString(),
            'reference' => $options['reference'] ?? "Deal: {$deal['data']['title']}",
            'department_id' => $deal['data']['department_id'] ?? null,
            'payment_term_id' => $options['payment_term_id'] ?? null,
            'grouped_lines' => [
                [
                    'section' => [
                        'title' => $deal['data']['title']
                    ],
                    'line_items' => [
                        [
                            'quantity' => 1,
                            'description' => $deal['data']['summary'] ?? $deal['data']['title'],
                            'unit_price' => [
                                'amount' => $deal['data']['estimated_value'] ?? 0,
                                'currency' => 'EUR'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->sdk->invoices()->create($invoiceData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to create invoice: ' . $result['message']);
        }
        
        return $result['data'];
    }

    /**
     * Generate monthly recurring invoices
     */
    public function generateRecurringInvoices(): array
    {
        $this->ensureConnected();
        
        // Get active subscriptions
        $subscriptions = $this->sdk->subscriptions()
            ->withCustomer()
            ->list(['status' => 'active']);
        
        $generatedInvoices = [];
        $errors = [];
        
        foreach ($subscriptions['data'] ?? [] as $subscription) {
            try {
                // Check if invoice already exists for current month
                $existingInvoices = $this->sdk->invoices()->list([
                    'customer_id' => $subscription['customer']['id'],
                    'subscription_id' => $subscription['id'],
                    'invoice_date_after' => now()->startOfMonth()->toDateString()
                ]);
                
                if (!empty($existingInvoices['data'])) {
                    continue; // Skip if already invoiced this month
                }
                
                // Create recurring invoice
                $invoice = $this->createRecurringInvoice($subscription);
                $generatedInvoices[] = $invoice;
                
            } catch (\Exception $e) {
                $errors[] = [
                    'subscription_id' => $subscription['id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'generated' => $generatedInvoices,
            'errors' => $errors,
            'summary' => [
                'total_subscriptions' => count($subscriptions['data'] ?? []),
                'invoices_generated' => count($generatedInvoices),
                'errors' => count($errors)
            ]
        ];
    }

    /**
     * Create invoice from subscription
     */
    private function createRecurringInvoice(array $subscription): array
    {
        $invoiceData = [
            'customer' => $subscription['customer'],
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'reference' => "Subscription: {$subscription['title']}",
            'subscription_id' => $subscription['id'],
            'grouped_lines' => [
                [
                    'section' => [
                        'title' => $subscription['title']
                    ],
                    'line_items' => $subscription['line_items'] ?? []
                ]
            ]
        ];

        $result = $this->sdk->invoices()->create($invoiceData);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to create recurring invoice: ' . $result['message']);
        }
        
        return $result['data'];
    }

    /**
     * Get overdue invoices with customer information
     */
    public function getOverdueInvoices(): array
    {
        $this->ensureConnected();
        
        $overdueInvoices = $this->sdk->invoices()
            ->with('customer')
            ->list([
                'due_date_before' => now()->toDateString(),
                'status' => 'sent'
            ], [
                'sort' => 'due_date',
                'sort_order' => 'asc'
            ]);
        
        // Calculate days overdue and organize by urgency
        $organized = [
            'critical' => [], // 30+ days overdue
            'urgent' => [],   // 15-29 days overdue
            'warning' => []   // 1-14 days overdue
        ];
        
        foreach ($overdueInvoices['data'] ?? [] as $invoice) {
            $dueDate = \Carbon\Carbon::parse($invoice['due_date']);
            $daysOverdue = now()->diffInDays($dueDate);
            
            $invoice['days_overdue'] = $daysOverdue;
            
            if ($daysOverdue >= 30) {
                $organized['critical'][] = $invoice;
            } elseif ($daysOverdue >= 15) {
                $organized['urgent'][] = $invoice;
            } else {
                $organized['warning'][] = $invoice;
            }
        }
        
        return [
            'invoices' => $organized,
            'summary' => [
                'total_overdue' => count($overdueInvoices['data'] ?? []),
                'critical' => count($organized['critical']),
                'urgent' => count($organized['urgent']),
                'warning' => count($organized['warning']),
                'total_amount' => array_sum(array_column($overdueInvoices['data'] ?? [], 'total'))
            ]
        ];
    }
}
```

## Time Tracking

### Time Tracking Service

```php
class TimeTrackingService extends TeamleaderService
{
    /**
     * Start timer for project
     */
    public function startTimer(string $projectId, array $data = []): array
    {
        $this->ensureConnected();
        
        // Stop any existing timers for this user
        $this->stopActiveTimers($data['user_id'] ?? null);
        
        $result = $this->sdk->timers()->start([
            'started_at' => now()->toISOString(),
            'description' => $data['description'] ?? null,
            'project_id' => $projectId,
            'user_id' => $data['user_id'] ?? null,
            'work_type_id' => $data['work_type_id'] ?? null
        ]);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to start timer: ' . $result['message']);
        }
        
        return $result['data'];
    }

    /**
     * Stop timer and create time entry
     */
    public function stopTimer(string $timerId, array $data = []): array
    {
        $this->ensureConnected();
        
        $result = $this->sdk->timers()->stop($timerId, [
            'ended_at' => now()->toISOString(),
            'description' => $data['description'] ?? null
        ]);
        
        if (isset($result['error'])) {
            throw new \Exception('Failed to stop timer: ' . $result['message']);
        }
        
        return $result['data'];
    }

    /**
     * Get time tracking report for period
     */
    public function getTimeReport(
        string $startDate, 
        string $endDate, 
        array $filters = []
    ): array {
        $this->ensureConnected();
        
        $queryFilters = array_merge([
            'started_after' => $startDate,
            'started_before' => $endDate
        ], $filters);
        
        $timeEntries = $this->sdk->timeTracking()
            ->with(['user', 'project', 'work_type'])
            ->list($queryFilters, ['page_size' => 1000]);
        
        // Organize by user and project
        $report = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'by_user' => [],
            'by_project' => [],
            'by_work_type' => [],
            'summary' => [
                'total_hours' => 0,
                'total_entries' => 0,
                'billable_hours' => 0
            ]
        ];
        
        foreach ($timeEntries['data'] ?? [] as $entry) {
            $hours = ($entry['duration'] ?? 0) / 3600;
            $userId = $entry['user_id'] ?? 'unknown';
            $projectId = $entry['project_id'] ?? 'unknown';
            $workTypeId = $entry['work_type_id'] ?? 'unknown';
            
            // By user
            if (!isset($report['by_user'][$userId])) {
                $report['by_user'][$userId] = [
                    'hours' => 0,
                    'entries' => 0,
                    'user_name' => 'Unknown'
                ];
            }
            $report['by_user'][$userId]['hours'] += $hours;
            $report['by_user'][$userId]['entries']++;
            
            // By project
            if (!isset($report['by_project'][$projectId])) {
                $report['by_project'][$projectId] = [
                    'hours' => 0,
                    'entries' => 0,
                    'project_name' => 'Unknown'
                ];
            }
            $report['by_project'][$projectId]['hours'] += $hours;
            $report['by_project'][$projectId]['entries']++;
            
            // By work type
            if (!isset($report['by_work_type'][$workTypeId])) {
                $report['by_work_type'][$workTypeId] = [
                    'hours' => 0,
                    'entries' => 0,
                    'work_type_name' => 'Unknown'
                ];
            }
            $report['by_work_type'][$workTypeId]['hours'] += $hours;
            $report['by_work_type'][$workTypeId]['entries']++;
            
            // Totals
            $report['summary']['total_hours'] += $hours;
            $report['summary']['total_entries']++;
            
            if ($entry['billable'] ?? false) {
                $report['summary']['billable_hours'] += $hours;
            }
        }
        
        // Add names from included data
        $this->enrichReportWithNames($report, $timeEntries['included'] ?? []);
        
        return $report;
    }

    /**
     * Stop all active timers for user
     */
    private function stopActiveTimers(?string $userId = null): void
    {
        $filters = $userId ? ['user_id' => $userId] : [];
        
        $activeTimers = $this->sdk->timers()->list($filters);
        
        foreach ($activeTimers['data'] ?? [] as $timer) {
            if ($timer['status'] === 'active') {
                $this->stopTimer($timer['id']);
            }
        }
    }

    /**
     * Enrich report with names from included data
     */
    private function enrichReportWithNames(array &$report, array $included): void
    {
        // Add user names
        foreach ($included['user'] ?? [] as $user) {
            $userId = $user['id'];
            if (isset($report['by_user'][$userId])) {
                $report['by_user'][$userId]['user_name'] = 
                    ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            }
        }
        
        // Add project names
        foreach ($included['project'] ?? [] as $project) {
            $projectId = $project['id'];
            if (isset($report['by_project'][$projectId])) {
                $report['by_project'][$projectId]['project_name'] = $project['title'] ?? 'Unknown';
            }
        }
        
        // Add work type names
        foreach ($included['work_type'] ?? [] as $workType) {
            $workTypeId = $workType['id'];
            if (isset($report['by_work_type'][$workTypeId])) {
                $report['by_work_type'][$workTypeId]['work_type_name'] = $workType['name'] ?? 'Unknown';
            }
        }
    }
}
```

## Bulk Operations

### Bulk Import Service

```php
class BulkImportService extends TeamleaderService
{
    /**
     * Import data with rate limit awareness and error handling
     */
    public function bulkImport(string $resourceType, array $data, array $options = []): array
    {
        $this->ensureConnected();
        
        $batchSize = $options['batch_size'] ?? 10;
        $delayBetweenBatches = $options['delay'] ?? 1; // seconds
        
        $results = [
            'successful' => [],
            'failed' => [],
            'statistics' => [
                'total' => count($data),
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'start_time' => now(),
                'batches_processed' => 0
            ]
        ];
        
        $batches = array_chunk($data, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            Log::info("Processing batch " . ($batchIndex + 1) . " of " . count($batches));
            
            // Check rate limits before processing batch
            $this->checkRateLimits();
            
            foreach ($batch as $index => $item) {
                try {
                    $result = $this->importSingleItem($resourceType, $item);
                    
                    $results['successful'][] = [
                        'index' => $index,
                        'data' => $result,
                        'original' => $item
                    ];
                    
                    $results['statistics']['successful']++;
                    
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'original' => $item
                    ];
                    
                    $results['statistics']['failed']++;
                    
                    Log::error("Bulk import error at index {$index}", [
                        'error' => $e->getMessage(),
                        'data' => $item
                    ]);
                }
                
                $results['statistics']['processed']++;
            }
            
            $results['statistics']['batches_processed']++;
            
            // Delay between batches to avoid rate limits
            if ($batchIndex < count($batches) - 1) {
                sleep($delayBetweenBatches);
            }
        }
        
        $results['statistics']['end_time'] = now();
        $results['statistics']['duration'] = $results['statistics']['start_time']
            ->diffInSeconds($results['statistics']['end_time']);
        
        return $results;
    }

    /**
     * Import single item based on resource type
     */
    private function importSingleItem(string $resourceType, array $data): array
    {
        switch ($resourceType) {
            case 'companies':
                return $this->sdk->companies()->create($data);
            
            case 'contacts':
                return $this->sdk->contacts()->create($data);
            
            case 'deals':
                return $this->sdk->deals()->create($data);
            
            default:
                throw new \Exception("Unsupported resource type: {$resourceType}");
        }
    }

    /**
     * Check and handle rate limits
     */
    private function checkRateLimits(): void
    {
        $stats = $this->sdk->getRateLimitStats();
        
        if ($stats['remaining'] <= 10) {
            $waitTime = $stats['seconds_until_reset'] + 1;
            Log::info("Rate limit approaching, waiting {$waitTime} seconds", $stats);
            sleep($waitTime);
        }
    }

    /**
     * Export data with relationships
     */
    public function exportData(string $resourceType, array $filters = []): array
    {
        $this->ensureConnected();
        
        $allData = [];
        $page = 1;
        $pageSize = 100;
        
        do {
            $result = $this->getResourceData($resourceType, $filters, $page, $pageSize);
            
            $data = $result['data'] ?? [];
            $allData = array_merge($allData, $data);
            
            Log::info("Exported page {$page}, got " . count($data) . " items");
            
            $hasMore = count($data) === $pageSize;
            $page++;
            
            // Respect rate limits during export
            $this->checkRateLimits();
            
        } while ($hasMore && $page <= 50); // Safety limit
        
        return [
            'data' => $allData,
            'meta' => [
                'total_exported' => count($allData),
                'resource_type' => $resourceType,
                'filters_applied' => $filters,
                'exported_at' => now()->toISOString()
            ]
        ];
    }

    /**
     * Get resource data with appropriate includes
     */
    private function getResourceData(string $resourceType, array $filters, int $page, int $pageSize): array
    {
        $options = [
            'page_number' => $page,
            'page_size' => $pageSize
        ];
        
        switch ($resourceType) {
            case 'companies':
                return $this->sdk->companies()
                    ->withResponsibleUser()
                    ->with(['addresses', 'business_type'])
                    ->list($filters, $options);
            
            case 'contacts':
                return $this->sdk->contacts()
                    ->withCompany()
                    ->withResponsibleUser()
                    ->list($filters, $options);
            
            case 'deals':
                return $this->sdk->deals()
                    ->withCustomer()
                    ->withResponsibleUser()
                    ->with(['deal_phase', 'deal_source'])
                    ->list($filters, $options);
            
            default:
                throw new \Exception("Unsupported export resource: {$resourceType}");
        }
    }
}
```

## Advanced Integration Patterns

### Webhook Handler Service

```php
class WebhookHandlerService extends TeamleaderService
{
    /**
     * Process webhook payload
     */
    public function processWebhook(array $payload): void
    {
        $eventType = $payload['type'] ?? null;
        $eventData = $payload['data'] ?? [];
        
        Log::info("Processing Teamleader webhook", [
            'event_type' => $eventType,
            'resource_id' => $eventData['id'] ?? null
        ]);
        
        switch ($eventType) {
            case 'deal.won':
                $this->handleDealWon($eventData);
                break;
            
            case 'invoice.paid':
                $this->handleInvoicePaid($eventData);
                break;
            
            case 'contact.created':
                $this->handleContactCreated($eventData);
                break;
            
            case 'project.completed':
                $this->handleProjectCompleted($eventData);
                break;
            
            default:
                Log::info("Unhandled webhook event type: {$eventType}");
        }
    }

    /**
     * Handle deal won webhook
     */
    private function handleDealWon(array $dealData): void
    {
        // Sync deal data to local database
        $this->syncDealToLocal($dealData['id']);
        
        // Trigger celebration email
        $this->sendDealWonNotification($dealData);
        
        // Create follow-up tasks
        $this->createPostSaleTasks($dealData);
    }

    /**
     * Handle invoice paid webhook
     */
    private function handleInvoicePaid(array $invoiceData): void
    {
        // Update local records
        $this->updateInvoiceStatus($invoiceData['id'], 'paid');
        
        // Send thank you email
        $this->sendPaymentConfirmation($invoiceData);
        
        // Update customer credit limit if applicable
        $this->updateCustomerCredit($invoiceData['customer_id']);
    }

    /**
     * Sync deal to local database
     */
    private function syncDealToLocal(string $dealId): void
    {
        $deal = $this->sdk->deals()
            ->withCustomer()
            ->withResponsibleUser()
            ->info($dealId);
        
        if (isset($deal['error'])) {
            Log::error("Failed to sync deal {$dealId}: " . $deal['message']);
            return;
        }
        
        // Update local database
        \DB::table('local_deals')->updateOrInsert(
            ['teamleader_id' => $dealId],
            [
                'title' => $deal['data']['title'],
                'value' => $deal['data']['estimated_value'],
                'status' => $deal['data']['status'],
                'customer_name' => $this->getCustomerName($deal),
                'updated_at' => now()
            ]
        );
    }
}
```

## Error Handling

### Built-in Error Handler

The SDK includes a comprehensive error handling system that automatically handles all API errors, retry logic, and exception management. You can configure how errors are handled through the configuration file:

```php
// In config/teamleader.php
'error_handling' => [
    'throw_exceptions' => false,  // Set to true to throw exceptions instead of returning error arrays
    'log_errors' => true,        // Log all errors
    'include_stack_trace' => false,
    'parse_teamleader_errors' => true,
],
```

### Exception Mode vs Array Mode

#### Array Mode (Default)
```php
$result = $teamleader->contacts()->create($data);

if (isset($result['error'])) {
    // Handle error
    $errors = $result['errors'] ?? [$result['message']];
    $userMessage = $result['user_message'] ?? $result['message'];
    $isRetryable = $result['retryable'] ?? false;
    
    foreach ($errors as $error) {
        Log::error("Contact creation failed: " . $error);
    }
}
```

#### Exception Mode
```php
use McoreServices\TeamleaderSDK\Exceptions\{
    ValidationException,
    RateLimitExceededException,
    AuthenticationException
};

// Enable exceptions for this instance
$teamleader->throwExceptions(true);

try {
    $contact = $teamleader->contacts()->create($data);
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->getAllErrors();
    $userMessage = $teamleader->getErrorHandler()->getUserFriendlyMessage($e);
} catch (RateLimitExceededException $e) {
    // Handle rate limit
    $retryAfter = $e->getRetryAfter();
    $resetTime = $e->getResetTime();
} catch (AuthenticationException $e) {
    // Handle auth failure - redirect to login
    return redirect('/auth/teamleader');
}
```

### Available Exception Types

- `AuthenticationException` - 401 errors, token issues
- `AuthorizationException` - 403 errors, permission denied
- `NotFoundException` - 404 errors, resource not found
- `ValidationException` - 422 errors, invalid data
- `RateLimitExceededException` - 429 errors, rate limit exceeded
- `ServerException` - 5xx errors, server issues
- `ConnectionException` - Network/connection problems
- `ConfigurationException` - SDK configuration issues

### Automatic Retry Logic

The SDK automatically retries transient errors (server errors, rate limits, connection issues) with exponential backoff:

```php
// This will automatically retry on transient errors
$deals = $teamleader->deals()->list();

// You can also use the retry logic directly
$result = $teamleader->getErrorHandler()->withRetry(function () use ($teamleader) {
    return $teamleader->deals()->info('some-deal-id');
}, 3); // 3 attempts
```

### Custom Error Handling

```php
class CustomTeamleaderService extends TeamleaderService
{
    protected function handleTeamleaderError(\Exception $e): void
    {
        $errorHandler = $this->sdk->getErrorHandler();
        
        if ($errorHandler->isRetryableError($e)) {
            // Queue for retry later
            dispatch(new RetryTeamleaderRequestJob($e->getContext()));
        } else {
            // Log and notify admin
            Log::error('Non-retryable Teamleader error', [
                'error' => $e->getMessage(),
                'user_message' => $errorHandler->getUserFriendlyMessage($e)
            ]);
        }
    }
}
```

This comprehensive usage documentation covers real-world scenarios and best practices for using the enhanced Teamleader SDK. The examples demonstrate proper error handling, rate limit awareness, and efficient use of sideloading to minimize API calls while maximizing functionality.
