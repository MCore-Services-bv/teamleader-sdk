<?php

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Services\HealthCheckService;

class TeamleaderHealthCommand extends Command
{
    protected $signature = 'teamleader:health
                           {--json : Output as JSON}
                           {--fix : Attempt to fix issues automatically}
                           {--score : Show health score only}';

    protected $description = 'Run comprehensive health checks on the Teamleader SDK';

    public function handle(HealthCheckService $healthCheck)
    {
        if ($this->option('score')) {
            $score = $healthCheck->getHealthScore();
            $this->line("Health Score: {$score}/100");

            return $score >= 80 ? 0 : 1;
        }

        $this->info('🏥 Running Teamleader SDK Health Check...');
        $this->newLine();

        $result = $healthCheck->check();

        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT));

            return $result->isHealthy() ? 0 : 1;
        }

        $this->displayOverallStatus($result);
        $this->displayDetailedResults($result);

        if ($this->option('fix')) {
            $this->attemptFixes($result);
        }

        $this->displayRecommendations($result);

        return $result->isHealthy() ? 0 : 1;
    }

    private function displayOverallStatus($result): void
    {
        $status = $result->getOverallStatus();
        $score = app(HealthCheckService::class)->getHealthScore();

        $statusColor = match ($status) {
            'healthy' => 'green',
            'caution' => 'yellow',
            'warning' => 'yellow',
            'critical' => 'red',
            'error' => 'red',
            default => 'blue'
        };

        $scoreColor = match (true) {
            $score >= 90 => 'green',
            $score >= 70 => 'yellow',
            default => 'red'
        };

        $this->line("Overall Status: <fg={$statusColor}>".strtoupper($status).'</>');
        $this->line("Health Score: <fg={$scoreColor}>{$score}/100</>");
        $this->newLine();
    }

    private function displayDetailedResults($result): void
    {
        $checks = $result->getChecks();

        foreach ($checks as $checkName => $check) {
            $this->displaySingleCheck($checkName, $check);
        }

        $this->newLine();
        $this->displaySummary($result->getSummary());
    }

    private function displaySingleCheck(string $checkName, array $check): void
    {
        $status = $check['status'];
        $details = $check['details'];

        $icon = match ($status) {
            'healthy' => '✅',
            'caution' => '⚠️',
            'warning' => '⚠️',
            'critical' => '🔴',
            'error' => '❌',
            'skipped' => '⏭️',
            'disabled' => '🚫',
            default => '❓'
        };

        $color = match ($status) {
            'healthy' => 'green',
            'caution' => 'yellow',
            'warning' => 'yellow',
            'critical' => 'red',
            'error' => 'red',
            'skipped' => 'blue',
            'disabled' => 'gray',
            default => 'white'
        };

        $displayName = ucwords(str_replace('_', ' ', $checkName));
        $this->line("{$icon} <fg={$color}>{$displayName}: ".strtoupper($status).'</>');

        // Show relevant details
        if (is_array($details)) {
            foreach ($details as $key => $value) {
                if (in_array($key, ['error', 'warning', 'message', 'status_description'])) {
                    $this->line("   → {$value}");
                } elseif ($key === 'errors' && is_array($value)) {
                    foreach ($value as $error) {
                        $this->line("   → <fg=red>{$error}</>");
                    }
                } elseif ($key === 'warnings' && is_array($value)) {
                    foreach ($value as $warning) {
                        $this->line("   → <fg=yellow>{$warning}</>");
                    }
                } elseif (in_array($key, ['response_time_ms', 'duration', 'cache_ttl_minutes'])) {
                    $this->line("   → {$key}: <fg=cyan>{$value}</>");
                }
            }
        }
    }

    private function displaySummary(array $summary): void
    {
        $this->line('<fg=yellow>📊 Summary:</>');
        $this->line("   Total Checks: <fg=cyan>{$summary['total_checks']}</>");

        if ($summary['healthy'] > 0) {
            $this->line("   <fg=green>✅ Healthy: {$summary['healthy']}</>");
        }

        if ($summary['caution'] > 0) {
            $this->line("   <fg=yellow>⚠️  Caution: {$summary['caution']}</>");
        }

        if ($summary['warning'] > 0) {
            $this->line("   <fg=yellow>⚠️  Warning: {$summary['warning']}</>");
        }

        if ($summary['critical'] > 0) {
            $this->line("   <fg=red>🔴 Critical: {$summary['critical']}</>");
        }

        if ($summary['error'] > 0) {
            $this->line("   <fg=red>❌ Error: {$summary['error']}</>");
        }

        if ($summary['skipped'] > 0) {
            $this->line("   <fg=blue>⏭️  Skipped: {$summary['skipped']}</>");
        }

        if ($summary['disabled'] > 0) {
            $this->line("   <fg=gray>🚫 Disabled: {$summary['disabled']}</>");
        }

        $this->newLine();
    }

    private function attemptFixes($result): void
    {
        $this->line('<fg=yellow>🔧 Attempting Automatic Fixes...</>');

        $checks = $result->getChecks();
        $fixed = 0;

        // Fix token sync issues
        if (isset($checks['token_status']) && $checks['token_status']['status'] !== 'healthy') {
            if ($this->attemptTokenSync()) {
                $this->line('   ✅ Synced tokens from database to cache');
                $fixed++;
            }
        }

        // Clear expired cache
        if (isset($checks['cache_system']) && $checks['cache_system']['status'] === 'error') {
            if ($this->attemptCacheClear()) {
                $this->line('   ✅ Cleared potentially corrupted cache');
                $fixed++;
            }
        }

        if ($fixed === 0) {
            $this->line('   ℹ️  No automatic fixes available for current issues');
        } else {
            $this->line("   🎉 Applied {$fixed} automatic fix(es)");
            $this->line('   💡 Run the health check again to verify fixes');
        }

        $this->newLine();
    }

    private function attemptTokenSync(): bool
    {
        try {
            $tokenService = app(\McoreServices\TeamleaderSDK\Services\TokenService::class);

            return $tokenService->syncTokensToCache();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function attemptCacheClear(): bool
    {
        try {
            \Illuminate\Support\Facades\Cache::flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displayRecommendations($result): void
    {
        $this->line('<fg=yellow>💡 Recommendations:</>');

        if ($result->hasErrors()) {
            $this->line('   • Fix configuration errors before proceeding');
            $this->line('   • Run <fg=cyan>php artisan teamleader:config:validate</> for detailed config check');
        }

        if ($result->hasCriticalIssues()) {
            $this->line('   • Critical issues require immediate attention');
            $this->line('   • Check your Teamleader API credentials and permissions');
        }

        if ($result->hasWarnings()) {
            $this->line('   • Address warnings to improve reliability');
            $this->line('   • Consider updating PHP extensions and Laravel version');
        }

        $score = app(HealthCheckService::class)->getHealthScore();
        if ($score < 80) {
            $this->line('   • Health score is below 80 - review failed checks');
        }

        $this->line('   • Monitor rate limits with <fg=cyan>php artisan teamleader:status</> regularly');
        $this->line('   • Check <fg=cyan>config/teamleader.php</> for optimization opportunities');
    }
}
