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

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/ticketit.php', 'ticketit'
        );

        $this->registerDependencies();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedTicketit::class,
                TicketDebugCommand::class
            ]);
        }

        $this->registerFormMacros();
    }

    public function boot()
    {
        try {
            $viewsDirectory = __DIR__.'/Views/bootstrap3';
            $this->loadViewsFrom($viewsDirectory, 'ticketit');
            $this->loadTranslationsFrom(__DIR__.'/Translations', 'ticketit');
            $this->loadMigrationsFrom(__DIR__.'/Migrations');
            $this->registerMiddleware();
            $this->registerValidationRules();
            $this->publishAssets($viewsDirectory);
            $this->setupDatabase();

        } catch (\Exception $e) {
            Log::error('TicketitServiceProvider boot error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleInstallationRoutes();
        }
    }

    protected function setupDatabase()
    {
        try {
            if (!Schema::hasTable('migrations')) {
                Log::info('Migrations table not found. Need to run migrations.');
                return;
            }

            if (!Schema::hasTable('ticketit_settings')) {
                Log::info('Ticketit tables not found. Need to run package migrations.');
                $this->handleInstallationRoutes();
                return;
            }

            $this->setupPackage();

        } catch (\Exception $e) {
            Log::error('Database setup error: ' . $e->getMessage());
        }
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
        $router = $this->app['router'];

        $router->aliasMiddleware('ticketit.debug', function(Request $request, \Closure $next) {
            if (config('app.debug')) {
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
            }
            return $next($request);
        });

        $router->aliasMiddleware('ticketit.customer', 
            \Ticket\Ticketit\Middleware\CustomerAuthMiddleware::class);
        $router->aliasMiddleware('ticketit.staff', 
            \Ticket\Ticketit\Middleware\StaffAuthMiddleware::class);
        $router->aliasMiddleware('ticketit.admin', 
            \Ticket\Ticketit\Middleware\AdminAuthMiddleware::class);
        $router->aliasMiddleware('ticketit.agent', 
            \Ticket\Ticketit\Middleware\AgentAuthMiddleware::class);
    }

    protected function registerValidationRules()
    {
        $this->app['validator']->extend('exists_ticket', function ($attribute, $value, $parameters) {
            try {
                return DB::table($parameters[0])->where('id', $value)->exists();
            } catch (\Exception $e) {
                Log::error('Validation rule error: ' . $e->getMessage());
                return false;
            }
        });
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
        try {
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
        } catch (\Exception $e) {
            Log::error('Database connection setup error: ' . $e->getMessage());
        }
    }

    protected function setupEventListeners()
    {
        try {
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
                
                if (Setting::grab('assigned_notification')) {
                    $original_ticket = Ticket::find($modified_ticket->id);
                    if ($original_ticket->agent_id != $modified_ticket->agent_id) {
                        $notification = new NotificationsController();
                        $notification->ticketAgentUpdated($modified_ticket, $original_ticket);
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
        } catch (\Exception $e) {
            Log::error('Event listener setup error: ' . $e->getMessage());
        }
    }

    protected function loadRoutes()
    {
        try {
            $settings = $this->getRouteSettings();

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

            Route::group([
                'middleware' => ['web', 'ticketit.staff'],
                'prefix' => 'staff/tickets',
                'as' => 'staff.tickets.',
                'namespace' => 'Ticket\Ticketit\Controllers'
            ], function () {
                Route::get('/', 'TicketsController@staffIndex')->name('index');
                Route::get('/{ticket}', 'TicketsController@staffShow')->name('show');
                Route::post('/{ticket}/status', 'TicketsController@updateStatus')->name('status.update');
            });

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
            Log::error('Route registration error: ' . $e->getMessage());
        }
    }

    protected function setupViewComposers()
    {
        try {
            view()->composer('ticketit::*', function ($view) {
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
                
                $settings = Cache::remember('ticketit_settings', 60, function () {
                    return Setting::all();
                });
                
                $view->with('setting', $settings);
            });
        } catch (\Exception $e) {
            Log::error('View composer setup error: ' . $e->getMessage());
        }
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
}