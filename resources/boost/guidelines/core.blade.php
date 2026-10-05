## mcore-services/teamleader-sdk

Laravel SDK for the Teamleader Focus API: OAuth, encrypted token storage and renewal, rate limiting and ~70 API resources behind one interface, for one Teamleader account or many. Full documentation: https://teamleader-sdk.mcore-services.dev/ (complete text for agents: https://teamleader-sdk.mcore-services.dev/llms-full.txt).

### Rules

- Always go through the facade `McoreServices\TeamleaderSDK\Facades\Teamleader`. Never call `api.focus.teamleader.eu` with `Http::` or Guzzle directly: you lose token refresh, rate limiting and validation.
- Never guess a filter, sort field, include or body field. The Teamleader API answers `200 OK` to names it does not know and ignores them (a mistyped filter returns every record). The SDK throws `InvalidArgumentException` before sending instead. Look the name up first: `php artisan teamleader:describe {resource}`, `Teamleader::{resource}()->getCapabilities()`, or the public constants on the resource class (e.g. `Companies::WRITE_FIELDS`, `ProjectTasks::STATUSES`).
- An `InvalidArgumentException` from the SDK means the code is wrong. Fix the call; do not catch it.
- Includes are per endpoint: `list` and `info` often accept different ones.
- Teamleader sets creation dates itself. Records (deals, notes, files, …) cannot be backdated; keep original dates in the content or a custom field.
- Run `php artisan teamleader:list` / `teamleader:call` to inspect real data. Nothing writes without `--write`; do not add `--write` unless the user asked for a write.

### Resource shape

Every resource follows the same shape. `$filters` maps to the API's `filter` object; `$options` carries `page_size`, `page_number`, `sort`, `sort_order` and `include`.

@verbatim
<code-snippet name="Resource methods" lang="php">
use McoreServices\TeamleaderSDK\Facades\Teamleader;

Teamleader::companies()->list(['status' => 'active'], ['page_size' => 50, 'sort' => 'name', 'include' => 'custom_fields']);
Teamleader::companies()->info('company-uuid');
Teamleader::companies()->create(['name' => 'Acme Corp', 'vat_number' => 'BE0123456789']);
Teamleader::companies()->update('company-uuid', ['website' => 'https://acme.be']);
Teamleader::companies()->delete('company-uuid');

// Resource-specific actions exist where the API has them
Teamleader::deals()->win('deal-uuid');
</code-snippet>
@endverbatim

Run `php artisan teamleader:resources` for every resource name and what it supports.

### Pagination

Most endpoints return no total count. Use `lazy()` instead of looping over `page_number` yourself.

@verbatim
<code-snippet name="Lazy pagination" lang="php">
Teamleader::companies()->lazy(['status' => 'active'])->each(function (array $company) {
    // one record at a time, pages of 100 fetched as needed
});

Teamleader::deals()->lazy(['status' => ['open']])->take(10)->all();
</code-snippet>
@endverbatim

### Errors

Failed requests throw typed exceptions, all extending `McoreServices\TeamleaderSDK\Exceptions\TeamleaderException`: `NotFoundException` (404), `ValidationException` (422), `AuthorizationException` (403), `AuthenticationException` (incl. `ConnectionNeedsReauthorizationException`: the user must connect again), `RateLimitExceededException` (429), `ServerException`.

@verbatim
<code-snippet name="Rate limit in a queued job" lang="php">
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;

try {
    Teamleader::deals()->list();
} catch (RateLimitExceededException $e) {
    $this->release($e->getRetryAfter());
}
</code-snippet>
@endverbatim

The limit is 200 requests per minute per Teamleader account, tracked in Redis. For many records, use bulk operations rather than a loop of single calls.

### Bulk operations

@verbatim
<code-snippet name="Bulk export and writes" lang="php">
Teamleader::bulk()
    ->export('contacts', ['tags' => ['customer']])
    ->toCsv(storage_path('contacts.csv'), ['id', 'first_name', 'last_name', 'emails.0.email']);

$result = Teamleader::bulk()->create('companies', $rows)->continueOnError()->run();
$result->succeeded();   // [row key => API response]
$result->failed();      // [row key => BulkFailure]

Teamleader::bulk()->update('deals', $rows)->dryRun()->run();      // shows what would be sent
Teamleader::bulk()->update('deals', $rows)->dispatch(chunk: 50);  // on the queue
Teamleader::bulk()->call('deals', 'win', $rows)->run();           // any method, once per row
</code-snippet>
@endverbatim

### Multiple connections

@verbatim
<code-snippet name="Use a named connection" lang="php">
Teamleader::connection('antwerp')->companies()->list();
</code-snippet>
@endverbatim

Connections live in `config/teamleader.php` or the database (`php artisan teamleader:connections:add {name}`). CLI commands take `--connection=`.

### Testing

Use the fake in tests; it needs no account, token or network, and still validates what the resources build.

@verbatim
<code-snippet name="Faking Teamleader" lang="php">
Teamleader::fake([
    'deals.create' => ['data' => ['id' => 'deal-1', 'type' => 'deal']],
    'deals.info'   => Teamleader::response()->status(404),
]);

// … code under test …

Teamleader::assertSent('deals.create', fn (array $body) => $body['title'] === 'Big deal');
</code-snippet>
@endverbatim

### Known API quirks

- Invoice grouped lines without a section title must omit the `section` key entirely; `null` and `''` are rejected.
- Webhook payloads carry the entity id at `subject.id`, not `data.id`.
- Select custom fields take the option label: use `Teamleader::customFields()->selectValue($fieldId, $optionIdOrLabel)`.
- `files()->upload()` / `download()` only return a temporary link; use `uploadFile()`, `downloadContents()` or `downloadTo()` to move the actual bytes.
