<?php

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Services\TeamleaderErrorHandler;
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;
use McoreServices\TeamleaderSDK\Exceptions\ServerException;
use Psr\Log\NullLogger;

class ErrorHandlerTest extends TestCase
{
    private TeamleaderErrorHandler $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandler = new TeamleaderErrorHandler(new NullLogger(), true);
    }

    /** @test */
    public function it_throws_validation_exception_for_422(): void
    {
        $this->expectException(ValidationException::class);

        $result = [
            'error' => true,
            'status_code' => 422,
            'message' => 'Validation failed',
            'errors' => ['Field is required']
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    /** @test */
    public function it_throws_rate_limit_exception_for_429(): void
    {
        $this->expectException(RateLimitExceededException::class);

        $result = [
            'error' => true,
            'status_code' => 429,
            'message' => 'Rate limit exceeded',
            'headers' => ['Retry-After' => ['60']]
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    /** @test */
    public function it_throws_server_exception_for_500(): void
    {
        $this->expectException(ServerException::class);

        $result = [
            'error' => true,
            'status_code' => 500,
            'message' => 'Server error'
        ];

        $this->errorHandler->handleApiError($result, 'test');
    }

    /** @test */
    public function it_does_not_throw_when_disabled(): void
    {
        $errorHandler = new TeamleaderErrorHandler(new NullLogger(), false);

        $result = [
            'error' => true,
            'status_code' => 500,
            'message' => 'Server error'
        ];

        // Should not throw
        $errorHandler->handleApiError($result, 'test');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function it_identifies_retryable_errors(): void
    {
        $serverError = new ServerException('Server error', 500);
        $this->assertTrue($this->errorHandler->isRetryableError($serverError));

        $rateLimitError = new RateLimitExceededException('Rate limit', 60);
        $this->assertTrue($this->errorHandler->isRetryableError($rateLimitError));

        $validationError = new ValidationException('Validation failed', 422);
        $this->assertFalse($this->errorHandler->isRetryableError($validationError));
    }
}
