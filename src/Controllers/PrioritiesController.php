<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Ticket\Ticketit\Models\Priority;
use Ticket\Ticketit\Helpers\LaravelVersion;

class PrioritiesController extends BaseTicketController
{
    public function __construct()
    {
        // Only staff can manage priorities
        $this->middleware('auth:web');
        $this->middleware('Ticket\Ticketit\Middleware\IsAdminMiddleware');
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $time = LaravelVersion::min('5.8') ? 60*60 : 60;
        $priorities = Cache::remember('ticketit::priorities', $time, function () {
            return Priority::all();
        });

        return view('ticketit::admin.priority.index', compact('priorities'));
    }

    public function create()
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        return view('ticketit::admin.priority.create');
    }

    public function store(Request $request)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $this->validate($request, [
            'name'  => 'required',
            'color' => 'required',
        ]);

        $priority = new Priority();
        $priority->create(['name' => $request->name, 'color' => $request->color]);

        Session::flash('status', trans('ticketit::lang.priority-name-has-been-created', 
            ['name' => $request->name]));

        Cache::forget('ticketit::priorities');

        return redirect()->action('\Ticket\Ticketit\Controllers\PrioritiesController@index');
    }

    public function show($id)
    {
        if (!$this->canManageTickets()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        return trans('ticketit::lang.priority-all-tickets-here');
    }

    public function edit($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $priority = Priority::findOrFail($id);
        return view('ticketit::admin.priority.edit', compact('priority'));
    }

    public function update(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $this->validate($request, [
            'name'  => 'required',
            'color' => 'required',
        ]);

        $priority = Priority::findOrFail($id);
        $priority->update(['name' => $request->name, 'color' => $request->color]);

        Session::flash('status', trans('ticketit::lang.priority-name-has-been-modified', 
            ['name' => $request->name]));

        Cache::forget('ticketit::priorities');

        return redirect()->action('\Ticket\Ticketit\Controllers\PrioritiesController@index');
    }

    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $priority = Priority::findOrFail($id);
        $name = $priority->name;
        $priority->delete();

        Session::flash('status', trans('ticketit::lang.priority-name-has-been-deleted', 
            ['name' => $name]));

        Cache::forget('ticketit::priorities');

        return redirect()->action('\Ticket\Ticketit\Controllers\PrioritiesController@index');
    }
}