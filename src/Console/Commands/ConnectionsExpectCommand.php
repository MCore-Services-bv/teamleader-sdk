<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;

/**
 * Set the Teamleader account a stored connection must connect to, without
 * entering its credentials again. A callback that connects any other account
 * is then refused and nothing is stored.
 *
 * The account id is only known after connecting, so the usual order is:
 * connections:add, connect, connections:expect {name} --current.
 */
class ConnectionsExpectCommand extends Command
{
    protected $signature = 'teamleader:connections:expect
                            {name? : The connection (leave out with --all)}
                            {account? : The Teamleader account id}
                            {--current : Use the account the connection is connected to now}
                            {--all : Every stored connection (with --current)}
                            {--clear : Remove the expected account}';

    protected $description = 'Set or clear the Teamleader account a stored connection must connect to';

    public function handle(ConnectionManager $manager, DatabaseConnectionStore $store, TokenStore $tokens): int
    {
        $name = $this->argument('name');
        $account = $this->argument('account');
        $current = (bool) $this->option('current');
        $clear = (bool) $this->option('clear');

        $given = array_filter([$account !== null && $account !== '', $current, $clear]);

        if (count($given) !== 1) {
            $this->error('Give exactly one of: an account id, --current or --clear.');

            return self::INVALID;
        }

        if ($this->option('all')) {
            if ($account !== null && $account !== '') {
                $this->error('--all takes --current or --clear, not one account id for every connection.');

                return self::INVALID;
            }

            $names = $store->names();
        } elseif (is_string($name) && $name !== '') {
            $names = [$name];
        } else {
            $this->error('Name a connection, or pass --all.');

            return self::INVALID;
        }

        if ($names === []) {
            $this->line('No stored connections.');

            return self::SUCCESS;
        }

        $rows = [];
        $problems = 0;

        foreach ($names as $connection) {
            $connected = $tokens->get($connection)?->accountId;

            if ($manager->sourceOf($connection) !== 'database') {
                $rows[] = [$connection, '—', '<fg=yellow>not stored in the database: set expected_account_id in config/teamleader.php'
                    .($connected ? " ({$connected})" : '').'</>'];
                $problems++;

                continue;
            }

            $expected = match (true) {
                $clear => null,
                $current => $connected,
                default => (string) $account,
            };

            if ($current && $expected === null) {
                $rows[] = [$connection, '—', '<fg=yellow>account unknown: connect it first, or run teamleader:tokens:refresh</>'];
                $problems++;

                continue;
            }

            $store->setExpectedAccount($connection, $expected);
            $manager->purge($connection);

            $note = match (true) {
                $expected === null => 'cleared',
                $connected !== null && $connected !== $expected => "<fg=yellow>set — but connected to {$connected} now; the next callback must connect {$expected}</>",
                default => 'set',
            };

            $rows[] = [$connection, $expected ?? '—', $note];
        }

        $this->table(['Connection', 'Expected account', 'Result'], $rows);

        return $problems > 0 ? self::FAILURE : self::SUCCESS;
    }
}
