<?php

namespace Ticket\Ticketit\Controllers;

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
use Illuminate\Routing\Controller;

class TicketsController extends Controller
{
    protected $tickets;
    protected $agent;

    /**
     * TicketsController constructor.
     *
     * @param Ticket $tickets
     * @param Agent $agent
     */
    public function __construct(Ticket $tickets, Agent $agent)
    {
        $this->middleware('Ticket\Ticketit\Middleware\ResAccessMiddleware', ['only' => ['show']]);
        $this->middleware('Ticket\Ticketit\Middleware\IsAgentMiddleware', ['only' => ['edit', 'update']]);
        $this->middleware('Ticket\Ticketit\Middleware\IsAdminMiddleware', ['only' => ['destroy']]);

        $this->tickets = $tickets;
        $this->agent = $agent;
    }

    /**
     * Determine if the current user is a customer
     *
     * @return bool
     */
    protected function isCustomer()
    {
        return Auth::guard('customer')->check();
    }

    /**
     * Get authenticated user (either customer or user)
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getAuthUser()
    {
        if ($this->isCustomer()) {
            return Auth::guard('customer')->user();
        }
        return Auth::user();
    }

    /**
     * Display customer tickets index
     *
     * @return \Illuminate\View\View
     */
    public function customerIndex()
    {
        if (!$this->isCustomer()) {
            return redirect()->route('home')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
        }

        $tickets = $this->tickets
            ->where('customer_id', $this->getAuthUser()->id)
            ->with(['status', 'priority', 'category', 'agent'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('ticketit::tickets.index_customer', compact('tickets'));
    }

    /**
     * Get table data for datatables
     *
     * @param bool $complete
     * @return mixed
     */
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

    /**
     * Get the base ticket collection based on user type
     *
     * @param bool $complete
     * @return mixed
     */
    protected function getTicketCollection($complete = false)
    {
        if ($this->isCustomer()) {
            return $this->getCustomerTickets($complete);
        }

        return $this->getStaffTickets($complete);
    }

    /**
     * Get customer specific tickets
     *
     * @param bool $complete
     * @return mixed
     */
    protected function getCustomerTickets($complete)
    {
        $tickets = $this->tickets->where('customer_id', $this->getAuthUser()->id);
        return $complete ? $tickets->complete() : $tickets->active();
    }

    /**
     * Get staff specific tickets
     *
     * @param bool $complete
     * @return mixed
     */
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

    /**
     * Join ticket tables for datatables
     *
     * @param mixed $collection
     * @return mixed
     */
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

    /**
     * Render ticket table
     *
     * @param mixed $collection
     * @return mixed
     */
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

    /**
     * Display index page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if ($this->isCustomer()) {
            return redirect()->route('customer.tickets.index');
        }

        $user = $this->getAuthUser();
        
        // Get all tickets if admin, only assigned tickets if agent
        $tickets = $this->tickets
            ->when(!$user->ticketit_admin, function($query) use ($user) {
                return $query->where('agent_id', $user->id);
            })
            ->with(['status', 'priority', 'category', 'customer'])
            ->latest()
            ->paginate(10);

        // For displaying customer details
        $tickets->getCollection()->transform(function ($ticket) {
            if ($ticket->customer) {
                $ticket->customer_name = $ticket->customer->name ?? 'Unknown';
                $ticket->customer_email = $ticket->customer->email ?? 'No email';
            } else {
                $ticket->customer_name = 'N/A';
                $ticket->customer_email = 'N/A';
            }
            return $ticket;
        });

        $complete = false;
        return view('ticketit::index', compact('tickets', 'complete'));
    }


    /**
     * Display completed tickets
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    
    public function indexComplete()
    {
        if ($this->isCustomer()) {
            return redirect()->route('customer.tickets.index');
        }

        $user = $this->getAuthUser();
        
        $tickets = $this->tickets
            ->whereNotNull('completed_at')
            ->when(!$user->ticketit_admin, function($query) use ($user) {
                return $query->where('agent_id', $user->id);
            })
            ->with(['status', 'priority', 'category', 'customer'])
            ->latest()
            ->paginate(10);

        $tickets->getCollection()->transform(function ($ticket) {
            if ($ticket->customer) {
                $ticket->customer_name = $ticket->customer->name ?? 'Unknown';
                $ticket->customer_email = $ticket->customer->email ?? 'No email';
            } else {
                $ticket->customer_name = 'N/A';
                $ticket->customer_email = 'N/A';
            }
            return $ticket;
        });

        $complete = true;
        return view('ticketit::index', compact('tickets', 'complete'));
    }


    /**
     * Returns priorities, categories and statuses lists
     *
     * @return array
     */
    protected function PCS()
{
    $time = LaravelVersion::min('5.8') ? 60*60 : 60;

    try {
        // Get priorities
        $priorities = Cache::remember('ticketit::priorities', $time, function () {
            return Models\Priority::orderBy('name')->get();
        });

        // Get categories
        $categories = Cache::remember('ticketit::categories', $time, function () {
            return Models\Category::orderBy('name')->get();
        });

        // Get statuses
        $statuses = Cache::remember('ticketit::statuses', $time, function () {
            return Models\Status::orderBy('name')->get();
        });

        // First check if there's data
        if ($priorities->isEmpty() && $categories->isEmpty()) {
            // Seed some default data
            $this->seedDefaultData();
            
            // Fetch again
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


    protected function seedDefaultData()
{
        try {
        // Seed default priorities if none exist
            if (Models\Priority::count() === 0) {
                Models\Priority::insert([
                    [
                        'name' => 'Low',
                        'color' => '#069900',
                        'created_at' => now(),
                        'updated_at' => now()
                ],
                [
                        'name' => 'Medium',
                        'color' => '#e1d200',
                        'created_at' => now(),
                        'updated_at' => now()
                ],
                [
                        'name' => 'High',
                        'color' => '#e10000',
                        'created_at' => now(),
                        'updated_at' => now()
                ]
            ]);
        }

        // Seed default categories if none exist
            if (Models\Category::count() === 0) {
                Models\Category::insert([
                 [
                        'name' => 'Technical',
                        'color' => '#0014f4',
                        'created_at' => now(),
                        'updated_at' => now()
                ],
                [
                        'name' => 'Billing',
                        'color' => '#2b9900',
                        'created_at' => now(),
                        'updated_at' => now()
                ],
                [
                        'name' => 'Customer Service',
                        'color' => '#7e0099',
                        'created_at' => now(),
                        'updated_at' => now()
                ]
            ]);
        }

        // Seed default statuses if none exist
            if (Models\Status::count() === 0) {
                Models\Status::insert([
                [
                        'name' => 'Open',
                        'color' => '#f39c12',
                        'created_at' => now(),
                        'updated_at' => now()
                ],
                [
                        'name' => 'In Progress',
                        'color' => '#3498db',
                        'created_at' => now(),
                        'updated_at' => now()
                ],
                [
                        'name' => 'Closed',
                        'color' => '#2ecc71',
                        'created_at' => now(),
                        'updated_at' => now()
                ]
            ]);
        }

        // Clear the cache after seeding
            Cache::forget('ticketit::priorities');
            Cache::forget('ticketit::categories');
            Cache::forget('ticketit::statuses');

     } catch (\Exception $e) {
            Log::error('Error seeding default data: ' . $e->getMessage());
    }
}

    /**
     * Show create ticket form
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create()
{
    if (!$this->isCustomer()) {
        return redirect()->route(Setting::grab('main_route').'.index')
            ->with('warning', 'Staff members cannot create tickets');
    }

    // Check if we have categories and priorities, if not seed them
    if (Models\Category::count() === 0 || Models\Priority::count() === 0) {
        $this->seedDefaultData();
    }

    list($priorities, $categories) = $this->PCS();
    
    return view('ticketit::tickets.create_customer', [
        'priorities' => $priorities,
        'categories' => $categories,
        'master' => 'layouts.app'
    ]);
}

    /**
     * Store a new ticket
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if (!$this->isCustomer()) {
            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-do-this'));
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|min:3',
            'content' => 'required|min:6',
            'priority_id' => 'required|exists:ticketit_priorities,id',
            'category_id' => 'required|exists:ticketit_categories,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $ticket = new Ticket();
            $ticket->subject = $request->subject;
            $ticket->setPurifiedContent($request->get('content'));
            $ticket->priority_id = $request->priority_id;
            $ticket->category_id = $request->category_id;
            $ticket->status_id = Setting::grab('default_status_id');
            $ticket->customer_id = $this->getAuthUser()->id;
            $ticket->autoSelectAgent();
            $ticket->save();

            return redirect()->route('customer.tickets.index')
                ->with('status', trans('ticketit::lang.the-ticket-has-been-created'));
                
        } catch (\Exception $e) {
            Log::error('Ticket creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', trans('ticketit::lang.ticket-creation-error'))
                ->withInput();
        }
    }

    /**
     * Display ticket
     *
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        $ticket = $this->tickets->findOrFail($id);

        if ($this->isCustomer()) {
            if ($ticket->customer_id != $this->getAuthUser()->id) {
                return redirect()->route('customer.tickets.index')
                    ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-do-this'));
            }

            $comments = $ticket->comments()
                ->orderBy('created_at', 'desc')
                ->paginate(Setting::grab('paginate_items'));
            
            return view('ticketit::tickets.show_customer', compact('ticket', 'comments'));
        }

        list($priority_lists, $category_lists, $status_lists) = $this->PCS();

        $close_perm = $this->permToClose($id);
        $reopen_perm = $this->permToReopen($id);

        $cat_agents = Models\Category::find($ticket->category_id)->agents()->agentsLists();
        $agent_lists = is_array($cat_agents) ? ['auto' => 'Auto Select'] + $cat_agents : ['auto' => 'Auto Select'];

        $comments = $ticket->comments()->paginate(Setting::grab('paginate_items'));

        return view('ticketit::tickets.show', compact(
            'ticket', 'status_lists', 'priority_lists', 'category_lists',
            'agent_lists', 'comments', 'close_perm', 'reopen_perm'
        ));
    }

    /**
     * Update ticket
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
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
        $ticket->setPurifiedContent($request->get('content'));
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

    /**
     * Delete ticket
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $ticket = $this->tickets->findOrFail($id);
        $subject = $ticket->subject;
        $ticket->delete();

        return redirect()->route(Setting::grab('main_route').'.index')
            ->with('status', trans('ticketit::lang.the-ticket-has-been-deleted', ['name' => $subject]));
    }

    /**
     * Complete ticket
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Reopen ticket
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Get agent select list HTML
     *
     * @param int $category_id
     * @param int $ticket_id
     * @return string
     */
    public function agentSelectList($category_id, $ticket_id)
    {
        $cat_agents = Models\Category::find($category_id)->agents()->agentsLists();
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

    /**
     * Check permission to close ticket
     *
     * @param int $id
     * @return string
     */
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

    /**
     * Check permission to reopen ticket
     *
     * @param int $id
     * @return string
     */
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

    /**
     * Calculate monthly performance
     *
     * @param int $period
     * @return array
     */
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

    /**
     * Calculate ticket performance
     *
     * @param Ticket $ticket
     * @return int|false
     */
    public function ticketPerformance($ticket)
    {
        if ($ticket->completed_at == null) {
            return false;
        }

        $created = new Carbon($ticket->created_at);
        $completed = new Carbon($ticket->completed_at);
        
        return $created->diff($completed)->days;
    }

    /**
     * Calculate interval performance
     *
     * @param Carbon $from
     * @param Carbon $to
     * @param int|bool $cat_id
     * @return float|false
     */
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

    /**
     * Get validation messages
     *
     * @return array
     */
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