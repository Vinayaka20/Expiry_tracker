<?php
/**
 * LifeLeft - Main Entry Point
 * Redirects to login or dashboard based on authentication status
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/');
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit;
?>
