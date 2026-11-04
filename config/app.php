<?php
/**
 * Application Configuration
 * Global settings and constants
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Application paths
define('BASE_URL', 'http://localhost/expiry_tracker');
define('BASE_PATH', dirname(__DIR__));

// File upload settings
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/png'
]);

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Pagination
define('RECORDS_PER_PAGE', 20);

// Date formats
define('DATE_FORMAT_DB', 'Y-m-d');
define('DATE_FORMAT_DISPLAY', 'd M Y');
define('DATETIME_FORMAT_DISPLAY', 'd M Y, g:i A');

// Status labels
define('STATUS_LABELS', [
    'expired' => 'Expired',
    'critical' => 'Critical',
    'upcoming' => 'Upcoming',
    'due_soon' => 'Due Soon',
    'active' => 'Active'
]);

// Status colors
define('STATUS_COLORS', [
    'expired' => '#EF4444',
    'critical' => '#DC2626',
    'upcoming' => '#F59E0B',
    'due_soon' => '#3B82F6',
    'active' => '#10B981'
]);

// Include database connection
require_once BASE_PATH . '/config/database.php';
?>
