// assets/js/main.js

// Global variables
let currentUser = null;
let notifications = [];

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initDataTables();
    loadNotifications();
    checkSession();
});

// Initialize tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
}

// Initialize DataTables
function initDataTables() {
    const tables = document.querySelectorAll('.datatable');
    tables.forEach(table => {
        if ($.fn.DataTable.isDataTable(table)) {
            $(table).DataTable().destroy();
        }
        $(table).DataTable({
            pageLength: 10,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No matching records found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });
}

// Load notifications
function loadNotifications() {
    fetch('../includes/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            notifications = data;
            updateNotificationBadge();
        })
        .catch(error => console.error('Error loading notifications:', error));
}

// Update notification badge
function updateNotificationBadge() {
    const unreadCount = notifications.filter(n => !n.read).length;
    const badges = document.querySelectorAll('.notification-badge');
    badges.forEach(badge => {
        badge.textContent = unreadCount;
        badge.style.display = unreadCount > 0 ? 'block' : 'none';
    });
}

// Check session
function checkSession() {
    fetch('../includes/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.loggedIn) {
                window.location.href = '../login.php';
            }
        });
}

// Format date
function formatDate(dateString, format = 'YYYY-MM-DD') {
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };
    return date.toLocaleDateString('en-US', options);
}

// Show toast notification
function showToast(message, type = 'success', duration = 3000) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: duration });
    bsToast.show();
    
    setTimeout(() => {
        toast.remove();
    }, duration + 1000);
}

// Create toast container
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Confirm action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print table
function printTable(tableId, title) {
    const table = document.getElementById(tableId);
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>${title || 'Print'}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { padding: 20px; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <h2 class="mb-4">${title || 'Report'}</h2>
                ${table.outerHTML}
                <div class="no-print text-center mt-4">
                    <button class="btn btn-primary" onclick="window.print()">Print</button>
                    <button class="btn btn-secondary" onclick="window.close()">Close</button>
                </div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
        
        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
        
        // Phone validation
        if (input.name === 'phone' && input.value) {
            const phoneRegex = /^[\d\s-+()]{10,}$/;
            if (!phoneRegex.test(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 1;
    if (password.match(/[a-z]+/)) strength += 1;
    if (password.match(/[A-Z]+/)) strength += 1;
    if (password.match(/[0-9]+/)) strength += 1;
    if (password.match(/[$@#&!]+/)) strength += 1;
    
    return strength;
}

// Auto logout after inactivity
let inactivityTime = function() {
    let time;
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeypress = resetTimer;
    document.onscroll = resetTimer;
    
    function logout() {
        alert('You have been logged out due to inactivity.');
        window.location.href = '../logout.php';
    }
    
    function resetTimer() {
        clearTimeout(time);
        time = setTimeout(logout, 1800000); // 30 minutes
    }
};

// Initialize inactivity timer
inactivityTime();