<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Ticket\Ticketit\Models\Category;
use Ticket\Ticketit\Helpers\LaravelVersion;
use Illuminate\Support\Facades\Cache;

class CategoriesController extends BaseTicketController
{
    public function __construct()
    {
        // Only staff with admin privileges can manage categories
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
        $categories = Cache::remember('ticketit::categories', $time, function () {
            return Category::all();
        });

        return view('ticketit::admin.category.index', compact('categories'));
    }

    public function create()
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        return view('ticketit::admin.category.create');
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

        $category = new Category();
        $category->create(['name' => $request->name, 'color' => $request->color]);

        Session::flash('status', trans('ticketit::lang.category-name-has-been-created', 
            ['name' => $request->name]));

        Cache::forget('ticketit::categories');

        return redirect()->action('\Ticket\Ticketit\Controllers\CategoriesController@index');
    }

    public function show($id)
    {
        if (!$this->canManageTickets()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        return 'All category related agents here';
    }

    public function edit($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $category = Category::findOrFail($id);
        return view('ticketit::admin.category.edit', compact('category'));
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

        $category = Category::findOrFail($id);
        $category->update(['name' => $request->name, 'color' => $request->color]);

        Session::flash('status', trans('ticketit::lang.category-name-has-been-modified', 
            ['name' => $request->name]));

        Cache::forget('ticketit::categories');

        return redirect()->action('\Ticket\Ticketit\Controllers\CategoriesController@index');
    }

    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $category = Category::findOrFail($id);
        $name = $category->name;
        $category->delete();

        Session::flash('status', trans('ticketit::lang.category-name-has-been-deleted', 
            ['name' => $name]));

        Cache::forget('ticketit::categories');

        return redirect()->action('\Ticket\Ticketit\Controllers\CategoriesController@index');
    }
}