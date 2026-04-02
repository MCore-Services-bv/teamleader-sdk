# Contacts

Manage contacts in Teamleader Focus CRM.

## Overview

The Contacts resource provides full CRUD operations for contact records. Beyond standard CRUD it exposes tag management,
avatar upload, and company link management (link, unlink, update).

## Endpoint

`contacts`

## Capabilities

| Capability  | Supported   |
|-------------|-------------|
| Pagination  | ✅ Supported |
| Filtering   | ✅ Supported |
| Sorting     | ✅ Supported |
| Sideloading | ✅ Supported |
| Creation    | ✅ Supported |
| Update      | ✅ Supported |
| Deletion    | ✅ Supported |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$contacts = Teamleader::contacts()->list();

$contacts = Teamleader::contacts()->list(
    ['status' => 'active', 'company_id' => 'company-uuid'],
    ['page_size' => 50, 'sort' => 'name', 'sort_order' => 'asc']
);

// With sideloading via options
$contacts = Teamleader::contacts()->list([], ['include' => 'custom_fields']);
```

---

### `info(string $id, string|array|null $includes = null)`

```php
$contact = Teamleader::contacts()->info('contact-uuid');

$contact = Teamleader::contacts()->info('contact-uuid', 'custom_fields,price_list');

$contact = Teamleader::contacts()
    ->withCustomFields()
    ->info('contact-uuid');
```

---

### `create(array $data)`

```php
$contact = Teamleader::contacts()->create([
    'first_name'              => 'Sarah',
    'last_name'               => 'De Smedt',
    'salutation'              => 'mrs',   // optional
    'gender'                  => 'female', // optional: male, female, unknown
    'language'                => 'nl',
    'marketing_mails_consent' => true,
    'emails'                  => [['type' => 'primary', 'email' => 'sarah@acme.be']],
    'telephones'              => [['type' => 'mobile', 'number' => '+32 475 12 34 56']],
    'tags'                    => ['Decision Maker'],
]);
```

> **Gender validation:** `gender` must be one of `male`, `female`, `unknown`. An `InvalidArgumentException` is thrown
> for any other value.

---

### `update(mixed $id, array $data)`

The `id` is injected into the request body before posting to `contacts.update`.

```php
Teamleader::contacts()->update('contact-uuid', [
    'last_name'  => 'De Smedt-Janssen',
    'telephones' => [['type' => 'mobile', 'number' => '+32 475 99 88 77']],
]);
```

---

### `delete(string $id)`

```php
Teamleader::contacts()->delete('contact-uuid');
```

---

### `uploadAvatar(string $id, string|null $image)`

Uploads or removes a contact avatar. The image must be a base64 data URI starting with `data:image/`. Pass `null` to
remove. An `InvalidArgumentException` is thrown if a non-null value doesn't start with `data:image/`.

Returns empty array (HTTP 204) on success.

```php
$imageData = base64_encode(file_get_contents('/path/to/avatar.jpg'));
Teamleader::contacts()->uploadAvatar('contact-uuid', 'data:image/jpeg;base64,' . $imageData);

// Remove avatar
Teamleader::contacts()->uploadAvatar('contact-uuid', null);
```

---

## Company Link Methods

### `linkToCompany(string $id, string $companyId, array $data = [])`

Links a contact to a company. Optional data: `position` (string), `decision_maker` (bool).

```php
// Basic link
Teamleader::contacts()->linkToCompany('contact-uuid', 'company-uuid');

