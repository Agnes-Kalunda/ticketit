@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket Details</h5>
                    <div>
                        <a href="{{ route('staff.tickets.index') }}" class="btn btn-secondary btn-sm">
                            Back to List
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h4>{{ $ticket->subject }}</h4>
                            <div class="mt-2">
                                <span class="badge" style="background-color: {{ $ticket->status_color }}">
                                    {{ $ticket->status_name }}
                                </span>
                                <span class="badge" style="background-color: {{ $ticket->priority_color }}">
                                    {{ $ticket->priority_name }}
                                </span>
                                <span class="badge" style="background-color: {{ $ticket->category_color }}">
                                    {{ $ticket->category_name }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="btn-group">
                                <button type="button" 
                                        class="btn btn-info dropdown-toggle"
                                        data-toggle="dropdown">
                                    Update Status
                                </button>
                                <div class="dropdown-menu">
                                    <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" 
                                          method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="open">
                                        <button type="submit" class="dropdown-item">Open</button>
                                    </form>
                                    <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" 
                                          method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="in-progress">
                                        <button type="submit" class="dropdown-item">In Progress</button>
                                    </form>
                                    <form action="{{ route('staff.tickets.status.update', $ticket->id) }}" 
                                          method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="closed">
                                        <button type="submit" class="dropdown-item">Closed</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            Customer Information
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> {{ $ticket->customer_name }}</p>
                            <p><strong>Email:</strong> {{ $ticket->customer_email }}</p>
                            <p><strong>Submitted:</strong> {{ \Carbon\Carbon::parse($ticket->created_at)->format('F j, Y g:i A') }}</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            Ticket Content
                        </div>
                        <div class="card-body">
                            {!! nl2br(e($ticket->content)) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.badge {
    color: white;
    padding: 0.35em 0.65em;
    font-size: 0.9em;
    margin-right: 0.5em;
}
.dropdown-menu form {
    margin: 0;
}
.dropdown-menu button {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: .25rem 1.5rem;
}
.dropdown-menu button:hover {
    background-color: #f8f9fa;
}
</style>
@endpush