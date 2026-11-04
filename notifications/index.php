<?php
/**
 * Notifications Page
 * View and manage notifications
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Notifications';
$page_subtitle = 'View your notifications';

// Mark all as read
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == '1') {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        setFlashMessage('success', 'All notifications marked as read.');
        header('Location: ' . BASE_URL . '/notifications/');
        exit;
    } catch (PDOException $e) {
        error_log("Mark all read error: " . $e->getMessage());
    }
}

try {
    // Get notifications
    $stmt = $pdo->prepare("
        SELECT n.*, er.title as record_title
        FROM notifications n
        LEFT JOIN expiry_records er ON n.record_id = er.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Notifications error: " . $e->getMessage());
    $notifications = [];
}

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Notifications</h3>
            <?php if (!empty($notifications)): ?>
            <a href="?mark_all_read=1" class="bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 px-3 py-1 rounded text-sm hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">Mark All as Read</a>
            <?php endif; ?>
        </div>
        <div class="p-6">
            <?php if (empty($notifications)): ?>
            <div class="text-center py-8">
                <p class="text-gray-500 dark:text-slate-400">No notifications</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($notifications as $notification): ?>
                <div class="p-4 rounded-lg <?php echo $notification['is_read'] ? 'bg-gray-50 dark:bg-slate-700' : 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500'; ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-medium px-2 py-1 rounded <?php echo $notification['is_read'] ? 'bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-slate-300' : 'bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200'; ?>">
                                    <?php echo ucfirst($notification['type']); ?>
                                </span>
                                <span class="text-xs text-gray-500 dark:text-slate-400">
                                    <?php echo formatDate($notification['created_at'], DATETIME_FORMAT_DISPLAY); ?>
                                </span>
                            </div>
                            <h4 class="font-medium text-gray-800 dark:text-white mb-1"><?php echo htmlspecialchars($notification['title']); ?></h4>
                            <p class="text-gray-600 dark:text-slate-300 text-sm"><?php echo htmlspecialchars($notification['message']); ?></p>
                            
                            <?php if ($notification['record_id']): ?>
                            <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $notification['record_id']; ?>" 
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                                View Record →
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$notification['is_read']): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/api/mark-notification-read.php" class="ml-4">
                            <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 px-3 py-1 rounded text-sm hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors" data-mark-read="<?php echo $notification['id']; ?>">
                                Mark Read
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
