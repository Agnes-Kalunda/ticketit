<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Request;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Helpers\LaravelVersion;
use Illuminate\Support\Facades\Log;

// Load settings with proper caching
$settings = [
    'main_route' => Setting::grab('main_route') ?: 'tickets',
    'main_route_path' => Setting::grab('main_route_path') ?: 'tickets',
    'admin_route' => Setting::grab('admin_route') ?: 'tickets-admin',
    'admin_route_path' => Setting::grab('admin_route_path') ?: 'tickets-admin'
];

$main_route = $settings['main_route'];
$main_route_path = $settings['main_route_path'];
$admin_route = $settings['admin_route'];
$admin_route_path = $settings['admin_route_path'];

// Customer Ticket Routes
Route::group([
    'middleware' => ['web', 'auth:customer', \App\Http\Middleware\DebugMiddleware::class],
    'prefix' => 'customer/tickets',
    'as' => 'customer.tickets.',
    'namespace' => 'Ticket\Ticketit\Controllers'
], function () {
    try {
        // Ticket Management
        Route::get('/', 'TicketsController@index')->name('index');
        Route::get('/create', 'TicketsController@create')->name('create');
        Route::post('/', 'TicketsController@store')->name('store');
        Route::get('/{ticket}', 'TicketsController@show')->name('show');

        // Customer Comments
        Route::post('/{ticket}/comments', 'CommentsController@store')
            ->middleware(['ticketit.debug', 'ticketit.can-comment']);
    
        Route::put('/comments/{comment}', 'CommentsController@update')
            ->middleware(['ticketit.debug', 'ticketit.comment-owner']);
    
        Route::delete('/comments/{comment}', 'CommentsController@destroy')
            ->middleware(['ticketit.debug', 'ticketit.comment-owner']);

        Log::info('Customer routes registered successfully');
    } catch (\Exception $e) {
        Log::error('Error registering customer routes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Staff Routes
Route::group([
    'middleware' => ['web', 'auth:web'],
    'prefix' => 'staff/tickets',
    'as' => 'staff.tickets.',
    'namespace' => 'Ticket\Ticketit\Controllers'
], function () {
    // Ticket Management
    Route::get('/', 'TicketsController@staffIndex')->name('index');
    Route::get('/{id}', 'TicketsController@staffShow')
        ->name('show')
        ->middleware('Ticket\Ticketit\Middleware\StaffAccessMiddleware');
    Route::post('/{id}/status', 'TicketsController@updateStatus')
        ->name('status.update')
        ->middleware('Ticket\Ticketit\Middleware\IsAgentMiddleware');

    // Staff Comments
    Route::post('/{ticket}/comments', 'CommentsController@store')
        ->name('comments.store')
        ->middleware('can:add-ticket-comment');
    Route::put('/comments/{comment}', 'CommentsController@update')
        ->name('comments.update')
        ->middleware('can:update-ticket-comment');
    Route::delete('/comments/{comment}', 'CommentsController@destroy')
        ->name('comments.destroy')
        ->middleware('can:delete-ticket-comment');

    // Assignment (Admin Only)
    Route::post('/{id}/assign', 'TicketsController@assignTicket')
        ->name('assign')
        ->middleware('Ticket\Ticketit\Middleware\IsAdminMiddleware');
});

// Extended Staff/Admin Routes
Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => $main_route_path
], function () use ($main_route, $main_route_path) {
    // Ticket Listing & Data
    Route::get('/complete', 'Ticket\Ticketit\Controllers\TicketsController@indexComplete')
        ->name("$main_route-complete");
    Route::get('/data/{id?}', 'Ticket\Ticketit\Controllers\TicketsController@data')
        ->name("$main_route.data");

    // Basic Ticket CRUD
    Route::get('/', 'Ticket\Ticketit\Controllers\TicketsController@index')
        ->name("$main_route.index");
    Route::get('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@show')
        ->name("$main_route.show");
    Route::put('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@update')
        ->name("$main_route.update")
        ->middleware('can:update-ticket');
    Route::delete('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@destroy')
        ->name("$main_route.destroy")
        ->middleware('admin');

    // Ticket Status Changes
    Route::get('/{ticket}/complete', 'Ticket\Ticketit\Controllers\TicketsController@complete')
        ->name("$main_route.complete")
        ->middleware('can:complete-ticket');
    Route::get('/{ticket}/reopen', 'Ticket\Ticketit\Controllers\TicketsController@reopen')
        ->name("$main_route.reopen")
        ->middleware('can:reopen-ticket');

    // Agent Assignment Routes
    Route::group(['middleware' => 'agent'], function () use ($main_route) {
        Route::get("/agents/list/{category_id?}/{ticket_id?}", [
            'as' => $main_route.'agentselectlist',
            'uses' => 'Ticket\Ticketit\Controllers\TicketsController@agentSelectList',
        ]);
    });
});

// Admin Routes
Route::group([
    'middleware' => ['web', 'auth', 'admin'],
    'prefix' => $admin_route_path,
    'as' => "$admin_route."
], function () {
    // Dashboard
    Route::get('/', 'Ticket\Ticketit\Controllers\DashboardController@index')
        ->name('dashboard');
    Route::get('/indicator/{indicator_period?}', 'Ticket\Ticketit\Controllers\DashboardController@index')
        ->name('dashboard.indicator');

    // User Management
    Route::resource('users', 'Ticket\Ticketit\Controllers\UsersController');
    
    // Status Management
    Route::resource('status', 'Ticket\Ticketit\Controllers\StatusesController');

    // Priority Management
    Route::resource('priority', 'Ticket\Ticketit\Controllers\PrioritiesController');

    // Category Management
    Route::resource('category', 'Ticket\Ticketit\Controllers\CategoriesController');

    // Agent Management
    Route::resource('agent', 'Ticket\Ticketit\Controllers\AgentsController');

    // Comment Management (Admin Only)
    Route::get('/comments', 'Ticket\Ticketit\Controllers\CommentsController@adminIndex')
        ->name('comments.index');
    Route::delete('/comments/{comment}/force', 'Ticket\Ticketit\Controllers\CommentsController@forceDelete')
        ->name('comments.force-delete');
    Route::post('/comments/bulk-delete', 'Ticket\Ticketit\Controllers\CommentsController@bulkDelete')
        ->name('comments.bulk-delete');

    // Configuration
    Route::resource('configuration', 'Ticket\Ticketit\Controllers\ConfigurationsController');
    Route::resource('administrator', 'Ticket\Ticketit\Controllers\AdministratorsController');
});

// Installation Routes
if (app('request')->is('tickets-install') || 
    app('request')->is('tickets-upgrade') || 
    app('request')->is('tickets') || 
    app('request')->is('tickets-admin') || 
    (isset($_SERVER['ARTISAN_TICKETIT_INSTALLING']) && $_SERVER['ARTISAN_TICKETIT_INSTALLING'])) {
    
    Route::get('/tickets-install', [
        'middleware' => LaravelVersion::authMiddleware(),
        'as' => 'tickets.install.index',
        'uses' => 'Ticket\Ticketit\Controllers\InstallController@index',
    ]);

    Route::post('/tickets-install', [
        'middleware' => LaravelVersion::authMiddleware(),
        'as' => 'tickets.install.setup',
        'uses' => 'Ticket\Ticketit\Controllers\InstallController@setup',
    ]);

    Route::get('/tickets-upgrade', [
        'middleware' => LaravelVersion::authMiddleware(),
        'as' => 'tickets.install.upgrade',
        'uses' => 'Ticket\Ticketit\Controllers\InstallController@upgrade',
    ]);
}

// Debug Middleware
Route::aliasMiddleware('log.route', function ($request, $next, $routeName) {
    Log::info("Route accessed: {$routeName}", [
        'user' => auth()->guard('customer')->check() ? 
            auth()->guard('customer')->user()->only(['id', 'name', 'email']) : 'Guest',
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'ip' => $request->ip()
    ]);
    return $next($request);
});