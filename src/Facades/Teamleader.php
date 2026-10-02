<?php

namespace McoreServices\TeamleaderSDK\Facades;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Facade;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Resources\Calendar\ActivityTypes;
use McoreServices\TeamleaderSDK\Resources\Calendar\CallOutcomes;
use McoreServices\TeamleaderSDK\Resources\Calendar\Calls;
use McoreServices\TeamleaderSDK\Resources\Calendar\Events;
use McoreServices\TeamleaderSDK\Resources\Calendar\Meetings;
use McoreServices\TeamleaderSDK\Resources\CRM\Addresses;
use McoreServices\TeamleaderSDK\Resources\CRM\BusinessTypes;
use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\Resources\CRM\Contacts;
use McoreServices\TeamleaderSDK\Resources\CRM\Tags;
use McoreServices\TeamleaderSDK\Resources\Deals\Deals;
use McoreServices\TeamleaderSDK\Resources\Deals\LostReasons;
use McoreServices\TeamleaderSDK\Resources\Deals\Orders;
use McoreServices\TeamleaderSDK\Resources\Deals\Phases;
use McoreServices\TeamleaderSDK\Resources\Deals\Pipelines;
use McoreServices\TeamleaderSDK\Resources\Deals\Quotations;
use McoreServices\TeamleaderSDK\Resources\Deals\Sources;
use McoreServices\TeamleaderSDK\Resources\Expenses\BookkeepingSubmissions;
use McoreServices\TeamleaderSDK\Resources\Expenses\Expenses;
use McoreServices\TeamleaderSDK\Resources\Expenses\IncomingCreditNotes;
use McoreServices\TeamleaderSDK\Resources\Expenses\IncomingInvoices;
use McoreServices\TeamleaderSDK\Resources\Expenses\Receipts;
use McoreServices\TeamleaderSDK\Resources\Files\Files;
use McoreServices\TeamleaderSDK\Resources\General\ClosingDays;
use McoreServices\TeamleaderSDK\Resources\General\Currencies;
use McoreServices\TeamleaderSDK\Resources\General\CustomFields;
use McoreServices\TeamleaderSDK\Resources\General\DayOffTypes;
use McoreServices\TeamleaderSDK\Resources\General\DaysOff;
use McoreServices\TeamleaderSDK\Resources\General\Departments;
use McoreServices\TeamleaderSDK\Resources\General\DocumentTemplates;
use McoreServices\TeamleaderSDK\Resources\General\EmailTracking;
use McoreServices\TeamleaderSDK\Resources\General\Notes;
use McoreServices\TeamleaderSDK\Resources\General\Teams;
use McoreServices\TeamleaderSDK\Resources\General\Users;
use McoreServices\TeamleaderSDK\Resources\General\UserSchedules;
use McoreServices\TeamleaderSDK\Resources\General\WorkTypes;
use McoreServices\TeamleaderSDK\Resources\Invoicing\CommercialDiscounts;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Creditnotes;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Invoices;
use McoreServices\TeamleaderSDK\Resources\Invoicing\PaymentMethods;
use McoreServices\TeamleaderSDK\Resources\Invoicing\PaymentTerms;
use McoreServices\TeamleaderSDK\Resources\Invoicing\Subscriptions;
use McoreServices\TeamleaderSDK\Resources\Invoicing\TaxRates;
use McoreServices\TeamleaderSDK\Resources\Invoicing\WithholdingTaxRates;
use McoreServices\TeamleaderSDK\Resources\Other\Accounts;
use McoreServices\TeamleaderSDK\Resources\Other\CloudPlatforms;
use McoreServices\TeamleaderSDK\Resources\Other\Migrate;
use McoreServices\TeamleaderSDK\Resources\Other\Webhooks;
use McoreServices\TeamleaderSDK\Resources\Planning\PlannableItems;
use McoreServices\TeamleaderSDK\Resources\Planning\Reservations;
use McoreServices\TeamleaderSDK\Resources\Planning\UserAvailability;
use McoreServices\TeamleaderSDK\Resources\Products\Categories;
use McoreServices\TeamleaderSDK\Resources\Products\PriceLists;
use McoreServices\TeamleaderSDK\Resources\Products\Products;
use McoreServices\TeamleaderSDK\Resources\Products\UnitOfMeasure;
use McoreServices\TeamleaderSDK\Resources\Projects\ExternalParties;
use McoreServices\TeamleaderSDK\Resources\Projects\Groups;
use McoreServices\TeamleaderSDK\Resources\Projects\LegacyMilestones;
use McoreServices\TeamleaderSDK\Resources\Projects\LegacyProjects;
use McoreServices\TeamleaderSDK\Resources\Projects\Materials;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectLines;
use McoreServices\TeamleaderSDK\Resources\Projects\Projects;
use McoreServices\TeamleaderSDK\Resources\Projects\ProjectTasks;
use McoreServices\TeamleaderSDK\Resources\Tasks\Tasks;
use McoreServices\TeamleaderSDK\Resources\Templates\MailTemplates;
use McoreServices\TeamleaderSDK\Resources\Tickets\Tickets;
use McoreServices\TeamleaderSDK\Resources\Tickets\TicketStatus;
use McoreServices\TeamleaderSDK\Resources\TimeTracking\Timers;
use McoreServices\TeamleaderSDK\Resources\TimeTracking\TimeTracking;
use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Testing\FakeConnectionManager;
use McoreServices\TeamleaderSDK\Testing\FakeResponse;
use McoreServices\TeamleaderSDK\Testing\ResponseSequence;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Authentication
 *
 * @method static string getAuthorizationUrl(?string $state = null)
 * @method static RedirectResponse authorize(?string $state = null)
 * @method static bool isAuthenticated()
 * @method static TeamleaderSDK setAccessToken(string $accessToken)
 * @method static string|null getToken()
 * @method static TeamleaderSDK useTokenService()
 * @method static TokenService getTokenService()
 * @method static void logout()
 *
 * Requests, configuration & diagnostics
 * @method static array request(string $method, string $endpoint, array $data = [])
 * @method static string getApiVersion()
 * @method static TeamleaderSDK setApiVersion(string $version)
 * @method static TeamleaderSDK throwExceptions(bool $throw = true)
 * @method static TeamleaderErrorHandler getErrorHandler()
 * @method static ApiRateLimiterService getRateLimiter()
 * @method static array getRateLimitStats()
 * @method static LoggerInterface getLogger()
 * @method static TeamleaderSDK addResource(string $name, string $class)
 * @method static int getApiCallCount()
 * @method static array getApiCalls()
 * @method static void resetApiCallStats()
 *
 * General
 * @method static ClosingDays closingDays()
 * @method static Currencies currencies()
 * @method static CustomFields customFields()
 * @method static DayOffTypes dayOffTypes()
 * @method static DaysOff daysOff()
 * @method static Departments departments()
 * @method static DocumentTemplates documentTemplates()
 * @method static EmailTracking emailTracking()
 * @method static Notes notes()
 * @method static Teams teams()
 * @method static UserSchedules userSchedules()
 * @method static Users users()
 * @method static WorkTypes workTypes()
 *
 * CRM
 * @method static Addresses addresses()
 * @method static BusinessTypes businessTypes()
 * @method static Companies companies()
 * @method static Contacts contacts()
 * @method static Tags tags()
 *
 * Deals
 * @method static Deals deals()
 * @method static Phases dealPhases()
 * @method static Pipelines dealPipelines()
 * @method static Sources dealSources()
 * @method static LostReasons lostReasons()
 * @method static Orders orders()
 * @method static Quotations quotations()
 *
 * Calendar
 * @method static ActivityTypes activityTypes()
 * @method static CallOutcomes callOutcomes()
 * @method static Calls calls()
 * @method static Events calendarEvents()
 * @method static Meetings meetings()
 *
 * Invoicing
 * @method static CommercialDiscounts commercialDiscounts()
 * @method static Creditnotes creditNotes()
 * @method static Invoices invoices()
 * @method static PaymentMethods paymentMethods()
 * @method static PaymentTerms paymentTerms()
 * @method static Subscriptions subscriptions()
 * @method static TaxRates taxRates()
 * @method static WithholdingTaxRates withholdingTaxRates()
 *
 * Expenses
 * @method static BookkeepingSubmissions bookkeepingSubmissions()
 * @method static Expenses expenses()
 * @method static IncomingCreditNotes incomingCreditNotes()
 * @method static IncomingInvoices incomingInvoices()
 * @method static Receipts receipts()
 *
 * Products
 * @method static Categories productCategories()
 * @method static PriceLists priceLists()
 * @method static Products products()
 * @method static UnitOfMeasure unitsOfMeasure()
 *
 * Projects
 * @method static ExternalParties externalParties()
 * @method static Groups groups()
 * @method static LegacyMilestones legacyMilestones()
 * @method static LegacyProjects legacyProjects()
 * @method static Materials materials()
 * @method static ProjectLines projectLines()
 * @method static ProjectTasks projectTasks()
 * @method static Projects projects()
 * @method static Projects nextgenProjects() Alias for projects() — Teamleader's webhook vocabulary
 *
 * Planning
 * @method static PlannableItems plannableItems()
 * @method static Reservations reservations()
 * @method static UserAvailability userAvailability()
 *
 * Tasks & time tracking
 * @method static Tasks tasks()
 * @method static Timers timers()
 * @method static TimeTracking timeTracking()
 *
 * Tickets
 * @method static TicketStatus ticketStatus()
 * @method static Tickets tickets()
 *
 * Files & templates
 * @method static Files files()
 * @method static MailTemplates mailTemplates()
 *
 * Other
 * @method static Accounts accounts()
 * @method static CloudPlatforms cloudPlatforms()
 * @method static Migrate migrate()
 * @method static Webhooks webhooks()
 *
 * Bulk
 * @method static \McoreServices\TeamleaderSDK\Bulk\BulkManager bulk()
 * @method static \McoreServices\TeamleaderSDK\Resources\Resource resource(string $key)
 *
 * Connections
 * @method static string connectionName()
 *
 * @see McoreServices\TeamleaderSDK\TeamleaderSDK
 */
