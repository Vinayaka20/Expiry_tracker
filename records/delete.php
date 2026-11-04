<?php
/**
 * Delete Record Page
 * Delete an expiry record with confirmation
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
    error_log("Delete record error: " . $e->getMessage());
    setFlashMessage('error', 'Failed to load record.');
    header('Location: ' . BASE_URL . '/records/');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('error', 'Invalid security token.');
    } elseif ($confirm !== 'yes') {
        setFlashMessage('error', 'Deletion cancelled.');
    } else {
        try {
            // Delete attachment file if exists
            if ($record['attachment_path'] && file_exists($record['attachment_path'])) {
                unlink($record['attachment_path']);
            }
            
            // Delete from database (cascade will handle related records)
            $stmt = $pdo->prepare("DELETE FROM expiry_records WHERE id = ? AND user_id = ?");
            $stmt->execute([$record_id, $user_id]);
            
            setFlashMessage('success', 'Record deleted successfully.');
            header('Location: ' . BASE_URL . '/records/');
            exit;
            
        } catch (PDOException $e) {
            error_log("Delete record error: " . $e->getMessage());
            setFlashMessage('error', 'Failed to delete record. Please try again.');
        }
    }
}

$page_title = 'Delete Record';
$page_subtitle = 'Confirm deletion';
$csrf_token = generateCsrfToken();

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-body">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Delete Record?</h3>
                <p class="text-gray-600">This action cannot be undone. All data including renewal history will be permanently deleted.</p>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full" style="background-color: <?php echo $record['category_color']; ?>"></div>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($record['title']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($record['category_name']); ?> • Expires: <?php echo formatDate($record['expiry_date']); ?></p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="confirm" value="yes">
                
                <div class="flex gap-4">
                    <button type="submit" class="btn btn-danger flex-1">Yes, Delete Record</button>
                    <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $record_id; ?>" class="btn btn-secondary flex-1">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
