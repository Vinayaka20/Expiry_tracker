<?php
/**
 * API Endpoint - Mark Notification as Read
 * AJAX endpoint for marking notifications as read
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';

requireLogin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($input['id']) ? intval($input['id']) : 0;

if (!$notification_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit;
}

$user_id = getCurrentUserId();

try {
    // Verify ownership
    $stmt = $pdo->prepare("SELECT user_id FROM notifications WHERE id = ?");
    $stmt->execute([$notification_id]);
    $notification = $stmt->fetch();
    
    if (!$notification || $notification['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Mark as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$notification_id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
