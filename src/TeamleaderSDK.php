<?php

namespace McoreServices\TeamleaderSDK;

use Closure;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Bulk\BulkManager;
use McoreServices\TeamleaderSDK\Connections\ConnectionConfig;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Events\ConnectionAuthorized;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\RequestSending;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Exceptions\AccountMismatchException;
use McoreServices\TeamleaderSDK\Exceptions\ConfigurationException;
use McoreServices\TeamleaderSDK\Exceptions\ConnectionException;
use McoreServices\TeamleaderSDK\Exceptions\ConnectionNeedsReauthorizationException;
use McoreServices\TeamleaderSDK\Exceptions\OAuthStateException;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\Traits\DispatchesEvents;
use McoreServices\TeamleaderSDK\Traits\SanitizesLogData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TeamleaderSDK
{
    use DispatchesEvents;
    use SanitizesLogData;

    protected static $apiCallCount = 0;

    /**
     * The most recent calls — method, endpoint, status, size and duration.
     *
     * Bounded to API_CALL_LOG_LIMIT entries. Until v3.0 every call was kept,
     * with its request body and response headers, for the life of the process:
     * a queue worker grew without limit and held personal data in memory. For
     * anything beyond a quick look, listen to the ResponseReceived event.
     *
     * @var list<array<string, mixed>>
     */
    protected static $apiCalls = [];

    public const API_CALL_LOG_LIMIT = 100;

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
        'userSchedules' => Resources\General\UserSchedules::class,

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
        'calendarEvents' => Resources\Calendar\Events::class,
        'activityTypes' => Resources\Calendar\ActivityTypes::class,

        // Invoicing
        'invoices' => Resources\Invoicing\Invoices::class,
        'creditNotes' => Resources\Invoicing\Creditnotes::class,
        'paymentMethods' => Resources\Invoicing\PaymentMethods::class,
        'paymentTerms' => Resources\Invoicing\PaymentTerms::class,
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
        'externalParties' => Resources\Projects\ExternalParties::class,
        'groups' => Resources\Projects\Groups::class,
        'materials' => Resources\Projects\Materials::class,
        'projectLines' => Resources\Projects\ProjectLines::class,
        'projects' => Resources\Projects\Projects::class,
        // Alias for projects(). Teamleader names the webhook event family
        // "nextgenProject" while naming the resource "projects-v2/projects", so
        // reasoning from the event names leads people to look for this method.
        // See the Projects class docblock.
        'nextgenProjects' => Resources\Projects\Projects::class,
        'projectTasks' => Resources\Projects\ProjectTasks::class,

        // Pganning
        'plannableItems' => Resources\Planning\PlannableItems::class,
        'reservations' => Resources\Planning\Reservations::class,
        'userAvailability' => Resources\Planning\UserAvailability::class,

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

    /**
     * Resource keys removed in v3.0, mapped to the name that replaced them.
     *
     * v2.2.6 renamed one misspelling and six snake_case outliers to camelCase
     * and kept the old keys as deprecated aliases. v3.0 removed the aliases;
     * this map only makes the "not found" error name the replacement.
     *
     * @var array<string, string>
     */
    protected array $removedResourceKeys = [
        'calenderEvents' => 'calendarEvents',
        'creditnotes' => 'creditNotes',
        'payment_methods' => 'paymentMethods',
        'payment_terms' => 'paymentTerms',
        'external_parties' => 'externalParties',
        'plannable_items' => 'plannableItems',
        'user_availability' => 'userAvailability',
    ];

    private TokenService $tokenService;

    private ApiRateLimiterService $rateLimiter;

    private LoggerInterface $logger;

    private TeamleaderErrorHandler $errorHandler;

    // Add flag to track manual token override
    private bool $manualTokenSet = false;

    /** Credentials and name of the connection this instance talks to */
    private ConnectionConfig $connectionConfig;

    public function __construct(
        ?TokenService $tokenService = null,
        ?ApiRateLimiterService $rateLimiter = null,
        ?LoggerInterface $logger = null,
        ?TeamleaderErrorHandler $errorHandler = null,
        ?ConnectionConfig $connection = null,
    ) {
        // Throws a ConfigurationException naming the connection when its
        // credentials are incomplete
        $this->connectionConfig = $connection ?? self::manager()->config(self::manager()->getDefaultConnection());

        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
            // Cast — a config value arriving from .env is a string, and Guzzle
            // 7.11 deprecates a string here (8.0 will require int|float), so
            // every API call emitted two deprecation notices under a strict
            // error handler.
            'timeout' => (float) config('teamleader.api.timeout', 30),
            'connect_timeout' => (float) config('teamleader.api.connect_timeout', 10),
            'read_timeout' => (float) config('teamleader.api.read_timeout', 25),
        ]);

        // Use dependency injection or create instances
        $this->logger = $logger ?: $this->resolveLogger();
        // Per connection: tokens, cache entries and refresh lock under the
        // connection's name; the rate-limit window under its client ID, because
        // Teamleader counts 200 requests a minute per integration
        $this->tokenService = $tokenService ?: new TokenService(null, $this->connectionConfig->name, $this->connectionConfig);
        $this->rateLimiter = $rateLimiter ?: new ApiRateLimiterService($this->logger, $this->connectionConfig->clientId);
        $this->errorHandler = $errorHandler ?: new TeamleaderErrorHandler($this->logger);

        // Set API version from config
        $this->apiVersion = config('teamleader.api_version', '2023-09-26');

        // Hosts — configurable for a proxy or a sandbox. Unset or empty keeps
        // Teamleader's production hosts.
        $this->baseUrl = rtrim((string) (config('teamleader.base_url') ?: $this->baseUrl), '/');
        $this->authUrl = rtrim((string) (config('teamleader.auth_url') ?: $this->authUrl), '/');

        // Get initial token from TokenService
        $this->accessToken = $this->tokenService->getValidAccessToken();

        if (! empty($this->accessToken)) {
            $this->logger->debug('TeamleaderSDK initialized with valid access token');
        } else {
            $this->logger->debug('TeamleaderSDK initialized without access token');
        }
    }

    /**
     * The logger for SDK output: `teamleader.logging.channel` when set, the
     * application's default logger otherwise.
     */
    private function resolveLogger(): LoggerInterface
    {
        $channel = config('teamleader.logging.channel');

        if (is_string($channel) && $channel !== '' && app()->bound('log')) {
            return app('log')->channel($channel);
        }

        return app()->bound(LoggerInterface::class) ? app(LoggerInterface::class) : new NullLogger;
    }

    /**
     * The name of the connection this instance talks to
     */
    public function connectionName(): string
    {
        return $this->connectionConfig->name;
    }

    public function getConnectionConfig(): ConnectionConfig
    {
        return $this->connectionConfig;
    }

    /**
     * Another connection's SDK instance:
     *
     *     Teamleader::connection('antwerp')->companies()->list();
     */
    public function connection(?string $name = null): self
    {
        return self::manager()->connection($name);
    }

    /**
     * Define a connection at runtime — see ConnectionManager::extend()
     *
     * @param  Closure(): array<string, mixed>  $config
     */
    public function extend(string $name, Closure $config): self
    {
        self::manager()->extend($name, $config);

        return $this;
    }

    /**
     * Resolve unknown connection names through your own lookup — see
     * ConnectionManager::resolveConnectionsUsing()
     *
     * @param  Closure(string): (array<string, mixed>|null)  $resolver
     */
    public function resolveConnectionsUsing(Closure $resolver): self
    {
        self::manager()->resolveConnectionsUsing($resolver);

        return $this;
    }

    private static function manager(): ConnectionManager
    {
        return app()->bound(ConnectionManager::class) ? app(ConnectionManager::class) : new ConnectionManager;
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
            'version' => $version,
        ]);

        return $this;
    }

    /** Session key holding the states authorize() generated: state => connection */
    public const OAUTH_STATE_SESSION_KEY = 'teamleader.oauth_states';

    /** Pending states kept per session — more than this and the oldest go */
    private const MAX_PENDING_STATES = 10;

    /**
     * Redirect to Teamleader to connect this connection's account.
     *
     * Without an argument, a random `state` is generated and remembered in the
     * session together with the connection name. handleCallback() then checks
     * it — which protects the flow against CSRF — and knows which connection
     * the callback belongs to, so one callback route serves every connection.
     *
     * Passing your own `$state` keeps the 2.x behaviour: nothing is stored and
     * checking it is up to you.
     */
    public function authorize(?string $state = null): RedirectResponse
    {
        return redirect($this->getAuthorizationUrl($state));
    }

    /**
     * The URL authorize() redirects to — for rendering a link or button.
     * Generates and remembers a `state` the same way authorize() does.
     */
    public function getAuthorizationUrl(?string $state = null): string
    {
        $state ??= $this->rememberState();

        $params = [
            'client_id' => $this->connectionConfig->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->connectionConfig->redirectUri,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        $url = $this->authUrl.'/oauth2/authorize?'.http_build_query($params);

        $this->logger->debug('Generated authorization URL', $this->sanitizeForLog([
            'connection' => $this->connectionConfig->name,
            'state' => $state ? 'present' : 'none',
            'redirect_uri' => $this->connectionConfig->redirectUri,
        ]));

        return $url;
    }

    /**
     * Handle the OAuth callback: check the state, exchange the code for tokens,
     * identify the connected account and store everything.
     *
     * When the state was generated by authorize(), the callback is routed to
     * the connection that started the flow, whichever connection this is
     * called on — so `Teamleader::handleCallback(...)` works for all of them.
     *
     * The connected account is identified with users.me. With
     * `expected_account_id` set on the connection, a different account is
     * refused and nothing is stored.
     *
     * @return self|false The SDK instance of the connection that was connected,
     *                    or false when the code exchange failed (and exceptions
     *                    are off). The instance is truthy, so 2.x code testing
     *                    the result in an `if` keeps working.
     *
     * @throws OAuthStateException When this session started a flow and the state does not match
     * @throws AccountMismatchException When the connected account is not the expected one
     */
    public function handleCallback(string $code, ?string $state = null): self|false
    {
        return $this->resolveCallbackConnection($state)->completeCallback($code);
    }

    /**
     * Exchange the code, identify and check the account, store — on the
     * connection the callback was routed to.
     *
     * @throws AccountMismatchException
     */
    private function completeCallback(string $code): self|false
    {
        $tokenData = $this->errorHandler->withRetry(fn () => $this->exchangeCode($code), 3, 'OAuth callback');

        if (! is_array($tokenData)) {
            return false;
        }

        [$accountId, $accountName] = $this->identifyAccount((string) $tokenData['access_token']);

        $expected = $this->connectionConfig->expectedAccountId;

        if ($expected !== null && $accountId !== $expected) {
            $this->logger->warning('OAuth callback connected an unexpected Teamleader account; nothing stored', [
                'connection' => $this->connectionConfig->name,
                'expected_account_id' => $expected,
                'account_id' => $accountId,
            ]);

            throw new AccountMismatchException($this->connectionConfig->name, $expected, $accountId);
        }

        $this->tokenService->storeTokens($tokenData, false, $accountId, $accountName);
        $this->accessToken = $tokenData['access_token'];
        $this->manualTokenSet = false;

        $this->logger->info('OAuth callback handled successfully', [
            'connection' => $this->connectionConfig->name,
            'account_id' => $accountId,
        ]);

        $this->fireEvent(ConnectionAuthorized::class, fn () => new ConnectionAuthorized(
            $this->connectionConfig->name, $accountId, $accountName
        ));

        return $this;
    }

    /**
     * The connection a callback belongs to.
     *
     * - The session holds the state: the connection that generated it (and the
     *   state is used up).
     * - The session holds states, but not this one: refused — a forged or
     *   stale callback.
     * - The session holds none: the caller manages state (2.x); this connection.
     *
     * @throws OAuthStateException
     */
    /**
     * The connection an authorisation with this state was started on, or null
     * when the session holds no such state. Does not consume the state: the
     * facade uses it to route a callback before any connection is built.
     */
    public static function pendingConnectionFor(?string $state): ?string
    {
        if (! is_string($state) || $state === '' || ! function_exists('app') || ! app()->bound('session')) {
            return null;
        }

        $pending = app('session')->driver()->get(self::OAUTH_STATE_SESSION_KEY, []);

        if (! is_array($pending)) {
            return null;
        }

        foreach ($pending as $candidate => $connection) {
            if (hash_equals((string) $candidate, $state) && is_string($connection) && $connection !== '') {
                return $connection;
            }
        }

        return null;
    }

    private function resolveCallbackConnection(?string $state): self
    {
        $session = $this->session();
        $pending = $session?->get(self::OAUTH_STATE_SESSION_KEY, []) ?? [];

        if (! is_array($pending) || $pending === []) {
            return $this;
        }

        $match = null;

        foreach ($pending as $candidate => $connection) {
            if (is_string($state) && hash_equals((string) $candidate, $state)) {
                $match = $connection;
            }
        }

        if ($match === null) {
            throw new OAuthStateException(
                'The OAuth state does not match any authorisation started in this session. Nothing was stored; '
                .'start again from authorize(). (A forged callback, or a session that expired in between.)'
            );
        }

        unset($pending[$state]);
        $session->put(self::OAUTH_STATE_SESSION_KEY, $pending);

        return $match === $this->connectionConfig->name ? $this : $this->connection((string) $match);
    }

    /** Generate a state and remember it for this connection; null without a session */
    private function rememberState(): ?string
    {
        $session = $this->session();

        if ($session === null) {
            $this->logger->warning('No session available: the OAuth state is not generated or checked', [
                'connection' => $this->connectionConfig->name,
            ]);

            return null;
        }

        $state = Str::random(40);
        $pending = $session->get(self::OAUTH_STATE_SESSION_KEY, []);
        $pending = is_array($pending) ? $pending : [];
        $pending[$state] = $this->connectionConfig->name;

        $session->put(self::OAUTH_STATE_SESSION_KEY, array_slice($pending, -self::MAX_PENDING_STATES, null, true));

        return $state;
    }

    private function session(): ?Session
    {
        return function_exists('app') && app()->bound('session') ? app('session')->driver() : null;
    }

    /**
     * Exchange an authorization code for tokens
     *
     * @return array<string, mixed>|false The token response, or false on failure
     */
    private function exchangeCode(string $code): array|false
    {
        $this->logger->info('Handling OAuth callback', [
            'connection' => $this->connectionConfig->name,
            'has_code' => $code !== '',
        ]);

        $response = $this->client->post($this->authUrl.'/oauth2/access_token', [
            'form_params' => [
                'client_id' => $this->connectionConfig->clientId,
                'client_secret' => $this->connectionConfig->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->connectionConfig->redirectUri,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            $responseBody = (string) $response->getBody();
            $this->logger->error('Token exchange failed', [
                'connection' => $this->connectionConfig->name,
                'status_code' => $response->getStatusCode(),
                'response' => $responseBody,
            ]);

            $this->errorHandler->handleApiError([
                'error' => true,
                'status_code' => $response->getStatusCode(),
                'message' => 'Token exchange failed',
                'response' => json_decode($responseBody, true),
            ], 'OAuth callback');

            return false;
        }

        $tokenData = json_decode((string) $response->getBody(), true);

        if (! is_array($tokenData) || ! isset($tokenData['access_token'])) {
            $this->logger->error('No access token in callback response', [
                'response_keys' => is_array($tokenData) ? array_keys($tokenData) : [],
            ]);

            $this->errorHandler->handleApiError([
                'error' => true,
                'status_code' => 400,
                'message' => 'No access token in response',
            ], 'OAuth callback');

            return false;
        }

        return $tokenData;
    }

    /**
     * The connected account's id (users.me) and name (its first department).
     * Either is null when it could not be read; the connection still works.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function identifyAccount(string $accessToken): array
    {
        $call = function (string $endpoint) use ($accessToken): ?array {
            try {
                $response = $this->client->post($this->baseUrl.'/'.$endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer '.$accessToken,
                        'Content-Type' => 'application/json',
                        'X-Api-Version' => $this->apiVersion,
                    ],
                    'json' => (object) [],
                ]);

                return $response->getStatusCode() === 200 ? json_decode((string) $response->getBody(), true) : null;
            } catch (GuzzleException) {
                return null;
            }
        };

        $accountId = $call('users.me')['data']['account']['id'] ?? null;
        $accountName = $accountId === null ? null : ($call('departments.list')['data'][0]['name'] ?? null);

        return [is_string($accountId) ? $accountId : null, is_string($accountName) ? $accountName : null];
    }

    /**
     * Make a request to the Teamleader API with automatic error handling
     */
    public function request($method, $endpoint, $data = [])
    {
        return $this->errorHandler->withRetry(function () use ($method, $endpoint, $data) {
            // If a manual token was set, use it. Otherwise, get from TokenService
            if (! $this->manualTokenSet) {
                $this->accessToken = $this->tokenService->getValidAccessToken();
            }

            if (empty($this->accessToken) && ! $this->manualTokenSet && $this->tokenService->needsReauthorization()) {
                $exception = new ConnectionNeedsReauthorizationException($this->connectionConfig->name);

                $this->fireEvent(RequestFailed::class, fn () => new RequestFailed(
                    (string) $method, (string) $endpoint, 401, $exception->getMessage(), $exception,
                    connection: $this->connectionConfig->name,
                ));

                throw $exception;
            }

            if (empty($this->accessToken)) {
                $this->logger->error('TeamleaderSDK: No valid access token available for request');

                $result = [
                    'error' => true,
                    'status_code' => 401,
                    'message' => 'No access token available. Please connect to Teamleader first.',
                ];

                $this->fireEvent(RequestFailed::class, fn () => new RequestFailed(
                    (string) $method, (string) $endpoint, 401, $result['message'],
                    connection: $this->connectionConfig->name,
                ));

                $this->errorHandler->handleApiError($result, "{$method} {$endpoint}");

                return $result;
            }

            $rateLimitingEnabled = (bool) config('teamleader.rate_limiting.enabled', true);

            if ($rateLimitingEnabled) {
                // Wait until the limiter confirms a slot is free.
                //
                // Before v2.1.2 this checked once, slept, rechecked, and then
                // dispatched regardless of what the recheck said — so a window
                // that was still full produced a 429 the proactive limiter was
                // supposed to prevent. The wait was also `sleep((int) $ms/1000)`,
                // which truncates any sub-second delay to zero, meaning a
                // corrected loop would have spun without ever waiting.
                $rateLimitCheck = $this->rateLimiter->checkAndThrottle();

                $waitedMs = 0;

                // Default deliberately short. Waiting out a full rate limit
                // window can take up to a minute, which in a queue worker is a
                // held slot and in a web request is a hanging page — behaviour
                // no consumer asked for and none had before v2.1.2, when the
                // gate never really blocked at all. So the SDK waits briefly and
                // then hands the decision back to the caller, which is what
                // withRetry() already does by re-throwing rate limit errors
                // without sleeping so a worker can release() instead of
                // blocking its thread.
                //
                // Raise teamleader.rate_limiting.max_wait_ms to have the SDK sit
                // out longer stalls itself.
                $maxWaitMs = (int) config('teamleader.rate_limiting.max_wait_ms', 5000);

                while (! $rateLimitCheck['can_proceed']) {
                    // Floor at one second: delay_applied is in milliseconds and
                    // busy-looping on a sub-second value helps nobody.
                    $delayMs = max(1000, (int) $rateLimitCheck['delay_applied']);

                    if ($waitedMs + $delayMs > $maxWaitMs) {
                        // Bounded rather than indefinite. An unbounded wait in the
                        // core request path is a hang waiting to happen, and a
                        // long one changes the SDK's behaviour under load in a
                        // way a patch release should not impose.
                        // INFO, not WARNING: handing the decision back is the intended
                        // path (a queued bulk chunk releases itself and succeeds). A
                        // real 429 from Teamleader is logged as a WARNING elsewhere.
                        $this->logger->info('TeamleaderSDK: Giving up waiting for rate limit window', [
                            'waited_ms' => $waitedMs,
                            'max_wait_ms' => $maxWaitMs,
                            'usage_percentage' => $rateLimitCheck['usage_percentage'],
                            'reason' => $rateLimitCheck['reason'],
                        ]);

                        $exception = new RateLimitExceededException(
                            'Rate limit window did not clear within the configured maximum wait of '
                            .round($maxWaitMs / 1000, 1).'s. Retry later — in a queue worker, '
                            .'release() using getRetryAfter(). Raise '
                            .'teamleader.rate_limiting.max_wait_ms to have the SDK wait longer.',
                            (int) ceil($delayMs / 1000)
                        );

                        $this->fireEvent(RateLimitWaited::class, fn () => new RateLimitWaited(
                            $waitedMs, (float) $rateLimitCheck['usage_percentage'], (string) $endpoint, true,
                            connection: $this->connectionConfig->name,
                        ));
                        $this->fireEvent(RequestFailed::class, fn () => new RequestFailed(
                            (string) $method, (string) $endpoint, null, $exception->getMessage(), $exception,
                            connection: $this->connectionConfig->name,
                        ));

                        throw $exception;
                    }

                    $this->logger->warning('TeamleaderSDK: Rate limit window full, waiting', [
                        'delay_ms' => $delayMs,
                        'waited_ms' => $waitedMs,
                        'reset_time' => $rateLimitCheck['reset_time'],
                        'reason' => $rateLimitCheck['reason'],
                    ]);

                    usleep($delayMs * 1000);
                    $waitedMs += $delayMs;

                    $rateLimitCheck = $this->rateLimiter->checkAndThrottle();
                }

                // Apply any progressive throttling delay
                if ($rateLimitCheck['delay_applied'] > 0) {
                    $delayMs = $rateLimitCheck['delay_applied'];

                    $this->logger->debug('TeamleaderSDK: Applying throttling delay', [
                        'delay_ms' => $delayMs,
                        'usage_percentage' => $rateLimitCheck['usage_percentage'],
                        'throttle_level' => $rateLimitCheck['throttle_level'],
                        'reason' => $rateLimitCheck['reason'],
                    ]);

                    usleep($delayMs * 1000); // Convert to microseconds
                    $waitedMs += $delayMs;
                }

                if ($waitedMs > 0) {
                    $this->fireEvent(RateLimitWaited::class, fn () => new RateLimitWaited(
                        (int) $waitedMs, (float) $rateLimitCheck['usage_percentage'], (string) $endpoint,
                        connection: $this->connectionConfig->name,
                    ));
                }

                // Record the request before dispatching it.
                //
                // Teamleader counts every request against the budget, including
                // the ones that come back 4xx and the 429s themselves. Recording
                // only successes — as this did before v2.1.2 — makes the local
                // window under-count precisely when errors are already happening,
                // so the limiter believes it has more headroom than it does and
                // produces further 429s.
                $this->rateLimiter->recordRequest();
            }

            $result = $this->makeRequest($method, $endpoint, $data);

            // Handle the response through our error handler
            $this->errorHandler->handleApiError($result, "{$method} {$endpoint}");

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
            'api_version' => $this->apiVersion,
        ]);

        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
                'X-Api-Version' => $this->apiVersion, // Add API version header
            ],
        ];

        if (! empty($data)) {
            $options['json'] = $data;
        }

        self::$apiCallCount++;
        $callDetails = [
            'method' => $method,
            'endpoint' => $endpoint,
            'api_version' => $this->apiVersion,
            'timestamp' => microtime(true),
        ];

        $this->fireEvent(RequestSending::class, fn () => new RequestSending(
            (string) $method, (string) $endpoint, (array) $this->sanitizeForLog((array) $data),
            connection: $this->connectionConfig->name,
        ));

        try {
            $fullUrl = $this->baseUrl.'/'.ltrim($endpoint, '/');
            $response = $this->client->request($method, $fullUrl, $options);

            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);
            $responseHeaders = $response->getHeaders();

            $callDetails['status_code'] = $statusCode;
            $callDetails['response_size'] = strlen($responseBody);
            $callDetails['duration'] = microtime(true) - $callDetails['timestamp'];
            self::$apiCalls[] = $callDetails;

            if (count(self::$apiCalls) > self::API_CALL_LOG_LIMIT) {
                self::$apiCalls = array_slice(self::$apiCalls, -self::API_CALL_LOG_LIMIT);
            }

            $this->fireEvent(ResponseReceived::class, fn () => new ResponseReceived(
                (string) $method,
                (string) $endpoint,
                $statusCode,
                round($callDetails['duration'] * 1000, 1),
                is_array($responseData) ? (array) $this->sanitizeForLog($responseData) : null,
                connection: $this->connectionConfig->name,
            ));

            // Update rate limiting state from response headers — only when the
            // limiter is on. Until v3.0 this, and the statistics in the log line
            // below, reached Redis on every response even with rate limiting
            // disabled, so an application without Redis failed on every call.
            if ((bool) config('teamleader.rate_limiting.enabled', true)) {
                $this->rateLimiter->updateFromResponseHeaders($responseHeaders);
            }

            // No limiter statistics here: they cost several Redis reads per
            // request for a debug line. getRateLimitStats() returns them on demand.
            $this->logger->debug('TeamleaderSDK: API response', [
                'status_code' => $statusCode,
                'response_body_length' => strlen($responseBody),
            ]);

            // Success responses
            if ($statusCode >= 200 && $statusCode < 300) {
                if ($statusCode === 204) {
                    return [
                        'success' => true,
                        'status_code' => $statusCode,
                        'message' => 'Operation completed successfully',
                        'headers' => $responseHeaders,
                    ];
                }

                if (! empty($responseData)) {
                    // Include headers in successful responses for rate limit tracking
                    $responseData['headers'] = $responseHeaders;

                    return $responseData;
                }

                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'data' => null,
                    'headers' => $responseHeaders,
                ];
            }

            // Enhanced error handling with Teamleader-specific error parsing
            $errorMessages = $this->parseTeamleaderErrors($responseData);
            $primaryError = ! empty($errorMessages) ? $errorMessages[0] : 'Unknown error';

            $this->fireEvent(RequestFailed::class, fn () => new RequestFailed(
                (string) $method, (string) $endpoint, $statusCode, (string) $primaryError,
                connection: $this->connectionConfig->name,
            ));

            return [
                'error' => true,
                'status_code' => $statusCode,
                'message' => $primaryError,
                'errors' => $errorMessages,
                'response' => $responseData,
                'headers' => $responseHeaders,
            ];

        } catch (GuzzleException $e) {
            // Before the handler, which throws when throw_exceptions is on
            $this->fireEvent(RequestFailed::class, fn () => new RequestFailed(
                (string) $method, (string) $endpoint, null, $e->getMessage(), $e,
                connection: $this->connectionConfig->name,
            ));

            $this->errorHandler->handleGuzzleException($e, "{$method} {$endpoint}");

            return [
                'error' => true,
                'status_code' => 0,
                'message' => 'HTTP request failed: '.$e->getMessage(),
                'exception' => get_class($e),
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
     * Send a file's contents to the upload link files.upload returned.
     *
     * The one request that does not go through request(): the link is a
     * temporary URL on Teamleader's file host, it takes the raw bytes (not JSON,
     * not form data) and no access token. Resources reach it only through
     * Files::uploadFile(), and the dry-run client records it instead of
     * sending it.
     *
     * @param  string  $location  data.location from files.upload
     * @param  resource|string  $contents  An open stream, or the bytes
     * @return array The decoded response, `data` holding the new file where Teamleader returns it
     *
     * @throws ConnectionException When the host cannot be reached
     * @throws TeamleaderException When the upload is refused; thrown whatever throw_exceptions says
     */
    public function sendFileContents(string $location, mixed $contents): array
    {
        try {
            $response = $this->client->request('POST', $location, [
                'headers' => ['Content-Type' => 'application/octet-stream'],
                'body' => $contents,
                // A large file can take longer than an API call
                'timeout' => max((float) config('teamleader.api.timeout', 30), 120.0),
                'read_timeout' => max((float) config('teamleader.api.read_timeout', 25), 120.0),
            ]);
        } catch (GuzzleException $e) {
            throw new ConnectionException('The file upload could not reach Teamleader: '.$e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = $body === '' ? [] : json_decode($body, true);

        if ($status < 200 || $status >= 300) {
            throw new TeamleaderException(
                "Teamleader refused the file upload (HTTP {$status})"
                .(is_array($decoded) && isset($decoded['errors'][0]['title']) ? ': '.$decoded['errors'][0]['title'] : '.'),
                $status,
                null,
                [],
                $status,
            );
        }

        $this->logger->info('TeamleaderSDK: File contents uploaded', [
            'connection' => $this->connectionConfig->name,
            'status' => $status,
        ]);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get rate limit statistics
     */
    public function getRateLimitStats(): array
    {
        return $this->rateLimiter->getStatistics();
    }

    /**
     * Bulk operations on this connection: export, and bulk writes in-process or queued
     */
    public function bulk(): BulkManager
    {
        return new BulkManager($this);
    }

    /**
     * Every resource key and its class, including ones added with addResource()
     *
     * @return array<string, class-string>
     */
    public function registeredResources(): array
    {
        return $this->resources;
    }

    /**
     * A resource by its key — the same instance as `$sdk->{$key}()`.
     *
     * @throws InvalidArgumentException When no resource has that key
     */
    public function resource(string $key): Resource
    {
        if (! isset($this->resources[$key])) {
            throw new InvalidArgumentException(
                "Unknown Teamleader resource '{$key}'. Run `php artisan teamleader:resources` for the list, "
                .'or see the API reference.'
            );
        }

        return $this->{$key}();
    }

    public function __call($name, $arguments)
    {
        if (isset($this->resources[$name])) {
            if (! isset($this->resourceInstances[$name])) {
                $class = $this->resources[$name];
                $this->resourceInstances[$name] = new $class($this);
            }

            return $this->resourceInstances[$name];
        }

        if (isset($this->removedResourceKeys[$name])) {
            $replacement = $this->removedResourceKeys[$name];

            throw new Exception(
                "Method or resource '{$name}' not found. It was renamed to '{$replacement}' in v2.2.6 "
                ."and the old name was removed in v3.0. Use {$replacement}()."
            );
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
            return ! empty($this->accessToken);
        }

        // Check both the current token and TokenService state
        $hasCurrentToken = ! empty($this->accessToken);
        $hasValidTokens = $this->tokenService->hasValidTokens();

        return $hasCurrentToken && $hasValidTokens;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        $this->manualTokenSet = true; // Mark that token was manually set

        $this->logger->debug('TeamleaderSDK: Access token set manually', [
            'token_preview' => substr($accessToken, 0, 20).'...',
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
