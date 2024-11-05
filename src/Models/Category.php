<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'ticketit_categories';
    
    protected $fillable = [
        'name',
        'color',
    ];



    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}