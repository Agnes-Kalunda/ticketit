<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Date\Date;
use Ticket\Ticketit\Traits\ContentEllipse;
use Ticket\Ticketit\Traits\Purifiable;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Models\Status;
use Ticket\Ticketit\Models\Priority;
use Ticket\Ticketit\Models\Category;
use Ticket\Ticketit\Models\Agent;
use Ticket\Ticketit\Models\Comment;

class Ticket extends Model
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
        'user_id',
        'customer_id' 
    ];

    // Auto-load relationships
    protected $with = ['status', 'priority', 'category'];

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
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Get Ticket status with default values
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Get Ticket priority with default values
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Get Ticket category with default values
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get Ticket comments
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'ticket_id');
    }

    // Scopes
    /**
     * Scope for customer tickets
     */


    public function getStatusColor()
     {
         try {
             return $this->status ? ($this->status->color ?: '#666666') : '#666666';
         } catch (\Exception $e) {
             return '#666666';
         }
     }
     
    public function getStatusName()
     {
         try {
             return $this->status ? ($this->status->name ?: 'Not Set') : 'Not Set';
         } catch (\Exception $e) {
             return 'Not Set';
         }
     }
     
    public function getPriorityColor()
     {
         try {
             return $this->priority ? ($this->priority->color ?: '#666666') : '#666666';
         } catch (\Exception $e) {
             return '#666666';
         }
     }
     
    public function getPriorityName()
     {
         try {
             return $this->priority ? ($this->priority->name ?: 'Not Set') : 'Not Set';
         } catch (\Exception $e) {
             return 'Not Set';
         }
     }
     
    public function getCategoryColor()
     {
         try {
             return $this->category ? ($this->category->color ?: '#666666') : '#666666';
         } catch (\Exception $e) {
             return '#666666';
         }
     }
     
    public function getCategoryName()
     {
         try {
             return $this->category ? ($this->category->name ?: 'Not Set') : 'Not Set';
         } catch (\Exception $e) {
             return 'Not Set';
         }
        }
    public function scopeCustomerTickets($query, $id)
    {
        return $query->where('customer_id', $id);
    }

    /**
     * Scope for user tickets
     */
    public function scopeUserTickets($query, $id)
    {
        return $query->where('user_id', $id);
    }

    /**
     * Scope for completed tickets
     */
    public function scopeComplete($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope for active tickets
     */
    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope for agent tickets
     */
    public function scopeAgentTickets($query, $id)
    {
        return $query->where('agent_id', $id);
    }

    /**
     * Scope for agent and user tickets
     */
    public function scopeAgentUserTickets($query, $id)
    {
        return $query->where(function ($subquery) use ($id) {
            $subquery->where('agent_id', $id)
                    ->orWhere('user_id', $id);
        });
    }

    // Helper methods
    /**
     * Check if ticket has comments
     */
    public function hasComments()
    {
        return (bool) count($this->comments);
    }

    /**
     * Check if ticket is complete
     */
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
     * Override to use custom Date class
     */
    public function freshTimestamp()
    {
        return new Date();
    }

    /**
     * Convert to datetime
     */
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