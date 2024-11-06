<?php

namespace Ticket\Ticketit\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait AuthChecks 
{
    /**
     * Get the current guard name
     */
    protected function getCurrentGuard()
    {
        if (Auth::guard('customer')->check()) {
            return 'customer';
        }
        if (Auth::guard('web')->check()) {
            return 'web';
        }
        return null;
    }

    /**
     * Check if current user is a customer
     */
    protected function isCustomer()
    {
        $isCustomer = Auth::guard('customer')->check();
        
        Log::info('[Auth Check] Customer check:', [
            'is_customer' => $isCustomer,
            'guard' => $this->getCurrentGuard(),
            'user' => Auth::guard('customer')->user() ? [
                'id' => Auth::guard('customer')->id(),
                'type' => 'customer'
            ] : null
        ]);
        
        return $isCustomer;
    }

    /**
     * Check if current user is staff
     */
    protected function isStaff()
    {
        $isStaff = Auth::guard('web')->check();
        
        Log::info('[Auth Check] Staff check:', [
            'is_staff' => $isStaff,
            'guard' => $this->getCurrentGuard(),
            'user' => Auth::guard('web')->user() ? [
                'id' => Auth::guard('web')->id(),
                'is_admin' => Auth::guard('web')->user()->ticketit_admin,
                'is_agent' => Auth::guard('web')->user()->ticketit_agent
            ] : null
        ]);
        
        return $isStaff;
    }

    /**
     * Check if current user is admin
     */
    protected function isAdmin()
    {
        if (!$this->isStaff()) {
            return false;
        }
        
        $user = Auth::guard('web')->user();
        $isAdmin = $user && $user->ticketit_admin == 1;
        
        Log::info('[Auth Check] Admin check:', [
            'is_admin' => $isAdmin,
            'user_id' => $user ? $user->id : null
        ]);
        
        return $isAdmin;
    }

    /**
     * Check if current user is agent
     */
    protected function isAgent()
    {
        if (!$this->isStaff()) {
            return false;
        }
        
        $user = Auth::guard('web')->user();
        $isAgent = $user && $user->ticketit_agent == 1;
        
        Log::info('[Auth Check] Agent check:', [
            'is_agent' => $isAgent,
            'user_id' => $user ? $user->id : null
        ]);
        
        return $isAgent;
    }

    /**
     * Get the authenticated user for the current context
     */
    protected function getAuthUser()
    {
        if ($this->isCustomer()) {
            return Auth::guard('customer')->user();
        }
        
        if ($this->isStaff()) {
            return Auth::guard('web')->user();
        }
        
        return null;
    }
}