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
    <div id="confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
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
        
        // Check for saved preference or system preference
        if (localStorage.getItem('darkMode') === 'true' || 
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        }
        
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                html.classList.toggle('dark');
                localStorage.setItem('darkMode', html.classList.contains('dark'));
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
