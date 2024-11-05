@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket Details</h5>
                    <a href="{{ route('tickets-admin.users.index') }}" class="btn btn-secondary">Back to List</a>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
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
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    {!! nl2br(e($ticket->content)) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12 text-muted">
                            Created: {{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.badge {
    color: white;
    padding: 0.35em 0.65em;
    font-size: 0.9em;
    margin-right: 0.5em;
}
.card {
    margin-bottom: 1rem;
}
</style>
@endpush
@endsection