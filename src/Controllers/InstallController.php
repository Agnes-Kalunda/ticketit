<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Ticket\Ticketit\Models\Agent;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Seeds\SettingsTableSeeder;
use Ticket\Ticketit\Seeds\TicketitTableSeeder;

class InstallController extends BaseTicketController
{
    public $migrations_tables = [];

    protected function getUserModel()
    {
        return config('ticketit.models.user', 'App\User');
    }

    protected function getCustomerModel()
    {
        return config('ticketit.models.customer', 'App\Customer');
    }

    public function __construct()
    {
        $this->middleware('auth:web');
        
        $migrations = File::files(dirname(dirname(__FILE__)).'/Migrations');
        foreach ($migrations as $migration) {
            $this->migrations_tables[] = basename($migration, '.php');
        }
    }

    public function publicAssets()
    {
        $public = $this->allFilesList(public_path('vendor/ticketit'));
        $assets = $this->allFilesList(base_path('vendor/ticket/ticketit/src/Public'));
        if ($public !== $assets) {
            Artisan::call('vendor:publish', [
                '--provider' => 'Ticket\Ticketit\TicketitServiceProvider',
                '--tag'     => ['public'],
            ]);
        }
    }

    public function index()
    {
        if (count($this->migrations_tables) == count($this->inactiveMigrations())
            || in_array('2015_10_08_123457_create_settings_table', $this->inactiveMigrations())
        ) {
            $views_files_list = $this->viewsFilesList(resource_path('views')) + 
                ['another' => trans('ticketit::install.another-file')];
            $inactive_migrations = $this->inactiveMigrations();

            // Get users list for admin selection using configured model
            $userModel = $this->getUserModel();
            $users_list = $userModel::pluck('name', 'id')->toArray();

            return view('ticketit::install.index', 
                compact('views_files_list', 'inactive_migrations', 'users_list'));
        }

        if ($this->isAdmin()) {
            $inactive_migrations = $this->inactiveMigrations();
            $inactive_settings = $this->inactiveSettings();

            return view('ticketit::install.upgrade', 
                compact('inactive_migrations', 'inactive_settings'));
        }

        throw new \Exception('Ticketit needs upgrade, admin should login and visit ticketit install route');
    }

    public function setup(Request $request)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $master = $request->master;
        if ($master == 'another') {
            $another_file = $request->other_path;
            $views_content = strstr(substr(strstr($another_file, 'views/'), 6), '.blade.php', true);
            $master = str_replace('/', '.', $views_content);
        }

        // Initialize settings with dual auth support
        $this->initialSettings($master);

        // Set up admin
        $admin_id = $request->admin_id;
        $userModel = $this->getUserModel();
        $admin = $userModel::find($admin_id);
        $admin->ticketit_admin = true;
        $admin->save();

        return redirect('/'.Setting::grab('main_route'));
    }

    public function upgrade()
    {
        if ($this->isAdmin()) {
            $this->initialSettings();
            return redirect('/'.Setting::grab('main_route'));
        }

        throw new \Exception('Ticketit upgrade path access: Only admin is allowed to upgrade');
    }

    public function initialSettings($master = false)
    {
        $inactive_migrations = $this->inactiveMigrations();
        
        if ($inactive_migrations) {
            // publish and run migrations
            Artisan::call('vendor:publish', [
                '--provider' => 'Ticket\Ticketit\TicketitServiceProvider',
                '--tag'     => ['db'],
            ]);
            Artisan::call('migrate');

            $this->settingsSeeder($master);

            if (in_array('2016_01_15_002617_add_htmlcontent_to_ticketit_and_comments', 
                $inactive_migrations)) {
                Artisan::call('ticketit:htmlify');
            }
        } elseif ($this->inactiveSettings()) {
            $this->settingsSeeder($master);
        }

        Cache::forget('ticketit::settings');
    }

    public function settingsSeeder($master = false)
    {
        $cli_path = 'config/ticketit.php';
        $provider_path = '../config/ticketit.php';
        $config_settings = [];
        $settings_file_path = false;

        if (File::isFile($cli_path)) {
            $settings_file_path = $cli_path;
        } elseif (File::isFile($provider_path)) {
            $settings_file_path = $provider_path;
        }

        if ($settings_file_path) {
            $config_settings = include $settings_file_path;
            File::move($settings_file_path, $settings_file_path.'.backup');
        }

        // dual auth settings
        $config_settings['guards'] = [
            'customer' => 'customer',
            'user' => 'web'
        ];

        $config_settings['models'] = [
            'customer' => $this->getCustomerModel(),
            'user' => $this->getUserModel()
        ];

        // customer ticket settings
        $config_settings['ticket'] = [
            'customer_can_create' => true,
            'agent_notify_customer' => true,
            'customer_notify_agent' => true
        ];

        if ($master) {
            $config_settings['master_template'] = $master;
        }

        $seeder = new SettingsTableSeeder();
        $seeder->config = $config_settings;
        $seeder->run();
    }

    public function viewsFilesList($dir_path)
    {
        $dir_files = File::files($dir_path);
        $files = [];
        foreach ($dir_files as $file) {
            $path = basename($file);
            $name = strstr(basename($file), '.', true);
            $files[$name] = $path;
        }
        return $files;
    }

    public function allFilesList($dir_path)
    {
        $files = [];
        if (File::exists($dir_path)) {
            $dir_files = File::allFiles($dir_path);
            foreach ($dir_files as $file) {
                $path = basename($file);
                $name = strstr(basename($file), '.', true);
                $files[$name] = $path;
            }
        }
        return $files;
    }

    public function inactiveMigrations()
    {
        $inactiveMigrations = [];
        $migration_arr = [];

        $tables = $this->migrations_tables;
        $migrations = DB::select('select * from '.DB::getTablePrefix().'migrations');

        foreach ($migrations as $migration_parent) {
            $migration_arr[] = $migration_parent->migration;
        }

        foreach ($tables as $table) {
            if (!in_array($table, $migration_arr)) {
                $inactiveMigrations[] = $table;
            }
        }

        return $inactiveMigrations;
    }

    public function inactiveSettings()
    {
        $seeder = new SettingsTableSeeder();
        $installed_settings = DB::table('ticketit_settings')->pluck('value', 'slug');

        if (!is_array($installed_settings)) {
            $installed_settings = $installed_settings->toArray();
        }

        $default_Settings = $seeder->getDefaults();

        if (count($installed_settings) == count($default_Settings)) {
            return false;
        }

        return array_diff_key($default_Settings, $installed_settings);
    }

    public function demoDataSeeder()
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $seeder = new TicketitTableSeeder();
        $seeder->run();
        session()->flash('status', 'Demo tickets, users, and agents are seeded!');

        return redirect()->route(Setting::grab('main_route') . '.index');
    }
}