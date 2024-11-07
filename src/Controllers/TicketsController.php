<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ticket\Ticketit\Helpers\LaravelVersion;
use Ticket\Ticketit\Models;
use Ticket\Ticketit\Models\Agent;
use Ticket\Ticketit\Models\Category;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Models\Ticket;
use Ticket\Ticketit\Models\Status;
use Ticket\Ticketit\Models\Priority;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

use Ticket\Ticketit\Seeds\TicketitTableSeeder;
use Ticket\Ticketit\Helpers\TicketitLogger as Logger;
class TicketsController extends Controller
{
    protected $tickets;
    protected $agent;

    public function __construct(Ticket $tickets, Agent $agent)
    {
        $this->middleware('Ticket\Ticketit\Middleware\ResAccessMiddleware', ['only' => ['show']]);
        $this->middleware('Ticket\Ticketit\Middleware\IsAgentMiddleware', ['only' => ['edit', 'update']]);
        $this->middleware('Ticket\Ticketit\Middleware\IsAdminMiddleware', ['only' => ['destroy']]);

        $this->tickets = $tickets;
        $this->agent = $agent;

        $this->ensureDefaultDataExists();
    }

    protected function ensureDefaultDataExists()
{
    try {
        $categoryCount = Category::count();
        $priorityCount = Priority::count();
        $statusCount = Status::count();

        if ($categoryCount === 0 || $priorityCount === 0 || $statusCount === 0) {
            $seeder = new TicketitTableSeeder();
            $seeder->run();
        }
    } catch (\Exception $e) {
        Log::error('Error ensuring default data exists: ' . $e->getMessage());
    }
}

    public function isCustomer()
    {
        return Auth::guard('customer')->check();
    }

    public function isAdmin()
    {
        return Auth::guard('web')->check() && Auth::user()->ticketit_admin;
    }

    public function isAgent()
    {
        return Auth::guard('web')->check() && Auth::user()->ticketit_agent;
    }

