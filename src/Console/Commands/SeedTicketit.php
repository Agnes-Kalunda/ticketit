<?php

namespace Ticket\Ticketit\Console\Commands;

use Illuminate\Console\Command;
use Ticket\Ticketit\Seeds\TicketitTableSeeder;
use Illuminate\Support\Facades\Schema;

class SeedTicketit extends Command
{
    protected $signature = 'ticketit:seed {--force : Force the operation to run in production}';
    protected $description = 'Seed Ticketit tables with default and demo data';

    public function handle()
    {
        if (!$this->option('force') && app()->environment('production')) {
            if (!$this->confirm('Do you wish to continue seeding in production?')) {
                $this->info('Command canceled.');
                return;
            }
        }

        // Check if tables exist
        if (!Schema::hasTable('ticketit_statuses') || 
            !Schema::hasTable('ticketit_priorities') || 
            !Schema::hasTable('ticketit_categories')) {
            
            $this->error('Ticketit tables not found. Please run migrations first.');
            return 1;
        }

        try {
            $this->info('Starting Ticketit seeder...');

            $seeder = new TicketitTableSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->info('Ticketit seeding completed successfully!');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('Error during seeding: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            
            return 1;
        }
    }
}