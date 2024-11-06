<?php

namespace Ticket\Ticketit;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
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
use Ticket\Ticketit\Console\Commands\TicketDebugCommand;
use Ticket\Ticketit\Console\Commands\SeedTicketit;
class TicketitServiceProvider extends ServiceProvider
{
    protected $commands = [
        SeedTicketit::class,
        TicketDebugCommand::class  
    ];

    /**
     * Register the application services.
     */
    public function register()
    {
        // Register package config
        $this->mergeConfigFrom(
            __DIR__.'/Config/ticketit.php', 'ticketit'
        );

        // Register Dependencies
        $this->registerDependencies();

        // Register Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedTicketit::class,
                TicketDebugCommand::class
            ]);
        }

        // Register Form Macros
        $this->registerFormMacros();
    }

    /**
     * Bootstrap the application services.
     */
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

            // Register Middleware
            $this->registerMiddleware();

            // Register Validation Rules
            $this->registerValidationRules();

            // Publish Assets
            $this->publishAssets($viewsDirectory);

            if (!$this->checkDatabase()) {
                Log::warning('Ticketit tables not found, handling installation routes');
                $this->handleInstallationRoutes();
                return;
            }

            $this->setupPackage();

        } catch (\Exception $e) {
            Log::error('TicketitServiceProvider boot error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleInstallationRoutes();
        }
    }

    protected function registerDependencies()
    {
        $this->app->register(\Collective\Html\HtmlServiceProvider::class);
        $this->app->register(\Jenssegers\Date\DateServiceProvider::class);
        $this->app->register(\Mews\Purifier\PurifierServiceProvider::class);

        // Register Aliases
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Form', \Collective\Html\FormFacade::class);
    }

    protected function registerFormMacros()
    {
        Form::macro('custom', function ($type, $name, $value = '#000000', $options = []) {
            return Form::input($type, $name, $value, array_merge(['class' => 'form-control'], $options));
        });
    }

    protected function registerMiddleware()
    {
        $router = $this->app['router'];
        
        // Debug middleware
        $router->aliasMiddleware('ticketit.debug', function(Request $request, \Closure $next) {
            Log::info('Ticketit Request:', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'input' => $request->all(),
                'headers' => $request->headers->all(),
                'session' => $request->hasSession() ? [
                    'has_token' => $request->session()->has('_token'),
                    'token' => $request->session()->token(),
                ] : 'no session',
                'auth' => [
                    'customer_check' => Auth::guard('customer')->check(),
                    'customer_id' => Auth::guard('customer')->id(),
                    'web_check' => Auth::guard('web')->check(),
                    'web_id' => Auth::guard('web')->id()
                ]
            ]);
            return $next($request);
        });

        // Register other middleware
        $router->aliasMiddleware('ticketit.customer', 
            \Ticket\Ticketit\Middleware\CustomerAuthMiddleware::class);
        
        $router->aliasMiddleware('ticketit.staff', 
            \Ticket\Ticketit\Middleware\StaffAuthMiddleware::class);
        
        $router->aliasMiddleware('ticketit.admin', 
            \Ticket\Ticketit\Middleware\AdminAuthMiddleware::class);
        
        $router->aliasMiddleware('ticketit.agent', 
            \Ticket\Ticketit\Middleware\AgentAuthMiddleware::class);
    }



    protected function checkDatabase()
    {
        return Schema::hasTable('migrations') && Schema::hasTable('ticketit_settings');
    }

    protected function setupPackage()
    {
        $this->setupDatabaseConnection();
        $this->setupEventListeners();
        $this->loadRoutes();
        $this->setupViewComposers();
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

    protected function setupEventListeners()
    {
        Comment::creating(function ($comment) {
            if (Setting::grab('comment_notification')) {
                $notification = new NotificationsController();
                $notification->newComment($comment);
            }
        });

        Ticket::updating(function ($modified_ticket) {
            $this->handleTicketUpdate($modified_ticket);
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

    protected function handleTicketUpdate($modified_ticket)
    {
        $original_ticket = Ticket::find($modified_ticket->id);
        
        if (Setting::grab('status_notification')) {
            if ($original_ticket->status_id != $modified_ticket->status_id || 
                $original_ticket->completed_at != $modified_ticket->completed_at) {
                $notification = new NotificationsController();
                $notification->ticketStatusUpdated($modified_ticket, $original_ticket);
            }
        }
        
        if (Setting::grab('assigned_notification')) {
            if ($original_ticket->agent_id != $modified_ticket->agent_id) {
                $notification = new NotificationsController();
                $notification->ticketAgentUpdated($modified_ticket, $original_ticket);
            }
        }
    }

    protected function loadRoutes()
    {
        try {
            Log::info('About to register Ticketit routes', [
                'existing_routes' => Route::getRoutes()->count()
            ]);

            $settings = $this->getRouteSettings();

            // Customer Routes with debug middleware
            Route::group([
                'middleware' => ['web', 'ticketit.customer', 'ticketit.debug'],
                'prefix' => 'customer/tickets',
                'as' => 'customer.tickets.',
                'namespace' => 'Ticket\Ticketit\Controllers'
            ], function () {
                Route::get('/', 'TicketsController@index')->name('index');
                Route::get('/create', 'TicketsController@create')->name('create');
                
                // Store route with extra debugging
                Route::post('/', function(Request $request) {
                    Log::info('Store route hit:', [
                        'request' => [
                            'method' => $request->method(),
                            'data' => $request->all(),
                            'headers' => $request->headers->all(),
                            'ip' => $request->ip(),
                            'ajax' => $request->ajax(),
                        ],
                        'auth' => [
                            'guard' => Auth::guard('customer')->getName(),
                            'check' => Auth::guard('customer')->check(),
                            'id' => Auth::guard('customer')->id()
                        ]
                    ]);
                    
                    return app()->call('Ticket\Ticketit\Controllers\TicketsController@store');
                })->name('store');
                
                Route::get('/{ticket}', 'TicketsController@show')->name('show');
            });

           

            Log::info('Ticketit routes registered successfully', [
                'total_routes' => Route::getRoutes()->count(),
                'routes' => collect(Route::getRoutes())->map(function($route) {
                    return [
                        'uri' => $route->uri(),
                        'name' => $route->getName(),
                        'methods' => $route->methods()
                    ];
                })->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Error registering routes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    protected function getRouteSettings()
    {
        return [
            'main_route' => Setting::grab('main_route') ?: 'tickets',
            'main_route_path' => Setting::grab('main_route_path') ?: 'tickets',
            'admin_route' => Setting::grab('admin_route') ?: 'tickets-admin',
            'admin_route_path' => Setting::grab('admin_route_path') ?: 'tickets-admin'
        ];
    }

    protected function setupViewComposers()
    {
        view()->composer('ticketit::*', function ($view) {
            try {
                
                $debug = [
                    'route' => Route::currentRouteName(), 
                    'middleware' => request()->route() ? request()->route()->middleware() : [],
                    'guard' => Auth::guard('customer')->getName(),
                    'authenticated' => Auth::guard('customer')->check(),
                    'user_id' => Auth::guard('customer')->id(),
                    'timestamp' => now()->toDateTimeString(),
                    'request_method' => request()->method(),
                    'request_path' => request()->path()
                ];
                
                Log::info('View composer debug info:', $debug);
                
                $view->with('debug', $debug);
                
                // Add settings
                $settings = Cache::remember('ticketit_settings', 60, function () {
                    return Setting::all();
                });
                $view->with('setting', $settings);

            } catch (\Exception $e) {
                Log::error('Error in view composer:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Provide fallback debug info
                $view->with('debug', [
                    'error' => 'Debug info unavailable',
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                $view->with('setting', collect([]));
            }
        });
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