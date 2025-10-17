<?php

namespace McoreServices\TeamleaderSDK;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Exceptions\ConfigurationException;
use McoreServices\TeamleaderSDK\Traits\SanitizesLogData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TeamleaderSDK
{
    use SanitizesLogData;
    protected static $apiCallCount = 0;
    protected static $apiCalls = [];
    protected $client;
    protected $accessToken;
    protected $baseUrl = 'https://api.focus.teamleader.eu';
    protected $authUrl = 'https://focus.teamleader.eu';
    protected string $apiVersion;
    protected $resources = [
        // General
        'departments' => Resources\General\Departments::class,
        'users' => Resources\General\Users::class,
        'teams' => Resources\General\Teams::class,
        'customFields' => Resources\General\CustomFields::class,
        'workTypes' => Resources\General\WorkTypes::class,
        'documentTemplates' => Resources\General\DocumentTemplates::class,
        'currencies' => Resources\General\Currencies::class,
        'notes' => Resources\General\Notes::class,
        'emailTracking' => Resources\General\EmailTracking::class,
        'closingDays' => Resources\General\ClosingDays::class,
        'dayOffTypes' => Resources\General\DayOffTypes::class,
        'daysOff' => Resources\General\DaysOff::class,

        // CRM
        'companies' => Resources\CRM\Companies::class,
        'contacts' => Resources\CRM\Contacts::class,
        'businessTypes' => Resources\CRM\BusinessTypes::class,
        'tags' => Resources\CRM\Tags::class,
        'addresses' => Resources\CRM\Addresses::class,

        // Deals
        'deals' => Resources\Deals\Deals::class,
        'quotations' => Resources\Deals\Quotations::class,
        'orders' => Resources\Deals\Orders::class,
        'dealPhases' => Resources\Deals\Phases::class,
        'dealPipelines' => Resources\Deals\Pipelines::class,
        'dealSources' => Resources\Deals\Sources::class,
        'lostReasons' => Resources\Deals\LostReasons::class,

        // Calendar
        'meetings' => Resources\Calendar\Meetings::class,
        'calls' => Resources\Calendar\Calls::class,
        'callOutcomes' => Resources\Calendar\CallOutcomes::class,
        'calenderEvents' => Resources\Calendar\Events::class,
        'activityTypes' => Resources\Calendar\ActivityTypes::class,

        // Invoicing
        'invoices' => Resources\Invoicing\Invoices::class,
        'creditnotes' => Resources\Invoicing\Creditnotes::class,
        'payment_methods' => Resources\Invoicing\PaymentMethods::class,
        'payment_terms' => Resources\Invoicing\PaymentTerms::class,
        'subscriptions' => Resources\Invoicing\Subscriptions::class,
        'taxRates' => Resources\Invoicing\TaxRates::class,
        'withholdingTaxRates' => Resources\Invoicing\WithholdingTaxRates::class,
        'commercialDiscounts' => Resources\Invoicing\CommercialDiscounts::class,

        // Expenses
        'expenses' => Resources\Expenses\Expenses::class,
        'bookkeepingSubmissions' => Resources\Expenses\BookkeepingSubmissions::class,
        'incomingInvoices' => Resources\Expenses\IncomingInvoices::class,
        'incomingCreditNotes' => Resources\Expenses\IncomingCreditNotes::class,
        'receipts' => Resources\Expenses\Receipts::class,

        // Products
        'priceLists' => Resources\Products\PriceLists::class,
        'productCategories' => Resources\Products\Categories::class,
        'products' => Resources\Products\Products::class,
        'unitsOfMeasure' => Resources\Products\UnitOfMeasure::class,

        // Legacy Projects
        'legacyMilestones' => Resources\Projects\LegacyMilestones::class,
        'legacyProjects' => Resources\Projects\LegacyProjects::class,

       // New Projects
        'external_parties' => Resources\Projects\ExternalParties::class,
        'groups' => Resources\Projects\Groups::class,
        'materials' => Resources\Projects\Materials::class,
        'projectLines' => Resources\Projects\ProjectLines::class,
        'projects' => Resources\Projects\Projects::class,
        'projectTasks' => Resources\Projects\ProjectTasks::class,

        // Tasks
        'tasks' => Resources\Tasks\Tasks::class,

        // Time Tracking
        'timeTracking' => Resources\TimeTracking\TimeTracking::class,
        'timers' => Resources\TimeTracking\Timers::class,

        // Tickets
        'ticketStatus' => Resources\Tickets\TicketStatus::class,
        'tickets' => Resources\Tickets\Tickets::class,

        // Files
        'files' => Resources\Files\Files::class,

        // Templates
        'mailTemplates' => Resources\Templates\MailTemplates::class,

        // Other
        'migrate' => Resources\Other\Migrate::class,
        'webhooks' => Resources\Other\Webhooks::class,
        'cloudPlatforms' => Resources\Other\CloudPlatforms::class,
        'accounts' => Resources\Other\Accounts::class,
    ];
    protected $resourceInstances = [];
    private TokenService $tokenService;
    private ApiRateLimiterService $rateLimiter;
    private LoggerInterface $logger;
    private TeamleaderErrorHandler $errorHandler;

    // Add flag to track manual token override
    private bool $manualTokenSet = false;

    public function __construct(
        TokenService           $tokenService = null,
        ApiRateLimiterService  $rateLimiter = null,
        LoggerInterface        $logger = null,
        TeamleaderErrorHandler $errorHandler = null
    )
    {
        $this->validateConfiguration();

        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
            'timeout' => config('teamleader.api.timeout', 30),
            'connect_timeout' => config('teamleader.api.connect_timeout', 10),
            'read_timeout' => config('teamleader.api.read_timeout', 25),
        ]);

        // Use dependency injection or create instances
        $this->tokenService = $tokenService ?: (app()->bound(TokenService::class) ? app(TokenService::class) : new TokenService());
        $this->rateLimiter = $rateLimiter ?: (app()->bound(ApiRateLimiterService::class) ? app(ApiRateLimiterService::class) : new ApiRateLimiterService());
        $this->logger = $logger ?: (app()->bound(LoggerInterface::class) ? app(LoggerInterface::class) : new NullLogger());
        $this->errorHandler = $errorHandler ?: new TeamleaderErrorHandler($this->logger);

        // Set API version from config
        $this->apiVersion = config('teamleader.api_version', '2023-09-26');

        // Get initial token from TokenService
        $this->accessToken = $this->tokenService->getValidAccessToken();

        if (!empty($this->accessToken)) {
            $this->logger->debug('TeamleaderSDK initialized with valid access token');
        } else {
            $this->logger->debug('TeamleaderSDK initialized without access token');
        }
    }

    /**
     * Validate SDK configuration
     */
    private function validateConfiguration(): void
    {
        $requiredConfig = ['client_id', 'client_secret', 'redirect_uri'];

        foreach ($requiredConfig as $key) {
            if (empty(config("teamleader.{$key}"))) {
                throw new ConfigurationException("Missing required configuration: teamleader.{$key}");
            }
        }
    }

    public static function getApiCallCount()
    {
        return self::$apiCallCount;
    }

    public static function getApiCalls()
    {
        return self::$apiCalls;
    }

    public static function resetApiCallStats()
    {
        self::$apiCallCount = 0;
        self::$apiCalls = [];
    }

    /**
     * Get the current API version
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * Set the API version to use for requests
     */
    public function setApiVersion(string $version): self
    {
        $this->apiVersion = $version;

        $this->logger->debug('TeamleaderSDK API version updated', [
            'version' => $version
        ]);

        return $this;
    }

    /**
     * Redirect to Teamleader authorization page
     */
    public function authorize(string $state = null): RedirectResponse
    {
        return redirect($this->getAuthorizationUrl($state));
    }

    /**
     * Generate authorization URL for OAuth 2 flow
     */
    public function getAuthorizationUrl(string $state = null): string
    {
        $params = [
            'client_id' => config('teamleader.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('teamleader.redirect_uri'),
        ];

        if ($state) {
            $params['state'] = $state;
        }

        $url = $this->authUrl . '/oauth2/authorize?' . http_build_query($params);

        $this->logger->debug('Generated authorization URL', $this->sanitizeForLog([
            'state' => $state ? 'present' : 'none',
            'redirect_uri' => config('teamleader.redirect_uri')
        ]));

        return $url;
    }

    /**
     * Handle OAuth 2 callback and exchange authorization code for tokens
     */
    public function handleCallback(string $code, string $state = null): bool
    {
        return $this->errorHandler->withRetry(function () use ($code, $state) {
            $this->logger->info('Handling OAuth callback', [
                'has_code' => !empty($code),
                'has_state' => !empty($state)
            ]);

            // Exchange authorization code for tokens
            $response = $this->client->post($this->authUrl . '/oauth2/access_token', [
                'form_params' => [
                    'client_id' => config('teamleader.client_id'),
                    'client_secret' => config('teamleader.client_secret'),
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => config('teamleader.redirect_uri'),
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $responseBody = (string)$response->getBody();
                $this->logger->error('Token exchange failed', [
                    'status_code' => $response->getStatusCode(),
                    'response' => $responseBody
                ]);

                $this->errorHandler->handleApiError([
                    'error' => true,
                    'status_code' => $response->getStatusCode(),
                    'message' => 'Token exchange failed',
                    'response' => json_decode($responseBody, true)
                ], 'OAuth callback');

                return false;
            }

            $tokenData = json_decode($response->getBody()->getContents(), true);

            if (!isset($tokenData['access_token'])) {
                $this->logger->error('No access token in callback response', [
                    'response_keys' => array_keys($tokenData ?? [])
                ]);

                $this->errorHandler->handleApiError([
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'No access token in response',
                    'response' => $tokenData
                ], 'OAuth callback');

                return false;
            }

            // Store tokens
            $this->tokenService->storeTokens($tokenData);
            $this->accessToken = $tokenData['access_token'];
            $this->manualTokenSet = false; // Reset since we're using TokenService

            $this->logger->info('OAuth callback handled successfully');
            return true;

        }, 3, 'OAuth callback');
    }

    /**
     * Make a request to the Teamleader API with automatic error handling
     */
    public function request($method, $endpoint, $data = [])
    {
        return $this->errorHandler->withRetry(function () use ($method, $endpoint, $data) {
            // If a manual token was set, use it. Otherwise, get from TokenService
            if (!$this->manualTokenSet) {
                $this->accessToken = $this->tokenService->getValidAccessToken();
            }

            if (empty($this->accessToken)) {
                $this->logger->error('TeamleaderSDK: No valid access token available for request');

                $result = [
                    'error' => true,
                    'status_code' => 401,
                    'message' => 'No access token available. Please connect to Teamleader first.'
                ];

                $this->errorHandler->handleApiError($result, "{$method} {$endpoint}");
                return $result;
            }

            // Check rate limiting and apply throttling
            $rateLimitCheck = $this->rateLimiter->checkAndThrottle();

            if (!$rateLimitCheck['can_proceed']) {
                // We need to wait for rate limit reset
                $waitTimeSeconds = $rateLimitCheck['delay_applied'] / 1000;

                $this->logger->warning('TeamleaderSDK: Rate limit exceeded, waiting for reset', [
                    'wait_time_seconds' => $waitTimeSeconds,
                    'reset_time' => $rateLimitCheck['reset_time'],
                    'reason' => $rateLimitCheck['reason']
                ]);

                // Sleep for the required time
                sleep((int)$waitTimeSeconds);

                // After waiting, recheck rate limits
                $rateLimitCheck = $this->rateLimiter->checkAndThrottle();
            }

            // Apply any throttling delay
            if ($rateLimitCheck['delay_applied'] > 0) {
                $delayMs = $rateLimitCheck['delay_applied'];

                $this->logger->debug('TeamleaderSDK: Applying throttling delay', [
                    'delay_ms' => $delayMs,
                    'usage_percentage' => $rateLimitCheck['usage_percentage'],
                    'throttle_level' => $rateLimitCheck['throttle_level'],
                    'reason' => $rateLimitCheck['reason']
                ]);

                usleep($delayMs * 1000); // Convert to microseconds
            }

            $result = $this->makeRequest($method, $endpoint, $data);

            // Handle the response through our error handler
            $this->errorHandler->handleApiError($result, "{$method} {$endpoint}");

            // Record successful request for rate limiting
            if (!isset($result['error']) || !$result['error']) {
                $this->rateLimiter->recordRequest();
            }

            return $result;

        }, config('teamleader.api.retry_attempts', 3), "{$method} {$endpoint}");
    }

    /**
     * Make the actual API request
     */
    protected function makeRequest($method, $endpoint, $data = [])
    {
        $this->logger->debug('TeamleaderSDK: Making API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'api_version' => $this->apiVersion
        ]);

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'X-Api-Version' => $this->apiVersion, // Add API version header
            ],
        ];

        if (!empty($data)) {
            $options['json'] = $data;
        }

        self::$apiCallCount++;
        $callDetails = [
            'method' => $method,
            'endpoint' => $endpoint,
            'api_version' => $this->apiVersion,
            'timestamp' => microtime(true),
            'request_data' => $data
        ];

        try {
            $fullUrl = $this->baseUrl . '/' . ltrim($endpoint, '/');
            $response = $this->client->request($method, $fullUrl, $options);

            $statusCode = $response->getStatusCode();
            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody, true);
            $responseHeaders = $response->getHeaders();

            $callDetails['status_code'] = $statusCode;
            $callDetails['response_size'] = strlen($responseBody);
            $callDetails['duration'] = microtime(true) - $callDetails['timestamp'];
            $callDetails['headers'] = $responseHeaders;
            self::$apiCalls[] = $callDetails;

            // Update rate limiting state from response headers
            $this->rateLimiter->updateFromResponseHeaders($responseHeaders);

            $this->logger->debug('TeamleaderSDK: API response', [
                'status_code' => $statusCode,
                'response_body_length' => strlen($responseBody),
                'rate_limit_stats' => $this->rateLimiter->getStatistics()
            ]);

            // Success responses
            if ($statusCode >= 200 && $statusCode < 300) {
                if ($statusCode === 204) {
                    return [
                        'success' => true,
                        'status_code' => $statusCode,
                        'message' => 'Operation completed successfully',
                        'headers' => $responseHeaders
                    ];
                }

                if (!empty($responseData)) {
                    // Include headers in successful responses for rate limit tracking
                    $responseData['headers'] = $responseHeaders;
                    return $responseData;
                }

                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'data' => null,
                    'headers' => $responseHeaders
                ];
            }

            // Enhanced error handling with Teamleader-specific error parsing
            $errorMessages = $this->parseTeamleaderErrors($responseData);
            $primaryError = !empty($errorMessages) ? $errorMessages[0] : 'Unknown error';

            return [
                'error' => true,
                'status_code' => $statusCode,
                'message' => $primaryError,
                'errors' => $errorMessages,
                'response' => $responseData,
                'headers' => $responseHeaders
            ];

        } catch (GuzzleException $e) {
            $this->errorHandler->handleGuzzleException($e, "{$method} {$endpoint}");

            return [
                'error' => true,
                'status_code' => 0,
                'message' => 'HTTP request failed: ' . $e->getMessage(),
                'exception' => get_class($e)
            ];
        }
    }

    // Keep all your existing utility methods

    /**
     * Parse Teamleader-specific error format
     */
    protected function parseTeamleaderErrors($responseData): array
    {
        $errors = [];

        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            foreach ($responseData['errors'] as $error) {
                if (is_array($error) && isset($error['title'])) {
                    $errors[] = $error['title'];
                } elseif (is_string($error)) {
                    $errors[] = $error;
                }
            }
        } elseif (isset($responseData['error'])) {
            $errors[] = $responseData['error_description'] ?? $responseData['error'];
        } elseif (isset($responseData['message'])) {
            $errors[] = $responseData['message'];
        }

        return $errors;
    }

    /**
     * Get the error handler instance
     */
    public function getErrorHandler(): TeamleaderErrorHandler
    {
        return $this->errorHandler;
    }

    /**
     * Enable or disable exception throwing
     */
    public function throwExceptions(bool $throw = true): self
    {
        $this->errorHandler->setThrowExceptions($throw);
        return $this;
    }

    /**
     * Get rate limiter instance
     */
    public function getRateLimiter(): ApiRateLimiterService
    {
        return $this->rateLimiter;
    }

    /**
     * Get rate limit statistics
     */
    public function getRateLimitStats(): array
    {
        return $this->rateLimiter->getStatistics();
    }

    public function __call($name, $arguments)
    {
        if (isset($this->resources[$name])) {
            if (!isset($this->resourceInstances[$name])) {
                $class = $this->resources[$name];
                $this->resourceInstances[$name] = new $class($this);
            }
            return $this->resourceInstances[$name];
        }
        throw new Exception("Method or resource '{$name}' not found");
    }

    public function addResource($name, $class)
    {
        $this->resources[$name] = $class;
        return $this;
    }

    public function isAuthenticated()
    {
        // If manual token is set, check that. Otherwise check TokenService
        if ($this->manualTokenSet) {
            return !empty($this->accessToken);
        }

        // Check both the current token and TokenService state
        $hasCurrentToken = !empty($this->accessToken);
        $hasValidTokens = $this->tokenService->hasValidTokens();

        return $hasCurrentToken && $hasValidTokens;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        $this->manualTokenSet = true; // Mark that token was manually set

        $this->logger->debug('TeamleaderSDK: Access token set manually', [
            'token_preview' => substr($accessToken, 0, 20) . '...'
        ]);

        return $this;
    }

    public function getToken()
    {
        return $this->accessToken;
    }

    public function logout()
    {
        $this->tokenService->clearTokens();
        $this->accessToken = null;
        $this->manualTokenSet = false; // Reset manual token flag
        $this->logger->debug('TeamleaderSDK: Logged out, tokens cleared');
    }

    /**
     * Get the token service instance
     */
    public function getTokenService(): TokenService
    {
        return $this->tokenService;
    }

    /**
     * Get the logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Reset manual token setting and revert to TokenService
     */
    public function useTokenService()
    {
        $this->manualTokenSet = false;
        $this->accessToken = $this->tokenService->getValidAccessToken();

        $this->logger->debug('TeamleaderSDK: Reverted to using TokenService for tokens');

        return $this;
    }
}
