<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Resources\Deals;

use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use McoreServices\TeamleaderSDK\Resources\Deals\Phases;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;
use McoreServices\TeamleaderSDK\Tests\Support\RecordingApiClient;

/**
 * Every pipeline has four fixed phases that cannot be deleted. A bulk delete
 * in a client migration failed four times with Teamleader's bare
 * "Unable to delete fixed deal phase."; the SDK now says what that means.
 */
final class FixedPhasesTest extends ResourceTestCase
{
    public function test_a_refused_fixed_phase_throws_a_clear_validation_exception(): void
    {
        $api = new class extends RecordingApiClient
        {
            public function request($method, $endpoint, $data = [])
            {
                throw new ValidationException('Unable to delete fixed deal phase.', 400, null, [], 400, ['Unable to delete fixed deal phase.']);
            }
        };

        try {
            (new Phases($api))->delete('phase-new');
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Deal phase phase-new is one of the four fixed phases', $e->getMessage());
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
            $this->assertSame(400, $e->getStatusCode());
            $this->assertInstanceOf(ValidationException::class, $e->getPrevious());
        }
    }

    public function test_other_refusals_pass_through_unchanged(): void
    {
        $api = new class extends RecordingApiClient
        {
            public function request($method, $endpoint, $data = [])
            {
                throw new ValidationException('Phase not found', 404);
            }
        };

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Phase not found');

        (new Phases($api))->delete('phase-x');
    }

    public function test_the_error_array_gets_the_clear_message_with_exceptions_off(): void
    {
        $this->api->queueResponse(['error' => true, 'status_code' => 400, 'message' => 'Unable to delete fixed deal phase.']);

        $response = $this->resource(Phases::class)->delete('phase-new');

        $this->assertTrue($response['error']);
        $this->assertStringContainsString('one of the four fixed phases', $response['message']);
    }
}
