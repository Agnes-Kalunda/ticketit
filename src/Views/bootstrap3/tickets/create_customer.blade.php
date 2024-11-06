@extends('layouts.app')

@section('content')
@php
    Log::info('Create ticket form rendered:', [
        'route' => Route::currentRouteName(),
        'customer' => auth()->guard('customer')->check() ? [
            'id' => auth()->guard('customer')->id(),
            'name' => auth()->guard('customer')->user()->name
        ] : 'not authenticated',
        'categories_count' => isset($categories) ? count($categories) : 0,
        'priorities_count' => isset($priorities) ? count($priorities) : 0
    ]);
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ trans('ticketit::lang.create-new-ticket') }}</h5>
                    <a href="{{ route('customer.tickets.index') }}" class="btn btn-secondary btn-sm">Back to Tickets</a>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('customer.tickets.store') }}" id="ticketForm">
                        @csrf
                        <input type="hidden" name="debug_token" value="{{ uniqid('ticket_') }}">

                        <div class="form-group row">
                            <label for="subject" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.subject') }}{{ trans('ticketit::lang.colon') }}
                            </label>
                            <div class="col-md-8">
                                <input type="text" 
                                       class="form-control @error('subject') is-invalid @enderror" 
                                       name="subject" 
                                       value="{{ old('subject') }}" 
                                       required>
                                @error('subject')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="category_name" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.category') }}{{ trans('ticketit::lang.colon') }}
                            </label>
                            <div class="col-md-8">
                                <select name="category_name" 
                                        class="form-control @error('category_name') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Category</option>
                                    <option value="Technical" style="color: #0014f4" {{ old('category_name') == 'Technical' ? 'selected' : '' }}>Technical</option>
                                    <option value="Billing" style="color: #2b9900" {{ old('category_name') == 'Billing' ? 'selected' : '' }}>Billing</option>
                                    <option value="Customer Service" style="color: #7e0099" {{ old('category_name') == 'Customer Service' ? 'selected' : '' }}>Customer Service</option>
                                </select>
                                @error('category_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="priority_name" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.priority') }}{{ trans('ticketit::lang.colon') }}
                            </label>
                            <div class="col-md-8">
                                <select name="priority_name" 
                                        class="form-control @error('priority_name') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Priority</option>
                                    <option value="Low" style="color: #069900" {{ old('priority_name') == 'Low' ? 'selected' : '' }}>Low</option>
                                    <option value="Medium" style="color: #e1d200" {{ old('priority_name') == 'Medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="High" style="color: #e10000" {{ old('priority_name') == 'High' ? 'selected' : '' }}>High</option>
                                </select>
                                @error('priority_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="content" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.message') }}{{ trans('ticketit::lang.colon') }}
                            </label>
                            <div class="col-md-8">
                                <textarea class="form-control @error('content') is-invalid @enderror" 
                                          name="content" 
                                          rows="5" 
                                          required>{{ old('content') }}</textarea>
                                @error('content')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-3">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    {{ trans('ticketit::lang.btn-submit') }}
                                </button>
                                <a href="{{ route('customer.dashboard') }}" class="btn btn-link">
                                    {{ trans('ticketit::lang.btn-cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('ticketForm').addEventListener('submit', function(e) {
    console.log('Form submitted');
    var submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Submitting...';
});
</script>
@endpush

@section('styles')
<style>
.card {
    margin-bottom: 1rem;
}
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.invalid-feedback {
    display: block;
}
.btn-submit:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}
</style>
@endsection
@endsection