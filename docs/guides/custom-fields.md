# Custom Fields

Custom fields are defined once per context (contact, company, deal, project
and so on) with `customFields()`, and filled in on the record itself through
`custom_fields`:

```php
Teamleader::deals()->create([
    'title' => 'Woonkrediet Janssens',
    'lead' => ['customer' => ['type' => 'contact', 'id' => $contactId]],
    'custom_fields' => [
        ['id' => $amountFieldId, 'value' => 250000],
        ['id' => $typeFieldId, 'value' => 'Woonkrediet'],
    ],
]);
```

## Select fields take the label

A `single_select` or `multi_select` field takes the option's **label** as its
value: a string for single select, a list of strings for multi select.
Teamleader refuses the option id, with *"The custom field with id … has an
invalid single selection value"*.

```php
['id' => $typeFieldId, 'value' => 'Woonkrediet']                                // single select
['id' => $productsFieldId, 'value' => ['Hypothecair', 'Persoonlijke lening']]   // multi select
```

The labels are the ones in the definition, exactly as written, so a renamed
option needs the new label in your mapping too.

### Resolving labels and ids

`selectValue()` takes a label or an option id and returns the label to send.
A value that is not an option throws before any request is sent, and the
message lists the options that exist:

```php
$value = Teamleader::customFields()->selectValue($typeFieldId, 'opt-uuid');        // 'Woonkrediet'
$value = Teamleader::customFields()->selectValue($productsFieldId, ['Hypothecair', 'opt-uuid']);
```

`options()` returns the whole list as `[label => option id]`:

```php
Teamleader::customFields()->options($typeFieldId);
// ['Woonkrediet' => '0b8f…', 'Hypothecair' => '5c1d…', 'Persoonlijke lening' => '9e2a…']
```

Both read the definition once per field and keep it for the rest of the
`customFields()` instance, so keep one instance while mapping many rows:

```php
$fields = Teamleader::customFields();

$rows = collect($leads)->map(fn ($lead) => [
    'title' => $lead->title,
    'lead' => ['customer' => ['type' => 'contact', 'id' => $lead->contact_id]],
    'custom_fields' => [
        ['id' => $typeFieldId, 'value' => $fields->selectValue($typeFieldId, $lead->credit_type)],
    ],
])->all();

Teamleader::bulk()->create('deals', $rows)->dryRun();
```

Resolve the values while building the rows, as above. The bulk validation
that runs before the first request works offline, so it cannot look up a
field definition itself.

## Finding the field ids

```bash
php artisan teamleader:list customFields --all --fields=id,context,label,type
php artisan teamleader:info customFields <uuid>
```

`teamleader:info` shows the options of a select field under
`configuration.options`.
