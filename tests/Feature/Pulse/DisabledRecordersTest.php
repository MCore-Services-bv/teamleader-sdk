<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Pulse;

use Illuminate\Foundation\Application;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderCallers;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests;
use PHPUnit\Framework\Attributes\Group;

/**
 * `'enabled' => false` in config/pulse.php turns a recorder off, as for
 * Pulse's own recorders.
 */
#[Group('pulse')]
final class DisabledRecordersTest extends PulseTestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('pulse.recorders.'.TeamleaderRequests::class.'.enabled', false);
        $app['config']->set('pulse.recorders.'.TeamleaderCallers::class.'.enabled', false);
    }

    public function test_disabled_recorders_record_nothing(): void
    {
        event(new ResponseReceived('POST', 'deals.list', 200, 10.0));
        event(new RequestFailed('POST', 'deals.list', null, 'Timed out'));

        foreach (Entries::types() as $type) {
            $this->assertCount(0, $this->entries($type), $type);
        }
    }
}
