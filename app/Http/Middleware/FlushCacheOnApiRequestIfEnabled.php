<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class FlushCacheOnApiRequestIfEnabled
{
    /**
     * Flush the default application cache store before handling the request (optional).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('api.flush_cache_on_every_request', false)) {
            Cache::flush();
        }

        return $next($request);
    }
}
