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
            return $this->unauthorizedRedirect($user, $customer);
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

            return $this->unauthorizedRedirect($user, $customer);

        } catch (\Exception $e) {
            Log::error('ResAccessMiddleware: Error checking access', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            
            return $this->unauthorizedRedirect($user, $customer);
        }
    }

    protected function getTicketId($request)
    {
        
        $ticketId = $request->route('ticket') ?? $request->route('id');
        if ($ticketId) {
            return $ticketId;
        }

        //check for ticket_id in request
        return $request->get('ticket_id');
    }

    protected function unauthorizedRedirect($user, $customer)
    {
        if ($customer) {
            return redirect()->route('customer.tickets.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
        }

        if ($user) {
            return redirect()->route('staff.tickets.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
        }

        return redirect()->route('login')
            ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
    }
}