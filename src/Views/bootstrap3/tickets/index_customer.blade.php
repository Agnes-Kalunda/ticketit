{{-- src/Views/tickets/customer/index.blade.php --}}
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
                    @if(session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
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
                                            <td>#{{ $ticket->id ?? 'N/A' }}</td>
                                            <td>{{ $ticket->subject ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-status" style="background-color: {{ $ticket->status_color ?? '#6c757d' }}">
                                                    {{ $ticket->status_name ?? 'Unknown' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-priority" style="background-color: {{ $ticket->priority_color ?? '#6c757d' }}">
                                                    {{ $ticket->priority_name ?? 'Unknown' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-category" style="background-color: {{ $ticket->category_color ?? '#6c757d' }}">
                                                    {{ $ticket->category_name ?? 'Unknown' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($ticket->created_at)
                                                    {{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($ticket->id)
                                                    <a href="{{ route('customer.tickets.show', $ticket->id) }}"
                                                       class="btn btn-sm btn-primary">View</a>
                                                @endif
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
.badge-status, .badge-priority, .badge-category {
    min-width: 70px;
    display: inline-block;
    text-align: center;
}
table th {
    background-color: #f8f9fa;
}
.table-responsive {
    margin-top: 1rem;
}
</style>
@endpush