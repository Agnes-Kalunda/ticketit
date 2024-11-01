<?php 

namespace Ticket\Ticketit\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;

trait HasTickets
{
    /**
     * Get all tickets associated with model
     * Handles both original user tickets and new customer tickets
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
     * Get active tickets (original functionality)
     */
    public function activeTickets(): HasMany
    {
        return $this->tickets()->whereNull('completed_at');
    }

    /**
     * Get completed tickets (original functionality)
     */
    public function completedTickets(): HasMany
    {
        return $this->tickets()->whereNotNull('completed_at');
    }

    /**
     * Get agent tickets (original functionality)
     */
    public function agentTickets($complete = false): HasMany
    {
        $query = $this->hasMany('Ticket\Ticketit\Models\Ticket', 'agent_id');
        return $complete ? 
            $query->whereNotNull('completed_at') : 
            $query->whereNull('completed_at');
    }

    /**
     * Check if user is agent (original functionality)
     */
    public function isAgent(): bool
    {
        return $this->ticketit_agent ?? false;
    }

    /**
     * Check if user is admin (original functionality)
     */
    public function isAdmin(): bool
    {
        return $this->ticketit_admin ?? false;
    }

    /**
     * New functionality: Check if model can manage tickets
     */
    public function canManageTickets(): bool
    {
        $userModel = Config::get('ticketit.models.user');
        
        return $this instanceof $userModel && 
               Config::get('ticketit.permissions.user.manage_tickets', true);
    }

    /**
     * New functionality: Check if model can create tickets
     */
    public function canCreateTicket(): bool
{
    $customerModel = Config::get('ticketit.models.customer');
    
    if ($this instanceof $customerModel) {
        return Config::get('ticketit.permissions.customer.create_ticket', true);
    }
    
    return false; // Users/staff cannot create tickets
}

    /**
     * New functionality: Check ticket viewing permissions
     */
    public function canViewTickets(): bool
    {
        $customerModel = Config::get('ticketit.models.customer');
        
        if ($this instanceof $customerModel) {
            return Config::get('ticketit.permissions.customer.view_own_tickets', true);
        }
        
        return Config::get('ticketit.permissions.user.view_all_tickets', true);
    }

    /**
     * Original functionality: Get all assigned tickets
     */
    public function allAssignedTickets(): HasMany
    {
        return $this->hasMany('Ticket\Ticketit\Models\Ticket', 'agent_id');
    }

    /**
     * Original functionality: Get user total tickets
     */
    public function userTotalTickets(): HasMany
    {
        return $this->hasMany('Ticket\Ticketit\Models\Ticket', 'user_id');
    }

    /**
     * New functionality: Check if owner of ticket
     */
    public function isTicketOwner($ticket_id): bool
    {
        $customerModel = Config::get('ticketit.models.customer');
        
        if ($this instanceof $customerModel) {
            return $this->tickets()
                       ->where('id', $ticket_id)
                       ->exists();
        }
        
        return $this->tickets()
                   ->where('id', $ticket_id)
                   ->orWhere('agent_id', $this->id)
                   ->exists();
    }

    /**
     * Original functionality: Get open tickets count
     */
    public function getOpenTicketsCount(): int
    {
        return $this->activeTickets()->count();
    }

    /**
     * New functionality: Get ticket statistics
     */
    public function getTicketStats(): array
    {
        return [
            'total' => $this->tickets()->count(),
            'active' => $this->activeTickets()->count(),
            'completed' => $this->completedTickets()->count(),
            'agent_assigned' => $this->isAgent() ? $this->agentTickets()->count() : 0,
            'high_priority' => $this->tickets()
                                  ->where('priority_id', Config::get('ticketit.defaults.priority_id'))
                                  ->count()
        ];
    }
}