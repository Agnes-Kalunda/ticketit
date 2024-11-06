@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ticketForm');
    if (form) {
        // Log initial form state
        console.log('Form found:', {
            action: form.action,
            method: form.method,
            id: form.id,
            exists: !!form
        });

        form.addEventListener('submit', function(e) {
             e.preventDefault(); 
            
            // Collect all form data
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            // Log submission details
            console.log('Form submission details:', {
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
                console.error('CSRF token mismatch:', {
                    metaToken: metaToken,
                    formToken: formToken
                });
            }

            // Log headers
            const headers = {};
            document.querySelectorAll('meta').forEach(meta => {
                if (meta.name) {
                    headers[meta.name] = meta.content;
                }
            });
            console.log('Available headers:', headers);

            // Log button state
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                console.log('Submit button state:', {
                    disabled: submitBtn.disabled,
                    text: submitBtn.innerText,
                    type: submitBtn.type
                });
            }
        });

        // Monitor input changes
        form.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', function(e) {
                console.log('Form field changed:', {
                    name: this.name,
                    value: this.value,
                    type: this.type,
                    valid: this.checkValidity()
                });
            });
        });
    } else {
        console.error('Ticket form not found! Check form ID');
    }
});
</script>
@endpush

@push('styles')
<style>
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
    display: none;
}
</style>
@endpush