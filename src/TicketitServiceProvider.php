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
    /**
     * Package specific publish groups
     */
    protected $ticketitPublishGroups = [];

    /**
     * Console commands
     */
    protected $commands = [
        SeedTicketit::class,
        TicketDebugCommand::class
    ];

    /**
     * Register the application services.
     */
    public function register()
    {
        // Register package config first
        $this->mergeConfigFrom(
            __DIR__.'/Config/ticketit.php', 'ticketit'
        );

        // Register Dependencies
        $this->registerDependencies();

        // Register Commands - Make sure they're registered even if tables don't exist
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
            // Initialize publish groups
            $this->setupPublishGroups();
            
            // Load core components
            $this->loadCoreComponents();

            // Register middleware and validation
            $this->registerMiddleware();
            $this->registerValidationRules();

            // Handle database setup
            if (!$this->checkDatabase()) {
                $this->handleInstallationRoutes();
                return;
            }

            // Setup full package
            $this->setupPackage();

        } catch (\Exception $e) {
            Log::error('TicketitServiceProvider boot error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleInstallationRoutes();
        }
    }

    protected function setupPublishGroups()
    {
        $viewsDirectory = __DIR__.'/Views/bootstrap3';

        $this->ticketitPublishGroups = [
            'ticketit-config' => [
                __DIR__.'/Config/ticketit.php' => config_path('ticketit.php'),
            ],
            'ticketit-migrations' => [
                __DIR__.'/Migrations' => database_path('migrations'),
            ],
            'ticketit-views' => [
                $viewsDirectory => resource_path('views/vendor/ticketit'),
            ],
            'ticketit-lang' => [
                __DIR__.'/Translations' => resource_path('lang/vendor/ticketit'),
            ],
            'ticketit-public' => [
                __DIR__.'/Public' => public_path('vendor/ticketit'),
            ],
        ];

        Log::info('Publishing groups set up', [
            'groups' => array_keys($this->ticketitPublishGroups)
        ]);

        // Publish each group individually
        foreach ($this->ticketitPublishGroups as $tag => $paths) {
            $this->publishes($paths, $tag);
        }

        // Publish all assets together
        $allPaths = [];
        foreach ($this->ticketitPublishGroups as $paths) {
            $allPaths = array_merge($allPaths, $paths);
        }
        $this->publishes($allPaths, 'ticketit-assets');
    }

    protected function loadCoreComponents()
    {
        $viewsDirectory = __DIR__.'/Views/bootstrap3';

        Log::info('Loading core components', [
            'views_path' => $viewsDirectory,
            'translations_path' => __DIR__.'/Translations',
            'migrations_path' => __DIR__.'/Migrations'
        ]);

        $this->loadViewsFrom($viewsDirectory, 'ticketit');
        $this->loadTranslationsFrom(__DIR__.'/Translations', 'ticketit');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
    }

    protected function registerDependencies()
    {
        $this->app->register(\Collective\Html\HtmlServiceProvider::class);
        $this->app->register(\Jenssegers\Date\DateServiceProvider::class);
        $this->app->register(\Mews\Purifier\PurifierServiceProvider::class);

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
        try {
            $router = $this->app['router'];
            
            // Debug middleware
            $router->aliasMiddleware('ticketit.debug', function(Request $request, \Closure $next) {
                Log::info('Ticketit Request:', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'input' => $request->all(),
                    'auth' => [
                        'customer_check' => Auth::guard('customer')->check(),
                        'customer_id' => Auth::guard('customer')->id(),
                        'web_check' => Auth::guard('web')->check(),
                        'web_id' => Auth::guard('web')->id()
                    ]
                ]);
                return $next($request);
            });

            // Register core middleware
            $router->aliasMiddleware('ticketit.customer', 
                \Ticket\Ticketit\Middleware\CustomerAuthMiddleware::class);
            
            $router->aliasMiddleware('ticketit.staff', 
                \Ticket\Ticketit\Middleware\StaffAuthMiddleware::class);
            
            $router->aliasMiddleware('ticketit.admin', 
                \Ticket\Ticketit\Middleware\AdminAuthMiddleware::class);
            
            $router->aliasMiddleware('ticketit.agent', 
                \Ticket\Ticketit\Middleware\AgentAuthMiddleware::class);

        } catch (\Exception $e) {
            Log::error('Error registering middleware: ' . $e->getMessage());
        }
    }

    protected function checkDatabase()
    {
        try {
            if (!Schema::hasTable('migrations')) {
                Log::info('Migrations table not found');
                return false;
            }

            if (!Schema::hasTable('ticketit_settings')) {
                Log::info('Ticketit settings table not found');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error checking database: ' . $e->getMessage());
            return false;
        }
    }

    protected function setupPackage()
    {
        try {
            $this->setupDatabaseConnection();
            $this->setupEventListeners();
            $this->loadRoutes();
            $this->setupViewComposers();

            Log::info('Package setup completed successfully');
        } catch (\Exception $e) {
            Log::error('Error setting up package: ' . $e->getMessage());
        }
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
        try {
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
        } catch (\Exception $e) {
            Log::error('Error handling ticket update: ' . $e->getMessage());
        }
    }

    protected function loadRoutes()
    {
        try {
            $settings = $this->getRouteSettings();

            // Customer Routes
            Route::group([
                'middleware' => ['web', 'ticketit.customer', 'ticketit.debug'],
                'prefix' => 'customer/tickets',
                'as' => 'customer.tickets.',
                'namespace' => 'Ticket\Ticketit\Controllers'
            ], function () {
                Route::get('/', 'TicketsController@index')->name('index');
                Route::get('/create', 'TicketsController@create')->name('create');
                Route::post('/', 'TicketsController@store')->name('store');
                Route::get('/{ticket}', 'TicketsController@show')->name('show');
            });

            // Staff Routes
            Route::group([
                'middleware' => ['web', 'ticketit.staff'],
                'prefix' => 'staff/tickets',
                'as' => 'staff.tickets.',
                'namespace' => 'Ticket\Ticketit\Controllers'
            ], function () {
                Route::get('/', 'TicketsController@staffIndex')->name('index');
                Route::get('/{ticket}', 'TicketsController@staffShow')->name('show');
                Route::post('/{ticket}/status', 'TicketsController@updateStatus')
                    ->name('status.update');
            });

            // Admin Routes
            Route::group([
                'middleware' => ['web', 'ticketit.admin'],
                'prefix' => $settings['admin_route_path'],
                'as' => 'admin.',
                'namespace' => 'Ticket\Ticketit\Controllers'
            ], function () {
                Route::resource('status', 'StatusesController');
                Route::resource('priority', 'PrioritiesController');
                Route::resource('category', 'CategoriesController');
            });

            Log::info('Routes registered successfully');
        } catch (\Exception $e) {
            Log::error('Error loading routes: ' . $e->getMessage());
        }
    }

    protected function setupViewComposers()
    {
        try {
            view()->composer('ticketit::*', function ($view) {
                try {
                    $settings = Cache::remember('ticketit_settings', 60, function () {
                        return Setting::all();
                    });

                    $debug = [
                        'route' => Route::currentRouteName(),
                        'middleware' => request()->route() ? request()->route()->middleware() : [],
                        'guard' => Auth::guard('customer')->getName(),
                        'authenticated' => Auth::guard('customer')->check(),
                        'user_id' => Auth::guard('customer')->id(),
                        'timestamp' => now()->toDateTimeString()
                    ];

                    Log::info('View composer debug info:', $debug);
                    
                    $view->with('debug', $debug);
                    $view->with('setting', $settings);

                } catch (\Exception $e) {
                    Log::error('Error in view composer: ' . $e->getMessage());
                    $view->with('setting', collect([]));
                }
            });
        } catch (\Exception $e) {
            Log::error('Error setting up view composers: ' . $e->getMessage());
        }
    }

    protected function publishAssets($viewsDirectory)
    {
        try {
            Log::info('Publishing assets', [
                'source_directory' => $viewsDirectory,
                'publish_groups' => array_keys($this->ticketitPublishGroups)
            ]);

            // Publish each group
            foreach ($this->ticketitPublishGroups as $tag => $paths) {
                $this->publishes($paths, $tag);
                
                // Verify published paths
                foreach ($paths as $source => $destination) {
                    if (!file_exists($destination)) {
                        Log::warning("Destination path does not exist after publishing: {$destination}");
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error publishing assets: ' . $e->getMessage());
        }
    }

    protected function registerValidationRules()
    {
        try {
            $this->app['validator']->extend('exists_ticket', function ($attribute, $value, $parameters) {
                return DB::table($parameters[0])->where('id', $value)->exists();
            });
        } catch (\Exception $e) {
            Log::error('Error registering validation rules: ' . $e->getMessage());
        }
    }

    protected function getRouteSettings()
    {
        try {
            return [
                'main_route' => Setting::grab('main_route') ?: 'tickets',
                'main_route_path' => Setting::grab('main_route_path') ?: 'tickets',
                'admin_route' => Setting::grab('admin_route') ?: 'tickets-admin',
                'admin_route_path' => Setting::grab('admin_route_path') ?: 'tickets-admin'
            ];
        } catch (\Exception $e) {
            Log::error('Error getting route settings: ' . $e->getMessage());
            return [
                'main_route' => 'tickets',
                'main_route_path' => 'tickets',
                'admin_route' => 'tickets-admin',
                'admin_route_path' => 'tickets-admin'
            ];
        }
    }

    protected function handleInstallationRoutes()
    {
        try {
            Route::group([
                'middleware' => 'web',
                'namespace' => 'Ticket\Ticketit\Controllers'
            ], function () {
                Route::get('/tickets-install', [
                    'as' => 'tickets.install.index',
                    'uses' => 'InstallController@index'
                ]);

                Route::post('/tickets-install', [
                    'as' => 'tickets.install.setup',
                    'uses' => 'InstallController@setup'
                ]);

                Route::get('/tickets-upgrade', [
                    'as' => 'tickets.install.upgrade',
                    'uses' => 'InstallController@upgrade'
                ]);

                Route::get('/tickets', function () {
                    return redirect()->route('tickets.install.index');
                });
            });

            Log::info('Installation routes registered successfully');
            
        } catch (\Exception $e) {
            Log::error('Error setting up installation routes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}