<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;

/**
 * Store a connection's credentials in the database — adding a Teamleader
 * account without a deploy. Create the integration in that account first.
 */
class ConnectionsAddCommand extends Command
{
    protected $signature = 'teamleader:connections:add
                            {name : The connection name, e.g. antwerp}
                            {--client-id= : The integration\'s client ID (prompted when left out)}
                            {--client-secret= : The integration\'s client secret (prompted, hidden, when left out)}
                            {--redirect-uri= : Only when it differs from TEAMLEADER_REDIRECT_URI}
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

        $clientId = (string) ($this->option('client-id') ?: $this->ask('Client ID'));
        $clientSecret = (string) ($this->option('client-secret') ?: $this->secret('Client secret'));

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Both the client ID and the client secret are required: every Teamleader account needs its own integration.');

            return self::FAILURE;
        }

        $store->put($name, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->option('redirect-uri') ?: null,
            'expected_account_id' => $this->option('expected-account') ?: null,
        ]);

        $manager->purge($name);

        // Validates the result: a missing redirect URI surfaces here, not at connect time
        $config = $manager->config($name);

        $this->info("Connection '{$name}' stored.");
        $this->line("Redirect URI: {$config->redirectUri} — register it in the integration if you have not.");
        $this->line("Next: connect the account, e.g. through Teamleader::connection('{$name}')->authorize().");

        return self::SUCCESS;
    }
}