class Teamleader extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'teamleader';
    }

    /*
     * The methods below go to the ConnectionManager directly instead of
     * through the default connection's instance. Resolving that instance
     * builds the default connection, which throws when it is not configured —
     * an application that only uses named connections could otherwise never
     * reach them through the facade.
     */

    /** The SDK for a connection; the default connection when no name is given */
    public static function connection(?string $name = null): TeamleaderSDK
    {
        return static::manager()->connection($name);
    }

    /**
     * Define a connection at runtime — see ConnectionManager::extend()
     *
     * @param  Closure(): array<string, mixed>  $config
     */
    public static function extend(string $name, Closure $config): ConnectionManager
    {
        return static::manager()->extend($name, $config);
    }

    /**
     * Resolve unknown connection names through your own lookup — see
     * ConnectionManager::resolveConnectionsUsing()
     *
     * @param  Closure(string): (array<string, mixed>|null)  $resolver
     */
    public static function resolveConnectionsUsing(Closure $resolver): ConnectionManager
    {
        return static::manager()->resolveConnectionsUsing($resolver);
    }

    /**
     * Complete the OAuth flow on the connection that started it. Returns that
     * connection's SDK, or false when the code exchange failed and
     * TEAMLEADER_THROW_EXCEPTIONS is off.
     */
    public static function handleCallback(string $code, ?string $state = null): TeamleaderSDK|false
    {
        return static::connection(TeamleaderSDK::pendingConnectionFor($state))->handleCallback($code, $state);
    }

    private static function manager(): ConnectionManager
    {
        return static::getFacadeApplication()->make(ConnectionManager::class);
    }

    // -- testing ----------------------------------------------------------------

    /**
     * Replace every connection with a fake that records requests instead of
     * sending them. Resources still validate what they build.
     *
     *     Teamleader::fake([
     *         'deals.create' => ['data' => ['id' => 'deal-1', 'type' => 'deal']],
     *         'deals.info'   => fn (array $body) => ['data' => ['id' => $body['id']]],
     *         'contacts.*'   => Teamleader::response()->status(404),
     *     ]);
     *
     *     Teamleader::assertSent('deals.create', fn (array $body) => $body['title'] === 'Big deal');
     *
     * Calling it again adds stubs to the same fake; recorded calls are kept.
     *
     * @param  array<string, mixed>  $responses  endpoint or pattern => array, FakeResponse, closure or sequence
     */
    public static function fake(array $responses = []): FakeConnectionManager
    {
        $app = static::getFacadeApplication();
        $current = $app->bound(ConnectionManager::class) ? $app->make(ConnectionManager::class) : null;

        if ($current instanceof FakeConnectionManager) {
            return $current->stub($responses);
        }

        $manager = new FakeConnectionManager($responses);
        $app->instance(ConnectionManager::class, $manager);
        $app->instance(TeamleaderSDK::class, $manager->connection());
        static::clearResolvedInstance(static::getFacadeAccessor());

        return $manager;
    }

    /** A response for a stub: a success by default; status() >= 400 makes it a refusal */
    public static function response(array $body = [], int $status = 200, array $headers = []): FakeResponse
    {
        return new FakeResponse($body, $status, $headers);
    }

    /**
     * Successive responses for one endpoint
     *
     * @param  list<array|FakeResponse|Closure|string>  $responses
     */
    public static function sequence(array $responses = []): ResponseSequence
    {
        return new ResponseSequence($responses);
    }

    /** Throw on any request no stub answers, instead of an empty success */
    public static function preventStrayRequests(bool $prevent = true): FakeConnectionManager
    {
        return static::fakeManager()->preventStrayRequests($prevent);
    }

    /**
     * Assert a request to an endpoint (or pattern) was sent, on any connection
     *
     * @param  (Closure(array $body, array $call): bool)|null  $callback
     */
    public static function assertSent(string $endpoint, ?Closure $callback = null): FakeConnectionManager
    {
        return static::fakeManager()->assertSent($endpoint, $callback);
    }

    /**
     * @param  (Closure(array $body, array $call): bool)|null  $callback
     */
    public static function assertNotSent(string $endpoint, ?Closure $callback = null): FakeConnectionManager
    {
        return static::fakeManager()->assertNotSent($endpoint, $callback);
    }

    public static function assertSentCount(string $endpoint, int $count): FakeConnectionManager
    {
        return static::fakeManager()->assertSentCount($endpoint, $count);
    }

    public static function assertNothingSent(): FakeConnectionManager
    {
        return static::fakeManager()->assertNothingSent();
    }

    /**
     * The recorded calls on every connection, optionally only those to an
     * endpoint and passing a check
     *
     * @param  (Closure(array $body, array $call): bool)|null  $callback
     * @return list<array{method: string, endpoint: string, body: array, connection: string}>
     */
    public static function recorded(?string $endpoint = null, ?Closure $callback = null): array
    {
        return static::fakeManager()->recorded($endpoint, $callback);
    }

    private static function fakeManager(): FakeConnectionManager
    {
        $manager = static::manager();

        if (! $manager instanceof FakeConnectionManager) {
            throw new RuntimeException('Call Teamleader::fake() before asserting on Teamleader requests.');
        }

        return $manager;
    }
}
