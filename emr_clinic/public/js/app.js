// EMR Clinic System JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Search functionality for patient tables
    const searchInputs = document.querySelectorAll('.patient-search');
    searchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = this.closest('.card').querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Dynamic form fields (for lab tests, etc.)
    const addFieldButtons = document.querySelectorAll('.add-field');
    addFieldButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const container = this.closest('.field-container');
            const template = container.querySelector('.field-template');
            const newField = template.cloneNode(true);
            newField.classList.remove('field-template');
            newField.style.display = 'block';
            
            // Clear the new field's value
            newField.querySelectorAll('input, textarea, select').forEach(function(input) {
                input.value = '';
            });
            
            container.appendChild(newField);
        });
    });

    // Remove field functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-field')) {
            e.target.closest('.field-item').remove();
        }
    });

    // File upload preview
    const fileInputs = document.querySelectorAll('.file-upload');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const preview = this.parentElement.querySelector('.file-preview');
            
            if (file && preview) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px;">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = `<div class="alert alert-info">File: ${file.name}</div>`;
                }
            }
        });
    });

    // Print functionality
    const printButtons = document.querySelectorAll('.print-btn');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Auto-save form data (localStorage)
    const autoSaveForms = document.querySelectorAll('.auto-save');
    autoSaveForms.forEach(function(form) {
        const formId = form.id || 'form-' + Math.random().toString(36).substr(2, 9);
        
        // Load saved data
        const savedData = localStorage.getItem('form-' + formId);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(function(key) {
                const input = form.querySelector('[name="' + key + '"]');
                if (input) {
                    input.value = data[key];
                }
            });
        }
        
        // Save data on input change
        form.addEventListener('input', function() {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem('form-' + formId, JSON.stringify(data));
        });
        
        // Clear saved data on successful submit
        form.addEventListener('submit', function() {
            localStorage.removeItem('form-' + formId);
        });
    });

    // Real-time notifications (if WebSocket is available)
    if (typeof WebSocket !== 'undefined') {
        // This would be implemented for real-time notifications
        console.log('WebSocket support available for real-time features');
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N for new patient
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            const newPatientLink = document.querySelector('a[href*="add_patient.php"]');
            if (newPatientLink) {
                window.location.href = newPatientLink.href;
            }
        }
        
        // Ctrl/Cmd + S for save (if in a form)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const activeForm = document.querySelector('form:focus-within');
            if (activeForm) {
                e.preventDefault();
                activeForm.submit();
            }
        }
    });

    // Responsive table wrapper
    const tables = document.querySelectorAll('.table-responsive');
    tables.forEach(function(table) {
        if (table.scrollWidth > table.clientWidth) {
            table.style.overflowX = 'auto';
        }
    });
});

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
} 