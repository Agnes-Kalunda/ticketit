<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Ticket\Ticketit\Models\Setting;

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

// Customer Routes
Route::group([
    'middleware' => ['web', 'auth:customer'],
    'prefix' => 'customer/tickets'
], function () {
    Route::get('/', 'Ticket\Ticketit\Controllers\TicketsController@index')
        ->name('customer.tickets.index');
    Route::get('/create', 'Ticket\Ticketit\Controllers\TicketsController@create')
        ->name('customer.tickets.create');
    Route::post('/', 'Ticket\Ticketit\Controllers\TicketsController@store')
        ->name('customer.tickets.store');
    Route::get('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@show')
        ->name('customer.tickets.show');
});

// Staff Routes
Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => 'staff/tickets'
], function () {
    Route::get('/', 'Ticket\Ticketit\Controllers\TicketsController@staffIndex')
        ->name('staff.tickets.index');
    Route::get('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@staffShow')
        ->name('staff.tickets.show');
    Route::post('/{ticket}/status', 'Ticket\Ticketit\Controllers\TicketsController@updateStatus')
        ->name('staff.tickets.status.update');
});

// Staff/Admin Extended Routes
Route::group([
    'middleware' => [\Ticket\Ticketit\Helpers\LaravelVersion::authMiddleware()],
    'prefix' => $main_route_path
], function () use ($main_route, $main_route_path, $admin_route, $admin_route_path) {
    
    // Ticket Management Routes
    Route::get('/complete', 'Ticket\Ticketit\Controllers\TicketsController@indexComplete')
        ->name("$main_route-complete");
    Route::get('/data/{id?}', 'Ticket\Ticketit\Controllers\TicketsController@data')
        ->name("$main_route.data");

    // Basic Ticket Routes for Staff
    Route::get('/', 'Ticket\Ticketit\Controllers\TicketsController@index')
        ->name("$main_route.index");
    Route::get('/create', 'Ticket\Ticketit\Controllers\TicketsController@create')
        ->name("$main_route.create");
    Route::post('/', 'Ticket\Ticketit\Controllers\TicketsController@store')
        ->name("$main_route.store");
    Route::get('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@show')
        ->name("$main_route.show");
    Route::put('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@update')
        ->name("$main_route.update");
    Route::delete('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@destroy')
        ->name("$main_route.destroy");

    // Ticket Status Management
    Route::get('/{ticket}/complete', 'Ticket\Ticketit\Controllers\TicketsController@complete')
        ->name("$main_route.complete");
    Route::get('/{ticket}/reopen', 'Ticket\Ticketit\Controllers\TicketsController@reopen')
        ->name("$main_route.reopen");

    // Comments Routes
    Route::resource("$main_route_path-comment", 'Ticket\Ticketit\Controllers\CommentsController', [
        'names' => [
            'store' => "$main_route-comment.store",
            'update' => "$main_route-comment.update",
            'destroy' => "$main_route-comment.destroy",
        ]
    ])->only(['store', 'update', 'destroy']);

    // Agent Routes
    Route::group(['middleware' => 'Ticket\Ticketit\Middleware\IsAgentMiddleware'], function () use ($main_route, $main_route_path) {
        Route::get("/agents/list/{category_id?}/{ticket_id?}", [
            'as' => $main_route.'agentselectlist',
            'uses' => 'Ticket\Ticketit\Controllers\TicketsController@agentSelectList',
        ]);
    });

    // Admin Routes
    Route::group(['middleware' => 'Ticket\Ticketit\Middleware\IsAdminMiddleware', 'prefix' => $admin_route_path], 
    function () use ($admin_route) {
        // Dashboard
        Route::get('/', 'Ticket\Ticketit\Controllers\DashboardController@index');
        Route::get('/indicator/{indicator_period?}', 'Ticket\Ticketit\Controllers\DashboardController@index')
            ->name("$admin_route.dashboard.indicator");

        // Status Management
        Route::resource('status', 'Ticket\Ticketit\Controllers\StatusesController', [
            'as' => $admin_route
        ]);

        // Priority Management
        Route::resource('priority', 'Ticket\Ticketit\Controllers\PrioritiesController', [
            'as' => $admin_route
        ]);

        // Category Management
        Route::resource('category', 'Ticket\Ticketit\Controllers\CategoriesController', [
            'as' => $admin_route
        ]);

        // Agent Management
        Route::resource('agent', 'Ticket\Ticketit\Controllers\AgentsController', [
            'as' => $admin_route
        ]);

        // Configuration Management
        Route::resource('configuration', 'Ticket\Ticketit\Controllers\ConfigurationsController', [
            'as' => $admin_route
        ]);

        // Administrator Management
        Route::resource('administrator', 'Ticket\Ticketit\Controllers\AdministratorsController', [
            'as' => $admin_route
        ]);
    });
});