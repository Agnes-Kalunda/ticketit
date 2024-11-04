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

                        <div class="form-group row">
                            <label for="subject" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.subject') }}
                            </label>
                            <div class="col-md-8">
                                <input type="text" 
                                       class="form-control @error('subject') is-invalid @enderror" 
                                       name="subject" 
                                       value="{{ old('subject') }}" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="category_id" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.category') }}
                            </label>
                            <div class="col-md-8">
                                <select name="category_id" 
                                        class="form-control @error('category_id') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" 
                                                {{ old('category_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="priority_id" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.priority') }}
                            </label>
                            <div class="col-md-8">
                                <select name="priority_id" 
                                        class="form-control @error('priority_id') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Priority</option>
                                    @foreach($priorities as $id => $name)
                                        <option value="{{ $id }}" 
                                                {{ old('priority_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="content" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.message') }}
                            </label>
                            <div class="col-md-8">
                                <textarea class="form-control @error('content') is-invalid @enderror" 
                                          name="content" 
                                          rows="5" 
                                          required>{{ old('content') }}</textarea>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-3">
                                <button type="submit" class="btn btn-primary">
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
@endsection