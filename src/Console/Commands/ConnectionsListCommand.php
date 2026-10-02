<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;

class ConnectionsListCommand extends Command
{
    protected $signature = 'teamleader:connections:list
                            {--json : Output as JSON}';

    protected $description = 'List Teamleader connections: where each is defined, and its status';

    public function handle(ConnectionManager $manager): int
    {
        $rows = [];

        foreach ($manager->statuses() as $name => $status) {
            $rows[] = [
                'connection' => $name,
                'defined_in' => $manager->sourceOf($name) ?? '—',
                'status' => $status['status'],
                'account' => $status['account_name'] ?? '—',
                'account_id' => $status['account_id'] ?? '—',
                'expected_account_id' => $this->expectedAccount($manager, $name),
            ];
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($rows, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($rows === []) {
            $this->line('No Teamleader connections. Add one with `php artisan teamleader:connections:add {name}`.');

            return self::SUCCESS;
        }

        $this->table(['Connection', 'Defined in', 'Status', 'Account', 'Account ID', 'Expected account'], array_map('array_values', $rows));

        return self::SUCCESS;
    }

    /** The expected_account_id set on a connection, or `—` when none */
    private function expectedAccount(ConnectionManager $manager, string $name): string
    {
        if (! $manager->isConfigured($name)) {
            return '—';
        }

        $expected = $manager->config($name)->expectedAccountId;

        return $expected ?? '—';
    }
}
