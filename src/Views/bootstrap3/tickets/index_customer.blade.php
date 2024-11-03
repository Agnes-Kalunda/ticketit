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
                    @if(session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">{{ trans('ticketit::lang.flash-x') }}</span>
                            </button>
                        </div>
                    @endif

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
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td>
                                                #{{ $ticket->id }}
                                            </td>
                                            <td>
                                                <a href="{{ route('customer.tickets.show', $ticket->id) }}">
                                                    {{ $ticket->subject }}
                                                </a>
                                            </td>
                                            <td>
                                            @if($ticket->priority)
    <span class="badge text-white" style="background-color: {{ $ticket->priority->color }}">
        {{ $ticket->priority->name }}
    </span>
@endif

@if($ticket->category)
    <span class="badge text-white" style="background-color: {{ $ticket->category->color }}">
        {{ $ticket->category->name }}
    </span>
@endif

                                            </td>
                                            <td>
                                                {{ $ticket->updated_at->diffForHumans() }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            {{ $tickets->links() }}
                        </div>

                        @if($tickets->hasPages())
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
    });
</script>
@endpush