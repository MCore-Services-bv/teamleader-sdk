<?php

namespace McoreServices\TeamleaderSDK\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \McoreServices\TeamleaderSDK\Resources\CRM\Companies companies()
 * @method static \McoreServices\TeamleaderSDK\Resources\CRM\Contacts contacts()
 * @method static \McoreServices\TeamleaderSDK\Resources\General\Departments departments()
 * @method static \McoreServices\TeamleaderSDK\Resources\General\CustomFields customFields()
 * @method static \McoreServices\TeamleaderSDK\Resources\General\Users users()
 * @method static bool handleCallback(string $code, string $state)
 * @method static \Illuminate\Http\RedirectResponse authorize()
 * @method static array request(string $method, string $endpoint, array $data = [])
 * @method static bool isAuthenticated()
 * @method static void logout()
 * @method static int getApiCallCount()
 * @method static array getApiCalls()
 * @method static void resetApiCallStats()
 *
 * @see \McoreServices\TeamleaderSDK\TeamleaderSDK
 */
class Teamleader extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'teamleader';
    }
}
