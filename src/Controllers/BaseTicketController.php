<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class BaseTicketController extends Controller
{
    protected function isCustomer()
    {
        return Auth::guard('customer')->check();
    }

    protected function getAuthUser()
    {
        if ($this->isCustomer()) {
            return Auth::guard('customer')->user();
        }
        return Auth::user();
    }

    protected function canManageTickets()
    {
        if ($this->isCustomer()) {
            return false;
        }
        $user = $this->getAuthUser();
        return $user && ($user->ticketit_agent || $user->ticketit_admin);
    }

    protected function isAdmin()
    {
        if ($this->isCustomer()) {
            return false;
        }
        $user = $this->getAuthUser();
        return $user && $user->ticketit_admin;
    }
}