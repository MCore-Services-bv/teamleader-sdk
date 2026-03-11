<?php

namespace McoreServices\TeamleaderSDK\Facades;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Facade;
use McoreServices\TeamleaderSDK\Resources\CRM\Companies;
use McoreServices\TeamleaderSDK\Resources\CRM\Contacts;
use McoreServices\TeamleaderSDK\Resources\General\CustomFields;
use McoreServices\TeamleaderSDK\Resources\General\Departments;
use McoreServices\TeamleaderSDK\Resources\General\Users;

/**
 * @method static Companies companies()
 * @method static Contacts contacts()
 * @method static Departments departments()
 * @method static CustomFields customFields()
 * @method static Users users()
 * @method static bool handleCallback(string $code, string $state)
 * @method static RedirectResponse authorize()
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
