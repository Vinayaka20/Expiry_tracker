<?php
/**
 * Renewal Process Page
 * Process a renewal for a record
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
    $stmt = $pdo->prepare("
        SELECT er.*, c.name as category_name
        FROM expiry_records er
        JOIN categories c ON er.category_id = c.id
        WHERE er.id = ? AND er.user_id = ?
    ");
    $stmt->execute([$record_id, $user_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        setFlashMessage('error', 'Record not found.');
        header('Location: ' . BASE_URL . '/records/');
        exit;
    }
} catch (PDOException $e) {
    error_log("Renewal process error: " . $e->getMessage());
    setFlashMessage('error', 'Failed to load record.');
    header('Location: ' . BASE_URL . '/records/');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_expiry_date = $_POST['new_expiry_date'] ?? '';
    $renewal_amount = floatval($_POST['renewal_amount'] ?? 0);
    $renewal_notes = sanitizeInput($_POST['renewal_notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = 'Invalid security token.';
    }
    
    // Validate required fields
    if (empty($new_expiry_date)) {
        $errors[] = 'New expiry date is required.';
    } elseif (!strtotime($new_expiry_date)) {
        $errors[] = 'Invalid expiry date.';
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Store previous expiry date in history
            $previous_expiry = $record['expiry_date'];
            
            // Update record
            $stmt = $pdo->prepare("
                UPDATE expiry_records 
                SET expiry_date = ?, renewal_date = CURDATE(), updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$new_expiry_date, $record_id, $user_id]);
            
            // Add to renewal history
            $stmt = $pdo->prepare("
                INSERT INTO renewal_history 
                (record_id, user_id, previous_expiry_date, new_expiry_date, renewal_date, renewal_amount, notes)
                VALUES (?, ?, ?, ?, CURDATE(), ?, ?)
            ");
            $stmt->execute([
                $record_id,
                $user_id,
                $previous_expiry,
                $new_expiry_date,
                $renewal_amount,
                $renewal_notes
            ]);
            
            // Update reminders
            $stmt = $pdo->prepare("DELETE FROM reminders WHERE record_id = ?");
            $stmt->execute([$record_id]);
            
            $stmt = $pdo->prepare("
                INSERT INTO reminders (user_id, record_id, reminder_days, reminder_date)
                VALUES (?, ?, ?, DATE_ADD(?, INTERVAL -? DAY))
            ");
            $stmt->execute([$user_id, $record_id, $record['reminder_days'], $new_expiry_date, $record['reminder_days']]);
            
            // Create notification
            createNotification(
                $user_id,
                'Renewal Completed',
                "You have renewed '{$record['title']}' successfully. New expiry date: " . formatDate($new_expiry_date),
                'renewal',
                $record_id
            );
            
            $pdo->commit();
            
            setFlashMessage('success', 'Record renewed successfully!');
            header('Location: ' . BASE_URL . '/records/view.php?id=' . $record_id);
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to process renewal. Please try again.';
            error_log("Renewal error: " . $e->getMessage());
        }
    }
}

$page_title = 'Renew Record';
$page_subtitle = 'Process a renewal';
$csrf_token = generateCsrfToken();

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Renew Record</h3>
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
            
            <!-- Current Record Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-3 h-3 rounded-full" style="background-color: <?php echo $record['category_color']; ?>"></div>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($record['title']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($record['category_name']); ?></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Current Expiry:</span>
                        <span class="font-medium ml-2"><?php echo formatDate($record['expiry_date']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Days Remaining:</span>
                        <span class="font-medium ml-2 <?php echo getDaysRemaining($record['expiry_date']) < 0 ? 'text-red-600' : ''; ?>">
                            <?php 
                            $days = getDaysRemaining($record['expiry_date']);
                            echo $days < 0 ? abs($days) . ' days ago' : $days . ' days';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label class="form-label">New Expiry Date *</label>
                    <input type="date" name="new_expiry_date" 
                        value="<?php echo date('Y-m-d', strtotime('+1 year', strtotime($record['expiry_date']))); ?>" 
                        class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Renewal Amount (₹)</label>
                    <input type="number" name="renewal_amount" value="<?php echo htmlspecialchars($renewal_amount ?? $record['amount']); ?>" 
                        class="form-control" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Renewal Notes</label>
                    <textarea name="renewal_notes" class="form-control form-textarea" rows="3" 
                        placeholder="Any notes about this renewal..."><?php echo htmlspecialchars($renewal_notes ?? ''); ?></textarea>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" class="btn btn-success flex-1">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Complete Renewal
                    </button>
                    <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $record_id; ?>" class="btn btn-secondary flex-1">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
