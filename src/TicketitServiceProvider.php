<?php

namespace Ticket\Ticketit;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Collective\Html\FormFacade as Form;
use Ticket\Ticketit\Console\Htmlify;
use Ticket\Ticketit\Controllers\InstallController;
use Ticket\Ticketit\Controllers\NotificationsController;
use Ticket\Ticketit\Models\Comment;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Models\Ticket;
use Ticket\Ticketit\Models\Category;
use Ticket\Ticketit\Models\Priority;
use Ticket\Ticketit\Models\Status;
use Ticket\Ticketit\ViewComposers\TicketItComposer;

class TicketitServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Ticket\Ticketit\Console\Commands\SeedTicketit'
    ];

    /**
     * Simple data retrieval with optional file caching
     */
    protected function getData($key, $callback)
    {
        try {
            $data = $callback();
            return $data;
        } catch (\Exception $e) {
            Log::error("Error retrieving data for {$key}: " . $e->getMessage());
            return collect([]);
        }
    }

    public function register()
    {
        // Register package config
        $this->mergeConfigFrom(
            __DIR__.'/Config/ticketit.php', 'ticketit'
        );

        // Register Dependencies
        $this->app->register(\Collective\Html\HtmlServiceProvider::class);
        $this->app->register(\Jenssegers\Date\DateServiceProvider::class);
        $this->app->register(\Mews\Purifier\PurifierServiceProvider::class);

        // Register Commands
        $this->commands($this->commands);

        // Register Form Macros
        Form::macro('custom', function ($type, $name, $value = '#000000', $options = []) {
            return Form::input($type, $name, $value, array_merge(['class' => 'form-control'], $options));
        });

        // Register Aliases
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Form', \Collective\Html\FormFacade::class);
    }

    public function boot()
    {
        try {
            // Load Views
            $viewsDirectory = __DIR__.'/Views/bootstrap3';
            $this->loadViewsFrom($viewsDirectory, 'ticketit');
            
            // Load Translations
            $this->loadTranslationsFrom(__DIR__.'/Translations', 'ticketit');

            // Load Migrations
            $this->loadMigrationsFrom(__DIR__.'/Migrations');

            // Register Validation Rules
            $this->registerValidationRules();

            // Publish Assets
            $this->publishAssets($viewsDirectory);

            if (!$this->checkDatabase()) {
                return;
            }

            $this->setupPackage();

        } catch (\Exception $e) {
            Log::error('TicketitServiceProvider boot error: ' . $e->getMessage());
            $this->handleInstallationRoutes();
        }
    }

    protected function checkDatabase()
    {
        return Schema::hasTable('migrations') && Schema::hasTable('ticketit_settings');
    }

    protected function setupPackage()
    {
        // Setup Database Connection
        $this->setupDatabaseConnection();

        // Setup Event Listeners
        $this->setupEventListeners();

        // Load Routes
        $this->loadRoutes();

        // Register View Composers
        view()->composer('*', function ($view) {
            $settings = $this->getData('settings', function () {
                return Setting::all();
            });
            $view->with('setting', $settings);
        });
    }

    protected function setupDatabaseConnection()
    {
        config(['database.connections.ticketit' => [
            'driver' => config('ticketit.database.connection', env('DB_CONNECTION', 'mysql')),
            'host' => config('ticketit.database.host', env('DB_HOST', '127.0.0.1')),
            'port' => config('ticketit.database.port', env('DB_PORT', '3306')),
            'database' => config('ticketit.database.database', env('DB_DATABASE', 'forge')),
            'username' => config('ticketit.database.username', env('DB_USERNAME', 'forge')),
            'password' => config('ticketit.database.password', env('DB_PASSWORD', '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]]);
    }

    protected function publishAssets($viewsDirectory)
    {
        $this->publishes([
            __DIR__.'/Config/ticketit.php' => config_path('ticketit.php'),
            __DIR__.'/Migrations' => database_path('migrations'),
            __DIR__.'/routes.php' => base_path('routes/ticketit.php'),
            $viewsDirectory => base_path('resources/views/vendor/ticketit'),
            __DIR__.'/Translations' => base_path('resources/lang/vendor/ticketit'),
            __DIR__.'/Public' => public_path('vendor/ticketit'),
        ], 'ticketit-assets');
    }

    protected function registerValidationRules()
    {
        $this->app['validator']->extend('exists_ticket', function ($attribute, $value, $parameters) {
            return DB::table($parameters[0])->where('id', $value)->exists();
        });
    }

    protected function setupEventListeners()
    {
        Comment::creating(function ($comment) {
            if (Setting::grab('comment_notification')) {
                $notification = new NotificationsController();
                $notification->newComment($comment);
            }
        });

        Ticket::updating(function ($modified_ticket) {
            if (Setting::grab('status_notification')) {
                $original_ticket = Ticket::find($modified_ticket->id);
                if ($original_ticket->status_id != $modified_ticket->status_id || 
                    $original_ticket->completed_at != $modified_ticket->completed_at) {
                    $notification = new NotificationsController();
                    $notification->ticketStatusUpdated($modified_ticket, $original_ticket);
                }
            }
            return true;
        });

        Ticket::created(function ($ticket) {
            if (Setting::grab('assigned_notification')) {
                $notification = new NotificationsController();
                $notification->newTicketNotifyAgent($ticket);
            }
            return true;
        });
    }

    protected function loadRoutes()
    {
        $settings = [
            'main_route' => Setting::grab('main_route') ?: 'tickets',
            'main_route_path' => Setting::grab('main_route_path') ?: 'tickets',
            'admin_route' => Setting::grab('admin_route') ?: 'tickets-admin',
            'admin_route_path' => Setting::grab('admin_route_path') ?: 'tickets-admin'
        ];

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

        // Admin Routes
        Route::group([
            'middleware' => ['web', 'auth', 'Ticket\Ticketit\Middleware\IsAdminMiddleware'],
            'prefix' => $settings['admin_route_path']
        ], function () {
            Route::resource('status', 'Ticket\Ticketit\Controllers\StatusesController');
            Route::resource('priority', 'Ticket\Ticketit\Controllers\PrioritiesController');
            Route::resource('category', 'Ticket\Ticketit\Controllers\CategoriesController');
        });
    }

    protected function handleInstallationRoutes()
    {
        Route::group(['middleware' => 'web'], function () {
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
}