<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Connections\ConnectionConfig;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;
use McoreServices\TeamleaderSDK\Exceptions\ConfigurationException;

/**
 * Store a connection's credentials in the database — adding a Teamleader
 * account without a deploy. Create the integration in that account first.
 *
 * Everything is validated before anything is stored: a run that fails
 * leaves the table as it was.
 */
class ConnectionsAddCommand extends Command
{
    protected $signature = 'teamleader:connections:add
                            {name : The connection name, e.g. antwerp}
                            {--client-id= : The integration\'s client ID (prompted when left out)}
                            {--client-secret= : The integration\'s client secret (prompted, hidden, when left out)}
                            {--redirect-uri= : Only when it differs from TEAMLEADER_REDIRECT_URI (prompted when that is not set either)}
                            {--expected-account= : Refuse a callback that connects any other Teamleader account}
                            {--force : Replace stored credentials without asking}';

    protected $description = 'Add a Teamleader connection, with its credentials stored encrypted in the database';

    public function handle(ConnectionManager $manager, DatabaseConnectionStore $store): int
    {
        $name = (string) $this->argument('name');

        try {
            DatabaseConnectionStore::assertValidName($name);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        }

        $source = $manager->sourceOf($name);

        if ($source === 'config') {
            $this->error("'{$name}' is defined in config/teamleader.php. Change it there, or choose another name.");

            return self::FAILURE;
        }

        if ($source === 'database' && ! $this->option('force')
            && ! $this->confirm("Connection '{$name}' is already stored. Replace its credentials?")) {
            return self::FAILURE;
        }

        $clientId = trim((string) ($this->option('client-id') ?: $this->ask('Client ID')));
        $clientSecret = trim((string) ($this->option('client-secret') ?: $this->secret('Client secret')));

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Both the client ID and the client secret are required: every Teamleader account needs its own integration.');

            return self::FAILURE;
        }

        $redirectUri = $this->redirectUri($manager);

        if ($redirectUri === false) {
            return self::FAILURE;
        }

        $credentials = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'expected_account_id' => $this->option('expected-account') ?: null,
        ];

        // Validate before storing, so a failed run never leaves a half-stored connection
        try {
            $config = ConnectionConfig::fromArray($name, $credentials, $manager->defaultRedirectUri());
        } catch (ConfigurationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $store->put($name, $credentials);
        $manager->purge($name);

        $this->info("Connection '{$name}' stored.");
        $this->line("Redirect URI: {$config->redirectUri} — register it in the integration if you have not.");

        if ($manager->getDefaultConnection() !== $name) {
            $this->line("To use it as the default connection, set TEAMLEADER_CONNECTION={$name} in .env.");
        }

        $this->line("Next: connect the account, e.g. through Teamleader::connection('{$name}')->authorize().");

        return self::SUCCESS;
    }

    /**
     * The redirect URI to store: the option, null to use the shared
     * TEAMLEADER_REDIRECT_URI, or — when neither is set — the answer to a
     * prompt. False when none can be had.
     */
    private function redirectUri(ConnectionManager $manager): string|null|false
    {
        $uri = $this->option('redirect-uri');

        if (is_string($uri) && $uri !== '') {
            return $this->validRedirectUri($uri) ? $uri : false;
        }

        if ($manager->defaultRedirectUri() !== null) {
            return null;
        }

        if (! $this->input->isInteractive()) {
            $this->error('No redirect URI: pass --redirect-uri, or set TEAMLEADER_REDIRECT_URI.');

            return false;
        }

        $suggestion = rtrim((string) config('app.url'), '/').'/teamleader/callback';
        $uri = trim((string) $this->ask('Redirect URI (as registered in the integration)', $suggestion));

        return $this->validRedirectUri($uri) ? $uri : false;
    }

    private function validRedirectUri(string $uri): bool
    {
        if (filter_var($uri, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $uri)) {
            return true;
        }

        $this->error("'{$uri}' is not a full URL. Use the callback URL registered in the integration, e.g. https://your-app.com/teamleader/callback.");

        return false;
    }
}
