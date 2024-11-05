@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manage Customer Tickets</h5>
                    <div class="btn-group">
                        <a href="{{ route('staff.tickets.index') }}" 
                           class="btn btn-outline-primary {{ !request('filter') ? 'active' : '' }}">
                            All
                        </a>
                        <a href="{{ route('staff.tickets.index', ['filter' => 'open']) }}" 
                           class="btn btn-outline-warning {{ request('filter') === 'open' ? 'active' : '' }}">
                            Open
                        </a>
                        <a href="{{ route('staff.tickets.index', ['filter' => 'in-progress']) }}" 
                           class="btn btn-outline-info {{ request('filter') === 'in-progress' ? 'active' : '' }}">
                            In Progress
                        </a>
                        <a href="{{ route('staff.tickets.index', ['filter' => 'closed']) }}" 
                           class="btn btn-outline-success {{ request('filter') === 'closed' ? 'active' : '' }}">
                            Closed
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

                    @if($tickets->isEmpty())
                        <div class="text-center text-muted my-4">
                            <p>No tickets found.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Category</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td>#{{ $ticket->id }}</td>
                                            <td>{{ $ticket->customer_name }}</td>
                                            <td>
                                                <a href="{{ route('staff.tickets.show', $ticket->id) }}"
                                                   class="text-primary text-decoration-none">
                                                    {{ $ticket->subject }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $ticket->status_color }}">
                                                    {{ $ticket->status_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $ticket->priority_color }}">
                                                    {{ $ticket->priority_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $ticket->category_color }}">
                                                    {{ $ticket->category_name }}
                                                </span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('staff.tickets.show', $ticket->id) }}" 
                                                       class="btn btn-sm btn-primary">View</a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-info dropdown-toggle"
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
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($tickets->hasPages())
                            <div class="mt-4">
                                {{ $tickets->links() }}
                            </div>
                        @endif
                    @endif
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
}
table th {
    background-color: #f8f9fa;
}
.text-decoration-none:hover {
    text-decoration: underline !important;
}
table td {
    vertical-align: middle;
}
.btn-group .dropdown-menu form {
    margin: 0;
}
.btn-group .dropdown-menu button {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: .25rem 1.5rem;
}
.btn-group .dropdown-menu button:hover {
    background-color: #f8f9fa;
}
</style>
@endpush