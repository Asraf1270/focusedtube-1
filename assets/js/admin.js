/**
 * FocusedTube - Admin JavaScript
 * 
 * Admin panel functionality
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (overlay) {
                overlay.classList.toggle('active');
            }
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
    
    // Close sidebar on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('open');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    });
    
    // Handle delete confirmations
    document.querySelectorAll('.delete-confirm').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                window.location.href = this.href;
            }
        });
    });
    
    // Handle bulk actions
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }
    
    // Handle form validation
    document.querySelectorAll('.validate-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const required = form.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    valid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Handle image preview
    document.querySelectorAll('.image-input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                const preview = document.querySelector(this.dataset.preview);
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Handle toast notifications auto-dismiss
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.classList.add('fade-out');
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

/**
 * Toggle admin theme
 */
function toggleAdminTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    document.cookie = `admin_theme=${newTheme}; path=/; max-age=31536000`;
    
    // Update toggle button
    const toggle = document.querySelector('.theme-toggle');
    if (toggle) {
        toggle.textContent = newTheme === 'dark' ? '🌙' : '☀️';
    }
}

/**
 * Open modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Submit form via AJAX
 */
function submitFormAjax(formId, callback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    const url = form.action || window.location.href;
    const method = form.method || 'POST';
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (callback) {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showToast('error', 'Failed to submit form');
    });
}

/**
 * Show toast notification
 */
function showToast(type, message) {
    const container = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span>${message}</span>
        <button class="toast-close" onclick="this.closest('.toast').remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

/**
 * Create toast container
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

/**
 * Export data to CSV
 */
function exportToCSV(data, filename) {
    const csv = data.map(row => Object.values(row).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/**
 * Print current page
 */
function printPage() {
    window.print();
}