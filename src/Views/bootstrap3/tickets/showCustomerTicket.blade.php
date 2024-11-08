```php
@extends('layouts.app')

@section('content')
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('customer.dashboard') }}">Support Ticket System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('customer.tickets.index') }}">
                        <i class="bi bi-ticket"></i> My Tickets
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('customer.tickets.create') }}">
                        <i class="bi bi-plus-circle"></i> New Ticket
                    </a>
                </li>
            </ul>
            <span class="navbar-text">
                <i class="bi bi-person"></i> {{ Auth::guard('customer')->user()->name }}
            </span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Ticket Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-ticket"></i>
                        Ticket #{{ $ticket->id }}
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="badge rounded-pill" style="background-color: {{ $ticket->status->color }}">
                            <i class="bi bi-circle-fill me-1"></i>{{ $ticket->status->name }}
                        </span>
                        <span class="badge rounded-pill" style="background-color: {{ $ticket->priority->color }}">
                            <i class="bi bi-flag-fill me-1"></i>{{ $ticket->priority->name }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-primary mb-3">{{ $ticket->subject }}</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="bi bi-folder me-2"></i><strong>Category:</strong> {{ $ticket->category->name }}</p>
                            <p><i class="bi bi-calendar me-2"></i><strong>Created:</strong> {{ $ticket->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="bi bi-clock me-2"></i><strong>Last Updated:</strong> {{ $ticket->updated_at->diffForHumans() }}</p>
                            @if($ticket->agent)
                                <p><i class="bi bi-person-badge me-2"></i><strong>Support Agent:</strong> {{ $ticket->agent->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rest of the template remains the same, just replace 'fas fa-' with 'bi bi-' -->
            <!-- For example: 'fas fa-reply' becomes 'bi bi-reply' -->
            
            <!-- Original Ticket Content -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-primary">
                                <span class="initials">{{ substr($ticket->customer->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ $ticket->customer->name }}</h6>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $ticket->created_at->format('M d, Y h:i A') }}
                                </small>
                            </div>
                            <div class="ticket-content mt-3 p-3 bg-light rounded">
                                {!! nl2br(e($ticket->content)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments/Replies Section -->
            @if($ticket->comments->count() > 0)
                <div class="comments-section mb-4">
                    @foreach($ticket->comments as $comment)
                        @php
                            $isStaffComment = $comment->user && property_exists($comment->user, 'ticketit_admin') && 
                                            ($comment->user->ticketit_admin || $comment->user->ticketit_agent);
                        @endphp
                        <div class="card mb-3 {{ $isStaffComment ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-circle {{ $isStaffComment ? 'bg-primary' : 'bg-secondary' }}">
                                            <span class="initials">
                                                {{ $isStaffComment ? substr($comment->user->name, 0, 1) : substr($ticket->customer->name, 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                @if($isStaffComment)
                                                    {{ $comment->user->name }}
                                                    <span class="badge bg-primary ms-2">Support Staff</span>
                                                @else
                                                    {{ $ticket->customer->name }}
                                                @endif
                                            </h6>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $comment->created_at->format('M d, Y h:i A') }}
                                            </small>
                                        </div>
                                        <div class="comment-content mt-3 p-3 bg-light rounded">
                                            {!! nl2br(e($comment->content)) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Reply Form -->
            @if($ticket->status->name !== 'Closed' && $can_reply)
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-reply me-2"></i>Add Reply</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.tickets.comments.store', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea name="content" 
                                          rows="4" 
                                          class="form-control @error('content') is-invalid @enderror"
                                          placeholder="Type your reply here..."
                                          required>{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-info d-flex align-items-center">
                    <i class="bi bi-info-circle me-2"></i>
                    @if($ticket->status->name === 'Closed')
                        This ticket is closed. Please create a new ticket if you need further assistance.
                    @else
                        You cannot reply to this ticket at this time.
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>

.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.avatar-circle .initials {
    color: white;
    font-size: 16px;
    font-weight: 500;
}

.ticket-content, .comment-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    border-radius: 8px;
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

.comments-section {
    max-height: 600px;
    overflow-y: auto;
    scrollbar-width: thin;
}

.border-primary {
    border-left: 4px solid #0d6efd !important;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.card-header {
    border-bottom: none;
}

textarea.form-control {
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

textarea.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
}

.btn-primary {
    padding: 0.5rem 1rem;
    border-radius: 6px;
}

.alert {
    border-radius: 8px;
}

/* Custom scrollbar styling */
.comments-section::-webkit-scrollbar {
    width: 6px;
}

.comments-section::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.comments-section::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.comments-section::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endpush
```