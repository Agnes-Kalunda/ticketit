<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'ticketit_statuses';
    
    protected $fillable = ['name', 'color'];
    
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        // Ensure default statuses exist
        static::created(function ($status) {
            if ($status->wasRecentlyCreated) {
                $defaults = [
                    ['name' => 'Open', 'color' => '#0014f4'],
                    ['name' => 'Pending', 'color' => '#f4a100'],
                    ['name' => 'Resolved', 'color' => '#00a65a']
                ];

                foreach ($defaults as $default) {
                    if (!static::where('name', $default['name'])->exists()) {
                        static::create($default);
                    }
                }
            }
        });
    }


     /**
     * Scope a query to only include active statuses.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('name', ['Open', 'Pending']);
    }


    /**
     * Scope a query to only include closed statuses.
     */
    public function scopeClosed($query)
    {
        return $query->where('name', 'Resolved');
    }


     /**
     * Check if this status is considered closed
     */
    public function isClosed()
    {
        return $this->name === 'Resolved';
    }







    

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