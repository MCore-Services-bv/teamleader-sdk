# Testing Your Integration

`Teamleader::fake()` replaces every connection with one that records
requests instead of sending them. No Teamleader account, token, Redis or
network is needed, and the resources still validate what they build: a test
that sends a filter or field Teamleader would ignore fails the same way
production would.

{% hint style="info" %}
Available from v3.1.
{% endhint %}

## Faking and asserting

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

public function test_a_signed_contract_becomes_a_deal(): void
{
    Teamleader::fake([
        'deals.create' => ['data' => ['id' => 'deal-1', 'type' => 'deal']],
    ]);

    $this->post('/contracts/42/sign');

    Teamleader::assertSent('deals.create', fn (array $body) => $body['title'] === 'Contract 42');
    $this->assertSame('deal-1', Contract::find(42)->teamleader_deal_id);
}
```

| Assertion | |
|---|---|
| `assertSent($endpoint, $check = null)` | At least one request; the optional check receives the body |
| `assertNotSent($endpoint, $check = null)` | None (that pass the check) |
| `assertSentCount($endpoint, $count)` | Exactly this many |
| `assertNothingSent()` | No requests at all |
| `recorded($endpoint = null, $check = null)` | The calls themselves: `method`, `endpoint`, `body`, `connection` |

Endpoints are Teamleader's names (`deals.create`, `contacts.list`) and may
be patterns: `deals.*`, `*.list`. A failed assertion says what *was* sent.

## Answering requests

A stub is one of:

```php
Teamleader::fake([
    // an array, returned as it is
    'deals.info' => ['data' => ['id' => 'deal-1', 'title' => 'Big deal']],

    // a closure, receiving the body and the endpoint
    'contacts.info' => fn (array $body) => ['data' => ['id' => $body['id']]],

    // a refusal: thrown as the typed exception, or returned as an error array
    // when TEAMLEADER_THROW_EXCEPTIONS is off — exactly as the real client does
    'invoices.book' => Teamleader::response()->status(422)->errors(['invoice_date is required']),
    'companies.info' => Teamleader::response()->status(404)->message('Company not found'),

    // successive answers
    'contacts.list' => Teamleader::sequence()
        ->push(['data' => [['id' => 'c1'], ['id' => 'c2']]])
        ->push(['data' => []]),
]);
```

An endpoint without a stub answers an empty success, `['data' => []]`. To
make an unstubbed request fail the test instead:

```php
Teamleader::fake([...]);
Teamleader::preventStrayRequests();
```

An exhausted sequence throws, so an unexpected extra request shows up.
`->whenEmpty($response)` answers every later request the same way instead.

Calling `fake()` again adds stubs to the same fake and keeps what was
recorded.

## Several connections

Every connection is faked, by any name. They share the stubs given to
`fake()`; one can have its own:

```php
Teamleader::fake(['users.me' => ['data' => ['id' => 'shared']]]);
Teamleader::connection('antwerp')->stub(['users.me' => ['data' => ['id' => 'antwerp-user']]]);

Teamleader::assertSent('companies.list');                        // on any connection
Teamleader::connection('antwerp')->assertSent('companies.list'); // on one
```

## Files, downloads and OAuth

The two file transfers are recorded under their own names:

| Recorded as | Stub with |
|---|---|
| `files.upload (contents)` — the bytes `uploadFile()` sends; body has `location` and `contents` | an array, like `['data' => ['id' => 'file-1', 'type' => 'file']]` |
| `download (contents)` — the fetch in `downloadContents()` / `downloadTo()`; body has `location` | a string: the document's bytes |

The link requests before them (`files.upload`, `invoices.download`) are
ordinary endpoints. Combine with `Storage::fake('s3')` to test `downloadTo()`.

`Teamleader::handleCallback()` completes without Teamleader: it records
`oauth.callback` with the code and state, and returns the connection.
Stub `oauth.callback` with a refusal to test the failure path.

## Bulk and queues

Bulk operations run through the fake as they would for real, validation
first. A refusal from a stub is counted as a failed row:

```php
Teamleader::fake([
    'companies.add' => Teamleader::sequence()
        ->push(['data' => ['id' => 'c1']])
        ->push(Teamleader::response()->status(422)->errors(['vat_number is invalid'])),
]);

$result = Teamleader::bulk()->create('companies', $rows)->continueOnError()->run();
```

Queued bulk: `dispatch()` refuses the `sync` queue driver, which is the
usual one in tests. Fake the bus and name a real connection:

```php
Bus::fake();

Teamleader::bulk()->create('companies', $rows)->dispatch(chunk: 50, queueConnection: 'database');

Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 2);
```
