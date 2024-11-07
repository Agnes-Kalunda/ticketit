{{-- ~/ticketit/src/Views/bootstrap3/tickets/staff/index.blade.php --}}
@extends('layouts.app')

@section('styles')
<style>
.ticket-list {
    margin-top: 20px;
}
.badge {
    padding: 0.4em 0.8em;
}
.priority-high { background-color: #dc3545; color: white; }
.priority-medium { background-color: #ffc107; color: black; }
.priority-low { background-color: #28a745; color: white; }
.status-open { background-color: #17a2b8; color: white; }
.status-pending { background-color: #ffc107; color: black; }
.status-closed { background-color: #6c757d; color: white; }
.unassigned { background-color: #ffc107; font-style: italic; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $isAdmin ? 'All Tickets' : 'My Assigned Tickets' }}</h5>
                    
                    <!-- Filters -->
                    <div class="d-flex gap-2">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                Status Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?status=all">All</a></li>
                                <li><a class="dropdown-item" href="?status=open">Open</a></li>
                                <li><a class="dropdown-item" href="?status=pending">Pending</a></li>
                                <li><a class="dropdown-item" href="?status=closed">Closed</a></li>
                            </ul>
                        </div>

                        @if($isAdmin)
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    Assignment Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="?assigned=all">All</a></li>
                                    <li><a class="dropdown-item" href="?assigned=0">Unassigned</a></li>
                                    <li><a class="dropdown-item" href="?assigned=1">Assigned</a></li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    @if($isAdmin)
                                        <th>Agent</th>
                                    @endif
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr>
                                        <td>{{ $ticket->id }}</td>
                                        <td>
                                            {{ $ticket->customer_name }}<br>
                                            <small class="text-muted">{{ $ticket->customer_email }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('staff.tickets.show', $ticket->id) }}">
                                                {{ $ticket->subject }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $ticket->category_color }}">
                                                {{ $ticket->category_name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge priority-{{ strtolower($ticket->priority_name) }}">
                                                {{ $ticket->priority_name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-{{ strtolower($ticket->status_name) }}">
                                                {{ $ticket->status_name }}
                                            </span>
                                        </td>
                                        @if($isAdmin)
                                            <td>
                                                @if($ticket->agent_name)
                                                    {{ $ticket->agent_name }}
                                                @else
                                                    <span class="badge unassigned">Unassigned</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('staff.tickets.show', $ticket->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    View
                                                </a>
                                                @if($isAdmin && !$ticket->agent_id)
                                                    <button type="button"
                                                            class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#assignModal"
                                                            data-ticket-id="{{ $ticket->id }}">
                                                        Assign
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 9 : 8 }}" class="text-center">
                                            No tickets found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($tickets instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="mt-4">
                            {{ $tickets->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($isAdmin)
    <!-- Assignment Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="assignForm" action="" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="agent_id">Select Agent</label>
                            <select name="agent_id" id="agent_id" class="form-control" required>
                                <option value="">Choose an agent...</option>
                                @foreach($availableAgents as $agent)
                                    <option value="{{ $agent->id }}">
                                        {{ $agent->name }} ({{ $agent->assigned_tickets_count }} tickets)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Assignment Modal Handler
    const assignModal = document.getElementById('assignModal');
    if (assignModal) {
        assignModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const ticketId = button.getAttribute('data-ticket-id');
            const form = document.getElementById('assignForm');
            form.action = `/staff/tickets/${ticketId}/assign`;
        });

        // Handle form submission
        document.getElementById('assignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(assignModal).hide();
                    window.location.reload();
                } else {
                    throw new Error(data.error || 'Failed to assign ticket');
                }
            })
            .catch(error => {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger mt-3';
                errorDiv.textContent = error.message;
                form.querySelector('.modal-body').prepend(errorDiv);
            });
        });
    }
});
</script>
@endpush