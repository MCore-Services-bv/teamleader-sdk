<?php

namespace McoreServices\TeamleaderSDK;

use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;
use McoreServices\TeamleaderSDK\Events\RequestSending;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Listeners\LogApiTraffic;
use McoreServices\TeamleaderSDK\Services\ConfigurationValidator;
use McoreServices\TeamleaderSDK\Tokens\DatabaseTokenStore;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;

class TeamleaderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/teamleader.php', 'teamleader');

        // Where tokens are kept. Bind your own TokenStore to replace it.
        $this->app->singletonIf(TokenStore::class, fn () => new DatabaseTokenStore);

        // Credentials added with teamleader:connections:add
        $this->app->singletonIf(DatabaseConnectionStore::class, fn () => new DatabaseConnectionStore);

        // One SDK instance per connection, built on first use
        $this->app->singleton(ConnectionManager::class, fn () => new ConnectionManager);

        // TeamleaderSDK and the facade resolve the default connection, so
        // single-account code is unchanged
        $this->app->singleton(TeamleaderSDK::class, fn ($app) => $app->make(ConnectionManager::class)->connection());

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

        // The token table. Run with `php artisan migrate`; publishing is only
        // needed to change them.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'teamleader-migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\TeamleaderStatusCommand::class,
                Console\Commands\TeamleaderHealthCommand::class,
                Console\Commands\TeamleaderConfigValidateCommand::class,
                Console\Commands\TeamleaderExportUuidsCommand::class,
                Console\Commands\RefreshTokensCommand::class,
                Console\Commands\ConnectionsAddCommand::class,
                Console\Commands\ConnectionsListCommand::class,
                Console\Commands\ConnectionsRemoveCommand::class,
            ]);
        }

        $this->scheduleTokenRefresh();

        $this->registerTrafficLogging();

        // Validate configuration on boot (if enabled)
        $this->validateConfigurationOnBoot();
    }

    /**
     * Renew tokens every ten minutes, so a connection nobody uses stays
     * connected and a refused refresh token is found before a real request
     * needs it. Needs the Laravel scheduler to run. Off with
     * teamleader.tokens.auto_refresh = false.
     */
    protected function scheduleTokenRefresh(): void
    {
        if (! config('teamleader.tokens.auto_refresh', true)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('teamleader:tokens:refresh')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    /**
     * Log request and response bodies when the configuration asks for it.
     *
     * Implemented as event listeners, so logging and any listener of your own
     * see exactly the same (sanitised) data.
     */
    protected function registerTrafficLogging(): void
    {
        if (config('teamleader.logging.log_requests')) {
            Event::listen(RequestSending::class, [LogApiTraffic::class, 'requestSending']);
        }

        if (config('teamleader.logging.log_responses')) {
            Event::listen(ResponseReceived::class, [LogApiTraffic::class, 'responseReceived']);
        }
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
     */
    protected function validateConfigurationOnBoot(): void
    {
        // Only validate if explicitly enabled
        if (! config('teamleader.validate_on_boot', false)) {
            return;
        }

        try {
            $validator = new ConfigurationValidator;
            $result = $validator->validate();

            // Environment-specific validation depth
            $environment = $this->app->environment();
            $isProduction = $environment === 'production';

            if (! $result->isValid()) {
                // Configuration has errors
                $errorCount = $result->getErrorCount();
                $errors = implode(', ', array_slice($result->errors, 0, 3)); // First 3 errors

                if ($isProduction) {
                    // In production, log critical errors but don't break the app
                    Log::critical('Teamleader SDK configuration invalid', [
                        'error_count' => $errorCount,
                        'errors' => $result->errors,
                        'environment' => $environment,
                        'validation_summary' => $result->getSummary(),
                    ]);
                } else {
                    // In development, be more verbose
                    Log::error('Teamleader SDK configuration validation failed', [
                        'error_count' => $errorCount,
                        'warning_count' => $result->getWarningCount(),
                        'errors' => $result->errors,
                        'warnings' => $result->warnings,
                        'environment' => $environment,
                        'suggestions' => $validator->getSuggestions(),
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

                if (! $isProduction) {
                    // Only log warnings in non-production
                    Log::warning('Teamleader SDK configuration has warnings', [
                        'warning_count' => $warningCount,
                        'warnings' => $result->warnings,
                        'environment' => $environment,
                        'suggestions' => $validator->getSuggestions(),
                    ]);
                }
            } else {
                // Configuration is completely valid
                Log::debug('Teamleader SDK configuration validated successfully', [
                    'environment' => $environment,
                    'validated_at' => now()->toIso8601String(),
                ]);
            }

        } catch (Exception $e) {
            // Don't let validation errors break the application
            Log::error('Teamleader SDK configuration validation encountered an error', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
        }
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            TeamleaderSDK::class,
            'teamleader',
        ];
    }
}
