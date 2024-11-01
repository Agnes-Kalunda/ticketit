<?php 

namespace Ticket\Ticketit\Traits;
trait HasTickets{

    // get all tickets associated with model
    public function tickets(){
        if($this instanceof \App\Customer){
            return $this->hasMany('Ticket\Ticketit\Models\Ticket', 'customer_id');

        }
        return $this->hasMany('Ticket\Ticketit\Models\Ticket', 'user_id');
}


    //  get active tickets

    public function activeTickets(){
        return $this->tickets()->whereNull('completed_at');
    }

    // get completed tickets
    public function completedTickets(){
        return $this->tickets()->whereNotNull('completed_at');
    }


    // check if model can manage ticket-config.file
    public function canManageTickets(){
        return $this instanceof \App\User &&
               config('ticketit.permissions.user.manage_tickets', true);

    }

    public function canCreateTicket(){
        if ($this instanceof \App\Customer){
            return config('ticketit.permissions.customer.create_ticket', true);
        }

        return config ('ticketit.permissions.user.create_ticket', true);

    }



}