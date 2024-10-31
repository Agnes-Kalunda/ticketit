<?php 
namespace Ticket\Ticketit\Services;

use Ticket\Ticketit\Models\Ticket;

class TicketService
{
    /**
     * create ticket 
     */
    public function createTicket($customer_id, array $data)
    {
        return Ticket::create([
            'customer_id' => $customer_id,
            'subject' => $data['subject'],
            'content' => $data['content'],
            'priority_id' => $data['priority_id'],
            'category_id' => $data['category_id'],
            'status_id' => config('ticketit.default_status_id'),
        ]);
    }

    /**
     * assign ticket
     */
    public function assignToStaff($ticket_id, $user_id)
    {
        $ticket = Ticket::findOrFail($ticket_id);
        $ticket->agent_id = $user_id;
        $ticket->save();
        
        return $ticket;
    }

    /**
     * get customer tickets
     */
    public function getCustomerTickets($customer_id)
    {
        return Ticket::where('customer_id', $customer_id)
                    ->with(['status', 'priority', 'category'])
                    ->get();
    }

    /**
     * get staff tickets
     */
    public function getStaffTickets($user_id)
    {
        return Ticket::where('agent_id', $user_id)
                    ->with(['status', 'priority', 'category'])
                    ->get();
    }
}