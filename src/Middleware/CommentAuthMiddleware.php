<?php

namespace Ticket\Ticketit\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Models\Ticket;
use Ticket\Ticketit\Models\Comment;

class CommentAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        $ticket = null;
        $comment = null;

        // Get the ticket or comment based on the request
        if ($request->route('ticket')) {
            $ticket = Ticket::find($request->route('ticket'));
        } elseif ($request->route('comment')) {
            $comment = Comment::find($request->route('comment'));
            $ticket = $comment ? $comment->ticket : null;
        }

        Log::info('CommentAuthMiddleware: Checking permissions', [
            'path' => $request->path(),
            'user_id' => $user ? $user->id : null,
            'is_agent' => $user ? $user->ticketit_agent : false,
            'is_admin' => $user ? $user->ticketit_admin : false,
            'ticket_id' => $ticket ? $ticket->id : null,
            'comment_id' => $comment ? $comment->id : null
        ]);

        // Check permissions based on user role and ticket ownership
        if (!$this->canPerformAction($user, $ticket, $comment, $request)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }
            
            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
        }

        return $next($request);
    }

    protected function canPerformAction($user, $ticket, $comment, $request)
    {
        if (!$user) {
            return false;
        }

        // Admin can do anything
        if ($user->ticketit_admin) {
            return true;
        }

        // Agent permissions
        if ($user->ticketit_agent) {
            // Can only act on tickets assigned to them
            if ($ticket && $ticket->agent_id !== $user->id) {
                return false;
            }

            // Can only modify their own comments
            if ($comment && $comment->user_id !== $user->id) {
                return false;
            }

            return true;
        }

        // Regular user permissions (if needed)
        // Add any specific permissions for regular users here

        return false;
    }
}