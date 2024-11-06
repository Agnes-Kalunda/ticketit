@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket #{{ $ticket->id }}: {{ $ticket->subject }}</h5>
                    <a href="{{ route('staff.tickets.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <strong>Customer:</strong>
                            <p>{{ $ticket->customer->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong>
                            <p>
                                <span class="badge {{ $ticket->status->class ?? 'bg-secondary' }}">
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

                    @if($isAdmin || $isAgent)
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Update Status</h6>
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
                                <button type="submit" class="btn btn-primary">Update</button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection