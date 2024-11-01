<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Ticket\Ticketit\Models\Agent;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Helpers\LaravelVersion;

class AgentsController extends BaseTicketController
{
    public function __construct()
    {
        // Only staff can manage agents - using web guard for staff
        $this->middleware('auth:web');
        $this->middleware('Ticket\Ticketit\Middleware\IsAdminMiddleware');
    }

    public function index()
    {
        // 
        $agents = Agent::agents()->get();
        return view('ticketit::admin.agent.index', compact('agents'));
    }

    public function create()
    {
        // show users who can be made agents
        $users = Agent::paginate(Setting::grab('paginate_items'));
        return view('ticketit::admin.agent.create', compact('users'));
    }

    public function store(Request $request)
    {
        $rules = [
            'agents' => 'required|array|min:1',
        ];

        if(LaravelVersion::min('5.2')){
            $rules['agents.*'] = 'integer|exists:users,id';
        }

        $this->validate($request, $rules);

        $agents_list = $this->addAgents($request->input('agents'));
        $agents_names = implode(',', $agents_list);

        Session::flash('status', trans('ticketit::lang.agents-are-added-to-agents', 
            ['names' => $agents_names]));

        return redirect()->action('\Ticket\Ticketit\Controllers\AgentsController@index');
    }

    public function update($id, Request $request)
    {
        $this->syncAgentCategories($id, $request);

        Session::flash('status', trans('ticketit::lang.agents-joined-categories-ok'));

        return redirect()->action('\Ticket\Ticketit\Controllers\AgentsController@index');
    }

    public function destroy($id)
    {
        $agent = $this->removeAgent($id);

        Session::flash('status', trans('ticketit::lang.agents-is-removed-from-team', 
            ['name' => $agent->name]));

        return redirect()->action('\Ticket\Ticketit\Controllers\AgentsController@index');
    }

    /**
     * Assign users as agents 
     */
    protected function addAgents($user_ids)
    {
        $users = Agent::find($user_ids);
        $users_list = [];
        
        foreach ($users as $user) {
            $user->ticketit_agent = true;
            $user->save();
            $users_list[] = $user->name;
        }

        return $users_list;
    }

    /**
     * Remove user from agents 
     */
    protected function removeAgent($id)
    {
        $agent = Agent::find($id);
        $agent->ticketit_agent = false;
        $agent->save();

        // Remove from categories
        $agent_cats = $agent->categories->pluck('id')->toArray();
        $agent->categories()->detach($agent_cats);

        return $agent;
    }

    /**
     * Sync Agent categories 
     */
    protected function syncAgentCategories($id, Request $request)
    {
        $form_cats = ($request->input('agent_cats') == null) ? [] : $request->input('agent_cats');
        $agent = Agent::find($id);
        $agent->categories()->sync($form_cats);
    }
}