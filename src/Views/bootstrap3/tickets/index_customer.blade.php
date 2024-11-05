
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Tickets</h5>
                    <a href="{{ route('customer.tickets.create') }}" class="btn btn-success btn-sm">
                        Create New Ticket
                    </a>
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

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
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
                                            <td>{{ $ticket->subject }}</td>
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
                                                <a href="{{ route('customer.tickets.index') }}" 
                                                   class="btn btn-sm btn-primary">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
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
</style>
@endpush