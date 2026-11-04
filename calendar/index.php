<?php
/**
 * Calendar Page
 * Monthly calendar view with expiry events
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Calendar';
$page_subtitle = 'View expiry dates by month';

// Get month and year from query params or use current
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate month/year
$month = max(1, min(12, $month));
$year = max(2000, min(2100, $year));

// Get first and last day of month
$first_day = mktime(0, 0, 0, $month, 1, $year);
$last_day = mktime(0, 0, 0, $month, date('t', $first_day), $year);
$days_in_month = date('t', $first_day);
$first_day_weekday = date('N', $first_day); // 1 = Monday, 7 = Sunday

// Get records for this month
try {
    $stmt = $pdo->prepare("
        SELECT er.*, c.name as category_name, c.color as category_color
        FROM expiry_records er
        JOIN categories c ON er.category_id = c.id
        WHERE er.user_id = ?
        AND (DATE(er.expiry_date) >= ? AND DATE(er.expiry_date) <= ?)
        ORDER BY er.expiry_date ASC
    ");
    $stmt->execute([
        $user_id,
        date('Y-m-01', $first_day),
        date('Y-m-t', $last_day)
    ]);
    $records = $stmt->fetchAll();
    
    // Group records by day
    $events_by_day = [];
    foreach ($records as $record) {
        $day = date('j', strtotime($record['expiry_date']));
        if (!isset($events_by_day[$day])) {
            $events_by_day[$day] = [];
        }
        $events_by_day[$day][] = $record;
    }
    
} catch (PDOException $e) {
    error_log("Calendar error: " . $e->getMessage());
    $records = [];
    $events_by_day = [];
}

// Navigation
$prev_month = $month == 1 ? 12 : $month - 1;
$prev_year = $month == 1 ? $year - 1 : $year;
$next_month = $month == 12 ? 1 : $month + 1;
$next_year = $month == 12 ? $year + 1 : $year;

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<!-- Calendar Navigation -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                class="bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
            </a>
            
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                    <?php echo $month_names[$month]; ?> <?php echo $year; ?>
                </h2>
                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                    class="text-blue-600 dark:text-blue-400 hover:underline text-sm">Today</a>
            </div>
            
            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                class="bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors flex items-center gap-2">
                Next
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

<!-- Calendar Grid -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 mb-6">
        <!-- Day Headers -->
        <div class="grid grid-cols-7 gap-2 mb-4">
            <div class="text-center font-semibold text-gray-600 dark:text-slate-400 py-2">Mon</div>
            <div class="text-center font-semibold text-gray-600 dark:text-slate-400 py-2">Tue</div>
            <div class="text-center font-semibold text-gray-600 dark:text-slate-400 py-2">Wed</div>
            <div class="text-center font-semibold text-gray-600 dark:text-slate-400 py-2">Thu</div>
            <div class="text-center font-semibold text-gray-600 dark:text-slate-400 py-2">Fri</div>
            <div class="text-center font-semibold text-gray-600 dark:text-slate-400 py-2">Sat</div>
            <div class="text-center font-semibold text-gray-600 dark:text-slate-400 py-2">Sun</div>
        </div>
        
        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-2">
            <?php
            // Empty cells before first day
            for ($i = 1; $i < $first_day_weekday; $i++):
            ?>
            <div class="h-32 bg-gray-50 dark:bg-slate-700 rounded-lg"></div>
            <?php endfor; ?>
            
            <!-- Days of month -->
            <?php for ($day = 1; $day <= $days_in_month; $day++):
                $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
                $day_events = $events_by_day[$day] ?? [];
            ?>
            <div class="h-32 border border-gray-200 dark:border-slate-700 rounded-lg p-2 <?php echo $is_today ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-300 dark:border-blue-700' : 'bg-white dark:bg-slate-800'; ?> hover:shadow-md transition-shadow">
                <div class="font-semibold <?php echo $is_today ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-slate-300'; ?> mb-2">
                    <?php echo $day; ?>
                </div>
                
                <?php if (!empty($day_events)): ?>
                <div class="space-y-1 overflow-y-auto max-h-24">
                    <?php foreach ($day_events as $event): ?>
                    <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $event['id']; ?>" 
                        class="block text-xs p-1 rounded truncate hover:opacity-80"
                        style="background-color: <?php echo $event['category_color']; ?>20; color: <?php echo $event['category_color']; ?>; border-left: 3px solid <?php echo $event['category_color']; ?>;">
                        <?php echo htmlspecialchars($event['title']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
            
            <!-- Empty cells after last day -->
            <?php
            $total_cells = $first_day_weekday + $days_in_month;
            $remaining_cells = 7 - ($total_cells % 7);
            if ($remaining_cells < 7):
                for ($i = 0; $i < $remaining_cells; $i++):
            ?>
            <div class="h-32 bg-gray-50 dark:bg-slate-700 rounded-lg"></div>
            <?php 
                endfor;
            endif;
            ?>
        </div>
    </div>

<!-- Legend -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow">
    <div class="p-6 border-b border-gray-200 dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Category Legend</h3>
    </div>
    <div class="p-6">
        <div class="flex flex-wrap gap-4">
            <?php 
            $categories = getCategories();
            foreach ($categories as $cat):
            ?>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background-color: <?php echo $cat['color']; ?>"></div>
                <span class="text-sm text-gray-600 dark:text-slate-400"><?php echo htmlspecialchars($cat['name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
