<?php

namespace Ticket\Ticketit\Seeds;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Ticket\Ticketit\Models\Status;
use Ticket\Ticketit\Models\Priority;
use Ticket\Ticketit\Models\Category;

class TicketitTableSeeder extends Seeder
{
    /**
     * Predefined settings for ticket system
     */

    // Category definition
    protected $categories = [
        'Technical' => [
            'name' => 'Technical',
            'color' => '#0014f4',
        ],
        'Billing' => [
            'name' => 'Billing',
            'color' => '#2b9900',
        ],
        'Customer Service' => [
            'name' => 'Customer Service',
            'color' => '#7e0099',
        ],
    ];

    // Status definition
    protected $statuses = [
        'Open' => [
            'name' => 'Open',
            'color' => '#f39c12',
        ],
        'In Progress' => [
            'name' => 'In Progress',
            'color' => '#3498db',
        ],
        'Closed' => [
            'name' => 'Closed',
            'color' => '#2ecc71',
        ],
    ];

    // Priority definitions
    protected $priorities = [
        'Low' => [
            'name' => 'Low',
            'color' => '#069900',
        ],
        'Medium' => [
            'name' => 'Medium',
            'color' => '#e1d200',
        ],
        'High' => [
            'name' => 'High',
            'color' => '#e10000',
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Allow mass assignment during seeding
        Model::unguard();

        try {
            // Seed Categories
            foreach ($this->categories as $category) {
                Category::firstOrCreate(
                    ['name' => $category['name']], 
                    [
                        'color' => $category['color'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            }

            // Seed Priorities
            foreach ($this->priorities as $priority) {
                Priority::firstOrCreate(
                    ['name' => $priority['name']], 
                    [
                        'color' => $priority['color'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            }

            // Seed Statuses
            foreach ($this->statuses as $status) {
                Status::firstOrCreate(
                    ['name' => $status['name']], 
                    [
                        'color' => $status['color'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            }

        } catch (\Exception $e) {
        
            throw new \Exception('Failed to seed Ticketit data: ' . $e->getMessage());
        } finally {
            // Re-enable mass assignment protection
            Model::reguard();
        }
    }
}