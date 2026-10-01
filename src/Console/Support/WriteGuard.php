<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Console\Support;

use Illuminate\Console\Command;

/**
 * The one gate in front of every CLI write.
 *
 *  - Without --write, nothing is sent.
 *  - With --write, the user confirms, seeing the endpoint and the row count.
 *    --force skips the question, for scripts.
 *  - In production, --force is required as well — and a run with -n
 *    (cron, CI) is refused instead of waiting on a question.
 */
final class WriteGuard
{
    public function __construct(private readonly Command $command) {}

    public function allows(string $what, int $rows): bool
    {
        if (! $this->command->option('write')) {
            $this->command->error("This would write to Teamleader: {$what}. Nothing was sent.");
            $this->command->line('Add --write to send it, or --dry-run (where available) to see exactly what would be sent.');

            return false;
        }

        $force = (bool) $this->command->option('force');

        if (app()->environment('production') && ! $force) {
            $this->command->error('In production, writing needs --force as well as --write. Nothing was sent.');

            return false;
        }

        if ($force) {
            return true;
        }

        // -n / --no-interaction: a script that cannot answer. (Without a
        // terminal and without -n, confirm() reads nothing and says no.)
        if ($this->command->option('no-interaction')) {
            $this->command->error('Not interactive: add --force to write without asking. Nothing was sent.');

            return false;
        }

        $rowsText = $rows === 1 ? '1 request' : "{$rows} requests";

        return $this->command->confirm("Send {$rowsText} to Teamleader — {$what}?", false);
    }
}