    protected function getAuthUser()
    {
        if ($this->isCustomer()) {
            return Auth::guard('customer')->user();
        }
        return Auth::user();
    }



    
    public function staffIndex()
{
    try {
        $user = $this->getAuthUser();
        $query = DB::table('ticketit')
            ->leftJoin('ticketit_statuses', 'ticketit.status_id', '=', 'ticketit_statuses.id')
            ->leftJoin('ticketit_priorities', 'ticketit.priority_id', '=', 'ticketit_priorities.id')
            ->leftJoin('ticketit_categories', 'ticketit.category_id', '=', 'ticketit_categories.id')
            ->leftJoin('customers', 'ticketit.customer_id', '=', 'customers.id')
            ->select([
                'ticketit.*',
                'ticketit_statuses.name as status_name',
                'ticketit_statuses.color as status_color',
                'ticketit_priorities.name as priority_name',
                'ticketit_priorities.color as priority_color',
                'ticketit_categories.name as category_name',
                'customers.name as customer_name'
            ]);

        // If user is agent but not admin, only show their tickets
        if ($this->isAgent() && !$this->isAdmin()) {
            $query->where('agent_id', $user->id);
        }

        $tickets = $query->orderBy('ticketit.created_at', 'desc')->get();

        // Get agent list if user is admin
        $agents = [];
        if ($this->isAdmin()) {
            $agents = $this->agent->agents()->get();
        }

        return view('ticketit::tickets.staff.index', [
            'tickets' => $tickets,
            'isAdmin' => $this->isAdmin(),
            'isAgent' => $this->isAgent(),
            'agents' => $agents,
            'stats' => [
                'total' => $tickets->count(),
                'unassigned' => $tickets->whereNull('agent_id')->count(),
                'open' => $tickets->where('status_id', 1)->count(),
                'pending' => $tickets->where('status_id', 2)->count(),
                'high_priority' => $tickets->where('priority_id', 3)->count()
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error loading staff tickets: ' . $e->getMessage());
        
        return view('ticketit::tickets.staff.index', [
            'tickets' => collect([]),
            'isAdmin' => $this->isAdmin(),
            'isAgent' => $this->isAgent(),
            'agents' => collect([]),
            'stats' => [
                'total' => 0,
                'unassigned' => 0,
                'open' => 0,
                'pending' => 0,
                'high_priority' => 0
            ],
            'error' => 'Error loading tickets. Please try again.'
        ]);
    }
}
    public function updateStatus(Request $request, $id)
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'status' => 'required|exists:ticketit_statuses,id'
            ]);

            // Check authorization
            if (!Auth::user()->isAgent()) {
                return redirect()->back()
                    ->with('error', 'Only agents can update ticket status');
            }

            // Get the ticket
            $ticket = Ticket::with('status')->findOrFail($id);
            
            // Check if ticket is assigned to this agent
            if ($ticket->agent_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return redirect()->back()
                    ->with('error', 'You can only update status of tickets assigned to you');
            }

            // Store old status for comparison
            $oldStatus = $ticket->status->name;
            
            // Update ticket
            $ticket->status_id = $validatedData['status'];
            $ticket->save();

            // Get new status name for message
            $newStatus = Status::find($validatedData['status'])->name;

            // Log the change
            Log::info('Ticket status updated:', [
                'ticket_id' => $ticket->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'updated_by' => Auth::id()
            ]);

            return redirect()->back()
                ->with('success', "Status updated from {$oldStatus} to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Error updating ticket status:', [
                'error' => $e->getMessage(),
                'ticket_id' => $id,
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->withErrors(['status' => 'Error updating ticket status'])
                ->withInput();
        }
    }
    public function staffShow($id)
{
    try {
        // Get the ticket with relationships
        $ticket = $this->tickets->with([
            'status', 
            'priority', 
            'category', 
            'customer', 
            'agent', 
            'comments.user'
        ])->findOrFail($id);

        // Check if admin
        if ($this->agent->isAdmin()) {
            // Get available agents using Agent model
            $agents = $this->agent->agents()->get();

            return view('ticketit::tickets.staff.show', [
                'ticket' => $ticket,
                'isAdmin' => true,
                'isAgent' => false,
                'agents' => $agents,
                'statuses' => Status::pluck('name', 'id')
            ]);
        }

        // Check if agent
        if ($this->agent->isAgent(auth()->id())) {
            // Check if ticket is assigned to this agent
            if (!$this->agent->isAssignedAgent($id)) {
                return redirect()->route('staff.tickets.index')
                    ->with('error', 'You can only view tickets assigned to you');
            }

            return view('ticketit::tickets.staff.show', [
                'ticket' => $ticket,
                'isAdmin' => false,
                'isAgent' => true,
                'statuses' => Status::pluck('name', 'id')
            ]);
        }

        // Neither admin nor agent
        return redirect()->route('staff.tickets.index')
            ->with('error', 'Unauthorized access');

    } catch (\Exception $e) {
        Log::error('Error showing ticket:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ticket_id' => $id
        ]);

        return redirect()->route('staff.tickets.index')
            ->with('error', 'Error loading ticket details');
    }
}
    public function assignTicket(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user->ticketit_admin) {
                return redirect()->back()
                    ->with('error', 'Only administrators can assign tickets');
            }

            // Validate agent selection
            $validatedData = $request->validate([
                'agent_id' => 'required|exists:users,id,ticketit_agent,1'
            ]);

            // Find and assign the ticket
            $ticket = $this->tickets->findOrFail($id);
            $ticket->agent_id = $validatedData['agent_id'];
            $ticket->save();

            // Log the assignment
            Log::info('Ticket assigned:', [
                'ticket_id' => $id,
                'agent_id' => $validatedData['agent_id'],
                'admin_id' => $user->id
            ]);

            return redirect()->back()
                ->with('success', 'Ticket assigned successfully');

        } catch (\Exception $e) {
            Log::error('Error assigning ticket:', [
                'error' => $e->getMessage(),
                'ticket_id' => $id,
                'admin_id' => $user->id ?? null
            ]);

            return redirect()->back()
                ->with('error', 'Error assigning ticket');
        }
    }


    public function agentShow($id)
{
    try {
        // Get the ticket with relationships
        $ticket = $this->tickets->with([
            'status',
            'priority',
            'category',
            'customer',
            'agent',
            'comments.user'
        ])->findOrFail($id);

        // Check if the logged-in user is an agent
        if (!auth()->user()->isAgent()) {
            return redirect()->route('staff.tickets.index')
                ->with('error', 'Unauthorized access');
        }

        // Check if the ticket is assigned to this agent
        if ($ticket->agent_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return redirect()->route('staff.tickets.index')
                ->with('error', 'You can only view tickets assigned to you');
        }

        // Return the agent view for the ticket
        return view('ticketit::tickets.staff.agent.show', [
            'ticket' => $ticket,
            'isAgent' => true,
            'isAdmin' => auth()->user()->isAdmin(),
            'statuses' => Status::pluck('name', 'id')
        ]);

    } catch (\Exception $e) {
        Log::error('Error showing agent ticket:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ticket_id' => $id
        ]);

        return redirect()->route('staff.tickets.index')
            ->with('error', 'Error loading ticket details');
    }
}

    public function data($complete = false)
    {
        if (LaravelVersion::min('5.4')) {
            $datatables = app(\Yajra\DataTables\DataTables::class);
        } else {
            $datatables = app(\Yajra\Datatables\Datatables::class);
        }

        $collection = $this->getTicketCollection($complete);
        $collection = $this->joinTicketTables($collection);
        $collection = $datatables->of($collection);

        $this->renderTicketTable($collection);

        $collection->editColumn('updated_at', '{!! \Carbon\Carbon::parse($updated_at)->diffForHumans() !!}');

        if (LaravelVersion::min('5.4')) {
            $collection->rawColumns(['subject', 'status', 'priority', 'category', 'agent']);
        }

        return $collection->make(true);
    }

    protected function getTicketCollection($complete = false)
    {
        if ($this->isCustomer()) {
            return $this->getCustomerTickets($complete);
        }
        return $this->getStaffTickets($complete);
    }

    protected function getCustomerTickets($complete)
    {
        $tickets = $this->tickets->where('customer_id', $this->getAuthUser()->id);
        return $complete ? $tickets->complete() : $tickets->active();
    }

    protected function getStaffTickets($complete)
    {
        $user = $this->agent->find(auth()->user()->id);

        if ($user->isAdmin()) {
            return $complete ? Ticket::complete() : Ticket::active();
        }

        if ($user->isAgent()) {
            return $complete ? 
                Ticket::complete()->agentUserTickets($user->id) : 
                Ticket::active()->agentUserTickets($user->id);
        }

        return $complete ? 
            Ticket::userTickets($user->id)->complete() : 
            Ticket::userTickets($user->id)->active();
    }

    protected function joinTicketTables($collection)
    {
        return $collection->join('ticketit_statuses', 'ticketit_statuses.id', '=', 'ticketit.status_id')
            ->join('ticketit_priorities', 'ticketit_priorities.id', '=', 'ticketit.priority_id')
            ->join('ticketit_categories', 'ticketit_categories.id', '=', 'ticketit.category_id')
            ->leftJoin('users', 'users.id', '=', 'ticketit.user_id')
            ->leftJoin('customers', 'customers.id', '=', 'ticketit.customer_id')
            ->select([
                'ticketit.id',
                'ticketit.subject AS subject',
                'ticketit_statuses.name AS status',
                'ticketit_statuses.color AS color_status',
                'ticketit_priorities.color AS color_priority',
                'ticketit_categories.color AS color_category',
                'ticketit.id AS agent',
                'ticketit.updated_at AS updated_at',
                'ticketit_priorities.name AS priority',
                'users.name AS owner',
                'customers.name AS customer_name',
                'ticketit.agent_id',
                'ticketit_categories.name AS category',
                'ticketit.customer_id',
                'ticketit.user_id'
            ]);
    }

    public function renderTicketTable($collection)
    {
        $collection->editColumn('subject', function ($ticket) {
            return (string) link_to_route(
                $this->isCustomer() ? 'customer.tickets.show' : Setting::grab('main_route').'.show',
                $ticket->subject,
                $ticket->id
            );
        });

        $collection->editColumn('status', function ($ticket) {
            $color = $ticket->color_status;
            $status = e($ticket->status);
            return "<div style='color: $color'>$status</div>";
        });

        $collection->editColumn('priority', function ($ticket) {
            $color = $ticket->color_priority;
            $priority = e($ticket->priority);
            return "<div style='color: $color'>$priority</div>";
        });

        $collection->editColumn('category', function ($ticket) {
            $color = $ticket->color_category;
            $category = e($ticket->category);
            return "<div style='color: $color'>$category</div>";
        });

        $collection->editColumn('agent', function ($ticket) {
            $ticket = $this->tickets->find($ticket->id);
            return e($ticket->agent->name);
        });

        if (!$this->isCustomer()) {
            $collection->editColumn('customer_name', function ($ticket) {
                return $ticket->customer_id ? e($ticket->customer_name) : 'N/A';
            });
        }

        return $collection;
    }

    protected function PCS()
    {
        $time = LaravelVersion::min('5.8') ? 60*60 : 60;

        try {
            $priorities = Cache::remember('ticketit::priorities', $time, function () {
                return Models\Priority::orderBy('name')->get();
            });

            $categories = Cache::remember('ticketit::categories', $time, function () {
                return Models\Category::orderBy('name')->get();
            });

            $statuses = Cache::remember('ticketit::statuses', $time, function () {
                return Models\Status::orderBy('name')->get();
            });

            if ($priorities->isEmpty() && $categories->isEmpty()) {
                $this->ensureDefaultDataExists();
                
                $priorities = Models\Priority::orderBy('name')->get();
                $categories = Models\Category::orderBy('name')->get();
                $statuses = Models\Status::orderBy('name')->get();
            }

            return [
                $priorities->pluck('name', 'id'),
                $categories->pluck('name', 'id'),
                $statuses->pluck('name', 'id')
            ];

        } catch (\Exception $e) {
            Log::error('Error in PCS method: ' . $e->getMessage());
            return [collect([]), collect([]), collect([])];
        }
    }

    public function create()
{
    try {
        Log::info('Starting ticket creation form', [
            'user' => auth()->guard('customer')->user()->only(['id', 'name', 'email'])
        ]);

        if (!$this->isCustomer()) {
            Log::warning('Non-customer attempted to access ticket creation');
            return redirect()->route('login')
                ->with('error', 'You must be logged in as a customer to create tickets.');
        }

        $this->ensureDefaultDataExists();

        $categories = Category::orderBy('name')->pluck('name', 'id');
        $priorities = Priority::orderBy('name')->pluck('name', 'id');

        Log::info('Rendering ticket creation form', [
            'view_exists' => [
                'ticketit::tickets.create_customer' => 
                    View::exists('ticketit::tickets.create_customer'),
                'vendor.ticketit.tickets.create_customer' => 
                    View::exists('vendor.ticketit.tickets.create_customer'),
            ],
            'view_paths' => View::getFinder()->getPaths(),
            'view_hints' => View::getFinder()->getHints()
        ]);

        return view('ticketit::tickets.create_customer', [
            'categories' => $categories,
            'priorities' => $priorities,
            'master' => 'layouts.app',
            'debug' => [
                'view_paths' => View::getFinder()->getPaths(),
                'view_hints' => View::getFinder()->getHints(),
                'auth' => [
                    'guard' => 'customer',
                    'check' => auth()->guard('customer')->check(),
                    'user' => auth()->guard('customer')->check() ? 
                        auth()->guard('customer')->user()->only(['id', 'name', 'email']) : null
                ]
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error in ticket creation form', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'view_paths' => View::getFinder()->getPaths(),
            'view_hints' => View::getFinder()->getHints()
        ]);

        return redirect()->back()
            ->with('error', 'Error loading ticket form. Please try again.');
    }



}


    protected function ensureDefaultSettingsExist()
    {
        try {
            $defaults = [
                'main_route' => 'tickets',
                'main_route_path' => 'tickets',
                'admin_route' => 'tickets-admin',
                'admin_route_path' => 'tickets-admin',
                'paginate_items' => '10',
                'default_status_id' => '1',
                'default_close_status_id' => '3',
                'default_reopen_status_id' => '1',
                'status_notification' => '1',
                'comment_notification' => '1',
                'close_ticket_perm' => ['owner' => 'yes', 'agent' => 'yes', 'admin' => 'yes'],
                'reopen_ticket_perm' => ['owner' => 'yes', 'agent' => 'yes', 'admin' => 'yes']
            ];

            foreach ($defaults as $slug => $value) {
                if (!Setting::grab($slug)) {
                    Setting::set($slug, $value);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error ensuring default settings: ' . $e->getMessage());
        }
    }
    public function store(Request $request)
{
    try {
        Log::info('Creating new ticket', [
            'request' => $request->all(),
            'user' => Auth::guard('customer')->user() ? 
                Auth::guard('customer')->user()->only(['id', 'name', 'email']) : null
        ]);

        if (!Auth::guard('customer')->check()) {
            throw new \Exception('Authentication required');
        }

        DB::beginTransaction();

        // Validate request
        $validator = Validator::make($request->all(), [
            'subject' => 'required|min:3',
            'content' => 'required|min:6',
            'category_name' => 'required|in:Technical,Billing,Customer Service',
            'priority_name' => 'required|in:Low,Medium,High',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Get or create category
            $category = Category::firstOrCreate(
                ['name' => $request->category_name],
                ['color' => $this->getCategoryColor($request->category_name)]
            );

            // Get  priority
            $priority = Priority::firstOrCreate(
                ['name' => $request->priority_name],
                ['color' => $this->getPriorityColor($request->priority_name)]
            );

            // Get default status
            $defaultStatusId = Setting::grab('default_status_id', 1);
            $status = Status::firstOrCreate(
                ['id' => $defaultStatusId],
                [
                    'name' => 'Open',
                    'color' => '#0014f4'
                ]
            );

            // Create ticket
            $ticket = new Ticket();
            $ticket->subject = $request->subject;
            $ticket->content = $request->content;
            $ticket->status_id = $status->id;
            $ticket->priority_id = $priority->id;
            $ticket->category_id = $category->id;
            $ticket->customer_id = Auth::guard('customer')->id();
            
            if (!$ticket->save()) {
                throw new \Exception('Failed to save ticket');
            }

            DB::commit();

            Log::info('Ticket created successfully', [
                'ticket_id' => $ticket->id,
                'customer_id' => $ticket->customer_id
            ]);

            return redirect()->route('customer.tickets.index')
                ->with('success', 'Ticket has been created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    } catch (\Exception $e) {
        Log::error('Error creating ticket: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        
        return redirect()->back()
            ->with('error', 'Failed to create ticket: ' . $e->getMessage())
            ->withInput();
    }
}

    protected function getCategoryColor($name)
    {
        return [
            'Technical' => '#0014f4',
            'Billing' => '#2b9900',
            'Customer Service' => '#7e0099'
        ][$name] ?? '#000000';
    }

    protected function getPriorityColor($name)
    {
        return [
            'Low' => '#069900',
            'Medium' => '#e1d200',
            'High' => '#e10000'
        ][$name] ?? '#000000';
    }





    public function index()
    {
        try {
            if (!$this->isCustomer()) {
                return redirect()->route('home')
                    ->with('warning', 'You are not permitted to access this page.');
            }

            $customer = $this->getAuthUser();
            
            $tickets = app('db')->connection()
                ->table('ticketit')
                ->where('customer_id', $customer->id)
                ->join('ticketit_statuses', 'ticketit.status_id', '=', 'ticketit_statuses.id')
                ->join('ticketit_priorities', 'ticketit.priority_id', '=', 'ticketit_priorities.id')
                ->join('ticketit_categories', 'ticketit.category_id', '=', 'ticketit_categories.id')
                ->select([
                    'ticketit.*',
                    'ticketit_statuses.name as status_name',
                    'ticketit_statuses.color as status_color',
                    'ticketit_priorities.name as priority_name',
                    'ticketit_priorities.color as priority_color',
                    'ticketit_categories.name as category_name',
                    'ticketit_categories.color as category_color'
                ])
                ->orderBy('ticketit.created_at', 'desc')
                ->get();

            return view('ticketit::tickets.customer.index', compact('tickets'));

        } catch (\Exception $e) {
            Log::error('Error loading tickets: ' . $e->getMessage(), [
                'customer_id' => $this->getAuthUser()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error loading tickets. Please try again.');
        }
}    
    public function show($id)
    {
        try {
            if (!$this->isCustomer()) {
                return redirect()->route('home')
                    ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
            }

            $customer = $this->getAuthUser();
            
            // Get ticket info
            $ticket = app('db')->connection()
                ->table('ticketit')
                ->where('ticketit.customer_id', $customer->id)
                ->where('ticketit.id', $id)
                ->join('ticketit_statuses', 'ticketit.status_id', '=', 'ticketit_statuses.id')
                ->join('ticketit_priorities', 'ticketit.priority_id', '=', 'ticketit_priorities.id')
                ->join('ticketit_categories', 'ticketit.category_id', '=', 'ticketit_categories.id')
                ->select([
                    'ticketit.*',
                    'ticketit_statuses.name as status_name',
                    'ticketit_statuses.color as status_color',
                    'ticketit_priorities.name as priority_name',
                    'ticketit_priorities.color as priority_color',
                    'ticketit_categories.name as category_name',
                    'ticketit_categories.color as category_color'
                ])
                ->first();

            if (!$ticket) {
                return redirect()->route('customer.tickets.index')
                    ->with('error', 'Ticket not found.');
            }

            
            return view('ticketit::bootstrap3.tickets.showTicket', compact('ticket'));

        } catch (\Exception $e) {
            Log::error('Error showing ticket: ' . $e->getMessage());
            return redirect()->route('customer.tickets.index')
                ->with('error', 'Error loading ticket. Please try again.');
        }
    }
    public function update(Request $request, $id)
    {
        if ($this->isCustomer()) {
            return redirect()->route('customer.tickets.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-do-this'));
        }

        $this->validate($request, [
            'subject' => 'required|min:3',
            'content' => 'required|min:6',
            'priority_id' => 'required|exists:ticketit_priorities,id',
            'category_id' => 'required|exists:ticketit_categories,id',
            'status_id' => 'required|exists:ticketit_statuses,id',
            'agent_id' => 'required',
        ]);

        $ticket = $this->tickets->findOrFail($id);

        $ticket->subject = $request->subject;
        $ticket->content = $request->content;
        $ticket->status_id = $request->status_id;
        $ticket->category_id = $request->category_id;
        $ticket->priority_id = $request->priority_id;

        if ($request->input('agent_id') == 'auto') {
            $ticket->autoSelectAgent();
        } else {
            $ticket->agent_id = $request->input('agent_id');
        }

        $ticket->save();

        return redirect()->route(Setting::grab('main_route').'.show', $id)
            ->with('status', trans('ticketit::lang.the-ticket-has-been-modified'));
    }

    public function complete($id)
    {
        if ($this->permToClose($id) == 'yes') {
            $ticket = $this->tickets->findOrFail($id);
            $ticket->completed_at = Carbon::now();

            if (Setting::grab('default_close_status_id')) {
                $ticket->status_id = Setting::grab('default_close_status_id');
            }

            $ticket->save();

            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('status', trans('ticketit::lang.the-ticket-has-been-completed', 
                    ['name' => $ticket->subject]));
        }

        return redirect()->route(Setting::grab('main_route').'.index')
            ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-do-this'));
    }

    public function reopen($id)
    {
        if ($this->permToReopen($id) == 'yes') {
            $ticket = $this->tickets->findOrFail($id);
            $ticket->completed_at = null;

            if (Setting::grab('default_reopen_status_id')) {
                $ticket->status_id = Setting::grab('default_reopen_status_id');
            }

            $ticket->save();

            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('status', trans('ticketit::lang.the-ticket-has-been-reopened', 
                    ['name' => $ticket->subject]));
        }

        return redirect()->route(Setting::grab('main_route').'.index')
            ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-do-this'));
    }

    public function agentSelectList($category_id, $ticket_id)
    {
        $cat_agents = Category::find($category_id)->agents()->agentsLists();
        $agents = is_array($cat_agents) ? ['auto' => 'Auto Select'] + $cat_agents : ['auto' => 'Auto Select'];

        $selected_Agent = $this->tickets->find($ticket_id)->agent->id;
        $select = '<select class="form-control" id="agent_id" name="agent_id">';
        foreach ($agents as $id => $name) {
            $selected = ($id == $selected_Agent) ? 'selected' : '';
            $select .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
        }
        $select .= '</select>';

        return $select;
    }

    public function destroy($id)
    {
        $ticket = $this->tickets->findOrFail($id);
        $subject = $ticket->subject;
        $ticket->delete();

        return redirect()->route(Setting::grab('main_route').'.index')
            ->with('status', trans('ticketit::lang.the-ticket-has-been-deleted', ['name' => $subject]));
    }

    public function permToClose($id)
    {
        $close_ticket_perm = Setting::grab('close_ticket_perm');

        if ($this->agent->isAdmin() && $close_ticket_perm['admin'] == 'yes') {
            return 'yes';
        }
        if ($this->agent->isAgent() && $close_ticket_perm['agent'] == 'yes') {
            return 'yes';
        }
        if ($this->agent->isTicketOwner($id) && $close_ticket_perm['owner'] == 'yes') {
            return 'yes';
        }

        return 'no';
    }

    public function permToReopen($id)
    {
        $reopen_ticket_perm = Setting::grab('reopen_ticket_perm');
        
        if ($this->agent->isAdmin() && $reopen_ticket_perm['admin'] == 'yes') {
            return 'yes';
        } 
        if ($this->agent->isAgent() && $reopen_ticket_perm['agent'] == 'yes') {
            return 'yes';
        } 
        if ($this->agent->isTicketOwner($id) && $reopen_ticket_perm['owner'] == 'yes') {
            return 'yes';
        }

        return 'no';
    }

    public function monthlyPerfomance($period = 2)
    {
        $categories = Category::all();
        $records = ['categories' => []];

        foreach ($categories as $cat) {
            $records['categories'][] = $cat->name;
        }

        for ($m = $period; $m >= 0; $m--) {
            $from = Carbon::now();
            $from->day = 1;
            $from->subMonth($m);
            
            $to = Carbon::now();
            $to->day = 1;
            $to->subMonth($m);
            $to->endOfMonth();
            
            $records['interval'][$from->format('F Y')] = [];
            
            foreach ($categories as $cat) {
                $records['interval'][$from->format('F Y')][] = 
                    round($this->intervalPerformance($from, $to, $cat->id), 1);
            }
        }

        return $records;
    }

    public function ticketPerformance($ticket)
    {
        if ($ticket->completed_at == null) {
            return false;
        }

        $created = new Carbon($ticket->created_at);
        $completed = new Carbon($ticket->completed_at);
        
        return $created->diff($completed)->days;
    }

    public function intervalPerformance($from, $to, $cat_id = false)
    {
        $query = Ticket::whereBetween('completed_at', [$from, $to]);
        
        if ($cat_id) {
            $query->where('category_id', $cat_id);
        }

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            return false;
        }

        $performance_count = 0;
        $counter = 0;

        foreach ($tickets as $ticket) {
            $performance_count += $this->ticketPerformance($ticket);
            $counter++;
        }

        return $performance_count / $counter;
    }

    protected function getValidationMessages()
    {
        return [
            'subject.required' => trans('ticketit::lang.validation.subject.required'),
            'subject.min' => trans('ticketit::lang.validation.subject.min'),
            'content.required' => trans('ticketit::lang.validation.content.required'),
            'content.min' => trans('ticketit::lang.validation.content.min'),
            'priority_id.required' => trans('ticketit::lang.validation.priority_id.required'),
            'priority_id.exists' => trans('ticketit::lang.validation.priority_id.exists'),
            'category_id.required' => trans('ticketit::lang.validation.category_id.required'),
            'category_id.exists' => trans('ticketit::lang.validation.category_id.exists'),
        ];
    }
}