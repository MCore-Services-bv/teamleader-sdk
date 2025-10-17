<?php

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Services\HealthCheckService;
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class TeamleaderStatusCommand extends Command
{
    protected $signature = 'teamleader:status
                           {--json : Output as JSON}';

    protected $description = 'Check Teamleader SDK connection status and statistics';

    public function handle(TeamleaderSDK $sdk, HealthCheckService $healthCheck)
    {
        $this->info('🔍 Checking Teamleader SDK Status...');
        $this->newLine();

        // Get basic connection info
        $isConnected = $sdk->isAuthenticated();
        $tokenInfo = $sdk->getTokenService()->getTokenInfo();
        $rateLimitStats = $sdk->getRateLimitStats();

        if ($this->option('json')) {
            $this->outputJson([
                'connected' => $isConnected,
                'api_version' => $sdk->getApiVersion(),
                'token_info' => $tokenInfo,
                'rate_limit' => $rateLimitStats,
                'api_calls_count' => $sdk::getApiCallCount(),
            ]);

            return;
        }

        // Connection Status
        $this->displayConnectionStatus($isConnected, $sdk);

        // Token Information
        $this->displayTokenInfo($tokenInfo);

        // Rate Limiting
        $this->displayRateLimitInfo($rateLimitStats);

        // API Statistics
        $this->displayApiStatistics($sdk);

        // Quick health check if verbose
        if ($this->option('verbose')) {
            $this->newLine();
            $this->line('<fg=yellow>Running quick health check...</>');
            $result = $healthCheck->check();
            $this->displayHealthSummary($result);
        }

        // Recommendations
        $this->displayRecommendations($isConnected, $rateLimitStats);

        return $isConnected ? 0 : 1;
    }

    private function displayConnectionStatus(bool $isConnected, TeamleaderSDK $sdk): void
    {
        $status = $isConnected ? '<fg=green>✅ Connected</>' : '<fg=red>❌ Not Connected</>';
        $this->line("Connection Status: {$status}");
        $this->line("API Version: <fg=cyan>{$sdk->getApiVersion()}</>");
        $this->line('Base URL: <fg=cyan>'.config('teamleader.base_url', 'https://api.focus.teamleader.eu').'</>');
        $this->newLine();
    }

    private function displayTokenInfo(array $tokenInfo): void
    {
        $this->line('<fg=yellow>📋 Token Information:</>');

        if ($tokenInfo['has_access_token']) {
            $this->line('  Access Token: <fg=green>✅ Present</>');
        } else {
            $this->line('  Access Token: <fg=red>❌ Missing</>');
        }

        if ($tokenInfo['has_refresh_token']) {
            $this->line('  Refresh Token: <fg=green>✅ Present</>');
        } else {
            $this->line('  Refresh Token: <fg=red>❌ Missing</>');
        }

        if ($tokenInfo['expires_at']) {
            $expiresAt = \Carbon\Carbon::parse($tokenInfo['expires_at']);
            $timeLeft = $expiresAt->diffForHumans();
            $color = $expiresAt->isPast() ? 'red' : ($expiresAt->diffInMinutes() < 30 ? 'yellow' : 'green');
            $this->line("  Expires: <fg={$color}>{$timeLeft}</>");
        }

        if ($tokenInfo['token_source']) {
            $this->line("  Source: <fg=blue>{$tokenInfo['token_source']}</>");
        }

        $this->newLine();
    }

    private function displayRateLimitInfo(array $stats): void
    {
        $this->line('<fg=yellow>🚦 Rate Limiting:</>');

        $usage = $stats['usage_percentage'];
        $color = match (true) {
            $usage >= 90 => 'red',
            $usage >= 70 => 'yellow',
            default => 'green'
        };

        $this->line("  Current Usage: <fg={$color}>{$usage}%</> ({$stats['current_usage']}/{$stats['rate_limit']})");
        $this->line("  Remaining: <fg=cyan>{$stats['remaining']}</> requests");
        $this->line("  Throttle Level: <fg=blue>{$stats['throttle_level']}</>");

        if ($stats['seconds_until_reset'] > 0) {
            $this->line("  Reset In: <fg=cyan>{$stats['seconds_until_reset']}</> seconds");
        }

        $this->newLine();
    }

    private function displayApiStatistics(TeamleaderSDK $sdk): void
    {
        $totalCalls = $sdk::getApiCallCount();
        $recentCalls = $sdk::getApiCalls();

        $this->line('<fg=yellow>📊 API Statistics:</>');
        $this->line("  Total Calls This Session: <fg=cyan>{$totalCalls}</>");

        if (! empty($recentCalls)) {
            $lastCall = end($recentCalls);
            $this->line("  Last Endpoint: <fg=cyan>{$lastCall['endpoint']}</>");
            $this->line('  Last Call: <fg=cyan>'.date('H:i:s', (int) $lastCall['timestamp']).'</>');
        }

        $this->newLine();
    }

    private function displayHealthSummary($healthResult): void
    {
        $status = $healthResult->getOverallStatus();
        $color = match ($status) {
            'healthy' => 'green',
            'warning' => 'yellow',
            default => 'red'
        };

        $this->line("Overall Health: <fg={$color}>".strtoupper($status).'</>');

        $summary = $healthResult->getSummary();
        if ($summary['error'] > 0) {
            $this->line("  <fg=red>Errors: {$summary['error']}</>");
        }
        if ($summary['warning'] > 0) {
            $this->line("  <fg=yellow>Warnings: {$summary['warning']}</>");
        }
    }

    private function displayRecommendations(bool $isConnected, array $rateLimitStats): void
    {
        $this->line('<fg=yellow>💡 Recommendations:</>');

        if (! $isConnected) {
            $this->line('  • Run <fg=cyan>php artisan teamleader:connect</> to authenticate');
        }

        if ($rateLimitStats['usage_percentage'] > 80) {
            $this->line('  • Consider implementing request throttling in your application');
        }

        if ($rateLimitStats['throttled_requests'] > 0) {
            $this->line("  • {$rateLimitStats['throttled_requests']} requests were throttled for optimal performance");
        }

        $this->line('  • Run <fg=cyan>php artisan teamleader:health</> for detailed diagnostics');
        $this->line('  • Run <fg=cyan>php artisan teamleader:config:validate</> to check configuration');
    }

    private function outputJson(array $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }
}
