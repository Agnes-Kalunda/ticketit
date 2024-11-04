<?php

namespace Ticket\Ticketit\Seeds;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TicketitTableSeeder extends Seeder
{
    // Original demo settings
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

    // Original category settings
    protected $categories = [
        'Technical'         => '#0014f4',
        'Billing'          => '#2b9900',
        'Customer Services' => '#7e0099',
    ];

    // Original status settings
    protected $statuses = [
        'Open'        => '#f39c12',
        'In Progress' => '#3498db',
        'Closed'      => '#2ecc71',
    ];

    // Original priority settings
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
            $faker = \Faker\Factory::create();
            
            Log::info('Starting Ticketit seeder...');

            // Get model classes from config
            $userModel = Config::get('ticketit.models.user');
            $customerModel = Config::get('ticketit.models.customer');

            // Create agents (staff users)
            $agents = [];
            $agents_counter = 1;

            $this->command->info('Creating demo agents...');
            
            // Create demo agents (Staff Users)
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

            // Create statuses
            $this->command->info('Creating statuses...');
            foreach ($this->statuses as $name => $color) {
                \Ticket\Ticketit\Models\Status::firstOrCreate(
                    ['name' => $name],
                    [
                        'color' => $color,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            // Create categories 
            $this->command->info('Creating categories...');
            foreach ($this->categories as $name => $color) {
                $category = \Ticket\Ticketit\Models\Category::firstOrCreate(
                    ['name' => $name],
                    [
                        'color' => $color,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                
                // Assign agents to category 
                $agent = array_rand($agents, $this->agents_per_category);
                $category->agents()->attach($agent);
            }

            // Create priorities
            $this->command->info('Creating priorities...');
            foreach ($this->priorities as $name => $color) {
                \Ticket\Ticketit\Models\Priority::firstOrCreate(
                    ['name' => $name],
                    [
                        'color' => $color,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            // Get counts for relationships
            $categories_qty = \Ticket\Ticketit\Models\Category::count();
            $priorities_qty = \Ticket\Ticketit\Models\Priority::count();
            $statuses_qty = \Ticket\Ticketit\Models\Status::count();

            // Create customers and their tickets
            $this->command->info('Creating demo customers and tickets...');
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

                // Create tickets for customer (maintaining original ticket creation logic)
                $tickets_qty = rand($this->tickets_per_user_min, $this->tickets_per_user_max);
                
                for ($t = 1; $t <= $tickets_qty; $t++) {
                    $rand_category = rand(1, $categories_qty);
                    $priority_id = rand(1, $priorities_qty);
                    
                    do {
                        $rand_status = rand(1, $statuses_qty);
                    } while ($rand_status == $this->default_closed_status_id);

                    $category = \Ticket\Ticketit\Models\Category::find($rand_category);
                    if ($category) {
                        $agents = $category->agents()->pluck('name', 'id')->toArray();
                        $agent_id = array_rand($agents);

                        $random_create = rand(1, $this->tickets_date_period);
                        $random_complete = rand($this->tickets_min_close_period, 
                                            $this->tickets_max_close_period);

                        $ticket = new \Ticket\Ticketit\Models\Ticket();
                        $ticket->subject = $faker->text(50);
                        $ticket->content = $faker->paragraphs(3, true);
                        $ticket->html = nl2br($ticket->content);
                        $ticket->status_id = $rand_status;
                        $ticket->priority_id = $priority_id;
                        $ticket->customer_id = $customer->id;
                        $ticket->agent_id = $agent_id;
                        $ticket->category_id = $rand_category;
                        $ticket->created_at = Carbon::now()->subDays($random_create);
                        $ticket->updated_at = Carbon::now()->subDays($random_create);

                        $completed_at = new Carbon($ticket->created_at);
                        if (!$completed_at->addDays($random_complete)->gt(Carbon::now())) {
                            $ticket->completed_at = $completed_at;
                            $ticket->updated_at = $completed_at;
                            $ticket->status_id = $this->default_closed_status_id;
                        }
                        
                        $ticket->save();

                        // Create comments (maintaining original comment creation)
                        $comments_qty = rand($this->comments_per_ticket_min, 
                                        $this->comments_per_ticket_max);

                        for ($c = 1; $c <= $comments_qty; $c++) {
                            $comment = new \Ticket\Ticketit\Models\Comment();
                            $comment->ticket_id = $ticket->id;
                            $comment->content = $faker->paragraphs(3, true);
                            $comment->html = nl2br($comment->content);
                            
                            // Alternate between customer and agent comments
                            if ($c % 2 == 0) {
                                $comment->customer_id = $customer->id;
                            } else {
                                $comment->user_id = $agent_id;
                            }

                            if (is_null($ticket->completed_at)) {
                                $random_comment_date = $faker->dateTimeBetween(
                                    '-'.$random_create.' days', 'now');
                            } else {
                                $random_comment_date = $faker->dateTimeBetween(
                                    '-'.$random_create.' days', 
                                    '-'.($random_create - $random_complete).' days');
                            }

                            $comment->created_at = $random_comment_date;
                            $comment->updated_at = $random_comment_date;
                            $comment->save();
                        }

                        // Update ticket's last update based on latest comment
                        $last_comment = $ticket->comments->sortByDesc('created_at')->first();
                        if ($last_comment) {
                            $ticket->updated_at = $last_comment->created_at;
                            $ticket->save();
                        }
                    }
                }
            }

            $this->command->info('Seeding completed successfully!');

        } catch (\Exception $e) {
            Log::error('Error in Ticketit seeder: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}