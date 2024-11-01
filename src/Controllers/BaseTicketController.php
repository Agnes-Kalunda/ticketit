<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class BaseTicketController extends Controller
{
    protected $tickets;
    protected $agent;

    /**
     * Check if current user is a customer
     * This integrates with your external authentication
     */
    protected function isCustomer()
    {
        return Auth::guard('customer')->check();
    }

    /**
     * Get authenticated user based on guard
     */
    protected function getAuthUser()
    {
        if ($this->isCustomer()) {
            return Auth::guard('customer')->user();
        }
        return Auth::user();
    }

    /**
     * Check if user can manage tickets
     */
    protected function canManageTickets()
    {
        if ($this->isCustomer()) {
            return false;
        }
        $user = $this->getAuthUser();
        return $user && ($user->ticketit_agent || $user->ticketit_admin);
    }

    /**
     * Original admin check functionality
     */
    protected function isAdmin()
    {
        return auth()->check() && auth()->user()->ticketit_admin;
    }
}