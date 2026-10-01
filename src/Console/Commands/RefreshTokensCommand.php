<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Services\TokenService;
use Throwable;

/**
 * Renew access tokens before they are needed.
 *
 * Tokens are otherwise only refreshed when a request needs one, so a
 * connection nobody uses for a while is never renewed, and a revoked refresh
 * token is only discovered when a real request fails. The package schedules
 * this every ten minutes (teamleader.tokens.auto_refresh).
 */
class RefreshTokensCommand extends Command
{
    protected $signature = 'teamleader:tokens:refresh
                            {--connection=* : Only these connections (repeatable); default: every connection with tokens}
                            {--force : Refresh even when the token is not due}';

    protected $description = 'Refresh Teamleader access tokens that expire soon';

    public function handle(ConnectionManager $manager): int
    {
        $within = (int) config('teamleader.tokens.refresh_before', 1800);
        $names = $this->option('connection') ?: array_keys($manager->statuses());

        if ($names === []) {
            $this->line('No Teamleader connections to refresh.');

            return self::SUCCESS;
        }

        $rows = [];
        $problems = 0;

        foreach ($names as $name) {
            if (! $manager->isConfigured($name)) {
                // Tokens stored under a name that is no longer in the configuration
                $rows[] = [$name, '<fg=yellow>not configured — tokens stored, skipped</>'];

                continue;
            }

            try {
                $result = $manager->connection($name)->getTokenService()->refreshIfDue($within, (bool) $this->option('force'));
            } catch (Throwable $e) {
                $result = TokenService::FAILED.': '.$e->getMessage();
            }

            if (str_starts_with($result, TokenService::FAILED) || $result === TokenService::NEEDS_REAUTHORIZATION) {
                $problems++;
            } elseif ($result !== TokenService::NOT_CONNECTED) {
                // A connection upgraded from 2.x has no account recorded yet
                try {
                    $manager->connection($name)->identifyAccountIfUnknown();
                } catch (Throwable) {
                    // Not worth failing the refresh over; tried again next run
                }
            }

            $rows[] = [$name, $this->label($result)];
        }

        $this->table(['Connection', 'Result'], $rows);

        if ($problems > 0) {
            $this->warn("{$problems} connection(s) could not be refreshed. Run `php artisan teamleader:status --all`.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function label(string $result): string
    {
        return match ($result) {
            TokenService::REFRESHED => '<fg=green>refreshed</>',
            TokenService::NOT_DUE => 'not due',
            TokenService::NOT_CONNECTED => '<fg=yellow>not connected</>',
            TokenService::NEEDS_REAUTHORIZATION => '<fg=red>needs reauthorization</>',
            default => "<fg=red>{$result}</>",
        };
    }
}
