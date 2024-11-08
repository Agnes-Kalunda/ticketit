<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Request;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Helpers\LaravelVersion;
use Illuminate\Support\Facades\Log;

try {
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

    /*
    |--------------------------------------------------------------------------
    | Customer Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'middleware' => ['web', 'auth:customer'],
        'prefix' => 'customer/tickets',
        'as' => 'customer.tickets.',
        'namespace' => 'Ticket\Ticketit\Controllers'
    ], function () {
        // Basic Ticket Operations
        Route::get('/', 'TicketsController@index')->name('index');
        Route::get('/create', 'TicketsController@create')->name('create');
        Route::post('/', 'TicketsController@store')->name('store');
        Route::get('/{id}', 'TicketsController@customerShow')->name('show')
            ->middleware('Ticket\Ticketit\Middleware\ResAccessMiddleware');
        Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');
        Route::post('/{id}/reply', 'TicketsController@customerReply')
            ->name('reply');

        // Customer Comments
        Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');
        Route::put('/comments/{comment}', 'CommentsController@update')->name('comments.update');
        Route::delete('/comments/{comment}', 'CommentsController@destroy')->name('comments.delete');

        // Customer Profile & Settings
        Route::get('/profile', 'CustomersController@profile')->name('profile');
        Route::put('/profile', 'CustomersController@updateProfile')->name('profile.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Staff & Agent Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'middleware' => ['web', 'auth:web'],
        'prefix' => 'staff/tickets',
        'as' => 'staff.tickets.',
        'namespace' => 'Ticket\Ticketit\Controllers'
    ], function () use ($main_route) {
        // Basic Staff Routes
        Route::middleware('Ticket\Ticketit\Middleware\StaffAuthMiddleware')->group(function() {
            Route::get('/', 'TicketsController@staffIndex')->name('index');
            Route::get('/dashboard', 'DashboardController@staffDashboard')->name('dashboard');
            Route::get('/{id}', 'TicketsController@staffShow')->name('show');
            Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');
        });

            // Admin only routes
        Route::middleware('Ticket\Ticketit\Middleware\AdminAuthMiddleware')->group(function() {
            Route::post('/{id}/assign', 'TicketsController@assignTicket')->name('admin.assign');
            Route::delete('/{id}', 'TicketsController@destroy')->name('admin.destroy');
        });


        // Agent Routes
        Route::middleware([
            'Ticket\Ticketit\Middleware\AgentAuthMiddleware',
            'Ticket\Ticketit\Middleware\ResAccessMiddleware'
        ])->group(function() {
            // Ticket Management
            Route::get('/{id}/view', 'TicketsController@agentShow')->name('agent.show');
            Route::post('/{id}/status', 'TicketsController@updateStatus')->name('status.update');
            Route::post('/{id}/assign', 'TicketsController@assignTicket')->name('admin.assign');
            Route::post('/{id}/complete', 'TicketsController@complete')->name('agent.complete');
            Route::post('/{id}/reopen', 'TicketsController@reopen')->name('agent.reopen');

            // Comments
            Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');
            Route::put('/comments/{comment}', 'CommentsController@update')->name('comments.update');
            Route::delete('/comments/{comment}', 'CommentsController@destroy')->name('comments.delete');

            // Agent Tools
            Route::get('/tools/categories', 'CategoriesController@agentIndex')->name('agent.categories');
            Route::get('/tools/priorities', 'PrioritiesController@agentIndex')->name('agent.priorities');
            Route::get('/tools/statuses', 'StatusesController@agentIndex')->name('agent.statuses');
        });

        // Admin Only Routes
        Route::middleware('Ticket\Ticketit\Middleware\AdminAuthMiddleware')->group(function() {
            Route::post('/{id}/assign-agent', 'TicketsController@assignTicket')->name('admin.assign');
            Route::post('/{id}/transfer', 'TicketsController@transferTicket')->name('admin.transfer');
            Route::delete('/{id}', 'TicketsController@destroy')->name('admin.destroy');
        });

        // Legacy/Compatibility Routes
        Route::middleware('Ticket\Ticketit\Middleware\StaffAuthMiddleware')->group(function() use ($main_route) {
            Route::get('/complete', 'TicketsController@indexComplete')->name("$main_route.complete");
            Route::get('/data/{id?}', 'TicketsController@data')->name("$main_route.data");
            Route::get('/agents/list/{category_id?}/{ticket_id?}', 'TicketsController@agentSelectList')
                ->name('agentselectlist');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Configuration Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'middleware' => ['web', 'auth:web', 'Ticket\Ticketit\Middleware\AdminAuthMiddleware'],
        'prefix' => $admin_route_path,
        'as' => "$admin_route.",
        'namespace' => 'Ticket\Ticketit\Controllers'
    ], function () {
        // Dashboard & Reports
        Route::get('/', 'DashboardController@index')->name('dashboard');
        Route::get('/indicator/{period?}', 'DashboardController@indicator')->name('dashboard.indicator');
        Route::get('/reports', 'ReportsController@index')->name('reports.index');
        Route::get('/reports/generate', 'ReportsController@generate')->name('reports.generate');
        Route::get('/reports/export', 'ReportsController@export')->name('reports.export');

        // Settings & Configuration
        Route::get('/settings', 'SettingsController@index')->name('settings.index');
        Route::post('/settings', 'SettingsController@update')->name('settings.update');
        Route::get('/settings/email', 'SettingsController@emailConfig')->name('settings.email');
        Route::post('/settings/email', 'SettingsController@updateEmailConfig')->name('settings.email.update');

        // User Management
        Route::resource('agents', 'AgentsController');
        Route::resource('administrators', 'AdministratorsController');
        Route::get('/users/inactive', 'UsersController@inactive')->name('users.inactive');
        Route::post('/users/{id}/activate', 'UsersController@activate')->name('users.activate');
        Route::post('/users/{id}/deactivate', 'UsersController@deactivate')->name('users.deactivate');

        // Ticket Configuration
        Route::resource('statuses', 'StatusesController');
        Route::resource('priorities', 'PrioritiesController');
        Route::resource('categories', 'CategoriesController');

        // Audit & Logs
        Route::get('/audits', 'AuditsController@index')->name('audits.index');
        Route::get('/activity-logs', 'ActivityLogsController@index')->name('activity-logs.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Installation Routes
    |--------------------------------------------------------------------------
    */
    if (Request::is('tickets-install') || 
        Request::is('tickets-upgrade') || 
        Request::is('tickets') || 
        Request::is('tickets-admin')) {
        
        Route::middleware(['web'])->group(function() {
            Route::get('/tickets-install', [
                'as' => 'tickets.install.index',
                'uses' => 'Ticket\Ticketit\Controllers\InstallController@index',
            ]);

            Route::post('/tickets-install', [
                'as' => 'tickets.install.setup',
                'uses' => 'Ticket\Ticketit\Controllers\InstallController@setup',
            ]);

            Route::get('/tickets-upgrade', [
                'as' => 'tickets.install.upgrade',
                'uses' => 'Ticket\Ticketit\Controllers\InstallController@upgrade',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'middleware' => ['auth:api'],
        'prefix' => 'api/tickets',
        'as' => 'api.tickets.',
        'namespace' => 'Ticket\Ticketit\Controllers\Api'
    ], function () {
        Route::get('/', 'TicketsController@index');
        Route::post('/', 'TicketsController@store');
        Route::get('/{id}', 'TicketsController@show');
        Route::put('/{id}', 'TicketsController@update');
        Route::delete('/{id}', 'TicketsController@destroy');
    });

    Log::info('All ticket routes registered successfully');

} catch (\Exception $e) {
    Log::error('Error registering ticket routes', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}