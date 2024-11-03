@extends('ticketit::layouts.master')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        {{ $complete ? trans('ticketit::lang.nav-completed-tickets') : trans('ticketit::lang.nav-active-tickets') }}
                    </h5>
                </div>

                <div class="card-body">
                    @if($tickets->isEmpty())
                        <div class="text-center text-muted my-4">
                            <p>{{ trans('ticketit::lang.index-empty-records') }}</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ trans('ticketit::lang.table-id') }}</th>
                                        <th>{{ trans('ticketit::lang.table-subject') }}</th>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Category</th>
                                        <th>{{ trans('ticketit::lang.table-last-updated') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td>#{{ $ticket->id }}</td>
                                            <td>
                                                <a href="{{ route(Setting::grab('main_route').'.show', $ticket->id) }}">
                                                    {{ $ticket->subject }}
                                                </a>
                                            </td>
                                            <td>{{ $ticket->customer_name }}</td>
                                            <td>{{ $ticket->customer_email }}</td>
                                            <td>
                                                <span class="badge text-white" style="background-color: #666666">
                                                    Status
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge text-white" style="background-color: #666666">
                                                    Priority
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge text-white" style="background-color: #666666">
                                                    Category
                                                </span>
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection