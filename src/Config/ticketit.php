<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication & Models
    |--------------------------------------------------------------------------
    */
    'models' => [
        'customer' => env('TICKETIT_CUSTOMER_MODEL', 'App\Customer'),
        'user' => env('TICKETIT_USER_MODEL', 'App\User'),
        'ticket' => env('TICKETIT_TICKET_MODEL', 'Ticket\Ticketit\Models\Ticket'),
        'comment' => env('TICKETIT_COMMENT_MODEL', 'Ticket\Ticketit\Models\Comment'),
    ],

    'guards' => [
        'customer' => env('TICKETIT_CUSTOMER_GUARD', 'customer'),
        'user' => env('TICKETIT_USER_GUARD', 'web'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connection' => env('TICKETIT_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
        'host' => env('TICKETIT_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('TICKETIT_DB_PORT', env('DB_PORT', '3306')),
        'database' => env('TICKETIT_DB_DATABASE', env('DB_DATABASE', 'forge')),
        'username' => env('TICKETIT_DB_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('TICKETIT_DB_PASSWORD', env('DB_PASSWORD', '')),
        'prefix' => env('TICKETIT_DB_PREFIX', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticketing System Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'status_id' => env('TICKETIT_DEFAULT_STATUS_ID', 1),
        'priority_id' => env('TICKETIT_DEFAULT_PRIORITY_ID', 1),
        'category_id' => env('TICKETIT_DEFAULT_CATEGORY_ID', 1),
        'close_status_id' => env('TICKETIT_DEFAULT_CLOSE_STATUS_ID', 3),
        'reopen_status_id' => env('TICKETIT_DEFAULT_REOPEN_STATUS_ID', 1),
        'agent_id' => env('TICKETIT_DEFAULT_AGENT_ID', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'main_route' => env('TICKETIT_MAIN_ROUTE', 'tickets'),
        'main_route_path' => env('TICKETIT_MAIN_ROUTE_PATH', 'tickets'),
        'admin_route' => env('TICKETIT_ADMIN_ROUTE', 'tickets-admin'),
        'admin_route_path' => env('TICKETIT_ADMIN_ROUTE_PATH', 'tickets-admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tickets Configuration
    |--------------------------------------------------------------------------
    */
    'ticket' => [
        'user_can_create' => false,  // staff/users cannot create tickets
        'customer_can_create' => true,  // customers can create tickets
        'agent_notify_customer' => true,  // notify customer when agent replies
        'customer_notify_agent' => true,  // notify agent when customer replies
        'attachments' => [
            'enabled' => true,
            'max_size' => 10240, // 10MB
            'mimes' => 'pdf,doc,docx,png,jpg,jpeg',
        ],
        'autoclose' => [
            'enabled' => true,
            'days' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'customer' => [
            'create_ticket' => true,
            'view_own_tickets' => true,
            'comment_own_tickets' => true,
            'edit_own_tickets' => false,
            'close_own_tickets' => true,
        ],
        'user' => [
            'view_all_tickets' => true,
            'manage_tickets' => true,
            'manage_settings' => true,
            'transfer_tickets' => true,
            'edit_ticket_status' => true,
        ],
        'admin' => [
            'manage_categories' => true,
            'manage_priorities' => true,
            'manage_statuses' => true,
            'manage_agents' => true,
            'manage_settings' => true,
            'delete_tickets' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'email' => [
            'enabled' => true,
            'template_directory' => 'ticketit::emails',
        ],
        'status' => [
            'enabled' => true,
            'notify_customer' => true,
            'notify_agent' => true,
        ],
        'comment' => [
            'enabled' => true,
            'notify_customer' => true,
            'notify_agent' => true,
        ],
        'assignment' => [
            'enabled' => true,
            'notify_customer' => true,
            'notify_agent' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | View Settings
    |--------------------------------------------------------------------------
    */
    'views' => [
        'theme' => 'bootstrap3', 
        'custom_templates' => false,
        'template_directory' => 'ticketit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 60, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        Ticket\Ticketit\TicketitServiceProvider::class,
    ],
];