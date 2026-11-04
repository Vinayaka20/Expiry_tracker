<?php
/**
 * Add Record Page
 * Create a new expiry record
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Add Record';
$page_subtitle = 'Add a new expiry record';

$errors = [];
$success = false;

$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $issue_date = $_POST['issue_date'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $reminder_days = intval($_POST['reminder_days'] ?? 7);
    $amount = floatval($_POST['amount'] ?? 0);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = 'Invalid security token.';
    }
    
    // Validate required fields
    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    
    if (empty($category_id)) {
        $errors[] = 'Category is required.';
    }
    
    if (empty($expiry_date)) {
        $errors[] = 'Expiry date is required.';
    } elseif (!strtotime($expiry_date)) {
        $errors[] = 'Invalid expiry date.';
    }
    
    if (!empty($issue_date) && !strtotime($issue_date)) {
        $errors[] = 'Invalid issue date.';
    }
    
    if ($reminder_days < 1 || $reminder_days > 365) {
        $errors[] = 'Reminder days must be between 1 and 365.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO expiry_records 
                (user_id, category_id, title, description, issue_date, expiry_date, reminder_days, amount, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $category_id,
                $title,
                $description,
                $issue_date ?: null,
                $expiry_date,
                $reminder_days,
                $amount,
                $notes
            ]);
            
            $record_id = $pdo->lastInsertId();
            
            // Handle file upload
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadFile($_FILES['attachment'], $user_id, $record_id);
                if (!$upload_result['success']) {
                    setFlashMessage('warning', 'Record created but file upload failed: ' . $upload_result['error']);
                }
            }
            
            // Create reminders
            $stmt = $pdo->prepare("
                INSERT INTO reminders (user_id, record_id, reminder_days, reminder_date)
                VALUES (?, ?, ?, DATE_ADD(?, INTERVAL -? DAY))
            ");
            $stmt->execute([$user_id, $record_id, $reminder_days, $expiry_date, $reminder_days]);
            
            setFlashMessage('success', 'Record added successfully!');
            header('Location: ' . BASE_URL . '/records/view.php?id=' . $record_id);
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Failed to add record. Please try again.';
            error_log("Add record error: " . $e->getMessage());
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Add New Record</h3>
        </div>
        <div class="p-6">
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 p-4 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Title *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" required>
                    </div>
                    
                    <div class="">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Category *</label>
                        <select name="category_id" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo isset($category_id) && $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Issue Date</label>
                        <input type="date" name="issue_date" value="<?php echo htmlspecialchars($issue_date ?? ''); ?>" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                    </div>
                    
                    <div class="">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Expiry Date *</label>
                        <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($expiry_date ?? ''); ?>" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" required>
                    </div>
                    
                    <div class="">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Reminder Days (before expiry)</label>
                        <input type="number" name="reminder_days" value="<?php echo htmlspecialchars($reminder_days ?? 7); ?>" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" min="1" max="365">
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">You will be reminded this many days before expiry</p>
                    </div>
                    
                    <div class="">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Amount (₹)</label>
                        <input type="number" name="amount" value="<?php echo htmlspecialchars($amount ?? ''); ?>" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Description</label>
                    <textarea name="description" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>
                
                <div class="">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Notes</label>
                    <textarea name="notes" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                </div>
                
                <div class="">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Attachment (PDF, JPG, PNG - Max 5MB)</label>
                    <input type="file" name="attachment" id="file-upload" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Add Record</button>
                    <a href="<?php echo BASE_URL; ?>/records/" class="bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 px-6 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
