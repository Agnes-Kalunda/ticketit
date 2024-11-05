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
use Ticket\Ticketit\Seeds\TicketitTableSeeder;

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
        if (!$this->isCustomer()) {
            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('warning', 'Staff members cannot create tickets');
        }

        try {
            $this->ensureDefaultDataExists();

            $categories = Category::orderBy('name')->pluck('name', 'id');
            $priorities = Priority::orderBy('name')->pluck('name', 'id');

            Log::info('Form data retrieved:', [
                'categories_count' => count($categories),
                'priorities_count' => count($priorities)
            ]);

            return view('ticketit::tickets.create_customer', [
                'categories' => $categories,
                'priorities' => $priorities,
                'master' => 'layouts.app'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in create form: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error loading form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
{
    try {
        Log::info('Ticket submission attempt:', [
            'customer_id' => $this->getAuthUser()->id,
            'request_data' => $request->all()
        ]);

        if (!$this->isCustomer()) {
            return redirect()->route(Setting::grab('main_route').'.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-do-this'));
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|min:3',
            'content' => 'required|min:6',
            'category_name' => 'required|in:Technical,Billing,Customer Service',
            'priority_name' => 'required|in:Low,Medium,High',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed:', ['errors' => $validator->errors()->toArray()]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $connection = app('db')->connection();
        $connection->beginTransaction();

        try {
            // Get category
            $category = Category::firstOrCreate(
                ['name' => $request->category_name],
                ['color' => $this->getCategoryColor($request->category_name)]
            );

            // Get priority
            $priority = Priority::firstOrCreate(
                ['name' => $request->priority_name],
                ['color' => $this->getPriorityColor($request->priority_name)]
            );

            // Get default open status
            $status = Status::firstOrCreate(
                ['name' => 'Open'],
                ['color' => '#f39c12']
            );

            $ticket = new Ticket();
            $ticket->subject = $request->subject;
            $ticket->content = $request->content;
            $ticket->priority_id = $priority->id;
            $ticket->category_id = $category->id;
            $ticket->status_id = $status->id;
            $ticket->customer_id = $this->getAuthUser()->id;

            if (!$ticket->save()) {
                throw new \Exception('Failed to save ticket');
            }

            $connection->commit();

            return redirect()->route('customer.tickets.index')
                ->with('status', trans('ticketit::lang.the-ticket-has-been-created'));

        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    } catch (\Exception $e) {
        Log::error('Ticket creation failed: ' . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Failed to create ticket: ' . $e->getMessage())
            ->withInput();
    }
}
    protected function getCategoryColor($name)
    {
        $colors = [
            'Technical' => '#0014f4',
            'Billing' => '#2b9900',
            'Customer Service' => '#7e0099'
        ];
        return $colors[$name] ?? '#000000';
    }

    protected function getPriorityColor($name)
{
    $colors = [
        'Low' => '#069900',
        'Medium' => '#e1d200',
        'High' => '#e10000'
    ];
    return $colors[$name] ?? '#000000';
}


    public function index()
    {
        try {
            if (!$this->isCustomer()) {
                return redirect()->route('home')
                    ->with('warning', trans('ticketit::lang.you-are-not-permitted-to-access'));
            }

            $tickets = Ticket::where('customer_id', $this->getAuthUser()->id)
                            ->with(['status', 'priority', 'category'])
                            ->orderBy('created_at', 'desc')
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
        $cat_agents = Category::find($ticket->category_id)->agents()->agentsLists();
        $agent_lists = is_array($cat_agents) ? ['auto' => 'Auto Select'] + $cat_agents : ['auto' => 'Auto Select'];
        $comments = $ticket->comments()->paginate(Setting::grab('paginate_items'));

        return view('ticketit::tickets.show', compact(
            'ticket', 'status_lists', 'priority_lists', 'category_lists',
            'agent_lists', 'comments', 'close_perm', 'reopen_perm'
        ));
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