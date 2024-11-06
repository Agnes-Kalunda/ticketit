<?php

namespace Ticket\Ticketit\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Models\Setting;

class CustomerAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('CustomerAuthMiddleware: Checking authentication', [
            'path' => $request->path(),
            'customer_check' => Auth::guard('customer')->check(),
            'user_id' => Auth::guard('customer')->id()
        ]);

        if (!Auth::guard('customer')->check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }
            
            return redirect()->route('customer.login')
                ->with('error', 'Please login to continue.');
        }

        return $next($request);
    }
}