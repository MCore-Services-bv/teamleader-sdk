<?php

namespace McoreServices\TeamleaderSDK;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use McoreServices\TeamleaderSDK\Services\ConfigurationValidator;

class TeamleaderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/teamleader.php', 'teamleader');

        // Register SDK singleton
        $this->app->singleton(TeamleaderSDK::class, function ($app) {
            return new TeamleaderSDK();
        });

        // Register facade alias
        $this->app->alias(TeamleaderSDK::class, 'teamleader');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/teamleader.php' => config_path('teamleader.php'),
        ], 'teamleader-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'teamleader-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\TeamleaderStatusCommand::class,
                Console\Commands\TeamleaderHealthCommand::class,
                Console\Commands\TeamleaderConfigValidateCommand::class,
            ]);
        }

        // Validate configuration on boot (if enabled)
        $this->validateConfigurationOnBoot();
    }

    /**
     * Validate SDK configuration on application boot
     *
     * This method runs automatic configuration validation when the application boots.
     * It's designed to catch configuration issues early in development and production.
     *
     * Behavior:
     * - Only runs if explicitly enabled via config: teamleader.validate_on_boot
     * - Logs warnings for invalid configuration (does not throw exceptions)
     * - Logs critical errors for missing required configuration
     * - In production: only validates critical configuration
     * - In development: performs comprehensive validation
     *
     * Configuration:
     * Set in config/teamleader.php or .env:
     *   'validate_on_boot' => env('TEAMLEADER_VALIDATE_ON_BOOT', false)
     *   TEAMLEADER_VALIDATE_ON_BOOT=true
     *
     * @return void
     */
    protected function validateConfigurationOnBoot(): void
    {
        // Only validate if explicitly enabled
        if (!config('teamleader.validate_on_boot', false)) {
            return;
        }

        try {
            $validator = new ConfigurationValidator();
            $result = $validator->validate();

            // Environment-specific validation depth
            $environment = $this->app->environment();
            $isProduction = $environment === 'production';

            if (!$result->isValid()) {
                // Configuration has errors
                $errorCount = $result->getErrorCount();
                $errors = implode(', ', array_slice($result->errors, 0, 3)); // First 3 errors

                if ($isProduction) {
                    // In production, log critical errors but don't break the app
                    Log::critical('Teamleader SDK configuration invalid', [
                        'error_count' => $errorCount,
                        'errors' => $result->errors,
                        'environment' => $environment,
                        'validation_summary' => $result->getSummary()
                    ]);
                } else {
                    // In development, be more verbose
                    Log::error('Teamleader SDK configuration validation failed', [
                        'error_count' => $errorCount,
                        'warning_count' => $result->getWarningCount(),
                        'errors' => $result->errors,
                        'warnings' => $result->warnings,
                        'environment' => $environment,
                        'suggestions' => $validator->getSuggestions()
                    ]);

                    // Optionally show in console during development
                    if ($this->app->runningInConsole() && config('app.debug')) {
                        echo "\n\033[0;31m[Teamleader SDK] Configuration validation failed!\033[0m\n";
                        echo "Errors: {$errors}\n";
                        echo "Run: php artisan teamleader:config:validate for details\n\n";
                    }
                }
            } elseif ($result->hasWarnings()) {
                // Configuration is valid but has warnings
                $warningCount = $result->getWarningCount();

                if (!$isProduction) {
                    // Only log warnings in non-production
                    Log::warning('Teamleader SDK configuration has warnings', [
                        'warning_count' => $warningCount,
                        'warnings' => $result->warnings,
                        'environment' => $environment,
                        'suggestions' => $validator->getSuggestions()
                    ]);
                }
            } else {
                // Configuration is completely valid
                Log::debug('Teamleader SDK configuration validated successfully', [
                    'environment' => $environment,
                    'validated_at' => now()->toIso8601String()
                ]);
            }

            // Cache validation result to avoid repeated checks
            if (config('teamleader.caching.enabled')) {
                cache()->put(
                    'teamleader_config_validation',
                    [
                        'is_valid' => $result->isValid(),
                        'validated_at' => now()->toIso8601String(),
                        'summary' => $result->getSummary()
                    ],
                    3600 // Cache for 1 hour
                );
            }

        } catch (\Exception $e) {
            // Don't let validation errors break the application
            Log::error('Teamleader SDK configuration validation encountered an error', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);
        }
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            TeamleaderSDK::class,
            'teamleader'
        ];
    }
}