// With position and decision-maker flag
Teamleader::contacts()->linkToCompany('contact-uuid', 'company-uuid', [
    'position'       => 'CEO',
    'decision_maker' => true,
]);
```

### `unlinkFromCompany(string $id, string $companyId)`

```php
Teamleader::contacts()->unlinkFromCompany('contact-uuid', 'company-uuid');
```

### `updateCompanyLink(string $id, string $companyId, array $data = [])`

Updates `position` and/or `decision_maker` on an existing link.

```php
Teamleader::contacts()->updateCompanyLink('contact-uuid', 'company-uuid', [
    'position'       => 'Managing Director',
    'decision_maker' => true,
]);
```

---

## Tag Methods

### `tag(string $id, string|array $tags)`

```php
Teamleader::contacts()->tag('contact-uuid', ['VIP', 'Decision Maker']);
Teamleader::contacts()->tag('contact-uuid', 'Newsletter'); // string also accepted
```

### `untag(string $id, string|array $tags)`

```php
Teamleader::contacts()->untag('contact-uuid', ['Prospect']);
```

### `manageTags(string $id, array $tagsToAdd = [], array $tagsToRemove = [])`

Makes **two separate API calls** internally. Returns `['tagged' => [...], 'untagged' => [...]]`.

```php
Teamleader::contacts()->manageTags(
    'contact-uuid',
    ['Active', 'Customer'],
    ['Lead', 'Prospect']
);
```

---

## Helper Methods

| Method                          | Filter applied                                   |
|---------------------------------|--------------------------------------------------|
| `search(string $term)`          | `term` (first name, last name, email, telephone) |
| `byEmail(string $email)`        | `email` → `{type: primary, email: $email}`       |
| `forCompany(string $companyId)` | `company_id`                                     |
| `active()`                      | `status: active`                                 |
| `deactivated()`                 | `status: deactivated`                            |
| `withTags(string\|array $tags)` | `tags`                                           |
| `updatedSince(string $date)`    | `updated_since`                                  |

All helpers accept an optional `$options` array as their last parameter.

### Fluent include methods

| Method               | Include added   |
|----------------------|-----------------|
| `withCustomFields()` | `custom_fields` |
| `withPriceList()`    | `price_list`    |

---

## Filters

| Filter                    | Type            | Description                                      |
|---------------------------|-----------------|--------------------------------------------------|
| `ids`                     | array           | Filter by UUIDs                                  |
| `email`                   | array or string | Email — string auto-wraps as `{type: primary}`   |
| `company_id`              | string          | Contacts linked to this company                  |
| `term`                    | string          | Searches first name, last name, email, telephone |
| `updated_since`           | string          | ISO 8601 datetime                                |
| `tags`                    | array           | All specified tags must be present               |
| `status`                  | string          | `active` or `deactivated`                        |
| `marketing_mails_consent` | bool            | Marketing consent flag                           |

---

## Sorting

| Field        | Description            |
|--------------|------------------------|
| `name`       | First name + last name |
| `added_at`   | Date added             |
| `updated_at` | Date last updated      |

---

## Sideloading

| Include         | Description         |
|-----------------|---------------------|
| `custom_fields` | Custom field values |
| `price_list`    | Assigned price list |

> **Note:** Contacts have a smaller include set than Companies. `addresses`, `responsible_user`, and `tags` are not
> available as sideloaded includes.

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'           => 'contact-uuid',
            'first_name'   => 'Sarah',
            'last_name'    => 'De Smedt',
            'salutation'   => 'mrs',
            'gender'       => 'female',
            'status'       => 'active',
            'language'     => 'nl',
            'emails'       => [['type' => 'primary', 'email' => 'sarah@acme.be']],
            'companies'    => [
                [
                    'customer'       => ['type' => 'company', 'id' => 'company-uuid'],
                    'position'       => 'CEO',
                    'decision_maker' => true,
                ],
            ],
            'added_at'    => '2025-01-15T10:00:00+00:00',
            'updated_at'  => '2025-03-01T09:00:00+00:00',
        ],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 87],
]
```

---

## Usage Examples

### Upsert by email

```php
$existing = Teamleader::contacts()->byEmail('sarah@acme.be');

if (!empty($existing['data'])) {
    Teamleader::contacts()->update($existing['data'][0]['id'], ['last_name' => 'De Smedt-Janssen']);
} else {
    Teamleader::contacts()->create([
        'first_name' => 'Sarah',
        'last_name'  => 'De Smedt',
        'emails'     => [['type' => 'primary', 'email' => 'sarah@acme.be']],
    ]);
}
```

### Sync/update a company link

```php
$contact    = Teamleader::contacts()->info('contact-uuid');
$isLinked   = false;

foreach ($contact['data']['companies'] ?? [] as $link) {
    if ($link['customer']['id'] === 'company-uuid') {
        $isLinked = true;
        break;
    }
}

if ($isLinked) {
    Teamleader::contacts()->updateCompanyLink('contact-uuid', 'company-uuid', [
        'position' => 'Managing Director',
    ]);
} else {
    Teamleader::contacts()->linkToCompany('contact-uuid', 'company-uuid', [
        'position' => 'Managing Director',
    ]);
}
```

### Get all decision makers for a company

```php
$contacts = Teamleader::contacts()->forCompany('company-uuid');

$decisionMakers = array_filter($contacts['data'], function ($contact) {
    foreach ($contact['companies'] ?? [] as $link) {
        if ($link['decision_maker'] === true) return true;
    }
    return false;
});
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\{NotFoundException, ValidationException, TeamleaderException};

// Invalid gender value
try {
    Teamleader::contacts()->create(['first_name' => 'Alex', 'gender' => 'other']);
} catch (InvalidArgumentException $e) {
    // 'Invalid gender value. Must be one of: male, female, unknown'
}

// Invalid avatar URI
try {
    Teamleader::contacts()->uploadAvatar('contact-uuid', 'plain-string');
} catch (InvalidArgumentException $e) {
    // 'Image must be a base64 data URI (e.g. data:image/png;base64,...) or null'
}

try {
    Teamleader::contacts()->info('contact-uuid');
} catch (NotFoundException $e) {
    // Contact does not exist
}
```

---

## Related Resources

- [[Companies]] — Contacts are linked to companies
- [[Tags]] — Tag reference list
- [[Deals]] — Deals reference contacts as customers
- [[Sideloading]] — Loading related data
- [[Filtering]] — Filter and pagination reference
