<?php
/**
 * Dashboard Page
 * Main dashboard with summary cards and statistics
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Dashboard';
$page_subtitle = 'Overview of your expiry records';

// Get dashboard statistics
try {
    // Total records
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expiry_records WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_records = $stmt->fetch()['count'];
    
    // Active records
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expiry_records WHERE user_id = ? AND expiry_date >= CURDATE()");
    $stmt->execute([$user_id]);
    $active_records = $stmt->fetch()['count'];
    
    // Expiring within 7 days
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expiry_records WHERE user_id = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$user_id]);
    $expiring_7_days = $stmt->fetch()['count'];
    
    // Expiring within 30 days
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expiry_records WHERE user_id = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$user_id]);
    $expiring_30_days = $stmt->fetch()['count'];
    
    // Expired records
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expiry_records WHERE user_id = ? AND expiry_date < CURDATE()");
    $stmt->execute([$user_id]);
    $expired_records = $stmt->fetch()['count'];
    
    // Renewed in last 30 days
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expiry_records WHERE user_id = ? AND renewal_date IS NOT NULL AND renewal_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$user_id]);
    $renewed_recently = $stmt->fetch()['count'];
    
    // Upcoming expirations (next 7 days)
    $stmt = $pdo->prepare("
        SELECT er.*, c.name as category_name, c.color as category_color
        FROM expiry_records er
        JOIN categories c ON er.category_id = c.id
        WHERE er.user_id = ? 
        AND er.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY er.expiry_date ASC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $upcoming_expirations = $stmt->fetchAll();
    
    // Recently added records
    $stmt = $pdo->prepare("
        SELECT er.*, c.name as category_name, c.color as category_color
        FROM expiry_records er
        JOIN categories c ON er.category_id = c.id
        WHERE er.user_id = ?
        ORDER BY er.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_records = $stmt->fetchAll();
    
    // Category-wise statistics
    $stmt = $pdo->prepare("
        SELECT c.name, c.color, COUNT(er.id) as count
        FROM categories c
        LEFT JOIN expiry_records er ON c.id = er.category_id AND er.user_id = ?
        GROUP BY c.id, c.name, c.color
        ORDER BY count DESC
    ");
    $stmt->execute([$user_id]);
    $category_stats = $stmt->fetchAll();
    
    // Monthly expiry overview (next 6 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(expiry_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM expiry_records
        WHERE user_id = ? 
        AND expiry_date >= CURDATE()
        AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(expiry_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_expiry = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_records = $active_records = $expiring_7_days = $expiring_30_days = $expired_records = $renewed_recently = 0;
    $upcoming_expirations = $recent_records = $category_stats = $monthly_expiry = [];
}

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-slate-400 text-sm">Total Records</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1"><?php echo $total_records; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-slate-400 text-sm">Active</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1"><?php echo $active_records; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-slate-400 text-sm">Expiring in 7 Days</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1"><?php echo $expiring_7_days; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-slate-400 text-sm">Expiring in 30 Days</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?php echo $expiring_30_days; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-slate-400 text-sm">Expired</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1"><?php echo $expired_records; ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-slate-400 text-sm">Renewed (30d)</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1"><?php echo $renewed_recently; ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Upcoming Expirations -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Upcoming Expirations (Next 7 Days)</h3>
        </div>
        <div class="p-6">
            <?php if (empty($upcoming_expirations)): ?>
            <div class="text-center py-8">
                <p class="text-gray-500 dark:text-slate-400">No upcoming expirations</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($upcoming_expirations as $record): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full" style="background-color: <?php echo $record['category_color']; ?>"></div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-white"><?php echo htmlspecialchars($record['title']); ?></p>
                            <p class="text-sm text-gray-500 dark:text-slate-400"><?php echo htmlspecialchars($record['category_name']); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-gray-800 dark:text-white"><?php echo formatDate($record['expiry_date']); ?></p>
                        <p class="text-sm text-gray-500 dark:text-slate-400"><?php echo getDaysRemaining($record['expiry_date']); ?> days left</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-6 border-t border-gray-200 dark:border-slate-700">
            <a href="<?php echo BASE_URL; ?>/records/" class="text-blue-600 dark:text-blue-400 hover:underline">View All Records →</a>
        </div>
    </div>
    
    <!-- Recently Added -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Recently Added Records</h3>
        </div>
        <div class="p-6">
            <?php if (empty($recent_records)): ?>
            <div class="text-center py-8">
                <p class="text-gray-500 dark:text-slate-400 mb-4">No records yet</p>
                <a href="<?php echo BASE_URL; ?>/records/add.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 inline-block">Add Your First Record</a>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recent_records as $record): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full" style="background-color: <?php echo $record['category_color']; ?>"></div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-white"><?php echo htmlspecialchars($record['title']); ?></p>
                            <p class="text-sm text-gray-500 dark:text-slate-400"><?php echo htmlspecialchars($record['category_name']); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-gray-800 dark:text-white"><?php echo formatDate($record['expiry_date']); ?></p>
                        <?php echo getStatusBadge(getExpiryStatus($record['expiry_date'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-6 border-t border-gray-200 dark:border-slate-700">
            <a href="<?php echo BASE_URL; ?>/records/" class="text-blue-600 dark:text-blue-400 hover:underline">View All Records →</a>
        </div>
    </div>
</div>

<!-- Category Statistics -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow mb-8">
    <div class="p-6 border-b border-gray-200 dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Records by Category</h3>
    </div>
    <div class="p-6">
        <?php if (empty($category_stats)): ?>
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-slate-400">No category data available</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php foreach ($category_stats as $stat): ?>
            <div class="p-4 bg-gray-50 dark:bg-slate-700 rounded-lg text-center">
                <div class="w-3 h-3 rounded-full mx-auto mb-2" style="background-color: <?php echo $stat['color']; ?>"></div>
                <p class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo $stat['count']; ?></p>
                <p class="text-sm text-gray-500 dark:text-slate-400"><?php echo htmlspecialchars($stat['name']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly Expiry Overview -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow">
    <div class="p-6 border-b border-gray-200 dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Monthly Expiry Overview (Next 6 Months)</h3>
    </div>
    <div class="p-6">
        <?php if (empty($monthly_expiry)): ?>
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-slate-400">No expiry data for the next 6 months</p>
        </div>
        <?php else: ?>
        <div class="h-64">
            <canvas id="monthlyChart"></canvas>
        </div>
        <script>
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_expiry, 'month')); ?>,
                    datasets: [{
                        label: 'Expiring Records',
                        data: <?php echo json_encode(array_column($monthly_expiry, 'count')); ?>,
                        backgroundColor: 'rgba(37, 99, 235, 0.8)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        </script>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
