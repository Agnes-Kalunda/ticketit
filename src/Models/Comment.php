<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;
use Ticket\Ticketit\Traits\ContentEllipse;
use Ticket\Ticketit\Traits\Purifiable;

class Comment extends Model
{
    use ContentEllipse;
    use Purifiable;

    protected $table = 'ticketit_comments';

    protected $fillable = ['content', 'user_id', 'ticket_id'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(config('ticketit.models.user', 'App\User'), 'user_id');
    }
}