<?php

namespace Ticket\Ticketit\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Models\Ticket;

class ResAccessMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        $customer = Auth::guard('customer')->user();
        
        Log::info('ResAccessMiddleware: Checking access', [
            'path' => $request->path(),
            'user_id' => $user ? $user->id : null,
            'customer_id' => $customer ? $customer->id : null,
            'is_admin' => $user ? $user->ticketit_admin : false,
            'is_agent' => $user ? $user->ticketit_agent : false
        ]);

        // Admin has full access
        if ($user && $user->ticketit_admin) {
            return $next($request);
        }

        // Get ticket ID from route
        $ticketId = $this->getTicketId($request);
        if (!$ticketId) {
            Log::error('ResAccessMiddleware: No ticket ID found');
            return $this->unauthorizedRedirect();
        }

        try {
            $ticket = Ticket::findOrFail($ticketId);
            
            // Customer can access their own tickets
            if ($customer && $ticket->customer_id === $customer->id) {
                return $next($request);
            }

            // Agent access check
            if ($user && $user->ticketit_agent) {
                // Unrestricted agent access
                if (Setting::grab('agent_restrict') == 'no') {
                    return $next($request);
                }
                
                // Restricted to assigned tickets
                if ($ticket->agent_id === $user->id) {
                    return $next($request);
                }
            }

            Log::warning('ResAccessMiddleware: Access denied', [
                'ticket_id' => $ticketId,
                'user_id' => $user ? $user->id : null,
                'customer_id' => $customer ? $customer->id : null
            ]);

            return $this->unauthorizedRedirect();

        } catch (\Exception $e) {
            Log::error('ResAccessMiddleware: Error checking access', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            
            return $this->unauthorizedRedirect();
        }
    }

    protected function getTicketId($request)
    {
        // Check show route
        if ($request->route()->getName() == Setting::grab('main_route').'.show') {
            return $request->route('ticket');
        }

        // Check comment route
        if ($request->route()->getName() == Setting::grab('main_route').'-comment.store') {
            return $request->get('ticket_id');
        }

        return null;
    }

    protected function unauthorizedRedirect()
    {
        return redirect()->route(Setting::grab('main_route').'.index')
            ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
    }
}