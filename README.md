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
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --force
```

4. Publish ticket Routes from the  package:
```bash
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag="ticketit-routes"
```

5. Run the migrations:
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

## Usage

### Customer Functions

```php
// Get customer tickets
$customer->tickets();
$customer->activeTickets();
$customer->completedTickets();

// Check permissions
$customer->canCreateTicket();
$customer->canViewTickets();

// Get statistics
$customer->getTicketStats();
```

### User/Staff Functions

```php
// Get user tickets
$user->tickets();
$user->activeTickets();
$user->agentTickets();

// Check roles/permissions
$user->isAgent();
$user->isAdmin();
$user->canManageTickets();

// Get statistics
$user->getTicketStats();
```

### Creating Tickets

For Customers:
```php
$ticket = new Ticket();
$ticket->subject = 'Issue Subject';
$ticket->content = 'Issue Description';
$ticket->priority_id = 1;
$ticket->category_id = 1;
$ticket->customer_id = auth()->guard('customer')->id();
$ticket->save();
```

For Users/Staff:
```php
$ticket = new Ticket();
$ticket->subject = 'Issue Subject';
$ticket->content = 'Issue Description';
$ticket->priority_id = 1;
$ticket->category_id = 1;
$ticket->user_id = auth()->id();
$ticket->save();
```

## Routes

The package provides separate route groups for customers and staff:

### Customer Routes
- `/customer/tickets` - List customer tickets
- `/customer/tickets/create` - Create new ticket
- `/customer/tickets/{id}` - View ticket

### Staff Routes
- `/tickets` - List all tickets
- `/tickets/create` - Create new ticket
- `/tickets/{id}` - View ticket
- `/tickets/{id}/edit` - Edit ticket
- `/tickets-admin` - Admin panel

## Customization

### Views
Publish and customize views:
```bash
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag="views"
```

## Events and Notifications

The package dispatches events for:
- Ticket creation
- Status changes
- Comments
- Agent assignment

Configure notification settings in the config file.

## Database Structure

Main tables:
- `ticketit` - Tickets
- `ticketit_comments` - Ticket comments
- `ticketit_categories` - Ticket categories
- `ticketit_priorities` - Ticket priorities
- `ticketit_statuses` - Ticket statuses


## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
