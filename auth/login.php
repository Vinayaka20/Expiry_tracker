<?php
/**
 * Login Page
 * User authentication
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate CSRF token
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Validate inputs
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id, email, password, full_name, is_active FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_active']) {
                    $errors[] = 'Your account has been deactivated.';
                } else {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Redirect to dashboard or intended page
                    $redirect_url = $_SESSION['redirect_url'] ?? BASE_URL . '/dashboard/';
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirect_url);
                    exit;
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Login failed. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

$csrf_token = generateCsrfToken();
$page_title = 'Login';
?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-md mx-auto">
    <div class="auth-glass-card p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Welcome Back</h2>
        
        <?php if (!empty($errors)): ?>
        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                    class="auth-input w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white/80"
                    required autofocus>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" 
                    class="auth-input w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white/80"
                    required>
            </div>
            
            <div class="mb-6 flex items-center">
                <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
            </div>
            
            <button type="submit" class="btn-gradient w-full text-white py-3 rounded-xl font-medium">
                Login
            </button>
        </form>
        
        <p class="text-center text-gray-600 mt-6">
            Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-teal-600 hover:text-teal-700 font-medium hover:underline">Register here</a>
        </p>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
