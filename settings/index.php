<?php
/**
 * Settings Page
 * User profile and application settings
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Settings';
$page_subtitle = 'Manage your account and preferences';

// Get user data
try {
    $stmt = $pdo->prepare("SELECT id, email, full_name, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $settings = getUserSettings($user_id);
    
} catch (PDOException $e) {
    error_log("Settings error: " . $e->getMessage());
    setFlashMessage('error', 'Failed to load settings.');
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

$errors = [];
$active_tab = $_GET['tab'] ?? 'profile';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = 'Invalid security token.';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $user_id]);
            
            setFlashMessage('success', 'Profile updated successfully!');
            header('Location: ' . BASE_URL . '/settings/');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Failed to update profile.';
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = 'Invalid security token.';
    }
    
    if (empty($current_password)) {
        $errors[] = 'Current password is required.';
    }
    
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if (!password_verify($current_password, $user_data['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $user_id]);
                
                setFlashMessage('success', 'Password changed successfully!');
                header('Location: ' . BASE_URL . '/settings/?tab=password');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to change password.';
            error_log("Password change error: " . $e->getMessage());
        }
    }
}

// Handle notification settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'notifications') {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = 'Invalid security token.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE user_settings SET email_notifications = ? WHERE user_id = ?");
            $stmt->execute([$email_notifications, $user_id]);
            
            setFlashMessage('success', 'Notification settings updated!');
            header('Location: ' . BASE_URL . '/settings/?tab=notifications');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Failed to update settings.';
            error_log("Notification settings error: " . $e->getMessage());
        }
    }
}

$csrf_token = generateCsrfToken();

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <!-- Tabs -->
    <div class="flex gap-4 mb-6 border-b">
        <a href="?tab=profile" class="pb-4 px-4 font-medium <?php echo $active_tab == 'profile' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-gray-800'; ?>">
            Profile
        </a>
        <a href="?tab=password" class="pb-4 px-4 font-medium <?php echo $active_tab == 'password' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-gray-800'; ?>">
            Password
        </a>
        <a href="?tab=notifications" class="pb-4 px-4 font-medium <?php echo $active_tab == 'notifications' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-gray-800'; ?>">
            Notifications
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-error mb-6">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($active_tab == 'profile'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Profile Information</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="profile">
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                        class="form-control" disabled>
                    <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                        class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                        class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
    
    <?php elseif ($active_tab == 'password'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Change Password</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="password">
                
                <div class="form-group">
                    <label class="form-label">Current Password *</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <input type="password" name="new_password" class="form-control" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
    
    <?php elseif ($active_tab == 'notifications'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Notification Settings</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="notifications">
                
                <div class="form-group">
                    <label class="flex items-center">
                        <input type="checkbox" name="email_notifications" 
                            <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?> 
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-gray-700">Enable Email Notifications</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-6">
                        Note: Email notifications require SMTP configuration (not yet implemented)
                    </p>
                </div>
                
                <button type="submit" class="btn btn-primary mt-4">Save Settings</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
