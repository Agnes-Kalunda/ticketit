<?php

namespace Ticket\Ticketit\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user || (!$user->ticketit_admin && !$user->ticketit_agent)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}