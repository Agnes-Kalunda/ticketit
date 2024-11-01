<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agent extends Model
{
    protected $table = 'users';

    /**
     * Get configured database connection
     */
    public function getConnectionName()
    {
        return config('ticketit.connection', env('DB_CONNECTION', 'mysql'));
    }

    /**
     * Get the user model class from config
     */
    protected static function getUserModel()
    {
        return config('ticketit.models.user', env('TICKETIT_USER_MODEL', 'App\User'));
    }

    /**
     * Get the customer model class from config
     */
    protected static function getCustomerModel()
    {
        return config('ticketit.models.customer', env('TICKETIT_CUSTOMER_MODEL', 'App\Customer'));
    }

    /**
     * Get user guard name
     */
    protected static function getUserGuard()
    {
        return config('ticketit.guards.user', env('TICKETIT_USER_GUARD', 'web'));
    }

    /**
     * Get customer guard name
     */
    protected static function getCustomerGuard()
    {
        return config('ticketit.guards.customer', env('TICKETIT_CUSTOMER_GUARD', 'customer'));
    }

    /**
     * List of all agents
     */
    public function scopeAgents($query, $paginate = false)
    {
        $query = $query->where('ticketit_agent', '1');
        return $paginate ? 
            $query->paginate($paginate, ['*'], 'agents_page') : 
            $query;
    }

    /**
     * List of all admins
     */
    public function scopeAdmins($query, $paginate = false)
    {
        $query = $query->where('ticketit_admin', '1');
        return $paginate ? 
            $query->paginate($paginate, ['*'], 'admins_page') : 
            $query->get();
    }

    /**
     * Get agents list for dropdown
     */
    public function scopeAgentsLists($query)
    {
        return $query->where('ticketit_agent', '1')
                    ->pluck('name', 'id')
                    ->toArray();
    }

    /**
     * Check if user is agent
     */
    public static function isAgent($id = null)
    {
        if ($id) {
            $userClass = static::getUserModel();
            $user = $userClass::find($id);
            return $user && $user->ticketit_agent;
        }

        $guard = static::getUserGuard();
        return auth()->guard($guard)->check() && 
               auth()->guard($guard)->user()->ticketit_agent;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        $guard = static::getUserGuard();
        return auth()->guard($guard)->check() && 
               auth()->guard($guard)->user()->ticketit_admin;
    }

    /**
     * Check if user is assigned agent for ticket
     */
    public static function isAssignedAgent($id)
    {
        $guard = static::getUserGuard();
        if (!auth()->guard($guard)->check()) {
            return false;
        }

        $user = auth()->guard($guard)->user();
        if (!$user->ticketit_agent) {
            return false;
        }

        $ticket = Ticket::find($id);
        return $ticket && $user->id == $ticket->agent_id;
    }

    /**
     * Check ticket ownership
     */
    public static function isTicketOwner($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) {
            return false;
        }

        $customerGuard = static::getCustomerGuard();
        $userGuard = static::getUserGuard();

        // Check customer ownership
        if (auth()->guard($customerGuard)->check()) {
            return $ticket->customer_id == auth()->guard($customerGuard)->id();
        }

        // Check user ownership
        if (auth()->guard($userGuard)->check()) {
            return $ticket->user_id == auth()->guard($userGuard)->id();
        }

        return false;
    }

    /**
     * Get related categories
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'ticketit_categories_users',
            'user_id',
            'category_id'
        );
    }

    /**
     * Get agent tickets
     */
    public function agentTickets($complete = false): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id')
                    ->when($complete, function ($query) {
                        return $query->whereNotNull('completed_at');
                    }, function ($query) {
                        return $query->whereNull('completed_at');
                    });
    }

    /**
     * Get total agent tickets
     */
    public function agentTotalTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }

    /**
     * Get completed agent tickets
     */
    public function agentCompleteTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id')
                    ->whereNotNull('completed_at');
    }

    /**
     * Get open agent tickets
     */
    public function agentOpenTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id')
                    ->whereNull('completed_at');
    }

    /**
     * Get all tickets based on user role
     */
    public function getTickets($complete = false)
    {
        $guard = static::getUserGuard();
        $user = static::getUserModel()::find(auth()->guard($guard)->id());

        if (!$user) {
            return collect();
        }

        if ($user->ticketit_admin) {
            return $complete ? 
                Ticket::whereNotNull('completed_at') :
                Ticket::whereNull('completed_at');
        }

        if ($user->ticketit_agent) {
            return $this->agentTickets($complete);
        }

        return Ticket::where('user_id', $user->id)
                    ->when($complete, function ($query) {
                        return $query->whereNotNull('completed_at');
                    }, function ($query) {
                        return $query->whereNull('completed_at');
                    });
    }

    /**
     * Check if customer can create tickets
     */
    public static function customerCanCreateTicket()
    {
        return config('ticketit.ticket.customer_can_create', true) &&
               config('ticketit.permissions.customer.create_ticket', true);
    }

    /**
     * Check if user can create tickets
     */
    public static function userCanCreateTicket()
    {
        return config('ticketit.ticket.user_can_create', true);
    }

    /**
     * Check if user can manage tickets
     */
    public static function userCanManageTickets()
    {
        $guard = static::getUserGuard();
        return auth()->guard($guard)->check() && 
               config('ticketit.permissions.user.manage_tickets', true) &&
               (auth()->guard($guard)->user()->ticketit_agent || 
                auth()->guard($guard)->user()->ticketit_admin);
    }

    /**
     * Check if customer can view own tickets
     */
    public static function customerCanViewOwnTickets()
    {
        return config('ticketit.permissions.customer.view_own_tickets', true);
    }

    /**
     * Check if customer can comment on own tickets
     */
    public static function customerCanCommentOwnTickets()
    {
        return config('ticketit.permissions.customer.comment_own_tickets', true);
    }

    /**
     * Check if agent should notify customer
     */
    public static function shouldNotifyCustomer()
    {
        return config('ticketit.ticket.agent_notify_customer', true);
    }

    /**
     * Check if customer should notify agent
     */
    public static function shouldNotifyAgent()
    {
        return config('ticketit.ticket.customer_notify_agent', true);
    }
}