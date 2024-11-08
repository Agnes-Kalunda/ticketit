@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Ticket Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Ticket #{{ $ticket->id }} - {{ $ticket->subject }}
                    </h5>
                    <div>
                        <span class="badge" style="background-color: {{ $ticket->status->color }}">
                            {{ $ticket->status->name }}
                        </span>
                        <span class="badge" style="background-color: {{ $ticket->priority->color }}">
                            {{ $ticket->priority->name }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Category:</strong> {{ $ticket->category->name }}</p>
                            <p><strong>Created:</strong> {{ $ticket->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Last Updated:</strong> {{ $ticket->updated_at->diffForHumans() }}</p>
                            @if($ticket->agent)
                                <p><strong>Assigned To:</strong> {{ $ticket->agent->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Original Ticket Content -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle">
                                <span class="initials">{{ substr($ticket->customer->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ $ticket->customer->name }}</h6>
                                <small class="text-muted">
                                    {{ $ticket->created_at->format('M d, Y h:i A') }}
                                </small>
                            </div>
                            <div class="ticket-content mt-2">
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
                        <div class="card mb-3 {{ $comment->user && ($comment->user->isAdmin() || $comment->user->isAgent()) ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-circle {{ $comment->user && ($comment->user->isAdmin() || $comment->user->isAgent()) ? 'bg-primary' : 'bg-secondary' }}">
                                            <span class="initials">
                                                @if($comment->user)
                                                    {{ substr($comment->user->name, 0, 1) }}
                                                @else
                                                    {{ substr($ticket->customer->name, 0, 1) }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                @if($comment->user && ($comment->user->isAdmin() || $comment->user->isAgent()))
                                                    {{ $comment->user->name }}
                                                    <span class="badge bg-primary ms-2">Support Staff</span>
                                                @else
                                                    {{ $ticket->customer->name }}
                                                @endif
                                            </h6>
                                            <small class="text-muted">
                                                {{ $comment->created_at->format('M d, Y h:i A') }}
                                            </small>
                                        </div>
                                        <div class="comment-content mt-2">
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
            @if(!$ticket->completed_at)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Add Reply</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.tickets.comments.store', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea name="content" 
                                          rows="4" 
                                          class="form-control @error('content') is-invalid @enderror"
                                          required>{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">
                                    Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    This ticket has been marked as completed. Please create a new ticket if you need further assistance.
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    background-color: #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-circle .initials {
    color: white;
    font-size: 16px;
    font-weight: 500;
}

.ticket-content, .comment-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.badge {
    padding: 0.5em 1em;
}

.comments-section {
    max-height: 600px;
    overflow-y: auto;
}

.border-primary {
    border-left: 4px solid #0d6efd !important;
}
</style>
@endpush