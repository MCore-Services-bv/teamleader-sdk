<?php

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Services\ConfigurationValidator;

class TeamleaderConfigValidateCommand extends Command
{
    protected $signature = 'teamleader:config:validate
                           {--json : Output as JSON}
                           {--fix : Show how to fix issues}
                           {--report : Generate full configuration report}';

    protected $description = 'Validate Teamleader SDK configuration and environment';

    public function handle(ConfigurationValidator $validator)
    {
        if ($this->option('report')) {
            return $this->generateReport($validator);
        }

        $this->info('⚙️  Validating Teamleader Configuration...');
        $this->newLine();

        $result = $validator->validate();

        if ($this->option('json')) {
            $this->outputJson($result);
            return $result->isValid() ? 0 : 1;
        }

        $this->displayValidationResult($result);

        if ($this->option('fix')) {
            $this->showFixSuggestions($validator);
        }

        $this->displayRecommendations($validator);

        return $result->isValid() ? 0 : 1;
    }

    private function generateReport(ConfigurationValidator $validator): int
    {
        $report = $validator->generateReport();

        $this->info('📋 Teamleader SDK Configuration Report');
        $this->line('Generated: ' . now()->toDateTimeString());
        $this->newLine();

        // Environment Info
        $this->line('<fg=yellow>Environment Information:</fg=yellow>');
        $this->line("  Environment: <fg=cyan>{$report['environment']}</>");
        $this->line("  PHP Version: <fg=cyan>{$report['php_version']}</>");
        $this->line("  Laravel Version: <fg=cyan>{$report['laravel_version']}</>");
        $this->newLine();

        // Configuration Summary
        $this->line('<fg=yellow>Configuration Summary:</fg=yellow>');
        foreach ($report['configuration_summary'] as $key => $value) {
            $displayKey = ucwords(str_replace('_', ' ', $key));
            if (is_bool($value)) {
                $value = $value ? 'enabled' : 'disabled';
            }
            $this->line("  {$displayKey}: <fg=cyan>{$value}</>");
        }
        $this->newLine();

        // Validation Results
        $status = $report['overall_status'];
        $statusColor = $status === 'valid' ? 'green' : 'red';
        $this->line("Overall Status: <fg={$statusColor}>" . strtoupper($status) . "</>");

        if (!empty($report['errors'])) {
            $this->line('<fg=red>Errors:</fg=red>');
            foreach ($report['errors'] as $error) {
                $this->line("  ❌ {$error}");
            }
        }

        if (!empty($report['warnings'])) {
            $this->line('<fg=yellow>Warnings:</fg=yellow>');
            foreach ($report['warnings'] as $warning) {
                $this->line("  ⚠️  {$warning}");
            }
        }

        if (!empty($report['suggestions'])) {
            $this->newLine();
            $this->line('<fg=yellow>Optimization Suggestions:</fg=yellow>');
            foreach ($report['suggestions'] as $suggestion) {
                $this->line("  💡 {$suggestion['title']}: {$suggestion['description']}");
                if (isset($suggestion['config'])) {
                    $this->line("     Config: <fg=cyan>{$suggestion['config']}</>");
                }
            }
        }

        return $status === 'valid' ? 0 : 1;
    }

    private function displayValidationResult($result): void
    {
        if ($result->isValid()) {
            $this->line('<fg=green>✅ Configuration is valid!</>');
        } else {
            $this->line('<fg=red>❌ Configuration has errors!</>');

            foreach ($result->errors as $error) {
                $this->line("   <fg=red>• {$error}</>");
            }
        }

        if ($result->hasWarnings()) {
            $this->newLine();
            $this->line('<fg=yellow>⚠️  Configuration warnings:</>');

            foreach ($result->warnings as $warning) {
                $this->line("   <fg=yellow>• {$warning}</>");
            }
        }

        $this->newLine();
        $this->line($result->getSummary());
        $this->newLine();
    }

    private function showFixSuggestions(ConfigurationValidator $validator): void
    {
        $this->line('<fg=yellow>🔧 Fix Suggestions:</>');

        // Check for missing environment variables
        $required = [
            'TEAMLEADER_CLIENT_ID' => 'Your Teamleader application client ID',
            'TEAMLEADER_CLIENT_SECRET' => 'Your Teamleader application client secret',
            'TEAMLEADER_REDIRECT_URI' => 'Your application callback URL'
        ];

        foreach ($required as $env => $description) {
            if (empty(env($env))) {
                $this->line("   1. Add to .env: <fg=cyan>{$env}=your_value</>");
                $this->line("      Description: {$description}");
            }
        }

        // Database suggestions
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->line('   2. Fix database connection:');
            $this->line('      <fg=cyan>php artisan migrate</> (if migrations needed)');
            $this->line('      Check DB_CONNECTION settings in .env');
        }

        // Cache suggestions
        if (!config('teamleader.caching.enabled')) {
            $this->line('   3. Enable caching for better performance:');
            $this->line('      Set <fg=cyan>TEAMLEADER_CACHING_ENABLED=true</> in .env');
        }

        // Production-specific suggestions
        if (app()->environment('production')) {
            if (config('teamleader.development.debug_mode')) {
                $this->line('   4. Disable debug mode in production:');
                $this->line('      Set <fg=cyan>TEAMLEADER_DEBUG_MODE=false</> in .env');
            }
        }

        $this->newLine();
    }

    private function displayRecommendations(ConfigurationValidator $validator): void
    {
        $suggestions = $validator->getSuggestions();

        if (empty($suggestions)) {
            $this->line('<fg=green>💡 No additional recommendations - configuration looks good!</>');
            return;
        }

        $this->line('<fg=yellow>💡 Optimization Recommendations:</>');

        foreach ($suggestions as $suggestion) {
            $icon = match($suggestion['type']) {
                'performance' => '🚀',
                'security' => '🔒',
                'reliability' => '🛡️',
                default => '💡'
            };

            $this->line("   {$icon} <fg=cyan>{$suggestion['title']}</>: {$suggestion['description']}");

            if (isset($suggestion['config'])) {
                $this->line("      Config: <fg=green>{$suggestion['config']}</>");
            }
        }

        $this->newLine();
        $this->line('📚 For detailed configuration options, check:');
        $this->line('   • config/teamleader.php');
        $this->line('   • https://github.com/mcore-services/teamleader-sdk#configuration');
    }

    private function outputJson($result): void
    {
        $output = [
            'valid' => $result->isValid(),
            'has_warnings' => $result->hasWarnings(),
            'summary' => $result->getSummary(),
            'error_count' => $result->getErrorCount(),
            'warning_count' => $result->getWarningCount(),
        ];

        if (!empty($result->errors)) {
            $output['errors'] = $result->errors;
        }

        if (!empty($result->warnings)) {
            $output['warnings'] = $result->warnings;
        }

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }
}
