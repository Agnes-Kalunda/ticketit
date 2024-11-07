@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $isAdmin ? 'All Tickets' : 'My Assigned Tickets' }}</h5>
                </div>

                <div class="card-body">
                    <!-- Stats Row -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="text-uppercase">Total Tickets</h6>
                                    <h2 class="mb-0">{{ $stats['total'] }}</h2>
                                </div>
                            </div>
                        </div>
                        @if($isAdmin)
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h6 class="text-uppercase">Unassigned</h6>
                                    <h2 class="mb-0">{{ $stats['unassigned'] }}</h2>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="text-uppercase">Open</h6>
                                    <h2 class="mb-0">{{ $stats['open'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h6 class="text-uppercase">High Priority</h6>
                                    <h2 class="mb-0">{{ $stats['high_priority'] }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets Table -->
                    @if($tickets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Subject</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td>{{ $ticket->id }}</td>
                                            <td>{{ $ticket->customer_name }}</td>
                                            <td>{{ $ticket->subject }}</td>
                                            <td>
                                                <span class="badge bg-{{ strtolower($ticket->priority_name) }}">
                                                    {{ $ticket->priority_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $ticket->status_color }}">
                                                    {{ $ticket->status_name }}
                                                </span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</td>
                                            <td>
                                                <a href="{{ route('staff.tickets.show', $ticket->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            No tickets found.
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
    padding: 0.5em 1em;
}
.badge.bg-low { background-color: #28a745; color: white; }
.badge.bg-medium { background-color: #ffc107; color: black; }
.badge.bg-high { background-color: #dc3545; color: white; }
</style>
@endpush