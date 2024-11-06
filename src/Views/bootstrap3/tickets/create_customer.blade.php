@extends('layouts.app')

@section('content')
@php
    Log::info('Create ticket form rendered:', [
        'route' => Route::currentRouteName(),
        'customer' => auth()->guard('customer')->check() ? [
            'id' => auth()->guard('customer')->id(),
            'name' => auth()->guard('customer')->user()->name
        ] : 'not authenticated',
        'categories' => $categories ?? [],
        'priorities' => $priorities ?? []
    ]);
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create New Support Ticket</h5>
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

                        <div class="form-group row mb-3">
                            <label for="subject" class="col-md-3 col-form-label text-md-right required">
                                Subject
                            </label>
                            <div class="col-md-8">
                                <input type="text" 
                                       class="form-control @error('subject') is-invalid @enderror" 
                                       id="subject"
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

                        <div class="form-group row mb-3">
                            <label for="category_name" class="col-md-3 col-form-label text-md-right required">
                                Category
                            </label>
                            <div class="col-md-8">
                                <select name="category_name" 
                                        id="category_name"
                                        class="form-control @error('category_name') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $key => $category)
                                        <option value="{{ $key }}" 
                                                style="color: {{ $category['color'] }}"
                                                {{ old('category_name') == $key ? 'selected' : '' }}>
                                            {{ $category['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="priority_name" class="col-md-3 col-form-label text-md-right required">
                                Priority
                            </label>
                            <div class="col-md-8">
                                <select name="priority_name" 
                                        id="priority_name"
                                        class="form-control @error('priority_name') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Priority</option>
                                    @foreach($priorities as $key => $priority)
                                        <option value="{{ $key }}" 
                                                style="color: {{ $priority['color'] }}"
                                                {{ old('priority_name') == $key ? 'selected' : '' }}>
                                            {{ $priority['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="content" class="col-md-3 col-form-label text-md-right required">
                                Message
                            </label>
                            <div class="col-md-8">
                                <textarea class="form-control @error('content') is-invalid @enderror" 
                                          id="content"
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
                                    Submit Ticket
                                </button>
                                <a href="{{ route('customer.dashboard') }}" class="btn btn-link">
                                    Cancel
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
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ticketForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
        // Log form data
        const formData = new FormData(this);
        console.log('Form data:', Object.fromEntries(formData));

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
    });
});
</script>
@endpush

@section('styles')
<style>
.card {
    margin-bottom: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.invalid-feedback {
    display: block;
}
.required:after {
    content: ' *';
    color: red;
}
select.form-control option {
    padding: 0.5rem;
}
.btn-submit:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}
</style>
@endsection
@endsection