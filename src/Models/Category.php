<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'ticketit_categories';
    
    protected $fillable = ['name', 'color'];
    
    public $timestamps = false;

    protected $guarded = ['id'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    public function agents()
    {
        return $this->belongsToMany(
            Agent::class, 
            'ticketit_categories_users', 
            'category_id', 
            'user_id'
        );
    }

    public function getColorAttribute($value)
    {
        return $value ?: '#666666';
    }
}