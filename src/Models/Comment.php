<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'ticketit_comments';
    
    protected $fillable = ['content', 'user_id', 'ticket_id'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function isFromAgent()
    {
        return $this->user && ($this->user->isAgent() || $this->user->isAdmin());
    }
}