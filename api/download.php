<?php
/**
 * API Endpoint - Download File
 * Secure file download with ownership verification
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify ownership
if (!verifyRecordOwnership($record_id, $user_id)) {
    http_response_code(403);
    die('Access denied');
}

try {
    $stmt = $pdo->prepare("SELECT attachment_path FROM expiry_records WHERE id = ? AND user_id = ?");
    $stmt->execute([$record_id, $user_id]);
    $record = $stmt->fetch();
    
    if (!$record || empty($record['attachment_path'])) {
        http_response_code(404);
        die('File not found');
    }
    
    $file_path = $record['attachment_path'];
    
    // Verify file exists and is within uploads directory
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Verify file is in uploads directory
    $real_path = realpath($file_path);
    $uploads_path = realpath(UPLOAD_DIR);
    
    if ($real_path === false || strpos($real_path, $uploads_path) !== 0) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Get file info
    $file_name = basename($file_path);
    $file_size = filesize($file_path);
    $file_type = mime_content_type($file_path);
    
    // Set headers
    header('Content-Type: ' . $file_type);
    header('Content-Length: ' . $file_size);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Cache-Control: private, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    die('Server error');
}
?>
