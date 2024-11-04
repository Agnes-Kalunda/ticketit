<?php

namespace Ticket\Ticketit\Seeds;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Ticket\Ticketit\Models\Status;
use Ticket\Ticketit\Models\Priority;
use Ticket\Ticketit\Models\Category;

class TicketitTableSeeder extends Seeder
{
    public $email_domain = '@example.com';
    public $agents_qty = 5;
    public $agents_per_category = 2;
    public $users_qty = 30;
    public $tickets_per_user_min = 1;
    public $tickets_per_user_max = 5;
    public $comments_per_ticket_min = 0;
    public $comments_per_ticket_max = 3;
    public $default_agent_password = 'demo';
    public $default_user_password = 'demo';
    public $tickets_date_period = 270;
    public $tickets_open = 20;
    public $tickets_min_close_period = 3;
    public $tickets_max_close_period = 5;
    public $default_closed_status_id = 2;

    // Category settings
    protected $categories = [
        'Technical'         => '#0014f4',
        'Billing'          => '#2b9900',
        'Customer Services' => '#7e0099',
    ];

    // Status settings
    protected $statuses = [
        'Open'        => '#f39c12',
        'In Progress' => '#3498db',
        'Closed'      => '#2ecc71',
    ];

    // Priority settings
    protected $priorities = [
        'Low'      => '#069900',
        'Medium'   => '#e1d200',
        'High'     => '#e10000',
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        Model::unguard();

        try {
            // Create statuses
            foreach ($this->statuses as $name => $color) {
                Status::updateOrCreate(
                    ['name' => $name],
                    [
                        'color' => $color,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            // Create priorities
            foreach ($this->priorities as $name => $color) {
                Priority::updateOrCreate(
                    ['name' => $name],
                    [
                        'color' => $color,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            // Create categories
            foreach ($this->categories as $name => $color) {
                Category::updateOrCreate(
                    ['name' => $name],
                    [
                        'color' => $color,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            // Optional: Create demo data only if specifically requested
            if (config('ticketit.seed_demo_data', false)) {
                $this->createDemoData();
            }

        } catch (\Exception $e) {
            if (isset($this->command)) {
                $this->command->error('Error seeding data: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Create demo data for testing
     */
    protected function createDemoData()
    {
        $faker = \Faker\Factory::create();
        
        // Get model classes from config
        $userModel = Config::get('ticketit.models.user');
        $customerModel = Config::get('ticketit.models.customer');

        // Create agents
        $agents = [];
        $agents_counter = 1;

        for ($a = 1; $a <= $this->agents_qty; $a++) {
            $agent = new $userModel();
            $agent->name = $faker->name;
            $agent->email = 'agent'.$agents_counter.$this->email_domain;
            $agent->ticketit_agent = 1;
            $agent->password = Hash::make($this->default_agent_password);
            $agent->save();
            
            $agents[$agent->id] = $agent;
            $agents_counter++;
        }

        // Get counts for relationships
        $categories = Category::all();
        $priorities = Priority::all();
        $statuses = Status::all();

        // Create customers and their tickets
        $customers_counter = 1;

        for ($u = 1; $u <= $this->users_qty; $u++) {
            // Create customer
            $customer = new $customerModel();
            $customer->name = $faker->name;
            $customer->email = 'customer'.$customers_counter.$this->email_domain;
            $customer->username = 'customer'.$customers_counter;
            $customer->password = Hash::make($this->default_user_password);
            $customer->save();
            $customers_counter++;

            // Create tickets for customer
            $tickets_qty = rand($this->tickets_per_user_min, $this->tickets_per_user_max);
            
            for ($t = 1; $t <= $tickets_qty; $t++) {
                $category = $categories->random();
                $priority = $priorities->random();
                $status = $statuses->random();

                // Get random agent from category
                $agent = $agents[array_rand($agents)];

                $created_date = Carbon::now()->subDays(rand(1, $this->tickets_date_period));
                $ticket = new \Ticket\Ticketit\Models\Ticket();
                $ticket->subject = $faker->sentence;
                $ticket->content = $faker->paragraphs(3, true);
                $ticket->status_id = $status->id;
                $ticket->priority_id = $priority->id;
                $ticket->customer_id = $customer->id;
                $ticket->agent_id = $agent->id;
                $ticket->category_id = $category->id;
                $ticket->created_at = $created_date;
                $ticket->updated_at = $created_date;

                // Randomly complete some tickets
                if (rand(0, 1)) {
                    $completed_date = (clone $created_date)->addDays(rand($this->tickets_min_close_period, $this->tickets_max_close_period));
                    if ($completed_date->lte(Carbon::now())) {
                        $ticket->completed_at = $completed_date;
                        $ticket->status_id = Status::where('name', 'Closed')->first()->id;
                    }
                }

                $ticket->save();

                // Create comments
                $comments_qty = rand($this->comments_per_ticket_min, $this->comments_per_ticket_max);
                $comment_date = clone $created_date;

                for ($c = 1; $c <= $comments_qty; $c++) {
                    $comment = new \Ticket\Ticketit\Models\Comment();
                    $comment->ticket_id = $ticket->id;
                    $comment->content = $faker->paragraph;
                    
                    // Alternate between customer and agent comments
                    if ($c % 2 == 0) {
                        $comment->customer_id = $customer->id;
                    } else {
                        $comment->user_id = $agent->id;
                    }

                    $comment_date = $comment_date->addHours(rand(1, 24));
                    if ($ticket->completed_at && $comment_date->gt($ticket->completed_at)) {
                        break;
                    }

                    $comment->created_at = $comment_date;
                    $comment->updated_at = $comment_date;
                    $comment->save();
                }
            }
        }
    }
}