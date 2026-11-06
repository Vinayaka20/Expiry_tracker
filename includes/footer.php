<?php
/**
 * Footer Component
 * Closes HTML tags and includes JavaScript
 */

if (isLoggedIn()): ?>
            </div>
        </main>
    </div>
    
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50"></div>
    
    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="glass-modal rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2" id="modal-title">Confirm Action</h3>
            <p class="text-gray-600 dark:text-slate-300 mb-6" id="modal-message">Are you sure you want to proceed?</p>
            <div class="flex justify-end gap-3">
                <button id="modal-cancel" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">Cancel</button>
                <button id="modal-confirm" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Confirm</button>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;

        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                html.classList.toggle('dark');
                localStorage.setItem('darkMode', html.classList.contains('dark'));
            });
        }

        // Sidebar: mobile drawer + desktop collapse
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const mobileToggle = document.getElementById('sidebarToggle');
        const collapseBtn = document.getElementById('sidebarCollapse');

        function closeMobileSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        function openMobileSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', () => {
                if (sidebar.classList.contains('-translate-x-full')) {
                    openMobileSidebar();
                } else {
                    closeMobileSidebar();
                }
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        // Close drawer when a nav link is clicked (mobile)
        if (sidebar) {
            sidebar.querySelectorAll('nav a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 1024) closeMobileSidebar();
                });
            });
        }

        // Desktop collapse (icon rail), persisted
        if (collapseBtn) {
            if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth >= 1024) {
                document.body.classList.add('sidebar-collapsed');
            }
            collapseBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
            });
        }
    </script>
</body>
</html>
<?php else: ?>
        </div>
    </div>
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
<?php endif; ?>
