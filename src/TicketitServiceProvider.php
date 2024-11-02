<?php

namespace Ticket\Ticketit;

use Collective\Html\FormFacade as CollectiveForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
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
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    
        $viewsDirectory = __DIR__.'/Views/bootstrap3';
        $this->loadViewsFrom($viewsDirectory, 'ticketit');
        $this->loadTranslationsFrom(__DIR__.'/Translations', 'ticketit');

        // Register validation msgs
        $this->app['validator']->extend('exists_ticket', function ($attribute, $value, $parameters) {
            return DB::table($parameters[0])->where('id', $value)->exists();
        });

        // custom validation messages
        $this->app['validator']->replacer('exists_ticket', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':table', $parameters[0], $message);
        });
    
        // Publish configurations
        $this->publishes([
            __DIR__.'/Config/ticketit.php' => config_path('ticketit.php'),
        ], 'ticketit-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/Migrations' => database_path('migrations')
        ], 'ticketit-migrations');

        // Publish routes
        $this->publishes([
            __DIR__.'/routes.php' => base_path('routes/ticketit/routes.php')
        ], 'ticketit-routes');

        // Publish all assets
        $this->publishes([
            $viewsDirectory => base_path('resources/views/vendor/ticketit'),
            __DIR__.'/Translations' => base_path('resources/lang/vendor/ticketit'),
            __DIR__.'/Public' => public_path('vendor/ticketit'),
            __DIR__.'/Config' => base_path('config'),
        ], 'ticketit-assets');

        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        if (!Schema::hasTable('migrations')) {
            // Database isn't installed yet.
            return;
        }

        try{
            if(!Schema::hasTable('ticketit_settings')){
                return;
            }
            
            $installer = new InstallController();

            // if a migration or new setting is missing scape to the installation
            if (empty($installer->inactiveMigrations()) && !$installer->inactiveSettings()) {
                // Configure database connection
                $this->setupDatabaseConnection();

                // Send the Agent User model to the view under $u
                // Send settings to views under $setting
                //cache $u
                $u = null;

                TicketItComposer::settings($u);

                // Adding HTML5 color picker to form elements
                CollectiveForm::macro('custom', function ($type, $name, $value = '#000000', $options = []) {
                    return CollectiveForm::input($type, $name, $value, array_merge(['class' => 'form-control'], $options));
                });

                TicketItComposer::general();
                TicketItComposer::codeMirror();
                TicketItComposer::sharedAssets();
                TicketItComposer::summerNotes();

                // Send notification when new comment is added
                Comment::creating(function ($comment) {
                    if (Setting::grab('comment_notification')) {
                        $notification = new NotificationsController();
                        $notification->newComment($comment);
                    }
                });

                // Send notification when ticket status is modified
                Ticket::updating(function ($modified_ticket) {
                    if (Setting::grab('status_notification')) {
                        $original_ticket = Ticket::find($modified_ticket->id);
                        if ($original_ticket->status_id != $modified_ticket->status_id || $original_ticket->completed_at != $modified_ticket->completed_at) {
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

                // Send notification when ticket is created
                Ticket::created(function ($ticket) {
                    if (Setting::grab('assigned_notification')) {
                        $notification = new NotificationsController();
                        $notification->newTicketNotifyAgent($ticket);
                    }
                    return true;
                });

                $main_route = Setting::grab('main_route');
                $main_route_path = Setting::grab('main_route_path');
                $admin_route = Setting::grab('admin_route');
                $admin_route_path = Setting::grab('admin_route_path');

                if (file_exists(Setting::grab('routes'))) {
                    include Setting::grab('routes');
                } else {
                    include __DIR__.'/routes.php';
                }
            }

        } catch(\Exception $e){
            // Handle installation routes
            if (Request::path() == 'tickets-install'
                    || Request::path() == 'tickets-upgrade'
                    || Request::path() == 'tickets'
                    || Request::path() == 'tickets-admin'
                    || (isset($_SERVER['ARTISAN_TICKETIT_INSTALLING']) && $_SERVER['ARTISAN_TICKETIT_INSTALLING'])) {
                
                $this->publishes([__DIR__.'/Migrations' => base_path('database/migrations')], 'db');

                $authMiddleware = LaravelVersion::authMiddleware();

                Route::get('/tickets-install', [
                    'middleware' => $authMiddleware,
                    'as'         => 'tickets.install.index',
                    'uses'       => 'Ticket\Ticketit\Controllers\InstallController@index',
                ]);

                Route::post('/tickets-install', [
                    'middleware' => $authMiddleware,
                    'as'         => 'tickets.install.setup',
                    'uses'       => 'Ticket\Ticketit\Controllers\InstallController@setup',
                ]);

                Route::get('/tickets-upgrade', [
                    'middleware' => $authMiddleware,
                    'as'         => 'tickets.install.upgrade',
                    'uses'       => 'Ticket\Ticketit\Controllers\InstallController@upgrade',
                ]);

                Route::get('/tickets', function () {
                    return redirect()->route('tickets.install.index');
                });

                Route::get('/tickets-admin', function () {
                    return redirect()->route('tickets.install.index');
                });
            }
            return;
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Register views again to ensure availability
        $viewsPath = __DIR__.'/Views/bootstrap3';
        $this->loadViewsFrom($viewsPath, 'ticketit');

        // Register the config
        $this->mergeConfigFrom(
            __DIR__.'/Config/ticketit.php', 'ticketit'
        );

        /*
         * Register the service provider for the dependency.
         */
        $this->app->register(\Collective\Html\HtmlServiceProvider::class);

        if (LaravelVersion::min('5.4')) {
            $this->app->register(\Yajra\DataTables\DataTablesServiceProvider::class);
        } else {
            $this->app->register(\Yajra\Datatables\DatatablesServiceProvider::class);
        }

        $this->app->register(\Jenssegers\Date\DateServiceProvider::class);
        $this->app->register(\Mews\Purifier\PurifierServiceProvider::class);

        /*
         * Create aliases for the dependency.
         */
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('CollectiveForm', 'Collective\Html\FormFacade');

        /*
         * Register htmlify command. Need to run this when upgrading from <=0.2.2
         */
        $this->app->singleton('command.ticket.ticketit.htmlify', function ($app) {
            return new Htmlify();
        });
        $this->commands('command.ticket.ticketit.htmlify');
    }

    /**
     * Setup the database connection for ticketit
     */
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
}