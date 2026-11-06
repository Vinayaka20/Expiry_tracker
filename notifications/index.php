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
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title">Notifications</h3>
            <?php if (!empty($notifications)): ?>
            <a href="?mark_all_read=1" class="text-sm font-semibold text-teal-600 dark:text-teal-400 hover:underline">Mark All as Read</a>
            <?php endif; ?>
        </div>
        <div class="section-body">
            <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <p class="text-theme-2">You're all caught up — no notifications.</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($notifications as $notification): ?>
                <div class="notif-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="notif-badge <?php echo $notification['is_read'] ? 'bg-theme-2 text-theme-2' : 'bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-300'; ?>">
                                <?php echo ucfirst($notification['type']); ?>
                            </span>
                            <span class="text-xs text-theme-3 font-medium">
                                <?php echo formatDate($notification['created_at'], DATETIME_FORMAT_DISPLAY); ?>
                            </span>
                            <?php if (!$notification['is_read']): ?>
                            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                            <?php endif; ?>
                        </div>
                        <h4 class="font-bold text-theme mb-1"><?php echo htmlspecialchars($notification['title']); ?></h4>
                        <p class="text-theme-2 text-sm"><?php echo htmlspecialchars($notification['message']); ?></p>
                        
                        <?php if ($notification['record_id']): ?>
                        <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $notification['record_id']; ?>" 
                            class="text-sm font-semibold text-teal-600 dark:text-teal-400 hover:underline mt-2 inline-block">
                            View Record →
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-shrink-0">
                        <?php if (!$notification['is_read']): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/api/mark-notification-read.php">
                            <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="icon-btn view !w-auto !h-auto px-3 py-1.5 text-xs font-semibold" data-mark-read="<?php echo $notification['id']; ?>">
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
