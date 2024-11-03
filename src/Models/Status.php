<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'ticketit_statuses';
    
    protected $fillable = ['name', 'color'];
    
    public $timestamps = false;

    // Add guarded property 
    protected $guarded = ['id'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    // Add helper method to get color with fallback
    public function getColorAttribute($value)
    {
        return $value ?: '#666666';
    }
}