<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Ticket\Ticketit\Models;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\User;

class CommentsController extends Controller
{
    public function __construct()
    {
        $this->middleware('Ticket\Ticketit\Middleware\IsAdminMiddleware', ['only' => ['edit', 'update', 'destroy']]);
        $this->middleware('Ticket\Ticketit\Middleware\ResAccessMiddleware', ['only' => ['store', 'show']]);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'ticket_id' => 'required|exists:ticketit,id',
            'content' => 'required|min:6',
        ]);

        try {
            DB::beginTransaction();

            $ticket = Models\Ticket::findOrFail($request->ticket_id);

            // Check authorization
            if (!$this->canAddComment($ticket)) {
                throw new \Exception('Unauthorized to add comment');
            }

            // Create comment
            $comment = new Models\Comment();
            $comment->content = $request->content;
            $comment->ticket_id = $ticket->id;
            $comment->user_id = Auth::id();

            if (!$comment->save()) {
                throw new \Exception('Failed to save comment');
            }

            // Update ticket timestamp
            $ticket->touch();

            // Create audit log
            Models\Audit::create([
                'operation' => sprintf(
                    'Comment added by %s (%s)',
                    Auth::user()->name,
                    Auth::user()->isAdmin() ? 'Admin' : (Auth::user()->isAgent() ? 'Agent' : 'User')
                ),
                'user_id' => Auth::id(),
                'ticket_id' => $ticket->id
            ]);

            DB::commit();

            return back()->with('success', 'Comment added successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding comment:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ticket_id' => $request->ticket_id
            ]);

            return back()
                ->with('error', 'Error adding comment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show comment edit form
     */
    public function edit($id)
    {
        try {
            $comment = Models\Comment::findOrFail($id);
            
            if (!$this->canEditComment($comment)) {
                return redirect()->back()->with('error', 'Unauthorized to edit comment');
            }

            return view('ticketit::comments.edit', compact('comment'));

        } catch (\Exception $e) {
            Log::error('Error editing comment:', [
                'error' => $e->getMessage(),
                'comment_id' => $id
            ]);

            return back()->with('error', 'Error loading comment');
        }
    }

    /**
     * Update the comment
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'content' => 'required|min:6',
        ]);

        try {
            DB::beginTransaction();

            $comment = Models\Comment::findOrFail($id);

            if (!$this->canEditComment($comment)) {
                throw new \Exception('Unauthorized to update comment');
            }

            $oldContent = $comment->content;
            $comment->content = $request->content;
            
            if (!$comment->save()) {
                throw new \Exception('Failed to update comment');
            }

            // Create audit log
            Models\Audit::create([
                'operation' => sprintf(
                    'Comment updated by %s (%s)',
                    Auth::user()->name,
                    Auth::user()->isAdmin() ? 'Admin' : 'Agent'
                ),
                'user_id' => Auth::id(),
                'ticket_id' => $comment->ticket_id
            ]);

            DB::commit();

            return redirect()
                ->route('staff.tickets.show', $comment->ticket_id)
                ->with('success', 'Comment updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating comment:', [
                'error' => $e->getMessage(),
                'comment_id' => $id
            ]);

            return back()
                ->with('error', 'Error updating comment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete the comment
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $comment = Models\Comment::findOrFail($id);
            
            if (!$this->canDeleteComment($comment)) {
                throw new \Exception('Unauthorized to delete comment');
            }

            $ticketId = $comment->ticket_id;

            if (!$comment->delete()) {
                throw new \Exception('Failed to delete comment');
            }

            // Create audit log
            Models\Audit::create([
                'operation' => sprintf(
                    'Comment deleted by %s (%s)',
                    Auth::user()->name,
                    Auth::user()->isAdmin() ? 'Admin' : 'Agent'
                ),
                'user_id' => Auth::id(),
                'ticket_id' => $ticketId
            ]);

            DB::commit();

            return redirect()
                ->route('staff.tickets.show', $ticketId)
                ->with('success', 'Comment deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting comment:', [
                'error' => $e->getMessage(),
                'comment_id' => $id
            ]);

            return back()->with('error', 'Error deleting comment: ' . $e->getMessage());
        }
    }

    /**
     * Check if user can add comment to ticket
     */
    private function canAddComment($ticket)
    {
        $user = Auth::user();

        // Admin can always comment
        if ($user->isAdmin()) {
            return true;
        }

        // Assigned agent can comment
        if ($user->isAgent() && $ticket->agent_id === $user->id) {
            return true;
        }

        // Regular user with ticket access can comment
        if ($ticket->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit comment
     */
    private function canEditComment($comment)
    {
        $user = Auth::user();
        return $user->isAdmin() || ($user->isAgent() && $comment->user_id === $user->id);
    }

    /**
     * Check if user can delete comment
     */
    private function canDeleteComment($comment)
    {
        return Auth::user()->isAdmin();
    }
}