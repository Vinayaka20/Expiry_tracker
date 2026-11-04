/**
 * LifeLeft - Main JavaScript
 * Handles interactivity, toasts, modals, and AJAX requests
 */

// Toast Notification System
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Confirmation Modal
function confirmAction(message, callback) {
    const modal = document.getElementById('confirm-modal');
    const modalMessage = document.getElementById('modal-message');
    const confirmBtn = document.getElementById('modal-confirm');
    const cancelBtn = document.getElementById('modal-cancel');
    
    if (!modal || !modalMessage || !confirmBtn || !cancelBtn) return;
    
    modalMessage.textContent = message;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    confirmBtn.onclick = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        callback();
    };
    
    cancelBtn.onclick = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };
}

// AJAX Helper
async function ajaxRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, mergedOptions);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('AJAX Error:', error);
        showToast('An error occurred. Please try again.', 'error');
        return null;
    }
}

// Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('border-red-500');
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    return isValid;
}

// Date Format Helper
function formatDate(dateString, format = 'd M Y') {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    };
    return date.toLocaleDateString('en-IN', options);
}

// Calculate Days Remaining
function getDaysRemaining(expiryDate) {
    const today = new Date();
    const expiry = new Date(expiryDate);
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

// Get Status from Days
function getStatusFromDays(days) {
    if (days < 0) return 'expired';
    if (days <= 3) return 'critical';
    if (days <= 7) return 'upcoming';
    if (days <= 30) return 'due_soon';
    return 'active';
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all forms with validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                showToast('Please fill in all required fields.', 'error');
            }
        });
    });
    
    // Initialize delete buttons
    const deleteButtons = document.querySelectorAll('[data-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.dataset.delete || 'Are you sure you want to delete this item?';
            const url = this.href;
            
            confirmAction(message, () => {
                window.location.href = url;
            });
        });
    });
    
    // Initialize search filter
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const query = this.value;
                const url = new URL(window.location);
                url.searchParams.set('search', query);
                window.location.href = url.toString();
            }, 500);
        });
    }
    
    // Initialize category filter
    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('category', this.value);
            window.location.href = url.toString();
        });
    }
    
    // Initialize status filter
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('status', this.value);
            window.location.href = url.toString();
        });
    }
    
    // Initialize sort
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('sort', this.value);
            window.location.href = url.toString();
        });
    }
    
    // Mark notifications as read
    const markReadButtons = document.querySelectorAll('[data-mark-read]');
    markReadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationId = this.dataset.markRead;
            
            fetch('/api/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('.notification-item').classList.remove('unread');
                    this.remove();
                    
                    // Update badge count
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.remove();
                        }
                    }
                }
            });
        });
    });
    
    // File upload preview
    const fileInput = document.getElementById('file-upload');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    showToast('File size exceeds 5MB limit.', 'error');
                    this.value = '';
                    return;
                }
                
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Invalid file type. Only PDF, JPG, and PNG allowed.', 'error');
                    this.value = '';
                    return;
                }
            }
        });
    }
    
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('aside');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
});

// Export functions for use in other scripts
window.LifeLeft = {
    showToast,
    confirmAction,
    ajaxRequest,
    validateForm,
    formatDate,
    getDaysRemaining,
    getStatusFromDays
};
