```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Ticket Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-ticket-detailed"></i> 
                        Ticket #{{ $ticket->id }}
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="badge rounded-pill" style="background-color: {{ $ticket->status->color }}">
                            {{ $ticket->status->name }}
                        </span>
                        <span class="badge rounded-pill" style="background-color: {{ $ticket->priority->color }}">
                            {{ $ticket->priority->name }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-primary mb-3">{{ $ticket->subject }}</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="bi bi-folder me-2"></i>
                                <strong>Category:</strong> 
                                {{ $ticket->category->name }}
                            </p>
                            <p class="mb-2">
                                <i class="bi bi-calendar me-2"></i>
                                <strong>Created:</strong> 
                                {{ $ticket->created_at->format('M d, Y h:i A') }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="bi bi-clock-history me-2"></i>
                                <strong>Last Updated:</strong> 
                                {{ $ticket->updated_at->diffForHumans() }}
                            </p>
                            @if($ticket->agent)
                                <p class="mb-2">
                                    <i class="bi bi-person-badge me-2"></i>
                                    <strong>Support Agent:</strong> 
                                    {{ $ticket->agent->name }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-secondary">
                                <span class="initials">{{ substr($ticket->customer->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ $ticket->customer->name }}</h6>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $ticket->created_at->format('M d, Y h:i A') }}
                                </small>
                            </div>
                            <div class="ticket-content mt-3 p-3 bg-light rounded">
                                {!! nl2br(e($ticket->content)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            @if($ticket->comments->count() > 0)
                <div class="comments-section mb-4">
                    @foreach($ticket->comments as $comment)
                        @php
                            $isStaffComment = $comment->user_id && 
                                            $comment->user && 
                                            property_exists($comment->user, 'ticketit_agent') && 
                                            ($comment->user->ticketit_agent || $comment->user->ticketit_admin);

                            $commenterName = $isStaffComment ? 
                                $comment->user->name : 
                                ($comment->customer_id === Auth::guard('customer')->id() ? 'You' : $ticket->customer->name);
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
                                            <div>
                                                <strong>{{ $commenterName }}</strong>
                                                @if($isStaffComment)
                                                    <span class="badge bg-primary ms-2">Support Staff</span>
                                                @endif
                                            </div>
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
            @if($ticket->status->name !== 'Closed')
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-reply me-2"></i>Add Reply
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.tickets.comments.store', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea 
                                    name="content" 
                                    rows="4" 
                                    class="form-control @error('content') is-invalid @enderror"
                                    placeholder="Type your reply here..."
                                    required
                                >{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mt-3 text-end">
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
                    This ticket is closed. Please create a new ticket if you need further assistance.
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
.container {
    max-width: 1200px;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
    margin-bottom: 1rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: none;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.initials {
    font-size: 16px;
}

.ticket-content, 
.comment-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.border-primary {
    border-left: 4px solid #0d6efd !important;
}

.comments-section {
    max-height: 600px;
    overflow-y: auto;
    scrollbar-width: thin;
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

textarea.form-control {
    min-height: 100px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    resize: vertical;
}

textarea.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
}

.btn-primary {
    padding: 0.5rem 1rem;
    border-radius: 6px;
}

/* Custom scrollbar */
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

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: start !important;
        gap: 0.5rem;
    }
    
    .d-flex {
        flex-direction: column;
    }
    
    .ms-3 {
        margin-left: 0 !important;
        margin-top: 1rem;
    }
    
    .avatar-circle {
        margin-bottom: 0.5rem;
    }
    
    .badge {
        display: inline-block;
        margin-top: 0.25rem;
    }
}
</style>
@endpush
