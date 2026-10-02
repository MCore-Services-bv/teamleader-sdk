<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;

/**
 * Rename a connection without connecting it again: its stored credentials and
 * its tokens move to the new name in one transaction.
 *
 * The rate-limit window is keyed on the integration's client ID, not on the
 * name, so it is unaffected. The cached token copy under the old name is
 * dropped; the new name reads the store on first use.
 */
class ConnectionsRenameCommand extends Command
{
    protected $signature = 'teamleader:connections:rename
                            {from : The current name}
                            {to : The new name}
                            {--tokens-only : Move only the tokens — for a connection defined in config/teamleader.php, after renaming it there}';

    protected $description = 'Rename a Teamleader connection, keeping its credentials and tokens';

    public function handle(ConnectionManager $manager, DatabaseConnectionStore $store, TokenStore $tokens): int
    {
        $from = (string) $this->argument('from');
        $to = (string) $this->argument('to');
        $tokensOnly = (bool) $this->option('tokens-only');

        try {
            DatabaseConnectionStore::assertValidName($to);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        }

        if ($from === $to) {
            $this->error('The new name is the same as the current one.');

            return self::INVALID;
        }

        $source = $manager->sourceOf($from);
        $stored = $tokens->get($from);

        if (! $tokensOnly && $source === 'config') {
            $this->error("'{$from}' is defined in config/teamleader.php. Rename it there, then run this command "
                .'with --tokens-only to move its tokens, so it stays connected.');

            return self::FAILURE;
        }

        if (! $tokensOnly && $source !== 'database') {
            $this->error("No stored connection named '{$from}'. See `php artisan teamleader:connections:list`.");

            return self::FAILURE;
        }

        if ($tokensOnly && $stored === null) {
            $this->error("No tokens are stored for '{$from}'.");

            return self::FAILURE;
        }

        if ((! $tokensOnly && $manager->sourceOf($to) !== null) || $tokens->get($to) !== null) {
            $this->error("'{$to}' already exists. Choose another name, or remove that connection first.");

            return self::FAILURE;
        }

        // The refresh lock: a refresh running now would write under the old name
        $lock = "teamleader:{$from}:refresh_lock";

        if (! Cache::add($lock, true, 60)) {
            $this->error("A token refresh for '{$from}' is running. Try again in a minute.");

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($store, $tokens, $from, $to, $tokensOnly, $stored) {
                if (! $tokensOnly) {
                    $store->rename($from, $to);
                }

                if ($stored !== null) {
                    $tokens->put($to, $stored);
                    $tokens->forget($from);
                }
            });
        } finally {
            Cache::forget($lock);
        }

        Cache::forget("teamleader:{$from}:tokens");
        $manager->purge($from);
        $manager->purge($to);

        $this->info("Connection '{$from}' is now '{$to}'".($stored !== null ? ', and still connected.' : '.'));

        if ($manager->getDefaultConnection() === $from) {
            $this->warn("TEAMLEADER_CONNECTION is '{$from}'. Set it to '{$to}' in .env.");
        }

        $this->line("Update anything that names the connection: Teamleader::connection('{$from}') in your code, "
            .'scheduled commands with --connection, and queued bulk jobs that have not run yet.');

        return self::SUCCESS;
    }
}
