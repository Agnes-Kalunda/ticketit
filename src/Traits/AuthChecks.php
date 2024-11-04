<?php

namespace Ticket\Ticketit\Traits;

use Illuminate\Support\Facades\Auth;

trait AuthChecks
{
    /**
     * Check if current user is a customer
     *
     * @return bool
     */
    protected function isCustomer()
    {
        return Auth::guard('customer')->check();
    }

    /**
     * Check if current user is staff
     *
     * @return bool
     */
    protected function isStaff()
    {
        return Auth::guard('web')->check();
    }

    /**
     * Check if current user is admin
     *
     * @return bool
     */
    protected function isAdmin()
    {
        if (!$this->isStaff()) {
            return false;
        }
        return Auth::user()->ticketit_admin == 1;
    }

    /**
     * Check if current user is agent
     *
     * @return bool
     */
    protected function isAgent()
    {
        if (!$this->isStaff()) {
            return false;
        }
        return Auth::user()->ticketit_agent == 1;
    }
}