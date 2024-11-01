# Ticketit - Laravel Support Ticket System

A Laravel support ticket system package with dual authentication support for both staff members (Users) and customers. This package is designed to handle support tickets between customers and staff members efficiently.

## Features

- Dual authentication system (Staff/Customers)
- Ticket management system
- Auto-assignment of agents
- Ticket categories and priorities
- Custom permission system
- Email notifications
- Statistics and reporting
- Configurable settings

## Requirements

- PHP 7.1.3 or higher
- Laravel 5.8 or higher
- MySQL 

## Installation

1. Install via Composer:
```bash
composer require ticket/ticketit
```

2. Register the service provider in `config/app.php`:
```php
'providers' => [
    // ...
    Ticket\Ticketit\TicketitServiceProvider::class,
];
```

3. Publish the configuration and assets:
```bash
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag="ticketit-config"
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag="ticketit"
php artisan migrate
```

4. Run the migrations:
```bash
php artisan migrate
```

## Configuration

### 1. Add HasTickets Trait

Add the `HasTickets` trait to both your User and Customer models:

```php
// app/User.php
use Ticket\Ticketit\Traits\HasTickets;

class User extends Authenticatable
{
    use HasTickets;
    // ...
}

// app/Customer.php
use Ticket\Ticketit\Traits\HasTickets;

class Customer extends Authenticatable
{
    use HasTickets;
    // ...
}
```

### 2. Configure Environment Variables

Add these to your `.env` file:
```env
TICKETIT_CUSTOMER_MODEL=App\Customer
TICKETIT_USER_MODEL=App\User
TICKETIT_CUSTOMER_GUARD=customer
TICKETIT_USER_GUARD=web
```

### 3. Update Config File

The `config/ticketit.php` file contains all settings:

```php
return [
    'models' => [
        'customer' => env('TICKETIT_CUSTOMER_MODEL', 'App\Customer'),
        'user' => env('TICKETIT_USER_MODEL', 'App\User'),
    ],

    'guards' => [
        'customer' => env('TICKETIT_CUSTOMER_GUARD', 'customer'),
        'user' => env('TICKETIT_USER_GUARD', 'web'),
    ],

    'ticket' => [
        'user_can_create' => false,
        'customer_can_create' => true,
        'agent_notify_customer' => true,
        'customer_notify_agent' => true,
    ],

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


