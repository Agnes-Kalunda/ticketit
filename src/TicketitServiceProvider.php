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
use Illuminate\Support\Facades\File;
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
        // Register config first
        $this->mergeConfigFrom(
            __DIR__.'/Config/ticketit.php', 'ticketit'
        );
    
        // Register dependencies
        $this->registerDependencies();
        
    
       
        if ($this->app->runningInConsole()) {
            $this->commands([
                'Ticket\Ticketit\Console\Commands\SeedTicketit',
                'Ticket\Ticketit\Console\Commands\TicketDebugCommand'
            ]);
        }
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

    // Debug paths
    Log::info('Setting up publish paths', [
        'views' => $viewsDirectory,
        'migrations' => __DIR__.'/Migrations',
        'config' => __DIR__.'/Config',
        'translations' => __DIR__.'/Translations',
        'routes' => __DIR__.'/routes.php',
        'public' => __DIR__.'/Public'
    ]);

    // Register views with namespace
    $this->loadViewsFrom($viewsDirectory, 'ticketit');
    
    // Published views take precedence
    $publishedPath = resource_path('views/vendor/ticketit');
    if (file_exists($publishedPath)) {
        $this->loadViewsFrom($publishedPath, 'ticketit');
    }

    
    $publishGroups = [
        // Config publishing
        'ticketit-config' => [
            __DIR__.'/Config/ticketit.php' => config_path('ticketit.php'),
        ],

        // Migration publishing
        'ticketit-migrations' => [
            __DIR__.'/Migrations' => database_path('migrations')
        ],

        // View publishing
        'ticketit-views' => [
            $viewsDirectory => resource_path('views/vendor/ticketit'),
        ],

        // Translation publishing
        'ticketit-lang' => [
            __DIR__.'/Translations' => resource_path('lang/vendor/ticketit'),
        ],

        // Public assets publishing
        'ticketit-public' => [
            __DIR__.'/Public' => public_path('vendor/ticketit'),
        ],

        // Routes publishing
        'ticketit-routes' => [
            __DIR__.'/routes.php' => base_path('routes/ticketit.php')
        ],
    ];

    // Register each publish group
    foreach ($publishGroups as $group => $paths) {
        $this->publishes($paths, $group);
    }

    // Register all assets together
    $allPaths = [];
    foreach ($publishGroups as $paths) {
        $allPaths = array_merge($allPaths, $paths);
    }
    $this->publishes($allPaths, 'ticketit-assets');


    $this->loadMigrationsFrom(__DIR__.'/Migrations');
    $this->loadTranslationsFrom(__DIR__.'/Translations', 'ticketit');


    $routesPath = base_path('routes/ticketit.php');
    if (file_exists($routesPath)) {
        $this->loadRoutesFrom($routesPath);
    } else {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}


    protected function registerPublishCommand()
    {
        $this->publishes([
            __DIR__.'/Views/bootstrap3' => resource_path('views/vendor/ticketit'),
        ], 'ticketit-views');
    }

    protected function loadCoreComponents()
    {
        try {
            $viewsDirectory = __DIR__.'/Views/bootstrap3';

            Log::info('Loading views from directory', [
                'path' => $viewsDirectory,
                'exists' => file_exists($viewsDirectory),
                'contents' => array_diff(scandir($viewsDirectory), ['.', '..']),
                'full_path' => realpath($viewsDirectory)
            ]);

            // Register primary namespace
            $this->loadViewsFrom($viewsDirectory, 'ticketit');

            // Register published views
            $publishedPath = resource_path('views/vendor/ticketit');
            if (file_exists($publishedPath)) {
                $this->loadViewsFrom($publishedPath, 'ticketit');
                Log::info('Loading published views from', ['path' => $publishedPath]);
            }

            // Log all available views
            Log::info('Available views:', [
                'views' => collect(File::allFiles($viewsDirectory))
                    ->map(function($file) {
                        return $file->getRelativePathname();
                    })
                    ->toArray()
            ]);

            // debug info for view hints
            Log::info('View hints:', [
                'hints' => View::getFinder()->getHints()
            ]);

            $this->loadTranslationsFrom(__DIR__.'/Translations', 'ticketit');
            $this->loadMigrationsFrom(__DIR__.'/Migrations');

        } catch (\Exception $e) {
            Log::error('Error loading core components: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    protected function getViewContents($view)
    {
        try {
            $viewPath = View::getFinder()->find("ticketit::$view");
            Log::info("View path for $view:", ['path' => $viewPath]);
            
            if (file_exists($viewPath)) {
                $contents = file_get_contents($viewPath);
                Log::info("View contents for $view:", [
                    'length' => strlen($contents),
                    'preview' => substr($contents, 0, 100)
                ]);
                return $contents;
            }
            
            Log::warning("View file not found: $viewPath");
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error getting view contents for $view: " . $e->getMessage());
            return null;
        }
    }
    protected function registerDependencies()
{
    
    $this->app->register(\Collective\Html\HtmlServiceProvider::class);
    $this->app->register(\Jenssegers\Date\DateServiceProvider::class);
    $this->app->register(\Mews\Purifier\PurifierServiceProvider::class);

    // Register facades
    $loader = \Illuminate\Foundation\AliasLoader::getInstance();
    $loader->alias('Form', \Collective\Html\FormFacade::class);
    $loader->alias('View', \Illuminate\Support\Facades\View::class);
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

        // Register comment-related middleware
        $router->aliasMiddleware('ticketit.comment.create', 
            \Ticket\Ticketit\Middleware\CommentCreateMiddleware::class);

        $router->aliasMiddleware('ticketit.comment.update', 
            \Ticket\Ticketit\Middleware\CommentUpdateMiddleware::class);

        $router->aliasMiddleware('ticketit.comment.delete', 
            \Ticket\Ticketit\Middleware\CommentDeleteMiddleware::class);

        // Comment permission middleware
        $router->aliasMiddleware('ticketit.can-comment', function(Request $request, \Closure $next) {
            $user = Auth::guard('web')->user();
            $customer = Auth::guard('customer')->user();
            $ticket = null;

            if ($request->route('ticket')) {
                $ticket = \Ticket\Ticketit\Models\Ticket::find($request->route('ticket'));
            }

            Log::info('Comment Permission Check:', [
                'url' => $request->fullUrl(),
                'ticket_id' => $ticket ? $ticket->id : null,
                'user' => $user ? [
                    'id' => $user->id,
                    'is_admin' => $user->ticketit_admin,
                    'is_agent' => $user->ticketit_agent
                ] : null,
                'customer' => $customer ? [
                    'id' => $customer->id
                ] : null
            ]);

            // Check permissions
            if (!$ticket) {
                return redirect()->back()->with('error', 'Ticket not found');
            }

            $canComment = false;

            if ($user) {
                // Admin can always comment
                if ($user->ticketit_admin) {
                    $canComment = true;
                }
                // Agent can comment on assigned tickets
                elseif ($user->ticketit_agent && $ticket->agent_id === $user->id) {
                    $canComment = true;
                }
            }
            elseif ($customer && $ticket->customer_id === $customer->id) {
                // Customer can comment on their own tickets
                $canComment = true;
            }

            if (!$canComment) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
                return redirect()->back()->with('error', 'You cannot comment on this ticket');
            }

            return $next($request);
        });

        // Comment ownership middleware for updates/deletes
        $router->aliasMiddleware('ticketit.comment-owner', function(Request $request, \Closure $next) {
            $user = Auth::guard('web')->user();
            $customer = Auth::guard('customer')->user();
            $comment = null;

            if ($request->route('comment')) {
                $comment = \Ticket\Ticketit\Models\Comment::find($request->route('comment'));
            }

            Log::info('Comment Ownership Check:', [
                'url' => $request->fullUrl(),
                'comment_id' => $comment ? $comment->id : null,
                'user' => $user ? [
                    'id' => $user->id,
                    'is_admin' => $user->ticketit_admin,
                    'is_agent' => $user->ticketit_agent
                ] : null,
                'customer' => $customer ? [
                    'id' => $customer->id
                ] : null
            ]);

            if (!$comment) {
                return redirect()->back()->with('error', 'Comment not found');
            }

            $canModify = false;

            if ($user) {
                // Admin can modify any comment
                if ($user->ticketit_admin) {
                    $canModify = true;
                }
                // Agent can modify their own comments
                elseif ($user->ticketit_agent && $comment->user_id === $user->id) {
                    $canModify = true;
                }
            }
            elseif ($customer && $comment->customer_id === $customer->id) {
                // Customer can modify their own comments
                $canModify = true;
            }

            if (!$canModify) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
                return redirect()->back()->with('error', 'You cannot modify this comment');
            }

            return $next($request);
        });

        Log::info('All middleware registered successfully');

    } catch (\Exception $e) {
        Log::error('Error registering middleware: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
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
            Log::info('Comment being created', [
                'ticket_id' => $comment->ticket_id,
                'user_id' => Auth::id()
            ]);
            return true;
        });
    
        Ticket::updating(function ($modified_ticket) {
            try {
                $original_ticket = Ticket::find($modified_ticket->id);
                Log::info('Ticket being updated', [
                    'ticket_id' => $modified_ticket->id,
                    'old_status' => $original_ticket->status_id,
                    'new_status' => $modified_ticket->status_id,
                    'user_id' => Auth::id()
                ]);
            } catch (\Exception $e) {
                Log::error('Error in ticket update event: ' . $e->getMessage());
            }
            return true;
        });
    
        Ticket::created(function ($ticket) {
            Log::info('New ticket created', [
                'ticket_id' => $ticket->id,
                'customer_id' => $ticket->customer_id,
                'agent_id' => $ticket->agent_id
            ]);
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

    protected function publishAssets()
{
    try {
        if ($this->app->runningInConsole()) {
            // Ensure directories exist
            $directories = [
                resource_path('views/vendor'),
                resource_path('views/vendor/ticketit'),
                resource_path('lang/vendor'),
                resource_path('lang/vendor/ticketit'),
                public_path('vendor/ticketit'),
                database_path('migrations'),
            ];

            foreach ($directories as $directory) {
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                    Log::info("Created directory: {$directory}");
                }
            }

            // Publish all assets
            $this->publishes([
                __DIR__.'/Views/bootstrap3' => resource_path('views/vendor/ticketit'),
                __DIR__.'/Translations' => resource_path('lang/vendor/ticketit'),
                __DIR__.'/Public' => public_path('vendor/ticketit'),
                __DIR__.'/Config/ticketit.php' => config_path('ticketit.php'),
                __DIR__.'/Migrations' => database_path('migrations'),
                __DIR__.'/routes.php' => base_path('routes/ticketit.php'),
            ], 'ticketit-assets');

            Log::info('Published all assets successfully');
        }
    } catch (\Exception $e) {
        Log::error('Error publishing assets: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
    }
}

    protected function copyDirectory($source, $destination)
    {
        try {
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $dir = opendir($source);
            while (($file = readdir($dir)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $sourcePath = $source . '/' . $file;
                $destinationPath = $destination . '/' . $file;

                if (is_dir($sourcePath)) {
                    $this->copyDirectory($sourcePath, $destinationPath);
                } else {
                    copy($sourcePath, $destinationPath);
                    Log::info('Copied file', [
                        'from' => $sourcePath,
                        'to' => $destinationPath
                    ]);
                }
            }
            closedir($dir);
        } catch (\Exception $e) {
            Log::error('Error copying directory: ' . $e->getMessage(), [
                'source' => $source,
                'destination' => $destination
            ]);
            throw $e;
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