@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create New Ticket</h5>
                    <a href="{{ route('customer.tickets.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
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

                    <form method="POST" action="{{ route('customer.tickets.store') }}">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="subject">Subject</label>
                            <input type="text" 
                                   class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" 
                                   name="subject" 
                                   value="{{ old('subject') }}" 
                                   required>
                            @error('subject')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="category_name">Category</label>
                            <select class="form-control @error('category_name') is-invalid @enderror" 
                                    id="category_name" 
                                    name="category_name" 
                                    required>
                                <option value="">Select Category</option>
                                <option value="Technical" {{ old('category_name') == 'Technical' ? 'selected' : '' }}>
                                    Technical
                                </option>
                                <option value="Billing" {{ old('category_name') == 'Billing' ? 'selected' : '' }}>
                                    Billing
                                </option>
                                <option value="Customer Service" {{ old('category_name') == 'Customer Service' ? 'selected' : '' }}>
                                    Customer Service
                                </option>
                            </select>
                            @error('category_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="priority_name">Priority</label>
                            <select class="form-control @error('priority_name') is-invalid @enderror" 
                                    id="priority_name" 
                                    name="priority_name" 
                                    required>
                                <option value="">Select Priority</option>
                                <option value="Low" {{ old('priority_name') == 'Low' ? 'selected' : '' }}>Low</option>
                                <option value="Medium" {{ old('priority_name') == 'Medium' ? 'selected' : '' }}>Medium</option>
                                <option value="High" {{ old('priority_name') == 'High' ? 'selected' : '' }}>High</option>
                            </select>
                            @error('priority_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="content">Message</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" 
                                      name="content" 
                                      rows="5" 
                                      required>{{ old('content') }}</textarea>
                            @error('content')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                Submit Ticket
                            </button>
                            <a href="{{ route('customer.tickets.index') }}" class="btn btn-link">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection