<?php
/**
 * Renewals Index Page
 * View all renewal history
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Renewals';
$page_subtitle = 'Renewal history and activity';

try {
    // Get recent renewals
    $stmt = $pdo->prepare("
        SELECT rh.*, er.title as record_title, c.name as category_name, c.color as category_color
        FROM renewal_history rh
        JOIN expiry_records er ON rh.record_id = er.id
        JOIN categories c ON er.category_id = c.id
        WHERE rh.user_id = ?
        ORDER BY rh.renewal_date DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $renewals = $stmt->fetchAll();
    
    // Get renewal statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_renewals,
            SUM(renewal_amount) as total_spent,
            AVG(renewal_amount) as avg_amount
        FROM renewal_history
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    // Get monthly renewal trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(renewal_date, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(renewal_amount) as total
        FROM renewal_history
        WHERE user_id = ?
        AND renewal_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(renewal_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_trends = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Renewals error: " . $e->getMessage());
    $renewals = [];
    $stats = ['total_renewals' => 0, 'total_spent' => 0, 'avg_amount' => 0];
    $monthly_trends = [];
}

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Renewals</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $stats['total_renewals']; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Spent</p>
                <p class="text-2xl font-bold text-green-600 mt-1"><?php echo $stats['total_spent'] ? formatAmount($stats['total_spent']) : '₹0.00'; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Average Amount</p>
                <p class="text-2xl font-bold text-purple-600 mt-1"><?php echo $stats['avg_amount'] ? formatAmount($stats['avg_amount']) : '₹0.00'; ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Renewal History -->
<div class="card mb-8">
    <div class="card-header">
        <h3 class="card-title">Renewal History</h3>
    </div>
    <div class="card-body">
        <?php if (empty($renewals)): ?>
        <div class="empty-state">
            <p class="empty-state-text">No renewals yet</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Record</th>
                        <th>Category</th>
                        <th>Previous Expiry</th>
                        <th>New Expiry</th>
                        <th>Renewal Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($renewals as $renewal): ?>
                    <tr>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $renewal['record_id']; ?>" 
                                class="font-medium text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($renewal['record_title']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="inline-flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" style="background-color: <?php echo $renewal['category_color']; ?>"></span>
                                <?php echo htmlspecialchars($renewal['category_name']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($renewal['previous_expiry_date']); ?></td>
                        <td><?php echo formatDate($renewal['new_expiry_date']); ?></td>
                        <td><?php echo formatDate($renewal['renewal_date']); ?></td>
                        <td><?php echo $renewal['renewal_amount'] > 0 ? formatAmount($renewal['renewal_amount']) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly Trends -->
<?php if (!empty($monthly_trends)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Monthly Renewal Trends (Last 12 Months)</h3>
    </div>
    <div class="card-body">
        <div class="h-64">
            <canvas id="renewalChart"></canvas>
        </div>
        <script>
            const ctx = document.getElementById('renewalChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_trends, 'month')); ?>,
                    datasets: [{
                        label: 'Renewal Amount (₹)',
                        data: <?php echo json_encode(array_column($monthly_trends, 'total')); ?>,
                        borderColor: 'rgba(37, 99, 235, 1)',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </div>
</div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
