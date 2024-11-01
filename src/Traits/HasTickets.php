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
}