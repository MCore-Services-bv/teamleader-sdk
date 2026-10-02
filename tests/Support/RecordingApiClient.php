<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support;

use McoreServices\TeamleaderSDK\Testing\FakeTeamleader;

/**
 * A TeamleaderSDK that records requests instead of sending them — the SDK's
 * own tests' name for FakeTeamleader, the class behind Teamleader::fake().
 *
 * Resources are constructed with a TeamleaderSDK instance and call
 * $this->api->request(...) to reach the API. Substituting this class lets a test
 * assert on the payload a resource *builds* without any network, token or Redis
 * involvement.
 *
 * This is the layer most of the SDK's historical bugs have lived in: a wrong
 * body key (`include` instead of `includes`), a wrong shape (a sort string array
 * instead of objects), or a filter that was silently dropped. The API answers
 * 200 to all of them, so only a payload assertion catches them.
 *
 * Usage:
 *
 *     $api = new RecordingApiClient;
 *     $files = new Files($api);
 *
 *     $files->forProduct('product-uuid');
 *
 *     $api->lastBody();      // the array that would have been sent
 *     $api->lastEndpoint();  // 'files.list'
 *
 * Responses default to an empty successful list. Queue specific ones when the
 * code under test reads what comes back:
 *
 *     $api->queueResponse(['data' => [['id' => 'abc']], 'headers' => []]);
 */
class RecordingApiClient extends FakeTeamleader {}
