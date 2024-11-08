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

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket #{{ $ticket->id }}: {{ e($ticket->subject) }}</h5>
                    <a href="{{ route('staff.tickets.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <strong>Customer:</strong>
                            <p>{{ e($ticket->customer->username ?? 'N/A') }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong>
                            <p>
                                <span class="badge badge-status-{{ strtolower($ticket->status->name ?? 'unknown') }}">
                                    {{ e($ticket->status->name ?? 'Unknown') }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong>Priority:</strong>
                            <p>
                                <span class="badge badge-priority-{{ strtolower($ticket->priority->name ?? 'unknown') }}">
                                    {{ e($ticket->priority->name ?? 'Unknown') }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong>Created:</strong>
                            <p>{{ $ticket->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Ticket Content</h6>
                        </div>
                        <div class="card-body">
                            {!! nl2br(e($ticket->content)) !!}
                        </div>
                    </div>

                    <!-- Update Ticket Status for Agent -->
                    @if($isAgent && ($ticket->agent_id == auth()->id() || auth()->user()->isAdmin()))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Update Ticket Status</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <select name="status" class="form-control @error('status') is-invalid @enderror">
                                    @foreach($statuses as $id => $name)
                                        <option value="{{ $id }}" {{ old('status', $ticket->status_id) == $id ? 'selected' : '' }}>
                                            {{ e($name) }}
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

                    <!-- Agent Add Comment -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Add a Comment</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.comments.store', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="content">Your Comment:</label>
                                    <textarea 
                                        name="content" 
                                        id="content" 
                                        class="form-control @error('content') is-invalid @enderror" 
                                        rows="3" 
                                        placeholder="Type your comment..."
                                    >{{ old('content') }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Comment</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            @if($ticket->comments && $ticket->comments->count() > 0)
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Comments & Responses</h6>
                        <span class="badge bg-primary">
                            {{ $ticket->comments->count() }} 
                            Comment{{ $ticket->comments->count() != 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div class="card-body">
                        @foreach($ticket->comments->sortByDesc('created_at') as $comment)
                            <div class="comment-box mb-3 p-3 border rounded {{ $comment->user_id == auth()->id() ? 'border-primary bg-light' : '' }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{{ e($comment->user->name) }}</strong>
                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
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