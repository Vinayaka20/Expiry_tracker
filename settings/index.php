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
    <div class="settings-tabs mb-6">
        <a href="?tab=profile" class="settings-tab <?php echo $active_tab == 'profile' ? 'active' : ''; ?>">Profile</a>
        <a href="?tab=password" class="settings-tab <?php echo $active_tab == 'password' ? 'active' : ''; ?>">Password</a>
        <a href="?tab=notifications" class="settings-tab <?php echo $active_tab == 'notifications' ? 'active' : ''; ?>">Notifications</a>
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
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title">Profile Information</h3>
        </div>
        <div class="section-body">
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
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title">Change Password</h3>
        </div>
        <div class="section-body">
            <form method="POST" action="" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="password">
                
                <div class="form-group">
                    <label class="form-label">Current Password *</label>
                    <div class="relative">
                        <input type="password" name="current_password" class="form-control w-full pr-11" required>
                        <button type="button" class="pw-toggle absolute right-3 top-1/2 -translate-y-1/2 text-theme-3 hover:text-theme transition-colors" tabindex="-1">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <div class="relative">
                        <input type="password" name="new_password" class="form-control w-full pr-11" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        <button type="button" class="pw-toggle absolute right-3 top-1/2 -translate-y-1/2 text-theme-3 hover:text-theme transition-colors" tabindex="-1">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password *</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" class="form-control w-full pr-11" required>
                        <button type="button" class="pw-toggle absolute right-3 top-1/2 -translate-y-1/2 text-theme-3 hover:text-theme transition-colors" tabindex="-1">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
    
    <?php elseif ($active_tab == 'notifications'): ?>
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title">Notification Settings</h3>
        </div>
        <div class="section-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="notifications">
                
                <div class="form-group">
                    <label class="flex items-center justify-between p-4 rounded-xl cursor-pointer" style="border: 1px solid var(--border); background: var(--surface-2);">
                        <span>
                            <span class="block font-semibold text-theme">Enable Email Notifications</span>
                            <span class="block text-xs text-theme-2 mt-0.5">Note: Email notifications require SMTP configuration (not yet implemented)</span>
                        </span>
                        <span class="toggle-switch ml-4">
                            <input type="checkbox" name="email_notifications" 
                                <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary mt-4">Save Settings</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Password visibility toggles
document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const eyeOpen = this.querySelector('.eye-open');
        const eyeClosed = this.querySelector('.eye-closed');
        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            input.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    });
});
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
