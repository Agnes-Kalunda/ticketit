@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket #{{ $ticket->id }}: {{ $ticket->subject }}</h5>
                    <!-- <a href="{{ route('staff.tickets.index') }}" class="btn btn-secondary btn-sm">Back to List</a> -->
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <strong>Customer:</strong>
                            <p>{{ $ticket->customer->username ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong>
                            <p>
                                <span class="badge {{ $ticket->status->class ?? '' }}">
                                    {{ $ticket->status->name ?? 'Unknown' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong>Priority:</strong>
                            <p>
                                <span class="badge {{ $ticket->priority->class ?? 'bg-secondary' }}">
                                    {{ $ticket->priority->name ?? 'Unknown' }}
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
                    @if($isAgent && $ticket->agent_id == auth()->id())
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Update Ticket Status</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <select name="status" class="form-control">
                                    @foreach($statuses as $id => $name)
                                        <option value="{{ $id }}" {{ $ticket->status_id == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
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
                            <form action="{{ route('staff.tickets.agent.comments.store', $ticket->id) }}" method="POST">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="content">Your Comment:</label>
                                    <textarea name="content" id="content" class="form-control" rows="3" placeholder="Type your comment..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Comment</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Comments Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Comments & Responses</h6>
                    <span class="badge bg-primary">{{ $ticket->comments->count() }} Comment{{ $ticket->comments->count() > 1 ? 's' : '' }}</span>
                </div>
                <div class="card-body">
                    @foreach($ticket->comments as $comment)
                    <div class="mb-3 p-3 border rounded {{ $comment->user_id == auth()->id() ? 'border-primary bg-light' : '' }}">
                        <strong>{{ $comment->user->name }}</strong>
                        <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                        <p>{!! nl2br(e($comment->content)) !!}</p>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
