<?php

namespace Ticket\Ticketit\Controllers;

use Ticket\Ticketit\Models\Agent;
use Ticket\Ticketit\Models\Category;
use Ticket\Ticketit\Models\Ticket;

class DashboardController extends BaseTicketController
{
    public function index($indicator_period = 2)
    {
        if ($this->isCustomer()) {
            return $this->customerDashboard();
        }
        return $this->staffDashboard($indicator_period);
    }

    protected function customerDashboard()
    {
        $user = $this->getAuthUser();
        $tickets = Ticket::where('customer_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('ticketit::dashboard.customer', [
            'tickets' => $tickets,
            'tickets_count' => $tickets->total(),
            'open_tickets_count' => $tickets->where('completed_at', null)->count(),
            'closed_tickets_count' => $tickets->where('completed_at', '!=', null)->count()
        ]);
    }

    protected function staffDashboard($indicator_period)
    {
        
        $tickets_count = Ticket::count();
        $open_tickets_count = Ticket::whereNull('completed_at')->count();
        $closed_tickets_count = $tickets_count - $open_tickets_count;

        // Per Category pagination
        $categories = Category::paginate(10, ['*'], 'cat_page');

        // Category statistics
        $categories_all = Category::all();
        $categories_share = [];
        foreach ($categories_all as $cat) {
            $categories_share[$cat->name] = $cat->tickets()->count();
        }

        // Agent statistics
        $agents_share_obj = Agent::agents()->with(['agentTotalTickets' => function ($query) {
            $query->addSelect(['id', 'agent_id']);
        }])->get();

        $agents_share = [];
        foreach ($agents_share_obj as $agent_share) {
            $agents_share[$agent_share->name] = $agent_share->agentTotalTickets->count();
        }

        
        $agents = Agent::agents(10);
        $users = Agent::users(10);

        // Performance data
        $ticketController = new TicketsController(new Ticket(), new Agent());
        $monthly_performance = $ticketController->monthlyPerfomance($indicator_period);

        $active_tab = request()->has('cat_page') ? 'cat' : 
                     (request()->has('agents_page') ? 'agents' : 
                     (request()->has('users_page') ? 'users' : 'cat'));

        return view(
            'ticketit::admin.index',
            compact(
                'open_tickets_count',
                'closed_tickets_count',
                'tickets_count',
                'categories',
                'agents',
                'users',
                'monthly_performance',
                'categories_share',
                'agents_share',
                'active_tab'
            ));
    }
}