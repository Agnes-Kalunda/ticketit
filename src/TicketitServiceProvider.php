<?php

namespace Ticket\Ticketit;

use Collective\Html\FormFacade as CollectiveForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Ticket\Ticketit\Console\Htmlify;
use Ticket\Ticketit\Controllers\InstallController;
use Ticket\Ticketit\Controllers\NotificationsController;
use Ticket\Ticketit\Helpers\LaravelVersion;
use Ticket\Ticketit\Models\Comment;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Models\Ticket;
use Ticket\Ticketit\ViewComposers\TicketItComposer;

class TicketitServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Ticket\Ticketit\Console\Commands\SeedTicketit'
    ];

    public function boot()
    {
        try {
            // Load Views
            $viewsDirectory = __DIR__.'/Views/bootstrap3';
            $this->loadViewsFrom($viewsDirectory, 'ticketit');
            
            // Load Translations
            $this->loadTranslationsFrom(__DIR__.'/Translations', 'ticketit');
            
            // Register Commands
            $this->commands($this->commands);

            // Register Custom Validation Rules
            $this->registerValidationRules();

            // Publish Assets
            $this->publishAssets($viewsDirectory);

            // Load Migrations
            $this->loadMigrationsFrom(__DIR__.'/Migrations');

            // Setup Package
            $this->setupPackage();

        } catch (\Exception $e) {
            Log::error('TicketitServiceProvider boot error: ' . $e->getMessage());
            $this->handleInstallationRoutes();
        }
    }

    protected function registerValidationRules()
    {
        $this->app['validator']->extend('exists_ticket', function ($attribute, $value, $parameters) {
            return DB::table($parameters[0])->where('id', $value)->exists();
        });

        $this->app['validator']->replacer('exists_ticket', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':table', $parameters[0], $message);
        });
    }

    protected function publishAssets($viewsDirectory)
    {
        // Config
        $this->publishes([
            __DIR__.'/Config/ticketit.php' => config_path('ticketit.php'),
        ], 'ticketit-config');

        // Migrations
        $this->publishes([
            __DIR__.'/Migrations' => database_path('migrations')
        ], 'ticketit-migrations');

        // Routes
        $this->publishes([
            __DIR__.'/routes.php' => base_path('routes/ticketit.php')
        ], 'ticketit-routes');

        // All Assets
        $this->publishes([
            $viewsDirectory => base_path('resources/views/vendor/ticketit'),
            __DIR__.'/Translations' => base_path('resources/lang/vendor/ticketit'),
            __DIR__.'/Public' => public_path('vendor/ticketit'),
            __DIR__.'/Config' => base_path('config'),
        ], 'ticketit-assets');
    }

    protected function setupPackage()
    {
        // Check if database is installed
        if (!Schema::hasTable('migrations')) {
            return;
        }

        // Check if settings table exists
        if (!Schema::hasTable('ticketit_settings')) {
            return;
        }

        $installer = new InstallController();

        // Check for inactive migrations or settings
        if (!empty($installer->inactiveMigrations()) || $installer->inactiveSettings()) {
            return;
        }

        // Setup Database Connection
        $this->setupDatabaseConnection();

        // Setup Forms
        $this->setupForms();

        // Setup View Composers
        $this->setupViewComposers();

        // Setup Event Listeners
        $this->setupEventListeners();

        // Load Routes
        $this->loadRoutes();
    }

    protected function setupDatabaseConnection()
    {
        $connection = [
            'driver' => config('ticketit.connection'),
            'host' => config('ticketit.host'),
            'port' => config('ticketit.port'),
            'database' => config('ticketit.database'),
            'username' => config('ticketit.username'),
            'password' => config('ticketit.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];

        config(['database.connections.ticketit' => $connection]);
    }

    protected function setupForms()
    {
        CollectiveForm::macro('custom', function ($type, $name, $value = '#000000', $options = []) {
            return CollectiveForm::input($type, $name, $value, array_merge(['class' => 'form-control'], $options));
        });
    }

    protected function setupViewComposers()
    {
        TicketItComposer::settings(null);
        TicketItComposer::general();
        TicketItComposer::codeMirror();
        TicketItComposer::sharedAssets();
        TicketItComposer::summerNotes();
    }

    protected function setupEventListeners()
    {
        // Comment Creation Notification
        Comment::creating(function ($comment) {
            if (Setting::grab('comment_notification')) {
                $notification = new NotificationsController();
                $notification->newComment($comment);
            }
        });

        // Ticket Status Update Notification
        Ticket::updating(function ($modified_ticket) {
            if (Setting::grab('status_notification')) {
                $original_ticket = Ticket::find($modified_ticket->id);
                if ($original_ticket->status_id != $modified_ticket->status_id || 
                    $original_ticket->completed_at != $modified_ticket->completed_at) {
                    $notification = new NotificationsController();
                    $notification->ticketStatusUpdated($modified_ticket, $original_ticket);
                }
            }

            if (Setting::grab('assigned_notification')) {
                $original_ticket = Ticket::find($modified_ticket->id);
                if ($original_ticket->agent->id != $modified_ticket->agent->id) {
                    $notification = new NotificationsController();
                    $notification->ticketAgentUpdated($modified_ticket, $original_ticket);
                }
            }
            return true;
        });

        // New Ticket Notification
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
        Route::middleware(['web', 'auth:customer'])
             ->prefix('customer/tickets')
             ->name('customer.')
             ->group(function () {
                Route::get('/', 'Ticket\Ticketit\Controllers\TicketsController@index')
                    ->name('tickets.index');
                Route::get('/create', 'Ticket\Ticketit\Controllers\TicketsController@create')
                    ->name('tickets.create');
                Route::post('/', 'Ticket\Ticketit\Controllers\TicketsController@store')
                    ->name('tickets.store');
                Route::get('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@show')
                    ->name('tickets.show');
             });

        // Staff Routes  
        Route::middleware(['web', 'auth'])
             ->prefix('staff/tickets')
             ->name('staff.')
             ->group(function () {
                Route::get('/', 'Ticket\Ticketit\Controllers\TicketsController@staffIndex')
                    ->name('tickets.index');
                Route::get('/{ticket}', 'Ticket\Ticketit\Controllers\TicketsController@staffShow')
                    ->name('tickets.show');
                Route::post('/{ticket}/status', 'Ticket\Ticketit\Controllers\TicketsController@updateStatus')
                    ->name('tickets.status.update');
             });

        // User Dashboard Routes
        Route::middleware(['web', 'auth'])
             ->group(function () {
                Route::get('/dashboard', 'User\DashboardController@index')
                    ->name('user.dashboard');
             });

        // Admin Routes
        Route::middleware(['web', 'auth', 'Ticket\Ticketit\Middleware\IsAdminMiddleware'])
             ->prefix($settings['admin_route_path'])
             ->name('admin.')
             ->group(function () {
                Route::get('/', 'Ticket\Ticketit\Controllers\DashboardController@index')
                    ->name('dashboard');
                Route::resource('status', 'Ticket\Ticketit\Controllers\StatusesController');
                Route::resource('priority', 'Ticket\Ticketit\Controllers\PrioritiesController');
                Route::resource('category', 'Ticket\Ticketit\Controllers\CategoriesController');
             });
    }

    protected function handleInstallationRoutes()
    {
        if (Request::path() == 'tickets-install'
            || Request::path() == 'tickets-upgrade'
            || Request::path() == 'tickets'
            || Request::path() == 'tickets-admin'
            || (isset($_SERVER['ARTISAN_TICKETIT_INSTALLING']) && $_SERVER['ARTISAN_TICKETIT_INSTALLING'])) {
            
            $this->publishes([
                __DIR__.'/Migrations' => base_path('database/migrations')
            ], 'db');

            $authMiddleware = LaravelVersion::authMiddleware();

            Route::get('/tickets-install', [
                'middleware' => $authMiddleware,
                'as' => 'tickets.install.index',
                'uses' => 'Ticket\Ticketit\Controllers\InstallController@index',
            ]);

            Route::post('/tickets-install', [
                'middleware' => $authMiddleware,
                'as' => 'tickets.install.setup',
                'uses' => 'Ticket\Ticketit\Controllers\InstallController@setup',
            ]);

            Route::get('/tickets-upgrade', [
                'middleware' => $authMiddleware,
                'as' => 'tickets.install.upgrade',
                'uses' => 'Ticket\Ticketit\Controllers\InstallController@upgrade',
            ]);

            Route::get('/tickets', function () {
                return redirect()->route('tickets.install.index');
            });

            Route::get('/tickets-admin', function () {
                return redirect()->route('tickets.install.index');
            });
        }
    }

    public function register()
    {
        // Register views
        $viewsPath = __DIR__.'/Views/bootstrap3';
        $this->loadViewsFrom($viewsPath, 'ticketit');

        // Register config
        $this->mergeConfigFrom(
            __DIR__.'/Config/ticketit.php', 'ticketit'
        );

        // Register Dependencies
        $this->registerDependencies();

        // Register Commands
        $this->registerCommands();
    }

    protected function registerDependencies()
    {
        // HTML/Form
        $this->app->register(\Collective\Html\HtmlServiceProvider::class);

        // DataTables
        if (LaravelVersion::min('5.4')) {
            $this->app->register(\Yajra\DataTables\DataTablesServiceProvider::class);
        } else {
            $this->app->register(\Yajra\Datatables\DatatablesServiceProvider::class);
        }

        // Other Dependencies
        $this->app->register(\Jenssegers\Date\DateServiceProvider::class);
        $this->app->register(\Mews\Purifier\PurifierServiceProvider::class);

        // Aliases
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('CollectiveForm', 'Collective\Html\FormFacade');
    }

    protected function registerCommands()
    {
        $this->app->singleton('command.ticket.ticketit.htmlify', function ($app) {
            return new Htmlify();
        });
        $this->commands('command.ticket.ticketit.htmlify');
    }
}