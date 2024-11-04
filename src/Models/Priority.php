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

    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate color if not provided
        static::creating(function ($priority) {
            if (!$priority->color) {
                $priority->color = '#' . substr(md5($priority->name), 0, 6);
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}