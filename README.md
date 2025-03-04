# Ticketit Package Documentation

A Laravel support ticket package with dual authentication support for staff members (Users) and customers. This package enables efficient ticket management between customers and staff.

## Features

- Dual authentication system (Staff/Customers)
- Ticket management with priority levels
- Status tracking and management
- Role-based access control (Admin/Agent/Customer)
- Comment system
- Customizable views and routes

## Requirements

- PHP 7.1.3+
- Laravel 5.8+
- MySQL/MariaDB

## Installation

1. Add the package to your composer.json:
```json
{
    "require": {
        "ticket/ticketit": "dev-main"
    }
}
```

2. Install via Composer:
```bash
composer require ticket/ticketit
```

3. Register ServiceProvider in `config/app.php`:
```php
'providers' => [
    Ticket\Ticketit\TicketitServiceProvider::class,
],
```

4. Publish package assets:
```bash
# Publish config
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag=ticketit-config

# Publish migrations
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag=ticketit-migrations

# Publish views
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag=ticketit-views

# Publish routes
php artisan vendor:publish --provider="Ticket\Ticketit\TicketitServiceProvider" --tag=ticketit-routes
```

## Configuration

### Models Setup

Add to your User model:
```php
use Ticket\Ticketit\Traits\HasTickets;

class User extends Authenticatable
{
    use HasTickets;

    protected $fillable = [
        'name', 'email', 'password', 'ticketit_admin', 'ticketit_agent'
    ];

    protected $casts = [
        'ticketit_admin' => 'boolean',
        'ticketit_agent' => 'boolean'
    ];
}
```

Add to your Customer model:
```php
use Ticket\Ticketit\Traits\HasTickets;

class Customer extends Authenticatable
{
    use HasTickets;

    protected $guard = 'customer';

    protected $fillable = [
        'name', 'email', 'password', 'username'
    ];
}
```

### Routes

The package provides these route groups:

```php
// Customer Routes
   Route::group([
        'middleware' => ['web', 'auth:customer'],
        'prefix' => 'customer/tickets',
        'as' => 'customer.tickets.',
        'namespace' => 'Ticket\Ticketit\Controllers'
    ], function () {
        // Basic Ticket Operations
        Route::get('/', 'TicketsController@index')->name('index');
        Route::get('/create', 'TicketsController@create')->name('create');
        Route::post('/', 'TicketsController@store')->name('store');
        Route::get('/{id}', 'TicketsController@customerShow')->name('show')
            ->middleware('Ticket\Ticketit\Middleware\ResAccessMiddleware');
        Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');
        Route::post('/{id}/reply', 'TicketsController@customerReply')
            ->name('reply');
    });

// Staff Routes
  Route::group([
        'middleware' => ['web', 'auth:web'],
        'prefix' => 'staff/tickets',
        'as' => 'staff.tickets.',
        'namespace' => 'Ticket\Ticketit\Controllers'
    ], function () use ($main_route) {
        // Basic Staff Routes
        Route::middleware('Ticket\Ticketit\Middleware\StaffAuthMiddleware')->group(function() {
            Route::get('/', 'TicketsController@staffIndex')->name('index');
            Route::get('/dashboard', 'DashboardController@staffDashboard')->name('dashboard');
            Route::get('/{id}', 'TicketsController@staffShow')->name('show');
            Route::post('/{ticket}/comments', 'CommentsController@store')->name('comments.store');


     } });

     // Admin only routes
    Route::middleware('Ticket\Ticketit\Middleware\AdminAuthMiddleware')->group(function() {
            Route::post('/{id}/assign', 'TicketsController@assignTicket')->name('admin.assign');
           
        });


     //Agent routes

    Route::middleware([
            'Ticket\Ticketit\Middleware\AgentAuthMiddleware',
            'Ticket\Ticketit\Middleware\ResAccessMiddleware'
        ])->group(function() {
            // Ticket Management
            Route::get('/{id}/view', 'TicketsController@agentShow')->name('agent.show');
            Route::post('/{id}/status', 'TicketsController@updateStatus')->name('status.update');
            });


```

### Database Schema

The package creates these tables:
- ticketit (tickets)
- ticketit_comments (comments)
- ticketit_categories (categories)
- ticketit_priorities (priorities)
- ticketit_statuses (statuses)
- ticketit_settings (settings)

## Usage

### Creating Tickets (Customers)
```php
$ticket = new Ticket();
$ticket->subject = 'Issue Subject';
$ticket->content = 'Issue Description';
$ticket->category_id = 1;
$ticket->priority_id = 1;
$ticket->customer_id = auth()->guard('customer')->id();
$ticket->save();
```

### Managing Tickets (Staff)
```php
// Get tickets assigned to agent
$tickets = Ticket::where('agent_id', auth()->id())->get();

// Update ticket status
$ticket->status_id = $newStatusId;
$ticket->save();

// Add comment
$comment = new Comment();
$comment->content = 'Response content';
$comment->ticket_id = $ticket->id;
$comment->user_id = auth()->id();
$comment->save();
```

## License

The MIT License (MIT). See [License File](LICENSE.md).