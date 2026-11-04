<?php
/**
 * Authentication Functions
 * Session management, login checks, and user verification
 */

require_once BASE_PATH . '/config/app.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require user to be logged in
 * Redirects to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, email, full_name, phone, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

/**
 * Verify user owns a record
 */
function verifyRecordOwnership($record_id, $user_id = null) {
    if ($user_id === null) {
        $user_id = getCurrentUserId();
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM expiry_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch();
        
        return $record && $record['user_id'] == $user_id;
    } catch (PDOException $e) {
        error_log("Ownership verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
        return false;
    }
    return true;
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
?>
