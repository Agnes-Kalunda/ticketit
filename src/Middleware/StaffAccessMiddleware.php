<?php
namespace Ticket\Ticketit\Middleware;

use Closure;
use Ticket\Ticketit\Models\Agent;

class StaffAccessMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!Agent::isAdmin() && !Agent::isAgent(auth()->id())) {
            return redirect()->route('staff.tickets.index')
                ->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}