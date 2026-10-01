<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;
use Throwable;

/**
 * Remove a connection stored with teamleader:connections:add, together with
 * its tokens. Access is not revoked on Teamleader's side: remove the
 * integration's access in that account as well.
 */
class ConnectionsRemoveCommand extends Command
{
    protected $signature = 'teamleader:connections:remove
                            {name : The connection to remove}
                            {--force : Do not ask for confirmation}';

    protected $description = 'Remove a stored Teamleader connection and its tokens';

    public function handle(ConnectionManager $manager, DatabaseConnectionStore $store, TokenStore $tokens): int
    {
        $name = (string) $this->argument('name');
        $source = $manager->sourceOf($name);

        if ($source === 'config') {
            $this->error("'{$name}' is defined in config/teamleader.php. Remove it there.");

            return self::FAILURE;
        }

        if ($source !== 'database') {
            $this->error("No stored connection named '{$name}'. See `php artisan teamleader:connections:list`.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Remove connection '{$name}' and its tokens? The account must then be connected again to be used.")) {
            return self::FAILURE;
        }

        // Tokens first, through the connection, so its cache entry goes too
        try {
            $manager->connection($name)->getTokenService()->clearTokens();
        } catch (Throwable) {
            $tokens->forget($name);
        }

        $store->forget($name);
        $manager->purge($name);

        $this->info("Connection '{$name}' removed. Revoke the integration's access in that Teamleader account as well.");

        return self::SUCCESS;
    }
}
