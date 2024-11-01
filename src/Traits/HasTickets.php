<?php 

namespace Ticket\Ticketit\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;

trait HasTickets
{
    /**
     * Get the model class from config
     */
    protected function getModelClass($type)
    {
        return Config::get("ticketit.models.{$type}", "App\\{$type}");
    }

    /**
     * Get all tickets associated with model
     */
    public function tickets(): HasMany
    {
        $customerModel = Config::get('ticketit.models.customer');
        
        if ($this instanceof $customerModel) {
            return $this->hasMany('Ticket\Ticketit\Models\Ticket', 'customer_id');
        }
        return $this->hasMany('Ticket\Ticketit\Models\Ticket', 'user_id');
    }

    /**
     * Get active tickets
     */
    public function activeTickets(): HasMany
    {
        return $this->tickets()->whereNull('completed_at');
    }

    /**
     * Get completed tickets
     */
    public function completedTickets(): HasMany
    {
        return $this->tickets()->whereNotNull('completed_at');
    }

    /**
     * Check if model can manage tickets
     */
    public function canManageTickets(): bool
    {
        $userModel = Config::get('ticketit.models.user');
        
        return $this instanceof $userModel && 
               Config::get('ticketit.permissions.user.manage_tickets', true);
    }


    /**
     * Check if model can create tickets
     */
    public function canCreateTicket(): bool
    {
        $customerModel = Config::get('ticketit.models.customer');
        
        if ($this instanceof $customerModel) {
            return Config::get('ticketit.permissions.customer.create_ticket', true);
        }
        
        return Config::get('ticketit.permissions.user.create_ticket', true);
    }

    /**
     * Check if model can view tickets
     */
    public function canViewTickets(): bool
    {
        $customerModel = $this->getModelClass('customer');
        
        if ($this instanceof $customerModel) {
            return Config::get('ticketit.permissions.customer.view_own_tickets', true);
        }
        
        return Config::get('ticketit.permissions.user.view_all_tickets', true);
    }

    /**
     * Check if model can comment on tickets
     */
    public function canCommentOnTickets(): bool
    {
        $customerModel = $this->getModelClass('customer');
        
        if ($this instanceof $customerModel) {
            return Config::get('ticketit.permissions.customer.comment_own_tickets', true);
        }
        
        return true; // Users can always comment
    }

    /**
     * Get tickets that need attention
     */
    public function pendingTickets(): HasMany
    {
        return $this->activeTickets()
            ->where('status_id', Config::get('ticketit.defaults.status_id'));
    }

    /**
     * Get high priority tickets
     */
    public function highPriorityTickets(): HasMany
    {
        return $this->activeTickets()
            ->where('priority_id', Config::get('ticketit.defaults.priority_id'));
    }

    /**
     * Get tickets by category
     */
    public function ticketsByCategory($category_id): HasMany
    {
        return $this->tickets()->where('category_id', $category_id);
    }

    /**
     * Check if should receive notifications
     */
    public function shouldNotify(): bool
    {
        $customerModel = $this->getModelClass('customer');
        
        if ($this instanceof $customerModel) {
            return Config::get('ticketit.ticket.agent_notify_customer', true);
        }
        
        return Config::get('ticketit.ticket.customer_notify_agent', true);
    }

    /**
     * Get ticket statistics
     */
    public function getTicketStats(): array
    {
        return [
            'total' => $this->tickets()->count(),
            'active' => $this->activeTickets()->count(),
            'completed' => $this->completedTickets()->count(),
            'pending' => $this->pendingTickets()->count(),
            'high_priority' => $this->highPriorityTickets()->count(),
        ];
    }

    /**
     * Check if model owns a specific ticket
     */
    public function ownsTicket($ticket_id): bool
    {
        $customerModel = $this->getModelClass('customer');
        
        if ($this instanceof $customerModel) {
            return $this->tickets()->where('id', $ticket_id)->exists();
        }
        
        return $this->tickets()->where('id', $ticket_id)
            ->orWhere('agent_id', $this->id)
            ->exists();
    }
}