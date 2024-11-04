@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ trans('ticketit::lang.index-my-tickets') }}</h5>
                    <a href="{{ route('customer.tickets.create') }}" class="btn btn-success btn-sm">
                        {{ trans('ticketit::lang.btn-create-new-ticket') }}
                    </a>
                </div>

                <div class="card-body">
                    {{-- Status Messages --}}
                    @if(session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
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

                    {{-- Tickets Table --}}
                    @if($tickets->isEmpty())
                        <div class="text-center text-muted my-4">
                            <p>{{ trans('ticketit::lang.table-empty') }}</p>
                            <a href="{{ route('customer.tickets.create') }}" class="btn btn-success">
                                {{ trans('ticketit::lang.create-new-ticket') }}
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ trans('ticketit::lang.table-id') }}</th>
                                        <th>{{ trans('ticketit::lang.table-subject') }}</th>
                                        <th>{{ trans('ticketit::lang.table-status') }}</th>
                                        <th>{{ trans('ticketit::lang.table-priority') }}</th>
                                        <th>{{ trans('ticketit::lang.table-category') }}</th>
                                        <th>{{ trans('ticketit::lang.table-last-updated') }}</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td>#{{ $ticket->id }}</td>
                                            <td>
                                                <a href="{{ route('customer.tickets.show', $ticket->id) }}" class="text-decoration-none">
                                                    {{ Str::limit($ticket->subject, 40) }}
                                                </a>
                                            </td>
                                            <td>
                                                @php
                                                    $statusColor = $ticket->status ? $ticket->status->color : '#666666';
                                                    $statusName = $ticket->status ? $ticket->status->name : 'Not Set';
                                                @endphp
                                                <span class="badge text-white" style="background-color: {{ $statusColor }}">
                                                    {{ $statusName }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $priorityColor = $ticket->priority ? $ticket->priority->color : '#666666';
                                                    $priorityName = $ticket->priority ? $ticket->priority->name : 'Not Set';
                                                @endphp
                                                <span class="badge text-white" style="background-color: {{ $priorityColor }}">
                                                    {{ $priorityName }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $categoryColor = $ticket->category ? $ticket->category->color : '#666666';
                                                    $categoryName = $ticket->category ? $ticket->category->name : 'Not Set';
                                                @endphp
                                                <span class="badge text-white" style="background-color: {{ $categoryColor }}">
                                                    {{ $categoryName }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($ticket->updated_at)
                                                    {{ $ticket->updated_at->diffForHumans() }}
                                                @else
                                                    Never
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('customer.tickets.show', $ticket->id) }}" 
                                                       class="btn btn-primary"
                                                       title="View Ticket">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @if($tickets->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $tickets->links() }}
                            </div>

                            <div class="text-muted text-center mt-2">
                                {{ trans('ticketit::lang.table-info', [
                                    'start' => $tickets->firstItem(),
                                    'end' => $tickets->lastItem(),
                                    'total' => $tickets->total()
                                ]) }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    $('.alert-dismissible').delay(5000).fadeOut(500);

    // Initialize tooltips if using Bootstrap
    $('[data-toggle="tooltip"]').tooltip();

    // Add loading state to links
    $('a').click(function() {
        $(this).addClass('disabled').attr('disabled', true);
    });
});
</script>
@endpush

@push('styles')
<style>
    .badge {
        font-size: 0.9em;
        padding: 0.35em 0.65em;
    }
    .table th {
        background-color: #f8f9fa;
    }
    .table td {
        vertical-align: middle;
    }
</style>
@endpush