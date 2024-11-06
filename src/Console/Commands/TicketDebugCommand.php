<?php

namespace Ticket\Ticketit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use PDO;
use Ticket\Ticketit\Models\Setting;

class TicketDebugCommand extends Command
{
    protected $signature = 'ticket:debug';
    protected $description = 'Debug ticket package configuration and routes';

    public function handle()
    {
        $this->info('Starting Ticket Package Debug...');

        // Check Database Connection
        $this->info('Checking database configuration...');
        try {
            $connection = DB::connection();
            $database = Config::get('database.connections.mysql.database');
            $this->line('Database configured: ' . $database);

            // Test connection
            DB::select('SELECT 1');
            $this->line('Database connection: Success');

            
            $version = DB::select('SELECT version() as ver')[0]->ver;
            $this->line('Database version: ' . $version);
        } catch (\Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
        }

        // Check Routes
        $this->info('Checking routes...');
        $routes = collect(Route::getRoutes())->filter(function($route) {
            return Str::contains($route->uri(), 'customer/tickets');
        });

        foreach($routes as $route) {
            $this->line("Route: {$route->uri()} [{$route->methods()[0]}]");
            $this->line("Name: " . $route->getName());
            $this->line("Action: " . $route->getActionName());
            $this->line("Middleware: " . implode(', ', $route->middleware()));
            $this->line('---');
        }

        // Check Database Tables
        $this->info('Checking database tables...');
        $tables = [
            'ticketit',
            'ticketit_categories',
            'ticketit_priorities',
            'ticketit_statuses',
            'ticketit_settings'
        ];

        foreach($tables as $table) {
            $exists = Schema::hasTable($table);
            $this->line("Table {$table}: " . ($exists ? 'EXISTS' : 'MISSING'));
            
            if ($exists) {
                // Count records
                $count = DB::table($table)->count();
                $this->line("  Records: {$count}");
                
                // Show structure
                $columns = Schema::getColumnListing($table);
                $this->line("  Columns:");
                foreach($columns as $column) {
                    $type = Schema::getColumnType($table, $column);
                    $this->line("    - {$column} ({$type})");
                }
            }
        }

        // Check Settings
        $this->info('Checking settings...');
        try {
            $settings = Setting::all();
            $this->line("Found " . $settings->count() . " settings");

            foreach($settings as $setting) {
                $this->line("- {$setting->slug}: {$setting->value}");
            }
        } catch(\Exception $e) {
            $this->error("Error accessing settings: " . $e->getMessage());
        }

        // Log test entry
        $this->info('Testing logging...');
        try {
            Log::info('Ticket debug test log entry', [
                'timestamp' => now()->toDateTimeString(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => Config::get('database.connections.mysql.database')
            ]);
            $this->line('Log entry created successfully');
        } catch(\Exception $e) {
            $this->error("Error writing to log: " . $e->getMessage());
        }

        // Summary
        $this->info("\nDebug Summary:");
        $this->line("- Routes found: " . $routes->count());
        $this->line("- Tables checked: " . count($tables));
        $this->line("- Settings found: " . (isset($settings) ? $settings->count() : 'N/A'));
        $this->line("- Database status: " . (DB::select('SELECT 1') ? 'Connected' : 'Failed'));

        $this->info('Debug complete!');
    }
}