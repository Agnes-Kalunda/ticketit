<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Ticket\Ticketit\Models\Status;
use Ticket\Ticketit\Helpers\LaravelVersion;

class StatusesController extends BaseTicketController
{
    public function __construct()
    {
        // Only staff can manage statuses
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
        $statuses = Cache::remember('ticketit::statuses', $time, function () {
            return Status::all();
        });

        return view('ticketit::admin.status.index', compact('statuses'));
    }

    public function create()
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        return view('ticketit::admin.status.create');
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

        $status = new Status();
        $status->create(['name' => $request->name, 'color' => $request->color]);

        Session::flash('status', trans('ticketit::lang.status-name-has-been-created', 
            ['name' => $request->name]));

        Cache::forget('ticketit::statuses');

        return redirect()->action('\Ticket\Ticketit\Controllers\StatusesController@index');
    }

    public function show($id)
    {
        if (!$this->canManageTickets()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        return trans('ticketit::lang.status-all-tickets-here');
    }

    public function edit($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $status = Status::findOrFail($id);
        return view('ticketit::admin.status.edit', compact('status'));
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

        $status = Status::findOrFail($id);
        $status->update(['name' => $request->name, 'color' => $request->color]);

        Session::flash('status', trans('ticketit::lang.status-name-has-been-modified', 
            ['name' => $request->name]));

        Cache::forget('ticketit::statuses');

        return redirect()->action('\Ticket\Ticketit\Controllers\StatusesController@index');
    }

    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $status = Status::findOrFail($id);
        $name = $status->name;
        $status->delete();

        Session::flash('status', trans('ticketit::lang.status-name-has-been-deleted', 
            ['name' => $name]));

        Cache::forget('ticketit::statuses');

        return redirect()->action('\Ticket\Ticketit\Controllers\StatusesController@index');
    }
}