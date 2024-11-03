<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    protected $table = 'ticketit_priorities';
    
    protected $fillable = ['name', 'color'];
    
    public $timestamps = false;

    protected $guarded = ['id'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    public function getColorAttribute($value)
    {
        return $value ?: '#666666';
    }
}
