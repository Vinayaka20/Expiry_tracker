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
<div class="section-card mb-6">
    <div class="section-body !py-4">
        <div class="flex items-center justify-between">
            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                class="icon-btn view !w-auto !h-auto px-4 py-2 flex items-center gap-2 text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
            </a>
            
            <div class="text-center">
                <h2 class="text-xl font-extrabold text-theme tracking-tight">
                    <?php echo $month_names[$month]; ?> <?php echo $year; ?>
                </h2>
                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                    class="text-teal-600 dark:text-teal-400 hover:underline text-sm font-semibold">Today</a>
            </div>
            
            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                class="icon-btn view !w-auto !h-auto px-4 py-2 flex items-center gap-2 text-sm font-semibold">
                Next
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Calendar Grid -->
<div class="section-card mb-6">
    <div class="section-body">
        <!-- Day Headers -->
        <div class="grid grid-cols-7 gap-2 mb-3">
            <div class="text-center text-xs font-bold uppercase tracking-wider text-theme-3 py-2">Mon</div>
            <div class="text-center text-xs font-bold uppercase tracking-wider text-theme-3 py-2">Tue</div>
            <div class="text-center text-xs font-bold uppercase tracking-wider text-theme-3 py-2">Wed</div>
            <div class="text-center text-xs font-bold uppercase tracking-wider text-theme-3 py-2">Thu</div>
            <div class="text-center text-xs font-bold uppercase tracking-wider text-theme-3 py-2">Fri</div>
            <div class="text-center text-xs font-bold uppercase tracking-wider text-theme-3 py-2">Sat</div>
            <div class="text-center text-xs font-bold uppercase tracking-wider text-theme-3 py-2">Sun</div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-2">
            <?php
            // Empty cells before first day
            for ($i = 1; $i < $first_day_weekday; $i++):
            ?>
            <div class="cal-day !bg-transparent !border-transparent"></div>
            <?php endfor; ?>

            <!-- Days of month -->
            <?php for ($day = 1; $day <= $days_in_month; $day++):
                $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
                $day_events = $events_by_day[$day] ?? [];
            ?>
            <div class="cal-day <?php echo $is_today ? 'today' : ''; ?>">
                <div class="cal-day-num"><?php echo $day; ?></div>

                <?php if (!empty($day_events)): ?>
                <div class="flex-1 overflow-y-auto space-y-1 max-h-28">
                    <?php foreach ($day_events as $event): ?>
                    <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $event['id']; ?>"
                        class="cal-event"
                        style="background-color: <?php echo $event['category_color']; ?>22; color: <?php echo $event['category_color']; ?>; border-left-color: <?php echo $event['category_color']; ?>;"
                        title="<?php echo htmlspecialchars($event['title']); ?>">
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
            <div class="cal-day !bg-transparent !border-transparent"></div>
            <?php
                endfor;
            endif;
            ?>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="section-card">
    <div class="section-header">
        <h3 class="section-title">Category Legend</h3>
    </div>
    <div class="section-body">
        <div class="flex flex-wrap gap-3">
            <?php 
            $categories = getCategories();
            foreach ($categories as $cat):
            ?>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium" style="background: var(--surface-2); border: 1px solid var(--border); color: var(--text-2);">
                <div class="w-3 h-3 rounded-full shadow-sm" style="background-color: <?php echo $cat['color']; ?>"></div>
                <?php echo htmlspecialchars($cat['name']); ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
