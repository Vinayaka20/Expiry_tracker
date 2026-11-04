<?php
/**
 * Analytics Page
 * View detailed analytics with Chart.js
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Analytics';
$page_subtitle = 'Detailed insights and trends';

try {
    // Records by category
    $stmt = $pdo->prepare("
        SELECT c.name, c.color, COUNT(er.id) as count
        FROM categories c
        LEFT JOIN expiry_records er ON c.id = er.category_id AND er.user_id = ?
        GROUP BY c.id, c.name, c.color
        ORDER BY count DESC
    ");
    $stmt->execute([$user_id]);
    $category_data = $stmt->fetchAll();
    
    // Monthly expiry trends (12 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(expiry_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM expiry_records
        WHERE user_id = ? 
        AND expiry_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(expiry_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_expiry = $stmt->fetchAll();
    
    // Renewal expenses by category
    $stmt = $pdo->prepare("
        SELECT c.name, c.color, COALESCE(SUM(rh.renewal_amount), 0) as total
        FROM categories c
        LEFT JOIN expiry_records er ON c.id = er.category_id AND er.user_id = ?
        LEFT JOIN renewal_history rh ON er.id = rh.record_id
        GROUP BY c.id, c.name, c.color
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id]);
    $category_spending = $stmt->fetchAll();
    
    // Status distribution
    $stmt = $pdo->prepare("
        SELECT 
            CASE
                WHEN expiry_date < CURDATE() THEN 'expired'
                WHEN DATEDIFF(expiry_date, CURDATE()) <= 3 THEN 'critical'
                WHEN DATEDIFF(expiry_date, CURDATE()) <= 7 THEN 'upcoming'
                WHEN DATEDIFF(expiry_date, CURDATE()) <= 30 THEN 'due_soon'
                ELSE 'active'
            END as status,
            COUNT(*) as count
        FROM expiry_records
        WHERE user_id = ?
        GROUP BY status
    ");
    $stmt->execute([$user_id]);
    $status_distribution = $stmt->fetchAll();
    
    // Monthly renewal expenses
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(renewal_date, '%Y-%m') as month,
            COALESCE(SUM(renewal_amount), 0) as total
        FROM renewal_history
        WHERE user_id = ?
        AND renewal_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(renewal_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_spending = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Analytics error: " . $e->getMessage());
    $category_data = $monthly_expiry = $category_spending = $status_distribution = $monthly_spending = [];
}

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Records by Category -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Records by Category</h3>
        </div>
        <div class="p-6">
            <div class="h-64">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Status Distribution -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Status Distribution</h3>
        </div>
        <div class="p-6">
            <div class="h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Monthly Expiry Trends -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Monthly Expiry Trends (12 Months)</h3>
        </div>
        <div class="p-6">
            <div class="h-64">
                <canvas id="expiryTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Category Spending -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Spending by Category</h3>
        </div>
        <div class="p-6">
            <div class="h-64">
                <canvas id="spendingChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Spending -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Monthly Renewal Expenses (12 Months)</h3>
    </div>
    <div class="p-6">
        <div class="h-64">
            <canvas id="monthlySpendingChart"></canvas>
        </div>
    </div>
</div>

<script>
console.log('Analytics page loaded');
console.log('Category data:', <?php echo json_encode($category_data); ?>);
console.log('Monthly expiry:', <?php echo json_encode($monthly_expiry); ?>);

// Records by Category
const categoryCtx = document.getElementById('categoryChart');
if (categoryCtx) {
    console.log('Category chart canvas found');
    new Chart(categoryCtx.getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($category_data, 'name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($category_data, 'count')); ?>,
            backgroundColor: <?php echo json_encode(array_column($category_data, 'color')); ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
    });
} else {
    console.log('Category chart canvas NOT found');
}

// Status Distribution
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    const statusColors = {
        'expired': '#EF4444',
        'critical': '#DC2626',
        'upcoming': '#F59E0B',
        'due_soon': '#3B82F6',
        'active': '#10B981'
    };
    new Chart(statusCtx.getContext('2d'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_map(function($s) { return ucfirst($s['status']); }, $status_distribution)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($status_distribution, 'count')); ?>,
            backgroundColor: <?php echo json_encode(array_map(function($s) use ($statusColors) { return $statusColors[$s['status']] ?? '#6B7280'; }, $status_distribution)); ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
    });
}

// Monthly Expiry Trends
const expiryTrendCtx = document.getElementById('expiryTrendChart');
if (expiryTrendCtx) {
    new Chart(expiryTrendCtx.getContext('2d'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_expiry, 'month')); ?>,
        datasets: [{
            label: 'Expiring Records',
            data: <?php echo json_encode(array_column($monthly_expiry, 'count')); ?>,
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
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
    });
}

// Category Spending
const spendingCtx = document.getElementById('spendingChart');
if (spendingCtx) {
    new Chart(spendingCtx.getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($category_spending, 'name')); ?>,
        datasets: [{
            label: 'Total Spent (₹)',
            data: <?php echo json_encode(array_column($category_spending, 'total')); ?>,
            backgroundColor: <?php echo json_encode(array_column($category_spending, 'color')); ?>
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
}

// Monthly Spending
const monthlySpendingCtx = document.getElementById('monthlySpendingChart');
if (monthlySpendingCtx) {
    new Chart(monthlySpendingCtx.getContext('2d'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_spending, 'month')); ?>,
        datasets: [{
            label: 'Renewal Expenses (₹)',
            data: <?php echo json_encode(array_column($monthly_spending, 'total')); ?>,
            borderColor: 'rgba(16, 185, 129, 1)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
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
}
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
