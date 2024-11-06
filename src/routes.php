<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Request;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Helpers\LaravelVersion;

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
    'prefix' => 'customer/tickets',
    'as' => 'customer.tickets.',
    'namespace' => 'Ticket\Ticketit\Controllers'
], function () {
    Route::get('/', 'TicketsController@index')->name('index');
    Route::get('/create', 'TicketsController@create')->name('create');
    Route::post('/', 'TicketsController@store')->name('store');
    Route::get('/{ticket}', 'TicketsController@show')->name('show');
});

// Staff Ticket Routes 
Route::group([
    'middleware' => ['web', 'auth:web'],
    'prefix' => 'staff/tickets',
    'as' => 'staff.tickets.',
    'namespace' => 'Ticket\Ticketit\Controllers'
], function () {
    Route::get('/', 'TicketsController@staffIndex')->name('index');
    Route::get('/{ticket}', 'TicketsController@staffShow')->name('show');
    Route::post('/{ticket}/status', 'TicketsController@updateStatus')->name('status.update');
});

// Staff/Admin Extended Routes
Route::group([
    'middleware' => ['web', 'auth'],
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
    Route::group(['middleware' => 'Ticket\Ticketit\Middleware\IsAgentMiddleware'], function () use ($main_route) {
        Route::get("/agents/list/{category_id?}/{ticket_id?}", [
            'as' => $main_route.'agentselectlist',
            'uses' => 'Ticket\Ticketit\Controllers\TicketsController@agentSelectList',
        ]);
    });
});

// Admin Routes
Route::group([
    'middleware' => ['web', 'auth', 'Ticket\Ticketit\Middleware\IsAdminMiddleware'],
    'prefix' => $admin_route_path,
    'as' => "$admin_route."
], function () {
    // Dashboard
    Route::get('/', 'Ticket\Ticketit\Controllers\DashboardController@index')
        ->name('dashboard');
    Route::get('/indicator/{indicator_period?}', 'Ticket\Ticketit\Controllers\DashboardController@index')
        ->name('dashboard.indicator');

    // User Management Routes
    Route::get('/users', 'Ticket\Ticketit\Controllers\UsersController@index')
        ->name('users.index');
    Route::get('/users/create', 'Ticket\Ticketit\Controllers\UsersController@create')
        ->name('users.create');
    Route::post('/users', 'Ticket\Ticketit\Controllers\UsersController@store')
        ->name('users.store');
    Route::get('/users/{user}/edit', 'Ticket\Ticketit\Controllers\UsersController@edit')
        ->name('users.edit');
    Route::put('/users/{user}', 'Ticket\Ticketit\Controllers\UsersController@update')
        ->name('users.update');
    Route::delete('/users/{user}', 'Ticket\Ticketit\Controllers\UsersController@destroy')
        ->name('users.destroy');

    // Status Management
    Route::resource('status', 'Ticket\Ticketit\Controllers\StatusesController');

    // Priority Management
    Route::resource('priority', 'Ticket\Ticketit\Controllers\PrioritiesController');

    // Category Management
    Route::resource('category', 'Ticket\Ticketit\Controllers\CategoriesController');

    // Agent Management
    Route::resource('agent', 'Ticket\Ticketit\Controllers\AgentsController');

    // Configuration Management
    Route::resource('configuration', 'Ticket\Ticketit\Controllers\ConfigurationsController');

    // Administrator Management
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