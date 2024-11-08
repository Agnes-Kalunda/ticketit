
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Ticket Header Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket #{{ $ticket->id }}</h5>
                    <div>
                        <span class="badge bg-light text-dark">{{ $ticket->status->name }}</span>
                        <span class="badge" style="background-color: {{ $ticket->priority->color }}">
                            {{ $ticket->priority->name }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-3 text-primary">{{ $ticket->subject }}</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Created:</strong> {{ $ticket->created_at->format('M d, Y h:i A') }}</p>
                            <p><strong>Category:</strong> {{ $ticket->category->name }}</p>
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

            <!-- Original Message -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="avatar-circle bg-secondary">{{ substr($ticket->customer->name, 0, 1) }}</div>
                        <div class="ms-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $ticket->customer->name }}</strong>
                                <small class="text-muted">{{ $ticket->created_at->format('M d, Y h:i A') }}</small>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded">
                                {!! nl2br(e($ticket->content)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversation Thread -->
            @if($ticket->comments->count() > 0)
                <div class="conversation-thread mb-4">
                    @foreach($ticket->comments as $comment)
                        @php
                            $isAgent = $comment->user && ($comment->user->ticketit_agent || $comment->user->ticketit_admin);
                        @endphp
                        <div class="card mb-3 {{ $isAgent ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="avatar-circle {{ $isAgent ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $isAgent ? substr($comment->user->name, 0, 1) : substr($ticket->customer->name, 0, 1) }}
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>{{ $isAgent ? $comment->user->name : $ticket->customer->name }}</strong>
                                                @if($isAgent)
                                                    <span class="badge bg-primary ms-2">Support Agent</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ $comment->created_at->format('M d, Y h:i A') }}</small>
                                        </div>
                                        <div class="mt-3 p-3 bg-light rounded">
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
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Reply</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.tickets.comments.store', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="form-group mb-3">
                                <textarea name="content" 
                                    rows="4" 
                                    class="form-control @error('content') is-invalid @enderror" 
                                    placeholder="Type your reply here..."
                                    required>{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Send Reply</button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    This ticket is closed. Please create a new ticket if you need further assistance
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.conversation-thread {
    max-height: 600px;
    overflow-y: auto;
}

.border-primary {
    border-left: 4px solid #0d6efd !important;
}

.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .d-flex {
        flex-direction: column;
    }
    
    .ms-3 {
        margin-left: 0 !important;
        margin-top: 1rem;
    }
    
    .avatar-circle {
        margin: 0 auto;
    }
}
</style>
@endsection
