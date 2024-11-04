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

    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate color if not provided
        static::creating(function ($category) {
            if (!$category->color) {
                $category->color = '#' . substr(md5($category->name), 0, 6);
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}