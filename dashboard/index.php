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
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-5 mb-8">
    <div class="stat-card accent-blue">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Total Records</p>
                <p class="stat-value mt-1"><?php echo $total_records; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card accent-green">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Active</p>
                <p class="stat-value mt-1"><?php echo $active_records; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card accent-amber">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Expiring in 7 Days</p>
                <p class="stat-value mt-1"><?php echo $expiring_7_days; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card accent-teal">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Expiring in 30 Days</p>
                <p class="stat-value mt-1"><?php echo $expiring_30_days; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card accent-rose">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Expired</p>
                <p class="stat-value mt-1"><?php echo $expired_records; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card accent-purple">
        <div class="flex items-start justify-between">
            <div>
                <p class="stat-label">Renewed (30d)</p>
                <p class="stat-value mt-1"><?php echo $renewed_recently; ?></p>
            </div>
            <div class="stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Upcoming Expirations -->
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                Upcoming Expirations
            </h3>
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Next 7 days</span>
        </div>
        <div class="section-body">
            <?php if (empty($upcoming_expirations)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-theme-2">Nothing expiring this week — you're all caught up.</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($upcoming_expirations as $record): ?>
                <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $record['id']; ?>" class="list-row">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: <?php echo $record['category_color']; ?>"></span>
                        <div class="min-w-0">
                            <p class="font-semibold text-theme truncate"><?php echo htmlspecialchars($record['title']); ?></p>
                            <p class="text-sm text-theme-2 truncate"><?php echo htmlspecialchars($record['category_name']); ?></p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-semibold text-theme text-sm"><?php echo formatDate($record['expiry_date']); ?></p>
                        <p class="text-xs text-theme-2"><?php echo getDaysRemaining($record['expiry_date']); ?> days left</p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="px-6 py-4 border-t border-theme">
            <a href="<?php echo BASE_URL; ?>/records/" class="text-sm font-semibold text-teal-600 dark:text-teal-400 hover:underline">View All Records →</a>
        </div>
    </div>

    <!-- Recently Added -->
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Recently Added
            </h3>
        </div>
        <div class="section-body">
            <?php if (empty($recent_records)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <p class="text-theme-2 mb-4">No records yet — add your first one to get started.</p>
                <a href="<?php echo BASE_URL; ?>/records/add.php" class="btn btn-primary px-5 py-2.5 rounded-xl inline-block text-sm">Add Your First Record</a>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_records as $record): ?>
                <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $record['id']; ?>" class="list-row">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: <?php echo $record['category_color']; ?>"></span>
                        <div class="min-w-0">
                            <p class="font-semibold text-theme truncate"><?php echo htmlspecialchars($record['title']); ?></p>
                            <p class="text-sm text-theme-2 truncate"><?php echo htmlspecialchars($record['category_name']); ?></p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-semibold text-theme text-sm"><?php echo formatDate($record['expiry_date']); ?></p>
                        <?php echo getStatusBadge(getExpiryStatus($record['expiry_date'])); ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="px-6 py-4 border-t border-theme">
            <a href="<?php echo BASE_URL; ?>/records/" class="text-sm font-semibold text-teal-600 dark:text-teal-400 hover:underline">View All Records →</a>
        </div>
    </div>
</div>

<!-- Category Statistics -->
<div class="section-card mb-8">
    <div class="section-header">
        <h3 class="section-title">Records by Category</h3>
    </div>
    <div class="section-body">
        <?php if (empty($category_stats)): ?>
        <div class="empty-state">
            <p class="text-theme-2">No category data available</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php foreach ($category_stats as $stat): ?>
            <div class="p-4 rounded-xl text-center transition-all duration-200 hover:-translate-y-1" style="background: var(--surface-2); border: 1px solid var(--border);">
                <div class="w-3.5 h-3.5 rounded-full mx-auto mb-2 shadow-sm" style="background-color: <?php echo $stat['color']; ?>"></div>
                <p class="text-2xl font-extrabold text-theme"><?php echo $stat['count']; ?></p>
                <p class="text-sm text-theme-2 font-medium"><?php echo htmlspecialchars($stat['name']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly Expiry Overview -->
<div class="section-card">
    <div class="section-header">
        <h3 class="section-title">Monthly Expiry Overview</h3>
        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Next 6 months</span>
    </div>
    <div class="section-body">
        <?php if (empty($monthly_expiry)): ?>
        <div class="empty-state">
            <p class="text-theme-2">No expiry data for the next 6 months</p>
        </div>
        <?php else: ?>
        <div class="h-72">
            <canvas id="monthlyChart"></canvas>
        </div>
        <script>
            const isDark = document.documentElement.classList.contains('dark');
            const chartText = isDark ? '#94A3B8' : '#64748B';
            const chartGrid = isDark ? 'rgba(148,163,184,0.12)' : 'rgba(148,163,184,0.18)';
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyGradient = ctx.createLinearGradient(0, 0, 0, 280);
            monthlyGradient.addColorStop(0, 'rgba(13, 148, 136, 0.45)');
            monthlyGradient.addColorStop(1, 'rgba(37, 99, 235, 0.05)');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_expiry, 'month')); ?>,
                    datasets: [{
                        label: 'Expiring Records',
                        data: <?php echo json_encode(array_column($monthly_expiry, 'count')); ?>,
                        backgroundColor: monthlyGradient,
                        borderColor: '#0D9488',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(37, 99, 235, 0.5)'
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
                            ticks: { stepSize: 1, color: chartText },
                            grid: { color: chartGrid }
                        },
                        x: {
                            ticks: { color: chartText },
                            grid: { display: false }
                        }
                    }
                }
            });
        </script>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
