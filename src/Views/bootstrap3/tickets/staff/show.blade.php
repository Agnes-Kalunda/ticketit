
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket #{{ $ticket->id }}</h5>
                    <!-- <a href="{{ route('staff.tickets.index') }}" class="btn btn-secondary">Back to List</a> -->
                </div>

                @if($isAdmin)
                <!-- Admin View -->
                <div class="card-body">
                    <!-- Ticket Assignment Section -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form action="{{ route('staff.tickets.assign', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Assign Ticket to Agent</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Select Agent</label>
                                            <select name="agent_id" class="form-control">
                                                <option value="">Choose an agent...</option>
                                                @foreach($agents as $agent)
                                                    <option value="{{ $agent->id }}" 
                                                            {{ $ticket->agent_id == $agent->id ? 'selected' : '' }}>
                                                        {{ $agent->name }} 
                                                       
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-3">
                                            {{ $ticket->agent_id ? 'Reassign Ticket' : 'Assign Ticket' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="col-md-4">
                            <!-- Current Status -->
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Current Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="text-muted">Status</label>
                                        <div>
                                            <span class="badge bg-{{ strtolower($ticket->status->name) }}">
                                                {{ $ticket->status->name }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted">Priority</label>
                                        <div>
                                            <span class="badge bg-{{ strtolower($ticket->priority->name) }}">
                                                {{ $ticket->priority->name }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($ticket->agent_id)
                                        <div class="mb-3">
                                            <label class="text-muted">Assigned To</label>
                                            <div>{{ $ticket->agent->name }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Ticket Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Customer Details</h6>
                                    <!-- <p><strong>Name:</strong> {{ $ticket->customer->username }}</p> -->
                                    <p><strong>Email:</strong> {{ $ticket->customer->email }}</p>
                                    <p><strong>Submitted:</strong> {{ $ticket->created_at->format('F j, Y g:i A') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Ticket Content</h6>
                                    <div class="ticket-content p-3 bg-light">
                                        {!! nl2br(e($ticket->content)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comments/Responses Section - View Only -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Ticket History</h6>
                        </div>
                        <div class="card-body">
                            @forelse($ticket->comments as $comment)
                                <div class="comment mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>
                                                {{ $comment->user->name }}
                                                @if($comment->user_id === $ticket->agent_id)
                                                    <span class="badge bg-primary">Agent</span>
                                                @endif
                                            </strong>
                                        </div>
                                        <small class="text-muted">
                                            {{ $comment->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        {!! nl2br(e($comment->content)) !!}
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">No responses yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.badge {
    padding: 0.5em 1em;
}
.badge.bg-low { background-color: #28a745; }
.badge.bg-medium { background-color: #ffc107; }
.badge.bg-high { background-color: #dc3545; }
.badge.bg-open { background-color: #17a2b8; }
.badge.bg-pending { background-color: #ffc107; }
.badge.bg-resolved { background-color: #28a745; }
.badge.bg-closed { background-color: #6c757d; }
.ticket-content {
    white-space: pre-wrap;
    border-radius: 4px;
}
</style>
@endpush
@endsection