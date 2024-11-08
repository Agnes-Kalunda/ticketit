<?php

namespace Ticket\Ticketit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Ticket\Ticketit\Models\Ticket;
use Illuminate\Support\Facades\Log;

class StoreCommentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'ticket_id' => 'required|exists:ticketit,id',
            'content' => 'required|min:6',
        ];
    }

    public function authorize()
    {
        try {
            $ticket = Ticket::findOrFail($this->ticket_id);
            $user = Auth::user();
            $customer = Auth::guard('customer')->user();

            // If user is admin, they can only view
            if ($user && $user->isAdmin()) {
                return false;
            }

            // Agent can add comments only to assigned tickets
            if ($user && $user->isAgent() && $ticket->agent_id === $user->id) {
                Log::info('Agent commenting on ticket', [
                    'agent_id' => $user->id,
                    'ticket_id' => $ticket->id
                ]);
                return true;
            }

            // Customer can add comments to their own tickets
            if ($customer && $ticket->customer_id === $customer->id) {
                Log::info('Customer commenting on ticket', [
                    'customer_id' => $customer->id,
                    'ticket_id' => $ticket->id
                ]);
                return true;
            }

            Log::warning('Unauthorized comment attempt', [
                'user_type' => $user ? 'staff' : ($customer ? 'customer' : 'unknown'),
                'user_id' => $user ? $user->id : ($customer ? $customer->id : null),
                'ticket_id' => $ticket->id
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Error in comment authorization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function messages()
    {
        return [
            'content.required' => 'Please enter a comment.',
            'content.min' => 'Comment must be at least 6 characters long.',
            'ticket_id.required' => 'Ticket ID is required.',
            'ticket_id.exists' => 'Invalid ticket reference.'
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('ticket_id') && $this->route('ticket')) {
            $this->merge([
                'ticket_id' => $this->route('ticket')
            ]);
        }
    }
}