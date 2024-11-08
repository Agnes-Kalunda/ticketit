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



    protected function getUserRole()
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return 'Admin';
        }
        if ($user->isAgent()) {
            return 'Agent';
        }
        return 'Customer';
    }


    /**
     * Store a newly created comment
     */
    public function store(Request $request, $ticket_id)
    {
        try {
            // Validate request
            $validatedData = $request->validate([
                'content' => 'required|string|min:6',
            ]);

            DB::beginTransaction();

            // Get the ticket
            $ticket = Models\Ticket::findOrFail($ticket_id);

            // Check authorization
            if (!$this->canAddComment($ticket)) {
                throw new \Exception('Unauthorized to add comment');
            }

            // Create comment
            $comment = new Models\Comment();
            $comment->content = $validatedData['content'];
            $comment->ticket_id = $ticket->id;
            $comment->user_id = Auth::id();

            if (!$comment->save()) {
                throw new \Exception('Failed to save comment');
            }

            // Update ticket timestamp
            $ticket->touch();

            // Log the comment
            Log::info('Comment added to ticket', [
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'user_type' => $this->getUserType()
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Comment added successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Comment validation failed:', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
                'ticket_id' => $ticket_id
            ]);
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding comment:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ticket_id' => $ticket_id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withErrors(['content' => $e->getMessage()])
                ->withInput();
        }
    }

    protected function getUserType()
    {
        if (Auth::guard('customer')->check()) {
            return 'Customer';
        }

        $user = Auth::user();
        if ($user->isAdmin()) {
            return 'Admin';
        }
        if ($user->isAgent()) {
            return 'Agent';
        }

        return 'User';
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
        try {
            // Validate the request
            $validatedData = $request->validate([
                'content' => 'required|min:6'
            ]);

            DB::beginTransaction();

            $comment = Models\Comment::findOrFail($id);
            
            if (!$this->canEditComment($comment)) {
                throw new \Exception('Unauthorized to update comment');
            }

            $comment->content = $validatedData['content'];
            
            if (!$comment->save()) {
                throw new \Exception('Failed to update comment');
            }

            // Create audit log
            Models\Audit::create([
                'operation' => 'Comment updated by ' . Auth::user()->name,
                'user_id' => Auth::id(),
                'ticket_id' => $comment->ticket_id
            ]);

            DB::commit();

            return back()->with('success', 'Comment updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating comment:', [
                'error' => $e->getMessage(),
                'comment_id' => $id,
                'user_id' => Auth::id()
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
    protected function canAddComment($ticket)
    {
        $user = Auth::user();

        // Admin cannot add comments
        if ($user && $user->isAdmin()) {
            return false;
        }

        // Agent can add comments to assigned tickets
        if ($user && $user->isAgent() && $ticket->agent_id === $user->id) {
            return true;
        }

        // Customer can add comments to their own tickets
        if (Auth::guard('customer')->check()) {
            return $ticket->customer_id === Auth::guard('customer')->id();
        }

        return false;
    }
    /**
     * Check if user can edit comment
     */
    protected function canEditComment($comment)
    {
        $user = Auth::user();

        // Admin cannot edit comments
        if ($user && $user->isAdmin()) {
            return false;
        }

        // Agent can edit their own comments on assigned tickets
        if ($user && $user->isAgent()) {
            return $comment->user_id === $user->id && 
                   $comment->ticket->agent_id === $user->id;
        }

        // Customer can edit their own comments
        if (Auth::guard('customer')->check()) {
            return $comment->user_id === Auth::guard('customer')->id();
        }

        return false;
    }

    /**
     * Check if user can delete comment
     */
    private function canDeleteComment($comment)
    {
        return Auth::user()->isAdmin();
    }
}