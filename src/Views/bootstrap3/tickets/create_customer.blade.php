@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    {{ trans('ticketit::lang.create-ticket-title') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('customer.tickets.store') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="subject" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.subject') }}{{ trans('ticketit::lang.colon') }}
                            </label>
                            <div class="col-md-8">
                                <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                       name="subject" value="{{ old('subject') }}" required>
                                <small class="form-text text-muted">
                                    {{ trans('ticketit::lang.create-ticket-brief-issue') }}
                                </small>
                                @error('subject')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="category_id" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.category') }}{{ trans('ticketit::lang.colon') }}
                            </label>
                            <div class="col-md-8">
                                <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                                    <option value="">{{ trans('ticketit::lang.select-category') }}</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="priority_id" class="col-md-3 col-form-label text-md-right">
                                {{ trans('ticketit::lang.priority') }}{{ trans('ticketit::lang.colon') }}
                            </label>
                            <div class="col-md-8">
                                <select name="priority_id" class="form-control @error('priority_id') is-invalid @enderror" required>
                                    <option value="">{{ trans('ticketit::lang.select-priority') }}</option>
                                    @foreach($priorities as $id => $name)
                                        <option value="{{ $id }}" {{ old('priority_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority_id')
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
                                          name="content" rows="5" required>{{ old('content') }}</textarea>
                                <small class="form-text text-muted">
                                    {{ trans('ticketit::lang.create-ticket-describe-issue') }}
                                </small>
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