<?php

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Exceptions\ServerException;
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use Psr\Log\NullLogger;

class ErrorHandlerTest extends TestCase
{
    private TeamleaderErrorHandler $errorHandler;

    public function test_throws_validation_exception_for422(): void
    {
        $this->expectException(ValidationException::class);

        $result = [
            'error' => true,
            'status_code' => 422,
            'message' => 'Validation failed',
            'errors' => ['Field is required'],
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    public function test_throws_rate_limit_exception_for429(): void
    {
        $this->expectException(RateLimitExceededException::class);

        $result = [
            'error' => true,
            'status_code' => 429,
            'message' => 'Rate limit exceeded',
            'headers' => ['Retry-After' => ['60']],
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    /**
     * 429 must always throw RateLimitExceededException regardless of the
     * throwExceptions flag. Swallowing a 429 silently returns an empty result
     * to the caller with no indication that the request failed — data loss.
     */
    public function test_throws_rate_limit_exception_for429_even_when_exceptions_disabled(): void
    {
        $errorHandler = new TeamleaderErrorHandler(new NullLogger, false);

        $this->expectException(RateLimitExceededException::class);

        $result = [
            'error' => true,
            'status_code' => 429,
            'message' => 'Rate limit exceeded',
            'headers' => ['Retry-After' => ['60']],
        ];

        $errorHandler->handleApiError($result, 'test');
    }

    /**
     * When both Retry-After and X-RateLimit-Reset headers are present, the
     * exception must be constructed without a TypeError. Previously, extractResetTime()
     * returned ?string while RateLimitExceededException::__construct() declared
     * ?int — under strict_types this caused a TypeError before the exception was
     * ever built, making every 429 with an X-RateLimit-Reset header uncatchable.
     */
    public function test_throws_rate_limit_exception_for429_with_reset_time_header(): void
    {
        $this->expectException(RateLimitExceededException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $result = [
            'error' => true,
            'status_code' => 429,
            'message' => 'Rate limit exceeded',
            'headers' => [
                'Retry-After' => ['30'],
                'X-RateLimit-Reset' => ['1634567890'],
            ],
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    /**
     * Verify reset time is correctly cast to int and accessible via getResetTime().
     */
    public function test_rate_limit_exception_carries_reset_time_as_int(): void
    {
        $caughtException = null;

        try {
            $this->errorHandler->handleApiError([
                'error' => true,
                'status_code' => 429,
                'message' => 'Rate limit exceeded',
                'headers' => [
                    'Retry-After' => ['30'],
                    'X-RateLimit-Reset' => ['1634567890'],
                ],
            ], 'test');
        } catch (RateLimitExceededException $e) {
            $caughtException = $e;
        }

        $this->assertNotNull($caughtException);
        $this->assertIsInt($caughtException->getResetTime());
        $this->assertEquals(1634567890, $caughtException->getResetTime());
        $this->assertEquals(30, $caughtException->getRetryAfter());
    }

    public function test_throws_server_exception_for500(): void
    {
        $this->expectException(ServerException::class);

        $result = [
            'error' => true,
            'status_code' => 500,
            'message' => 'Server error',
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    public function test_does_not_throw_when_disabled_for_non_429_errors(): void
    {
        $errorHandler = new TeamleaderErrorHandler(new NullLogger, false);

        $result = [
            'error' => true,
            'status_code' => 500,
            'message' => 'Server error',
        ];

        // Should not throw for 500 when exceptions are disabled
        $errorHandler->handleApiError($result, 'test');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_identifies_retryable_errors(): void
    {
        $serverError = new ServerException('Server error', 500);
        $this->assertTrue($this->errorHandler->isRetryableError($serverError));

        $rateLimitError = new RateLimitExceededException('Rate limit', 60);
        $this->assertTrue($this->errorHandler->isRetryableError($rateLimitError));

        $validationError = new ValidationException('Validation failed', 422);
        $this->assertFalse($this->errorHandler->isRetryableError($validationError));
    }

    /**
     * withRetry must re-throw RateLimitExceededException immediately without sleeping.
     * Sleeping for Retry-After inside a queue worker blocks the worker thread.
     */
    public function test_with_retry_rethrows_rate_limit_exception_immediately(): void
    {
        $this->expectException(RateLimitExceededException::class);

        $callCount = 0;

        $this->errorHandler->withRetry(function () use (&$callCount) {
            $callCount++;
            throw new RateLimitExceededException('Rate limit exceeded', 60);
        }, 3, 'test');

        // Callback must only be called once — withRetry must not retry on 429
        $this->assertEquals(1, $callCount);
    }

    public function test_with_retry_retries_server_exceptions(): void
    {
        $this->expectException(ServerException::class);

        $callCount = 0;

        $this->errorHandler->withRetry(function () use (&$callCount) {
            $callCount++;
            throw new ServerException('Server error', 500);
        }, 3, 'test');

        // ServerException should be retried up to maxAttempts before throwing
        $this->assertEquals(3, $callCount);
    }

    public function test_rate_limit_exception_carries_retry_after(): void
    {
        $caughtException = null;

        try {
            $this->errorHandler->withRetry(function () {
                throw new RateLimitExceededException('Rate limit exceeded', 45);
            }, 3, 'test');
        } catch (RateLimitExceededException $e) {
            $caughtException = $e;
        }

        $this->assertNotNull($caughtException);
        $this->assertEquals(45, $caughtException->getRetryAfter());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandler = new TeamleaderErrorHandler(new NullLogger, true);
    }
}
