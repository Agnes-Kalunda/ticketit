@extends('layouts.app')

@section('styles')
<style>
.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}
.badge.bg-low { background-color: #28a745; color: white; }
.badge.bg-medium { background-color: #ffc107; color: black; }
.badge.bg-high { background-color: #dc3545; color: white; }
.badge.bg-open { background-color: #17a2b8; color: white; }
.badge.bg-pending { background-color: #ffc107; color: black; }
.badge.bg-resolved { background-color: #28a745; color: white; }
.badge.bg-closed { background-color: #6c757d; color: white; }

.comment-box {
    transition: all 0.3s ease;
}
.comment-box:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.ticket-info-label {
    font-weight: 600;
    color: #495057;
}
.ticket-content {
    white-space: pre-wrap;
    color: #212529;
}
.gap-2 {
    gap: 0.5rem;
}
</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Alert Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Ticket Details Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Ticket #{{ $ticket->id }}</h5>
                        <p class="text-muted mb-0">{{ $ticket->subject }}</p>
                    </div>
                    <a href="{{ route('staff.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <div class="card-body">
                    <!-- Ticket Information -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="ticket-info-label mb-1">Customer</div>
                            <p class="mb-0">{{ $ticket->customer->username ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <div class="ticket-info-label mb-1">Status</div>
                            <span class="badge bg-{{ strtolower($ticket->status->name ?? 'secondary') }}">
                                {{ $ticket->status->name ?? 'Unknown' }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="ticket-info-label mb-1">Priority</div>
                            <span class="badge bg-{{ strtolower($ticket->priority->name ?? 'secondary') }}">
                                {{ $ticket->priority->name ?? 'Unknown' }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="ticket-info-label mb-1">Created</div>
                            <p class="mb-0">{{ $ticket->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    <!-- Ticket Content -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Ticket Description</h6>
                        </div>
                        <div class="card-body">
                            <div class="ticket-content">
                                {!! nl2br(e($ticket->content)) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Status Update Form for Agents -->
                    @if($isAgent && $ticket->agent_id == auth()->id())
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Update Status</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" 
                                      method="POST" 
                                      class="d-flex gap-2">
                                    @csrf
                                    <select name="status" 
                                            class="form-control @error('status') is-invalid @enderror">
                                        @foreach($statuses as $id => $name)
                                            <option value="{{ $id }}" 
                                                    {{ old('status', $ticket->status_id) == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- Add Comment Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Add Reply</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.comments.store', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="content">Your Reply:</label>
                                    <textarea name="content" 
                                              id="content" 
                                              class="form-control @error('content') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Type your reply...">{{ old('content') }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    Submit Reply
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            @if($ticket->comments->isNotEmpty())
                <div class="card mb-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Comments History</h6>
                        <span class="badge bg-primary">
                            {{ $ticket->comments->count() }} 
                            {{ Str::plural('Comment', $ticket->comments->count()) }}
                        </span>
                    </div>
                    <div class="card-body">
                        @foreach($ticket->comments->sortByDesc('created_at') as $comment)
                            <div class="comment-box mb-3 p-3 border rounded 
                                      {{ $comment->user_id == auth()->id() ? 'border-primary bg-light' : '' }}">
                                <div class="comment-header">
                                    <div>
                                        <strong>{{ $comment->user->name }}</strong>
                                        @if($comment->user_id == $ticket->agent_id)
                                            <span class="badge bg-info ms-2">Agent</span>
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
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
@endpush