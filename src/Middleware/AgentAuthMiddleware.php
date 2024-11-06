<?php

namespace Ticket\Ticketit\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Models\Setting;

class AgentAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        
        Log::info('AgentAuthMiddleware: Checking permissions', [
            'path' => $request->path(),
            'user_id' => $user ? $user->id : null,
            'is_agent' => $user ? $user->ticketit_agent : false,
            'is_admin' => $user ? $user->ticketit_admin : false
        ]);

        if (!$user || (!$user->ticketit_agent && !$user->ticketit_admin)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }
            
            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
        }

        return $next($request);
    }
}