@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Submit New Ticket') }}</div>

                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('customer.tickets.store') }}" id="ticketForm">
                        @csrf

                        <div class="form-group row">
                            <label for="subject" class="col-md-4 col-form-label text-md-right">{{ __('Subject') }}</label>
                            <div class="col-md-6">
                                <input id="subject" type="text" 
                                       class="form-control @error('subject') is-invalid @enderror" 
                                       name="subject" value="{{ old('subject') }}" 
                                       required autofocus>
                                @error('subject')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="category_name" class="col-md-4 col-form-label text-md-right">{{ __('Category') }}</label>
                            <div class="col-md-6">
                                <select id="category_name" 
                                        name="category_name" 
                                        class="form-control @error('category_name') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Category</option>
                                    <option value="Technical">Technical Support</option>
                                    <option value="Billing">Billing</option>
                                    <option value="Customer Service">Customer Service</option>
                                </select>
                                @error('category_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="priority_name" class="col-md-4 col-form-label text-md-right">{{ __('Priority') }}</label>
                            <div class="col-md-6">
                                <select id="priority_name" 
                                        name="priority_name" 
                                        class="form-control @error('priority_name') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Priority</option>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                                @error('priority_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="content" class="col-md-4 col-form-label text-md-right">{{ __('Message') }}</label>
                            <div class="col-md-6">
                                <textarea id="content" 
                                          class="form-control @error('content') is-invalid @enderror" 
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
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    {{ __('Submit Ticket') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    @if(config('app.debug'))
                        <div class="form-debug">
                            <pre id="debugOutput"></pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    margin-top: 2rem;
}
.invalid-feedback {
    display: block;
}
.form-debug {
    position: fixed;
    bottom: 10px;
    right: 10px;
    background: #f8f9fa;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 12px;
    z-index: 9999;
    max-height: 300px;
    overflow-y: auto;
    display: none;
}
.debug-toggle {
    position: fixed;
    bottom: 10px;
    right: 10px;
    z-index: 10000;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ticketForm');
    const debugOutput = document.getElementById('debugOutput');
    
   
    if (debugOutput) {
        const debugToggle = document.createElement('button');
        debugToggle.className = 'btn btn-sm btn-info debug-toggle';
        debugToggle.innerText = 'Toggle Debug';
        debugToggle.onclick = function() {
            const debugPanel = document.querySelector('.form-debug');
            debugPanel.style.display = debugPanel.style.display === 'none' ? 'block' : 'none';
        };
        document.body.appendChild(debugToggle);
    }

    function logDebug(msg, data) {
        console.log(msg, data);
        if (debugOutput) {
            const existing = debugOutput.innerHTML;
            debugOutput.innerHTML = `${new Date().toISOString()} - ${msg}\n${JSON.stringify(data, null, 2)}\n\n${existing}`;
        }
    }

    if (form) {
        // Log initial form state
        logDebug('Form initialized', {
            action: form.action,
            method: form.method,
            id: form.id,
            exists: !!form,
            csrf: document.querySelector('meta[name="csrf-token"]')?.content
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect form data
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            logDebug('Form submission', {
                timestamp: new Date().toISOString(),
                url: window.location.href,
                action: this.action,
                method: this.method,
                formData: data,
                auth: {
                    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content,
                    formToken: formData.get('_token')
                }
            });

            // Check CSRF token
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const formToken = formData.get('_token');
            
            if (metaToken !== formToken) {
                logDebug('CSRF token mismatch', {
                    metaToken: metaToken,
                    formToken: formToken
                });
            }

            // Submit the form after debug logging
            this.submit();
        });

        // Monitor input changes
        form.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', function(e) {
                logDebug('Field changed', {
                    name: this.name,
                    value: this.value,
                    type: this.type,
                    valid: this.checkValidity(),
                    validationMessage: this.validationMessage
                });
            });
        });
    } else {
        logDebug('Form not found', {
            message: 'Ticket form not found! Check form ID',
            location: window.location.href
        });
    }

    // Log page load complete
    logDebug('Page load complete', {
        url: window.location.href,
        timestamp: new Date().toISOString()
    });
});
</script>
@endpush