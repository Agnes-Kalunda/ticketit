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

try {
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
        // Ticket Management
        Route::get('/', 'TicketsController@index')->name('index');
        Route::get('/create', 'TicketsController@create')->name('create');
        Route::post('/', 'TicketsController@store')->name('store');
        Route::get('/{ticket}', 'TicketsController@show')->name('show');

        // Customer Comments
        Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');
        Route::put('/comments/{comment}', 'CommentsController@update')->name('comments.update');
        Route::delete('/comments/{comment}', 'CommentsController@destroy')->name('comments.delete');
    });

    /*
    |--------------------------------------------------------------------------
    | Staff Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'middleware' => ['web', 'auth:web'],
        'prefix' => 'staff/tickets',
        'as' => 'staff.tickets.',
        'namespace' => 'Ticket\Ticketit\Controllers'
    ], function () {
        // Base Staff Routes (accessible by all staff)
        Route::middleware('Ticket\Ticketit\Middleware\StaffAuthMiddleware')->group(function() {
            Route::get('/', 'TicketsController@staffIndex')->name('index');
            Route::get('/dashboard', 'DashboardController@staffDashboard')->name('dashboard');
            Route::get('/{id}', 'TicketsController@staffShow')->name('show');
        });

        // Agent Only Routes
        Route::middleware('Ticket\Ticketit\Middleware\AgentAuthMiddleware')->group(function() {
            // Ticket Management
            Route::get('/{id}/view', 'TicketsController@agentShow')->name('agent.show');
            Route::post('/{id}/status', 'TicketsController@updateStatus')->name('status.update');
            
            // Comments/Replies
            Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');
            Route::put('/comments/{comment}', 'CommentsController@update')->name('comments.update');
            Route::delete('/comments/{comment}', 'CommentsController@destroy')->name('comments.delete');

            // Agent Tools
            Route::get('/tools/categories', 'CategoriesController@index')->name('categories.index');
            Route::get('/tools/priorities', 'PrioritiesController@index')->name('priorities.index');
        });

        // Admin Only Routes
        Route::middleware('Ticket\Ticketit\Middleware\AdminAuthMiddleware')->group(function() {
            // Ticket Administration
            Route::post('/{id}/assign', 'TicketsController@assignTicket')->name('admin.assign');
            Route::delete('/{id}', 'TicketsController@destroy')->name('admin.destroy');

            // User Management
            Route::prefix('management')->group(function() {
                // Agents Management
                Route::get('/agents', 'AgentsController@index')->name('admin.agents.index');
                Route::post('/agents', 'AgentsController@store')->name('admin.agents.store');
                Route::put('/agents/{id}', 'AgentsController@update')->name('admin.agents.update');
                Route::delete('/agents/{id}', 'AgentsController@destroy')->name('admin.agents.destroy');

                // Categories Management
                Route::resource('categories', 'CategoriesController')->except(['show']);
                
                // Priorities Management
                Route::resource('priorities', 'PrioritiesController')->except(['show']);
                
                // Statuses Management
                Route::resource('statuses', 'StatusesController')->except(['show']);
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Configuration Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'middleware' => ['web', 'auth', 'Ticket\Ticketit\Middleware\AdminAuthMiddleware'],
        'prefix' => $admin_route_path,
        'as' => "$admin_route.",
        'namespace' => 'Ticket\Ticketit\Controllers'
    ], function () {
        // Dashboard
        Route::get('/', 'DashboardController@index')->name('dashboard');
        Route::get('/indicator/{period?}', 'DashboardController@indicator')->name('dashboard.indicator');

        // Settings
        Route::get('/settings', 'SettingsController@index')->name('settings.index');
        Route::post('/settings', 'SettingsController@update')->name('settings.update');

        // Reports
        Route::get('/reports', 'ReportsController@index')->name('reports.index');
        Route::get('/reports/generate', 'ReportsController@generate')->name('reports.generate');
        Route::get('/reports/export', 'ReportsController@export')->name('reports.export');
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

    // Log successful route registration
    Log::info('Ticket routes registered successfully');

} catch (\Exception $e) {
    Log::error('Error registering ticket routes', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Debug Middleware
Route::aliasMiddleware('ticketit.debug', function ($request, $next) {
    Log::info('Ticket Route accessed:', [
        'uri' => $request->getRequestUri(),
        'method' => $request->getMethod(),
        'user' => auth()->check() ? auth()->user()->only(['id', 'name', 'email']) : 'Guest',
        'customer' => auth()->guard('customer')->check() ? 
            auth()->guard('customer')->user()->only(['id', 'name', 'email']) : null
    ]);
    return $next($request);
});