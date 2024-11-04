@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ trans('ticketit::lang.create-new-ticket') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('customer.tickets.store') }}">
                        @csrf

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
                                <button type="submit" class="btn btn-primary">
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
@endsection