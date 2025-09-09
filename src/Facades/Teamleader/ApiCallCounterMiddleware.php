<?php

namespace App\Http\Middleware;

use Closure;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class ApiCallCounterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Reset the API call counter at the beginning of each request
        Teamleader::resetApiCallStats();

        return $next($request);
    }
}
