<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    protected $table = 'ticketit_priorities';
    
    protected $fillable = [
        'name',
        'color',
    ];

    
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}