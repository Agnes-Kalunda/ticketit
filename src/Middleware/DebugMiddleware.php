<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class DebugMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('Request Debug', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user' => auth()->guard('customer')->check() ? auth()->guard('customer')->user()->toArray() : 'Guest',
            'session' => $request->session()->all(),
            'route' => $request->route()->getName()
        ]);

        $response = $next($request);

        Log::info('Response Debug', [
            'status' => $response->status(),
            'content' => $response->getContent() ? 'Has Content' : 'Empty Content'
        ]);

        return $response;
    }
}