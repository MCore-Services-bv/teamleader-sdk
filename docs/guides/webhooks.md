# Webhooks

Teamleader can call a URL of yours when something happens — an invoice is
booked, a deal is won, a contact is added.

## Registering

```php
Teamleader::webhooks()->register('https://your-app.com/webhooks/teamleader', [
    'invoice.booked',
    'deal.won',
]);
```

Event types are checked against the list in the specification before the
request is sent; an unknown type throws. To register a whole category:

```php
$types = Teamleader::webhooks()->getInvoiceEventTypes();   // invoice.* and incomingInvoice.*

Teamleader::webhooks()->register('https://your-app.com/webhooks/teamleader', $types);
```

Helpers exist for invoices, credit notes, deals, contacts, companies,
projects, tasks, tickets and time tracking. `getEventTypesByCategory('meeting')`
returns any other prefix, and `getAvailableEventTypes()` returns them all. The
full list is under **Accepted values** on the [Webhooks reference page](../reference/other/webhooks.md).

```php
Teamleader::webhooks()->list();
Teamleader::webhooks()->unregister('https://your-app.com/webhooks/teamleader', ['deal.won']);
```

## Receiving

The payload carries the entity id at `subject.id`, not `data.id`:

```json
{
  "type": "invoice.booked",
  "subject": { "type": "invoice", "id": "..." }
}
```

Keep the route fast: acknowledge, queue, and fetch the record in the job.

```php
// routes/web.php
Route::post('/webhooks/teamleader/{secret}', function (Request $request, string $secret) {
    abort_unless(hash_equals(config('services.teamleader.webhook_secret'), $secret), 404);

    ProcessTeamleaderEvent::dispatch($request->input('type'), $request->input('subject.id'));

    return response()->noContent();
});
```

- **Exclude the route from CSRF protection** — it is a `POST` from outside
  your application.
- **Put a secret in the URL.** The SDK does not verify where a webhook request
  came from, so register an unguessable URL and check it, as above.
- **Fetch the record** in the job with `info()` rather than trusting the
  payload for anything beyond the id.

## Projects: "nextgen" event names

Webhook events for the current project system are named `nextgenProject.*`,
while the SDK resource is `projects()` and the API path is
`projects-v2/projects.*`. They are the same thing; see the
[Projects reference page](../reference/projects/projects.md).
