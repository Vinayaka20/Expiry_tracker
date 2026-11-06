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
    <div class="stat-card accent-blue">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Total Renewals</p>
                <p class="stat-value mt-1"><?php echo $stats['total_renewals']; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card accent-green">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Total Spent</p>
                <p class="stat-value mt-1"><?php echo $stats['total_spent'] ? formatAmount($stats['total_spent']) : '₹0.00'; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card accent-purple">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Average Amount</p>
                <p class="stat-value mt-1"><?php echo $stats['avg_amount'] ? formatAmount($stats['avg_amount']) : '₹0.00'; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Renewal History -->
<div class="section-card mb-8">
    <div class="section-header">
        <h3 class="section-title">Renewal History</h3>
        <span class="text-xs font-semibold text-theme-3 uppercase tracking-wide"><?php echo count($renewals); ?> records</span>
    </div>
    <div class="section-body !p-0">
        <?php if (empty($renewals)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
            <p class="text-theme-2">No renewals yet — renewed records will appear here.</p>
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
                                class="font-semibold text-teal-600 dark:text-teal-400 hover:underline">
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
<div class="section-card">
    <div class="section-header">
        <h3 class="section-title">Monthly Renewal Trends</h3>
        <span class="text-xs font-semibold text-theme-3 uppercase tracking-wide">Last 12 months</span>
    </div>
    <div class="section-body">
        <div class="h-72">
            <canvas id="renewalChart"></canvas>
        </div>
        <script>
            const isDarkR = document.documentElement.classList.contains('dark');
            const rText = isDarkR ? '#94A3B8' : '#64748B';
            const rGrid = isDarkR ? 'rgba(148,163,184,0.12)' : 'rgba(148,163,184,0.18)';
            const ctx = document.getElementById('renewalChart').getContext('2d');
            const rGradient = ctx.createLinearGradient(0, 0, 0, 280);
            rGradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
            rGradient.addColorStop(1, 'rgba(13, 148, 136, 0.02)');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_trends, 'month')); ?>,
                    datasets: [{
                        label: 'Renewal Amount (₹)',
                        data: <?php echo json_encode(array_column($monthly_trends, 'total')); ?>,
                        borderColor: '#2563EB',
                        backgroundColor: rGradient,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#0D9488',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: rText },
                            grid: { color: rGrid }
                        },
                        x: {
                            ticks: { color: rText },
                            grid: { display: false }
                        }
                    }
                }
            });
        </script>
    </div>
</div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
