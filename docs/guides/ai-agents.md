# AI Agents

Coding agents such as Claude Code, Cursor and GitHub Copilot write better
Teamleader code when they know how the SDK works. The SDK gives them that in
two ways: guidelines for Laravel Boost, and the full documentation as plain
text.

{% hint style="info" %}
Boost guidelines are available from v3.3.
{% endhint %}

## Laravel Boost

[Laravel Boost](https://laravel.com/docs/boost) collects guidelines from your
application's packages and writes them into the instruction files your agent
reads (`CLAUDE.md`, `AGENTS.md`, `.cursor/rules`, and so on). The SDK ships its
guidelines in `resources/boost/guidelines/core.blade.php`, so there is nothing
to copy:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

After upgrading the SDK, refresh them:

```bash
php artisan boost:update
```

The guidelines tell the agent:

- to go through the `Teamleader` facade, never call the API with `Http::`
  directly, so token refresh, rate limiting and validation keep working
- never to guess a filter, sort field, include or body field, and where to
  look them up: `php artisan teamleader:describe {resource}`,
  `getCapabilities()`, or the constants on the resource class
- that an `InvalidArgumentException` from the SDK means the code is wrong,
  and is fixed rather than caught
- to page with `lazy()`, write many records with `Teamleader::bulk()`, and
  test with `Teamleader::fake()`
- that the CLI writes nothing without `--write`, which it adds only when you
  asked for a write
- the API quirks that cost time to find out, such as the invoice `section`
  key and `subject.id` in webhook payloads

## Any other agent

The documentation site serves two files written for language models:

| File | Contents |
|---|---|
| [`llms.txt`](https://teamleader-sdk.mcore-services.dev/llms.txt) | An index of every page, with links |
| [`llms-full.txt`](https://teamleader-sdk.mcore-services.dev/llms-full.txt) | The complete documentation in one file |

Give `llms-full.txt` to an agent that has no Boost integration, or paste it
into a chat. Every page is also available as Markdown by adding `.md` to its
address.

## Let the agent check its own work

Two things make an agent's mistakes surface immediately instead of in
production:

- **Tests with `Teamleader::fake()`.** The resources still validate what they
  build, so a mistyped filter or field fails the test. See
  [Testing your integration](testing.md).
- **The command line.** `php artisan teamleader:describe deals` shows what an
  endpoint accepts, and `php artisan teamleader:list deals --filter=…` runs a
  real read. Neither writes anything. See [Command line](cli.md).
