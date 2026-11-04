<?php
/**
 * Logout Page
 * Destroy session and redirect to login
 */

require_once dirname(__DIR__) . '/config/app.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
?>
