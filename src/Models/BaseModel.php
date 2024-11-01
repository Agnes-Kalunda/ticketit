<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * get the database connection for the model.
     */
    public function getConnectionName()
    {
        return 'ticketit';
    }
}