
<?php

return [
    // models config
    'models' => [
        'customer' => env('TICKETIT_CUSTOMER_MODEL', 'App\Customer'),
        'user' => env('TICKETIT_USER_MODEL', 'App\User'),
    ],

    // Authentication guards
    'guards' => [
        'customer' => env('TICKETIT_CUSTOMER_GUARD', 'customer'),
        'user' => env('TICKETIT_USER_GUARD', 'web'),
    ],

    // Default IDs
    'defaults' => [
        'status_id' => env('TICKETIT_DEFAULT_STATUS_ID', 1),
        'priority_id' => env('TICKETIT_DEFAULT_PRIORITY_ID', 1),
        'category_id' => env('TICKETIT_DEFAULT_CATEGORY_ID', 1),
    ],

    // Database configuration
    'connection' => env('TICKETIT_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
    'host' => env('TICKETIT_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('TICKETIT_DB_PORT', env('DB_PORT', '3306')),
    'database' => env('TICKETIT_DB_DATABASE', env('DB_DATABASE', 'forge')),
    'username' => env('TICKETIT_DB_USERNAME', env('DB_USERNAME', 'forge')),
    'password' => env('TICKETIT_DB_PASSWORD', env('DB_PASSWORD', '')),


     // ticket configuration
     'ticket' => [
        'user_can_create' => false,  // allow staff/users to create tickets
        'customer_can_create' => true,  // allow customers to create tickets
        'agent_notify_customer' => true,  // notify customer when agent replies
        'customer_notify_agent' => true,  // notify agent when customer replies
    ],

     // access permissions
     'permissions' => [
        'customer' => [
            'create_ticket' => true,
            'view_own_tickets' => true,
            'comment_own_tickets' => true,
        ],
        'user' => [
            'view_all_tickets' => true,
            'manage_tickets' => true,
            'manage_settings' => true,
        ],
    ],

    

   
];