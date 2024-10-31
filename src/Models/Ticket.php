<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Date\Date;
use Ticket\Ticketit\Traits\ContentEllipse;
use Ticket\Ticketit\Traits\Purifiable;
use Illuminate\Support\Facades\Log; // Add this for logging

class Ticket extends BaseModel
{
    use ContentEllipse;
    use Purifiable;

    protected $table = 'ticketit';
    
    protected $dates = ['completed_at'];

    protected $fillable = [
        'subject',
        'content',
        'status_id',
        'priority_id',
        'category_id',
        'agent_id',
        'user_id',      // For staff submitted tickets
        'customer_id'   // For customer submitted tickets
    ];

    /**
     * Get Ticket customer - using config for model reference
     */
    public function customer()
    {
        return $this->belongsTo(config('ticketit.models.customer'), 'customer_id');
    }

    /**
     * Get Ticket owner (staff) - using config for model reference
     */
    public function user()
    {
        return $this->belongsTo(config('ticketit.models.user'), 'user_id');
    }

    /**
     * Get Ticket agent
     */
    public function agent()
    {
        return $this->belongsTo('Ticket\Ticketit\Models\Agent', 'agent_id');
    }

    /**
     * Get Ticket status
     */
    public function status()
    {
        return $this->belongsTo('Ticket\Ticketit\Models\Status', 'status_id');
    }

    /**
     * Get Ticket priority
     */
    public function priority()
    {
        return $this->belongsTo('Ticket\Ticketit\Models\Priority', 'priority_id');
    }

    /**
     * Get Ticket category
     */
    public function category()
    {
        return $this->belongsTo('Ticket\Ticketit\Models\Category', 'category_id');
    }

    /**
     * Get Ticket comments
     */
    public function comments()
    {
        return $this->hasMany('Ticket\Ticketit\Models\Comment', 'ticket_id');
    }

    // Scopes
    public function scopeCustomerTickets($query, $id)
    {
        return $query->where('customer_id', $id);
    }

    public function scopeUserTickets($query, $id)
    {
        return $query->where('user_id', $id);
    }

    public function scopeComplete($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeAgentTickets($query, $id)
    {
        return $query->where('agent_id', $id);
    }

    public function scopeAgentUserTickets($query, $id)
    {
        return $query->where(function ($subquery) use ($id) {
            $subquery->where('agent_id', $id)
                    ->orWhere('user_id', $id);
        });
    }

    // Helper methods
    public function hasComments()
    {
        return (bool) count($this->comments);
    }

    public function isComplete()
    {
        return (bool) $this->completed_at;
    }

    /**
     * Auto select agent based on lowest ticket count
     */
    public function autoSelectAgent()
{
    try {
        $cat_id = $this->category_id;
        $category = Category::find($cat_id);
        
        if (!$category) {
            return $this;
        }

        // Get agents through category relationship
        $agents = $category->agents;

        // If no agents found in category
        if ($agents->isEmpty()) {
            return $this;
        }

        // Find agent with lowest ticket count
        $lowestCount = PHP_INT_MAX;
        $selectedAgentId = null;

        foreach ($agents as $agent) {
            $ticketCount = $agent->agentOpenTickets->count();
            if ($ticketCount < $lowestCount) {
                $lowestCount = $ticketCount;
                $selectedAgentId = $agent->id;
            }
        }

        if ($selectedAgentId) {
            $this->agent_id = $selectedAgentId;
        }

        return $this;
    } catch (\Exception $e) {
        if (config('app.debug')) {
            Log::error('Error in autoSelectAgent: ' . $e->getMessage());
        }
        return $this;
    }
}

    /**
     * Date handling methods
     */
    public function freshTimestamp()
    {
        return new Date();
    }

    protected function asDateTime($value)
    {
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Date::createFromFormat('Y-m-d', $value)->startOfDay();
        } elseif (!$value instanceof \DateTimeInterface) {
            $format = $this->getDateFormat();
            return Date::createFromFormat($format, $value);
        }

        return Date::instance($value);
    }
}