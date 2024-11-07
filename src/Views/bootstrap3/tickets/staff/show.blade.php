@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Main Ticket Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            Ticket #{{ $ticket->id }}
                            <span class="badge bg-secondary ms-2">{{ $ticket->subject }}</span>
                        </h5>
                    </div>
                    <div>
                        <a href="{{ route('staff.tickets.index') }}" class="btn btn-secondary btn-sm">
                            Back to List
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Ticket Details Section -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <strong>Customer:</strong>
                                <p class="mb-0">{{ $ticket->user->name ?? 'N/A' }}</p>
                                <small class="text-muted">{{ $ticket->user->email ?? '' }}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <p>
                                    <span class="badge" style="background-color: {{ $ticket->status->color ?? '#6c757d' }}">
                                        {{ $ticket->status->name ?? 'Unknown' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <strong>Priority:</strong>
                                <p>
                                    <span class="badge" style="background-color: {{ $ticket->priority->color ?? '#6c757d' }}">
                                        {{ $ticket->priority->name ?? 'Unknown' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <strong>Created:</strong>
                                <p>{{ $ticket->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Assignment Section -->
                    @if($isAdmin)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Assign Support Agent</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.assign', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8">
                                        <select name="agent_id" class="form-control @error('agent_id') is-invalid @enderror">
                                            <option value="">Select Support Agent</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}" 
                                                    {{ $ticket->agent_id == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->name }} ({{ $agent->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('agent_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">
                                            {{ $ticket->agent_id ? 'Reassign Ticket' : 'Assign Ticket' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Ticket Content -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Ticket Description</h6>
                        </div>
                        <div class="card-body bg-light">
                            <div class="ticket-content">
                                {!! nl2br(e($ticket->content)) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Agent Status Update Section -->
                    @if($isAgent && ($ticket->agent_id === auth()->id()))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Update Ticket Status</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8">
                                        <select name="status" class="form-control @error('status') is-invalid @enderror">
                                            @foreach($statuses as $id => $status)
                                                <option value="{{ $id }}" 
                                                    {{ $ticket->status_id == $id ? 'selected' : '' }}>
                                                    {{ $status }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
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
                    @endif

                    <!-- Comments Section -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Comments & Responses</h6>
                            <span class="badge bg-primary">{{ $ticket->comments->count() }} {{ Str::plural('Comment', $ticket->comments->count()) }}</span>
                        </div>
                        <div class="card-body">
                            <!-- Comments List -->
                            <div class="comments-container mb-4">
                                @forelse($ticket->comments()->orderBy('created_at', 'desc')->get() as $comment)
                                    <div class="comment mb-3 p-3 border rounded {{ $comment->user_id === $ticket->agent_id ? 'border-primary bg-light' : '' }}">
                                        <div class="comment-header d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>
                                                    {{ $comment->user->name }}
                                                </strong>
                                                @if($comment->user_id === $ticket->agent_id)
                                                    <span class="badge bg-primary ms-2">Agent</span>
                                                @elseif($comment->user->isAdmin())
                                                    <span class="badge bg-danger ms-2">Admin</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="comment-content">
                                            {!! nl2br(e($comment->content)) !!}
                                        </div>
                                        @if($isAdmin || ($isAgent && $comment->user_id === auth()->id()))
                                            <div class="comment-actions mt-2 text-end">
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete('{{ route('staff.tickets.comments.destroy', $comment->id) }}')">
                                                    Delete
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="alert alert-info mb-0">
                                        No comments yet. Be the first to respond!
                                    </div>
                                @endforelse
                            </div>

                            <!-- Add Comment Form -->
                            @if($isAgent || $isAdmin || auth()->id() === $ticket->user_id)
                                <form action="{{ route('staff.tickets.comments.store', $ticket->id) }}" method="POST">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="content">Add Response</label>
                                        <textarea id="content" 
                                                name="content" 
                                                rows="3" 
                                                class="form-control @error('content') is-invalid @enderror"
                                                placeholder="Type your response here..."
                                                required>{{ old('content') }}</textarea>
                                        @error('content')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            Submit Response
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this comment?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush

@push('styles')
<style>
.ticket-content {
    white-space: pre-wrap;
    font-family: inherit;
}
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}
.comment {
    transition: all 0.2s ease;
}
.comment:hover {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}
</style>
@endpush
@endsection
