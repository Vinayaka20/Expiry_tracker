<?php
/**
 * Reminders Page
 * View and manage reminder settings
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Reminders';
$page_subtitle = 'Configure reminder preferences';

// Get user settings
$settings = getUserSettings($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reminder_30 = isset($_POST['reminder_30_days']) ? 1 : 0;
    $reminder_15 = isset($_POST['reminder_15_days']) ? 1 : 0;
    $reminder_7 = isset($_POST['reminder_7_days']) ? 1 : 0;
    $reminder_3 = isset($_POST['reminder_3_days']) ? 1 : 0;
    $reminder_1 = isset($_POST['reminder_1_day']) ? 1 : 0;
    $default_reminder_days = intval($_POST['default_reminder_days'] ?? 7);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE user_settings 
                SET reminder_30_days = ?, reminder_15_days = ?, reminder_7_days = ?, 
                    reminder_3_days = ?, reminder_1_day = ?, default_reminder_days = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $reminder_30,
                $reminder_15,
                $reminder_7,
                $reminder_3,
                $reminder_1,
                $default_reminder_days,
                $user_id
            ]);
            
            setFlashMessage('success', 'Reminder settings updated successfully!');
            header('Location: ' . BASE_URL . '/reminders/');
            exit;
            
        } catch (PDOException $e) {
            error_log("Update reminder settings error: " . $e->getMessage());
            setFlashMessage('error', 'Failed to update settings.');
        }
    }
}

$csrf_token = generateCsrfToken();

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 -mx-6 -mt-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Reminder Preferences</h3>
        </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Default Reminder Days</label>
                    <input type="number" name="default_reminder_days" 
                        value="<?php echo htmlspecialchars($settings['default_reminder_days'] ?? 7); ?>" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" min="1" max="365">
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Default number of days before expiry to send reminders</p>
                </div>
                
                <div class="border-t border-gray-200 dark:border-slate-700 pt-6 mt-6">
                    <h4 class="font-medium text-gray-800 dark:text-white mb-4">Automatic Reminders</h4>
                    <p class="text-sm text-gray-500 dark:text-slate-400 mb-4">Select which automatic reminders you want to receive:</p>
                    
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="reminder_30_days" 
                                <?php echo ($settings['reminder_30_days'] ?? 1) ? 'checked' : ''; ?> 
                                class="w-4 h-4 text-blue-600 border-gray-300 dark:border-slate-600 rounded focus:ring-blue-500 dark:bg-slate-700">
                            <span class="ml-2 text-gray-700 dark:text-slate-300">30 days before expiry</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="reminder_15_days" 
                                <?php echo ($settings['reminder_15_days'] ?? 1) ? 'checked' : ''; ?> 
                                class="w-4 h-4 text-blue-600 border-gray-300 dark:border-slate-600 rounded focus:ring-blue-500 dark:bg-slate-700">
                            <span class="ml-2 text-gray-700 dark:text-slate-300">15 days before expiry</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="reminder_7_days" 
                                <?php echo ($settings['reminder_7_days'] ?? 1) ? 'checked' : ''; ?> 
                                class="w-4 h-4 text-blue-600 border-gray-300 dark:border-slate-600 rounded focus:ring-blue-500 dark:bg-slate-700">
                            <span class="ml-2 text-gray-700 dark:text-slate-300">7 days before expiry</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="reminder_3_days" 
                                <?php echo ($settings['reminder_3_days'] ?? 1) ? 'checked' : ''; ?> 
                                class="w-4 h-4 text-blue-600 border-gray-300 dark:border-slate-600 rounded focus:ring-blue-500 dark:bg-slate-700">
                            <span class="ml-2 text-gray-700 dark:text-slate-300">3 days before expiry</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="reminder_1_day" 
                                <?php echo ($settings['reminder_1_day'] ?? 1) ? 'checked' : ''; ?> 
                                class="w-4 h-4 text-blue-600 border-gray-300 dark:border-slate-600 rounded focus:ring-blue-500 dark:bg-slate-700">
                            <span class="ml-2 text-gray-700 dark:text-slate-300">1 day before expiry</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors mt-6">Save Settings</button>
            </form>
    </div>
    
    <!-- Reminder Processing Info -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Reminder Processing</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-600 dark:text-slate-300 mb-4">
                Reminders are processed automatically. To enable automatic reminder processing on Windows:
            </p>
            <ol class="list-decimal list-inside text-gray-600 dark:text-slate-300 space-y-2">
                <li>Open Windows Task Scheduler</li>
                <li>Create a new task to run PHP</li>
                <li>Set the trigger to run daily</li>
                <li>Set the action to run: <code class="bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded text-gray-800 dark:text-slate-200">php "<?php echo BASE_PATH; ?>/reminders/process.php"</code></li>
            </ol>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-4">
                Note: Email notifications require SMTP configuration in Settings.
            </p>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
