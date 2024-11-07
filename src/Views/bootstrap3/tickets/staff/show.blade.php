@extends('layouts.app')

@section('styles')
<style>
.ticket-content {
    white-space: pre-wrap;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}
.badge {
    padding: 0.4em 0.8em;
}
.priority-high { background-color: #dc3545; color: white; }
.priority-medium { background-color: #ffc107; color: black; }
.priority-low { background-color: #28a745; color: white; }
.status-open { background-color: #17a2b8; color: white; }
.status-pending { background-color: #ffc107; color: black; }
.status-closed { background-color: #6c757d; color: white; }

.comment {
    border-left: 3px solid #dee2e6;
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0 4px 4px 0;
}
.comment.agent-comment {
    border-left-color: #0d6efd;
    background-color: #f0f7ff;
}
.timeline-line {
    position: relative;
    padding-left: 30px;
}
.timeline-line::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #dee2e6;
}
.status-history {
    padding: 10px;
    border-radius: 4px;
    background-color: #f8f9fa;
    margin-bottom: 10px;
}
.status-history:hover {
    background-color: #f0f0f0;
}
</style>
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <!-- Main Ticket Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Ticket #{{ $ticket->id }}</h5>
                        <div class="small text-muted">{{ $ticket->subject }}</div>
                    </div>
                    <a href="{{ route('staff.tickets.index') }}" class="btn btn-secondary btn-sm">
                        Back to List
                    </a>
                </div>

                <div class="card-body">
                    <!-- Ticket Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Customer</label>
                                <div>
                                    {{ $ticket->customer->name }}<br>
                                    <small class="text-muted">{{ $ticket->customer->email }}</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Category</label>
                                <div>
                                    <span class="badge" style="background-color: {{ $ticket->category->color }}">
                                        {{ $ticket->category->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Status</label>
                                <div>
                                    <span class="badge status-{{ strtolower($ticket->status->name) }}">
                                        {{ $ticket->status->name }}
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Priority</label>
                                <div>
                                    <span class="badge priority-{{ strtolower($ticket->priority->name) }}">
                                        {{ $ticket->priority->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Content -->
                    <div class="ticket-content mb-4">
                        {!! nl2br(e($ticket->content)) !!}
                    </div>

                    <!-- Admin: Assignment & Status Monitoring -->
                    @if($isAdmin)
                    <div class="row">
                        <!-- Agent Assignment -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Ticket Assignment</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('staff.tickets.assign', $ticket->id) }}" method="POST">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-8">
                                                <select name="agent_id" class="form-control">
                                                    <option value="">Select Agent...</option>
                                                    @foreach($agents as $agent)
                                                        <option value="{{ $agent->id }}" 
                                                            {{ $ticket->agent_id == $agent->id ? 'selected' : '' }}>
                                                            {{ $agent->name }} ({{ $agent->assigned_tickets_count }} tickets)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    {{ $ticket->agent_id ? 'Reassign' : 'Assign' }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Status History -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Status History</h6>
                                </div>
                                <div class="card-body">
                                    @forelse($ticket->statusHistory as $history)
                                        <div class="status-history">
                                            <div class="d-flex justify-content-between">
                                                <span>
                                                    Changed to: 
                                                    <span class="badge status-{{ strtolower($history->new_status) }}">
                                                        {{ $history->new_status }}
                                                    </span>
                                                </span>
                                                <small class="text-muted">
                                                    {{ $history->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                By: {{ $history->agent->name }}
                                            </small>
                                        </div>
                                    @empty
                                        <div class="text-muted">No status changes recorded.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Agent: Status Update -->
                    @if($isAgent && $ticket->agent_id === auth()->id())
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Update Status</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="row align-items-end">
                                    <div class="col-md-8">
                                        <select name="status" class="form-control">
                                            @foreach($statuses as $id => $name)
                                                <option value="{{ $id }}" 
                                                    {{ $ticket->status_id == $id ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">
                                            Update Status
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Agent: Add Comment -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Add Response</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.comments.store', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="form-group mb-3">
                                    <textarea name="content" rows="3" 
                                            class="form-control @error('content') is-invalid @enderror"
                                            required></textarea>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        Submit Response
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Comments List (Visible to both Admin and Agent) -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Ticket History</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline-line">
                                @forelse($ticket->comments()->orderBy('created_at', 'desc')->get() as $comment)
                                    <div class="comment {{ $comment->user_id === $ticket->agent_id ? 'agent-comment' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>{{ $comment->user->name }}</strong>
                                                @if($comment->user_id === $ticket->agent_id)
                                                    <span class="badge bg-primary ms-2">Agent</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="comment-content">
                                            {!! nl2br(e($comment->content)) !!}
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted">No responses yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Disable submit buttons on form submission to prevent double-submit
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            }
        });
    });
});
</script>
@endpush