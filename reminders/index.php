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
    <div class="section-card mb-6">
        <div class="section-header">
            <h3 class="section-title">Reminder Preferences</h3>
        </div>
        <div class="section-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-6">
                    <label class="form-label">Default Reminder Days</label>
                    <input type="number" name="default_reminder_days" 
                        value="<?php echo htmlspecialchars($settings['default_reminder_days'] ?? 7); ?>" 
                        class="form-control w-full sm:w-40" min="1" max="365">
                    <p class="text-xs text-theme-2 mt-1.5">Default number of days before expiry to send reminders</p>
                </div>
                
                <div class="border-t border-theme pt-6">
                    <h4 class="font-bold text-theme mb-1">Automatic Reminders</h4>
                    <p class="text-sm text-theme-2 mb-5">Choose which reminders you want to receive before an item expires:</p>
                    
                    <div class="space-y-3">
                        <?php
                        $reminder_options = [
                            'reminder_30_days' => ['30 days before expiry', 'reminder_30_days'],
                            'reminder_15_days' => ['15 days before expiry', 'reminder_15_days'],
                            'reminder_7_days'  => ['7 days before expiry',  'reminder_7_days'],
                            'reminder_3_days'  => ['3 days before expiry',  'reminder_3_days'],
                            'reminder_1_day'   => ['1 day before expiry',   'reminder_1_day'],
                        ];
                        foreach ($reminder_options as $name => $opt):
                            $checked = ($settings[$name] ?? 1) ? 'checked' : '';
                        ?>
                        <label class="flex items-center justify-between p-3.5 rounded-xl cursor-pointer transition-all hover:bg-theme-2" style="border: 1px solid var(--border);">
                            <span class="text-theme font-medium"><?php echo $opt[0]; ?></span>
                            <span class="toggle-switch">
                                <input type="checkbox" name="<?php echo $name; ?>" <?php echo $checked; ?>>
                                <span class="toggle-slider"></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-xl mt-6">Save Settings</button>
            </form>
        </div>
    </div>
    
    <!-- Reminder Processing Info -->
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title">Reminder Processing</h3>
        </div>
        <div class="section-body">
            <p class="text-theme-2 mb-4">
                Reminders are processed automatically. To enable automatic reminder processing on Windows:
            </p>
            <ol class="list-decimal list-inside text-theme-2 space-y-2">
                <li>Open Windows Task Scheduler</li>
                <li>Create a new task to run PHP</li>
                <li>Set the trigger to run daily</li>
                <li>Set the action to run: <code class="bg-theme-2 px-2 py-1 rounded-lg text-theme text-xs font-mono">php "<?php echo BASE_PATH; ?>/reminders/process.php"</code></li>
            </ol>
            <p class="text-sm text-theme-3 mt-4">
                Note: Email notifications require SMTP configuration in Settings.
            </p>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
