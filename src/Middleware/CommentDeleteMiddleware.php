<?php

namespace Ticket\Ticketit\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Models\Comment;

class CommentDeleteMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        $comment = Comment::find($request->route('comment'));

        Log::info('CommentDeleteMiddleware: Checking permissions', [
            'path' => $request->path(),
            'user_id' => $user ? $user->id : null,
            'is_agent' => $user ? $user->ticketit_agent : false,
            'is_admin' => $user ? $user->ticketit_admin : false,
            'comment_id' => $comment ? $comment->id : null
        ]);

        if (!$this->canDelete($user, $comment)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }
            
            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
        }

        return $next($request);
    }

    protected function canDelete($user, $comment)
    {
        if (!$user || !$comment) {
            return false;
        }

        // Admin can delete any comment
        if ($user->ticketit_admin) {
            return true;
        }

        // Agent can delete their own comments or comments on their tickets
        if ($user->ticketit_agent) {
            return $comment->user_id === $user->id || 
                   ($comment->ticket && $comment->ticket->agent_id === $user->id);
        }

        return false;
    }
}