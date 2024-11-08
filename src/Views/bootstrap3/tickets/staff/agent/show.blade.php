@extends('layouts.app')

@section('styles')
<style>
.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}
.badge-status-open { background-color: #17a2b8; color: white; }
.badge-status-pending { background-color: #ffc107; color: black; }
.badge-status-resolved { background-color: #28a745; color: white; }
.badge-status-closed { background-color: #6c757d; color: white; }

.badge-priority-high { background-color: #dc3545; color: white; }
.badge-priority-medium { background-color: #ffc107; color: black; }
.badge-priority-low { background-color: #28a745; color: white; }

.comment-box {
    transition: all 0.3s ease;
}
.comment-box:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.text-muted {
    color: #6c757d;
}
.border-primary {
    border-color: #007bff;
}
.bg-light {
    background-color: #f8f9fa;
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
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Ticket Details Card -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Ticket #{{ $ticket->id }}</h5>
                        <p class="text-muted mb-0">{{ $ticket->subject }}</p>
                    </div>
                    <a href="{{ route('staff.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                        <!-- <i class="fas fa-arrow-left"></i> Back to List -->
                    </a>
                </div>

                <div class="card-body">
                    <!-- Ticket Information -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-muted mb-1">Customer</div>
                            <strong>{{ $ticket->customer->username ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted mb-1">Status</div>
                            <span class="badge badge-status-{{ strtolower($ticket->status->name ?? 'unknown') }}">
                                {{ $ticket->status->name ?? 'Unknown' }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted mb-1">Priority</div>
                            <span class="badge badge-priority-{{ strtolower($ticket->priority->name ?? 'unknown') }}">
                                {{ $ticket->priority->name ?? 'Unknown' }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted mb-1">Created</div>
                            <strong>{{ $ticket->created_at->format('M d, Y H:i') }}</strong>
                        </div>
                    </div>

                    <!-- Ticket Content -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Description</h6>
                        </div>
                        <div class="card-body bg-light">
                            {!! nl2br(e($ticket->content)) !!}
                        </div>
                    </div>

                    <!-- Status Update Form - Only for Assigned Agent -->
                    @if($isAgent && !$isAdmin && $ticket->agent_id == auth()->id())
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Update Status</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                                        @foreach($statuses as $id => $name)
                                            <option value="{{ $id }}" {{ old('status', $ticket->status_id) == $id ? 'selected' : '' }}>
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

                    <!-- Comment Form - Not for Admins -->
                    @if(!$isAdmin && ($isAgent && $ticket->agent_id == auth()->id()))
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Add Comment</h6>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

                                <form action="{{ route('staff.tickets.comments.store', $ticket->id) }}" method="POST">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="content">Your Comment:</label>
                                        <textarea 
                                            name="content" 
                                            id="content" 
                                            class="form-control @error('content') is-invalid @enderror" 
                                            rows="3" 
                                            placeholder="Type your comment here..."
                                        >{{ old('content') }}</textarea>
                                        @error('content')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Comment</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- Comments Section -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Comments History</h6>
                            <span class="badge bg-primary">
                                {{ $ticket->comments->count() }} 
                                {{ Str::plural('Comment', $ticket->comments->count()) }}
                            </span>
                        </div>
                        <div class="card-body">
                            @forelse($ticket->comments->sortByDesc('created_at') as $comment)
                                <div class="comment-box mb-3 p-3 border rounded {{ $comment->user_id == auth()->id() ? 'border-primary bg-light' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong>{{ $comment->user->name }}</strong>
                                            @if($comment->user->isAgent())
                                                <span class="badge bg-info ms-2">Agent</span>
                                            @elseif($comment->user->isAdmin())
                                                <span class="badge bg-danger ms-2">Admin</span>
                                            @else
                                                <span class="badge bg-secondary ms-2">Customer</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="comment-content">
                                        {!! nl2br(e($comment->content)) !!}
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    No comments yet
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
@endsection