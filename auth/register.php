<?php
/**
 * Registration Page
 * User registration with validation
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Invalid email format.';
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Validate full name
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered.';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$email, $password_hash, $full_name, $phone]);
                
                // Create default settings
                $user_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

$csrf_token = generateCsrfToken();
$page_title = 'Register';
?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-md mx-auto">
    <div class="auth-glass-card p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Account</h2>
        
        <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6">
            Registration successful! <a href="<?php echo BASE_URL; ?>/auth/login.php" class="font-semibold underline">Login here</a>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" 
                    class="auth-input w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white/80"
                    required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                    class="auth-input w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white/80"
                    required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Phone (Optional)</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                    class="auth-input w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white/80">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" 
                    class="auth-input w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white/80"
                    required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" 
                    class="auth-input w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white/80"
                    required>
            </div>
            
            <button type="submit" class="btn-gradient w-full text-white py-3 rounded-xl font-medium">
                Register
            </button>
        </form>
        
        <p class="text-center text-gray-600 mt-6">
            Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-teal-600 hover:text-teal-700 font-medium hover:underline">Login here</a>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
