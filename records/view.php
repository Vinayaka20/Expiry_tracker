<?php
/**
 * View Record Page
 * Display detailed information about a single record
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
        SELECT er.*, c.name as category_name, c.color as category_color
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
    
    // Get renewal history
    $stmt = $pdo->prepare("
        SELECT * FROM renewal_history
        WHERE record_id = ? AND user_id = ?
        ORDER BY renewal_date DESC
    ");
    $stmt->execute([$record_id, $user_id]);
    $renewal_history = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("View record error: " . $e->getMessage());
    setFlashMessage('error', 'Failed to load record.');
    header('Location: ' . BASE_URL . '/records/');
    exit;
}

$page_title = $record['title'];
$page_subtitle = 'Record details';
$days_remaining = getDaysRemaining($record['expiry_date']);
$status = getExpiryStatus($record['expiry_date']);

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <!-- Record Header -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium" 
                            style="background-color: <?php echo $record['category_color']; ?>20; color: <?php echo $record['category_color']; ?>;">
                            <?php echo htmlspecialchars($record['category_name']); ?>
                        </span>
                        <?php echo getStatusBadge($status); ?>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($record['title']); ?></h2>
                </div>
                <div class="flex gap-2">
                    <a href="<?php echo BASE_URL; ?>/records/edit.php?id=<?php echo $record['id']; ?>" 
                        class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    <a href="<?php echo BASE_URL; ?>/renewals/process.php?id=<?php echo $record['id']; ?>" 
                        class="btn btn-success">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Renew
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Record Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Key Information</h3>
            </div>
            <div class="card-body space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-500">Issue Date</span>
                    <span class="font-medium"><?php echo formatDate($record['issue_date']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Expiry Date</span>
                    <span class="font-medium <?php echo $days_remaining < 0 ? 'text-red-600' : ''; ?>">
                        <?php echo formatDate($record['expiry_date']); ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Days Remaining</span>
                    <span class="font-medium <?php echo $days_remaining < 0 ? 'text-red-600' : ($days_remaining <= 7 ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php echo $days_remaining < 0 ? abs($days_remaining) . ' days ago' : $days_remaining . ' days'; ?>
                    </span>
                </div>
                <?php if ($record['renewal_date']): ?>
                <div class="flex justify-between">
                    <span class="text-gray-500">Last Renewed</span>
                    <span class="font-medium"><?php echo formatDate($record['renewal_date']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($record['amount'] > 0): ?>
                <div class="flex justify-between">
                    <span class="text-gray-500">Amount</span>
                    <span class="font-medium"><?php echo formatAmount($record['amount']); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <span class="text-gray-500">Reminder</span>
                    <span class="font-medium"><?php echo $record['reminder_days']; ?> days before</span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Additional Information</h3>
            </div>
            <div class="card-body space-y-4">
                <?php if ($record['description']): ?>
                <div>
                    <span class="text-gray-500 block mb-1">Description</span>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($record['description'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($record['notes']): ?>
                <div>
                    <span class="text-gray-500 block mb-1">Notes</span>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($record['attachment_path']): ?>
                <div>
                    <span class="text-gray-500 block mb-1">Attachment</span>
                    <a href="<?php echo BASE_URL; ?>/api/download.php?id=<?php echo $record['id']; ?>" 
                        class="btn btn-sm btn-secondary" target="_blank">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Attachment
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="flex justify-between text-sm text-gray-500 pt-4 border-t">
                    <span>Created: <?php echo formatDate($record['created_at'], DATETIME_FORMAT_DISPLAY); ?></span>
                    <span>Updated: <?php echo formatDate($record['updated_at'], DATETIME_FORMAT_DISPLAY); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Renewal History -->
    <?php if (!empty($renewal_history)): ?>
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Renewal History</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Previous Expiry</th>
                            <th>New Expiry</th>
                            <th>Renewal Date</th>
                            <th>Amount</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($renewal_history as $history): ?>
                        <tr>
                            <td><?php echo formatDate($history['previous_expiry_date']); ?></td>
                            <td><?php echo formatDate($history['new_expiry_date']); ?></td>
                            <td><?php echo formatDate($history['renewal_date']); ?></td>
                            <td><?php echo $history['renewal_amount'] > 0 ? formatAmount($history['renewal_amount']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($history['notes'] ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Actions -->
    <div class="flex gap-4">
        <a href="<?php echo BASE_URL; ?>/records/" class="btn btn-secondary">Back to Records</a>
        <a href="<?php echo BASE_URL; ?>/records/delete.php?id=<?php echo $record['id']; ?>" 
            class="btn btn-danger ml-auto" data-delete="Are you sure you want to delete '<?php echo htmlspecialchars($record['title']); ?>'? This action cannot be undone.">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Delete Record
        </a>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
