<?php
// includes/footer.php
?>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <script>
        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', 
                document.getElementById('sidebar').classList.contains('collapsed'));
        }
        
        // Load sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            }
            
            // Initialize DataTables
            $('.datatable').DataTable({
                pageLength: 10,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search..."
                }
            });
        });
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.style.zIndex = '9999';
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // Confirm dialog
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
    </script>
</body>
</html>