<?php

namespace Ticket\Ticketit\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Models\Setting;

class StaffAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('StaffAuthMiddleware: Checking authentication', [
            'path' => $request->path(),
            'staff_check' => Auth::guard('web')->check(),
            'user_id' => Auth::guard('web')->id()
        ]);

        if (!Auth::guard('web')->check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }
            
            return redirect()->route('login')
                ->with('error', 'Please login to access staff area.');
        }

        return $next($request);
    }
}