<?php
/**
 * Records Index Page
 * List all expiry records with search, filter, and sort
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$page_title = 'Records';
$page_subtitle = 'Manage your expiry records';

// Get query parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'expiry_date';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * RECORDS_PER_PAGE;

// Build query
$where = ['er.user_id = ?'];
$params = [$user_id];

if (!empty($search)) {
    $where[] = '(er.title LIKE ? OR er.description LIKE ? OR er.notes LIKE ?)';
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category_filter)) {
    $where[] = 'er.category_id = ?';
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    $status_conditions = [
        'expired' => 'er.expiry_date < CURDATE()',
        'critical' => 'er.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)',
        'upcoming' => 'er.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)',
        'due_soon' => 'er.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)',
        'active' => 'er.expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
    ];
    
    if (isset($status_conditions[$status_filter])) {
        $where[] = $status_conditions[$status_filter];
    }
}

$where_clause = implode(' AND ', $where);

// Valid sort options
$valid_sorts = ['expiry_date', 'title', 'created_at', 'amount'];
$sort = in_array($sort, $valid_sorts) ? $sort : 'expiry_date';
$order = $sort === 'expiry_date' ? 'ASC' : 'DESC';

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM expiry_records er WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / RECORDS_PER_PAGE);
    
    // Get records
    $sql = "
        SELECT er.*, c.name as category_name, c.color as category_color
        FROM expiry_records er
        JOIN categories c ON er.category_id = c.id
        WHERE $where_clause
        ORDER BY er.$sort $order
        LIMIT ? OFFSET ?
    ";
    $params[] = RECORDS_PER_PAGE;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    // Get categories for filter
    $categories = getCategories();
    
} catch (PDOException $e) {
    error_log("Records error: " . $e->getMessage());
    $records = [];
    $total_records = 0;
    $total_pages = 0;
    $categories = [];
}

?>
<?php require_once BASE_PATH . '/includes/header.php'; ?>

<!-- Filters -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 mb-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" id="search-input" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                    placeholder="Search records..." class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <select name="category" id="category-filter" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <select name="status" id="status-filter" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                    <option value="">All Status</option>
                    <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="critical" <?php echo $status_filter == 'critical' ? 'selected' : ''; ?>>Critical (≤3 days)</option>
                    <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming (≤7 days)</option>
                    <option value="due_soon" <?php echo $status_filter == 'due_soon' ? 'selected' : ''; ?>>Due Soon (≤30 days)</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active (>30 days)</option>
                </select>
            </div>
            <div>
                <select name="sort" id="sort-select" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                    <option value="expiry_date" <?php echo $sort == 'expiry_date' ? 'selected' : ''; ?>>Sort by Expiry Date</option>
                    <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>Sort by Title</option>
                    <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Sort by Created Date</option>
                    <option value="amount" <?php echo $sort == 'amount' ? 'selected' : ''; ?>>Sort by Amount</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Records Table -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow">
    <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">All Records (<?php echo $total_records; ?>)</h3>
        <a href="<?php echo BASE_URL; ?>/records/add.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Record
        </a>
    </div>
    <div class="p-6">
        <?php if (empty($records)): ?>
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-slate-400 mb-4">No records found</p>
            <a href="<?php echo BASE_URL; ?>/records/add.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 inline-block">Add Your First Record</a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="text-left py-3 px-4 text-gray-600 dark:text-slate-300 font-semibold">Title</th>
                        <th class="text-left py-3 px-4 text-gray-600 dark:text-slate-300 font-semibold">Category</th>
                        <th class="text-left py-3 px-4 text-gray-600 dark:text-slate-300 font-semibold">Expiry Date</th>
                        <th class="text-left py-3 px-4 text-gray-600 dark:text-slate-300 font-semibold">Days Left</th>
                        <th class="text-left py-3 px-4 text-gray-600 dark:text-slate-300 font-semibold">Status</th>
                        <th class="text-left py-3 px-4 text-gray-600 dark:text-slate-300 font-semibold">Amount</th>
                        <th class="text-left py-3 px-4 text-gray-600 dark:text-slate-300 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                    <tr class="border-b border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700">
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-800 dark:text-white"><?php echo htmlspecialchars($record['title']); ?></div>
                            <?php if ($record['attachment_path']): ?>
                            <span class="text-xs text-gray-500 dark:text-slate-400">📎 Attachment</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center gap-2 text-gray-800 dark:text-white">
                                <span class="w-2 h-2 rounded-full" style="background-color: <?php echo $record['category_color']; ?>"></span>
                                <?php echo htmlspecialchars($record['category_name']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-800 dark:text-white"><?php echo formatDate($record['expiry_date']); ?></td>
                        <td class="py-3 px-4">
                            <span class="<?php echo getDaysRemaining($record['expiry_date']) < 0 ? 'text-red-600' : 'text-gray-600 dark:text-slate-400'; ?>">
                                <?php 
                                $days = getDaysRemaining($record['expiry_date']);
                                echo $days < 0 ? abs($days) . ' days ago' : $days . ' days';
                                ?>
                            </span>
                        </td>
                        <td class="py-3 px-4"><?php echo getStatusBadge(getExpiryStatus($record['expiry_date'])); ?></td>
                        <td class="py-3 px-4 text-gray-800 dark:text-white"><?php echo $record['amount'] > 0 ? formatAmount($record['amount']) : '-'; ?></td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="<?php echo BASE_URL; ?>/records/view.php?id=<?php echo $record['id']; ?>" 
                                    class="p-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/records/edit.php?id=<?php echo $record['id']; ?>" 
                                    class="p-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/records/delete.php?id=<?php echo $record['id']; ?>" 
                                    class="p-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors" data-delete="Are you sure you want to delete '<?php echo htmlspecialchars($record['title']); ?>'?" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-gray-600 dark:text-slate-400">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total_records; ?> records)
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                    class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">Previous</a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                    class="px-4 py-2 <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300'; ?> rounded hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                    class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
