<?php
/**
 * Edit Record Page
 * Update an existing expiry record
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify ownership
if (!verifyRecordOwnership($record_id, $user_id)) {
    setFlashMessage('error', 'Record not found or access denied.');
    header('Location: ' . BASE_URL . '/records/');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM expiry_records WHERE id = ? AND user_id = ?");
    $stmt->execute([$record_id, $user_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        setFlashMessage('error', 'Record not found.');
        header('Location: ' . BASE_URL . '/records/');
        exit;
    }
} catch (PDOException $e) {
    error_log("Edit record error: " . $e->getMessage());
    setFlashMessage('error', 'Failed to load record.');
    header('Location: ' . BASE_URL . '/records/');
    exit;
}

$errors = [];
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
                UPDATE expiry_records 
                SET category_id = ?, title = ?, description = ?, issue_date = ?, expiry_date = ?, 
                    reminder_days = ?, amount = ?, notes = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $category_id,
                $title,
                $description,
                $issue_date ?: null,
                $expiry_date,
                $reminder_days,
                $amount,
                $notes,
                $record_id,
                $user_id
            ]);
            
            // Handle file upload
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                // Delete old attachment if exists
                if ($record['attachment_path'] && file_exists($record['attachment_path'])) {
                    unlink($record['attachment_path']);
                }
                
                $upload_result = uploadFile($_FILES['attachment'], $user_id, $record_id);
                if (!$upload_result['success']) {
                    setFlashMessage('warning', 'Record updated but file upload failed: ' . $upload_result['error']);
                }
            }
            
            // Update reminders
            $stmt = $pdo->prepare("DELETE FROM reminders WHERE record_id = ?");
            $stmt->execute([$record_id]);
            
            $stmt = $pdo->prepare("
                INSERT INTO reminders (user_id, record_id, reminder_days, reminder_date)
                VALUES (?, ?, ?, DATE_ADD(?, INTERVAL -? DAY))
            ");
            $stmt->execute([$user_id, $record_id, $reminder_days, $expiry_date, $reminder_days]);
            
            setFlashMessage('success', 'Record updated successfully!');
            header('Location: ' . BASE_URL . '/records/view.php?id=' . $record_id);
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Failed to update record. Please try again.';
            error_log("Update record error: " . $e->getMessage());
        }
    }
}

// Use existing data or POST data
$title = $title ?? $record['title'];
$category_id = $category_id ?? $record['category_id'];
$description = $description ?? $record['description'];
$issue_date = $issue_date ?? $record['issue_date'];
$expiry_date = $expiry_date ?? $record['expiry_date'];
$reminder_days = $reminder_days ?? $record['reminder_days'];
$amount = $amount ?? $record['amount'];
$notes = $notes ?? $record['notes'];

$page_title = 'Edit Record';
$page_subtitle = 'Update record details';
$csrf_token = generateCsrfToken();

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Record</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error mb-6">
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
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" 
                            class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category_id" class="form-control form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="issue_date" value="<?php echo htmlspecialchars($issue_date); ?>" 
                            class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Expiry Date *</label>
                        <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($expiry_date); ?>" 
                            class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reminder Days (before expiry)</label>
                        <input type="number" name="reminder_days" value="<?php echo htmlspecialchars($reminder_days); ?>" 
                            class="form-control" min="1" max="365">
                        <p class="text-xs text-gray-500 mt-1">You will be reminded this many days before expiry</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Amount (₹)</label>
                        <input type="number" name="amount" value="<?php echo htmlspecialchars($amount); ?>" 
                            class="form-control" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="form-group mt-6">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control form-textarea" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control form-textarea" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Attachment (PDF, JPG, PNG - Max 5MB)</label>
                    <input type="file" name="attachment" id="file-upload" 
                        class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if ($record['attachment_path']): ?>
                    <p class="text-sm text-gray-500 mt-1">
                        Current: <a href="<?php echo BASE_URL; ?>/api/download.php?id=<?php echo $record['id']; ?>" target="_blank">View Attachment</a>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" class="btn btn-primary">Update Record</button>
                    <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $record_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
