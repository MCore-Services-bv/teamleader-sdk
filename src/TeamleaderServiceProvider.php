<?php

namespace McoreServices\TeamleaderSDK;

use Illuminate\Support\ServiceProvider;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Services\ConfigurationValidator;
use McoreServices\TeamleaderSDK\Services\HealthCheckService;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Console\Commands\TeamleaderStatusCommand;
use McoreServices\TeamleaderSDK\Console\Commands\TeamleaderHealthCommand;
use McoreServices\TeamleaderSDK\Console\Commands\TeamleaderConfigValidateCommand;
use Psr\Log\LoggerInterface;

class TeamleaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/teamleader.php' => config_path('teamleader.php'),
        ], 'teamleader-config');

        $this->publishes([
            __DIR__.'/../docs' => resource_path('docs/teamleader'),
        ], 'teamleader-docs');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                TeamleaderStatusCommand::class,
                TeamleaderHealthCommand::class,
                TeamleaderConfigValidateCommand::class,
            ]);
        }

        // Register custom validation rules
        $this->registerValidationRules();

        // Register macros for testing
        if ($this->app->environment(['testing', 'local'])) {
            $this->registerTestingMacros();
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/teamleader.php', 'teamleader');

        // Register core services as singletons
        $this->registerCoreServices();

        // Register SDK singleton instance
        $this->app->singleton(TeamleaderSDK::class, function ($app) {
            return new TeamleaderSDK(
                $app->make(TokenService::class),
                $app->make(ApiRateLimiterService::class),
                $app->has('log') ? $app->make(LoggerInterface::class) : null,
                $app->make(TeamleaderErrorHandler::class)
            );
        });

        // Register aliases
        $this->app->alias(TeamleaderSDK::class, 'teamleader');
        $this->app->alias(TokenService::class, 'teamleader.tokens');
        $this->app->alias(ApiRateLimiterService::class, 'teamleader.ratelimiter');
        $this->app->alias(ConfigurationValidator::class, 'teamleader.config.validator');
        $this->app->alias(HealthCheckService::class, 'teamleader.health');
    }

    /**
     * Register core SDK services
     */
    protected function registerCoreServices(): void
    {
        // Token service for OAuth management
        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService();
        });

        // Rate limiter for API throttling
        $this->app->singleton(ApiRateLimiterService::class, function ($app) {
            $logger = $app->has('log') ? $app->make(LoggerInterface::class) : null;
            return new ApiRateLimiterService($logger);
        });

        // Configuration validator
        $this->app->singleton(ConfigurationValidator::class, function ($app) {
            return new ConfigurationValidator();
        });

        // Error handler
        $this->app->singleton(TeamleaderErrorHandler::class, function ($app) {
            $logger = $app->has('log') ? $app->make(LoggerInterface::class) : null;
            $throwExceptions = config('teamleader.error_handling.throw_exceptions', false);
            return new TeamleaderErrorHandler($logger, $throwExceptions);
        });

        // Health check service
        $this->app->singleton(HealthCheckService::class, function ($app) {
            return new HealthCheckService(
                $app->make(TeamleaderSDK::class),
                $app->make(ConfigurationValidator::class)
            );
        });
    }

    /**
     * Register custom validation rules
     */
    protected function registerValidationRules(): void
    {
        // Add custom validation rules for Teamleader-specific data
        $this->app->make('validator')->extend('teamleader_uuid', function ($attribute, $value, $parameters, $validator) {
            // Teamleader UUIDs are typically 36-character UUIDs
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
        });

        $this->app->make('validator')->extend('teamleader_email_array', function ($attribute, $value, $parameters, $validator) {
            if (!is_array($value)) {
                return false;
            }

            foreach ($value as $email) {
                if (!isset($email['type'], $email['email']) || !filter_var($email['email'], FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
            }

            return true;
        });

        $this->app->make('validator')->extend('teamleader_phone_array', function ($attribute, $value, $parameters, $validator) {
            if (!is_array($value)) {
                return false;
            }

            foreach ($value as $phone) {
                if (!isset($phone['type'], $phone['number'])) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Register testing macros
     */
    protected function registerTestingMacros(): void
    {
        // Add collection macro for testing responses
        if (class_exists('Illuminate\Support\Collection')) {
            \Illuminate\Support\Collection::macro('assertStructure', function (array $structure) {
                \McoreServices\TeamleaderSDK\Testing\TeamleaderTestHelpers::assertResponseStructure($this->toArray(), $structure);
                return $this;
            });
        }

        // Add HTTP testing macros if available
        if (class_exists('Illuminate\Testing\TestResponse')) {
            \Illuminate\Testing\TestResponse::macro('assertTeamleaderStructure', function (array $structure) {
                \McoreServices\TeamleaderSDK\Testing\TeamleaderTestHelpers::assertResponseStructure($this->json(), $structure);
                return $this;
            });

            \Illuminate\Testing\TestResponse::macro('assertTeamleaderPagination', function () {
                \McoreServices\TeamleaderSDK\Testing\TeamleaderTestHelpers::assertPaginationStructure($this->json());
                return $this;
            });

            \Illuminate\Testing\TestResponse::macro('assertTeamleaderError', function () {
                \McoreServices\TeamleaderSDK\Testing\TeamleaderTestHelpers::assertErrorStructure($this->json());
                return $this;
            });
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TeamleaderSDK::class,
            TokenService::class,
            ApiRateLimiterService::class,
            ConfigurationValidator::class,
            HealthCheckService::class,
            TeamleaderErrorHandler::class,
            'teamleader',
            'teamleader.tokens',
            'teamleader.ratelimiter',
            'teamleader.config.validator',
            'teamleader.health',
            TeamleaderStatusCommand::class,
            TeamleaderHealthCommand::class,
            TeamleaderConfigValidateCommand::class,
        ];
    }
}
