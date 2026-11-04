<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

require_once BASE_PATH . '/config/app.php';

/**
 * Calculate expiry status based on days remaining
 */
function getExpiryStatus($expiry_date) {
    $today = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $expiry = new DateTime($expiry_date, new DateTimeZone('Asia/Kolkata'));
    $interval = $today->diff($expiry);
    $days = $interval->days;
    
    if ($expiry < $today) {
        return 'expired';
    } elseif ($days <= 3) {
        return 'critical';
    } elseif ($days <= 7) {
        return 'upcoming';
    } elseif ($days <= 30) {
        return 'due_soon';
    } else {
        return 'active';
    }
}

/**
 * Get days remaining until expiry
 */
function getDaysRemaining($expiry_date) {
    $today = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $expiry = new DateTime($expiry_date, new DateTimeZone('Asia/Kolkata'));
    $interval = $today->diff($expiry);
    
    if ($expiry < $today) {
        return -$interval->days;
    }
    
    return $interval->days;
}

/**
 * Format date for display
 */
function formatDate($date, $format = DATE_FORMAT_DISPLAY) {
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Format amount
 */
function formatAmount($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Truncate text
 */
function truncateText($text, $length = 50) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $colors = [
        'expired' => 'bg-red-100 text-red-800',
        'critical' => 'bg-red-100 text-red-800',
        'upcoming' => 'bg-yellow-100 text-yellow-800',
        'due_soon' => 'bg-blue-100 text-blue-800',
        'active' => 'bg-green-100 text-green-800'
    ];
    
    $colorClass = $colors[$status] ?? 'bg-gray-100 text-gray-800';
    $label = STATUS_LABELS[$status] ?? ucfirst($status);
    
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $colorClass . '">' . $label . '</span>';
}

/**
 * Get all categories
 */
function getCategories() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get category by ID
 */
function getCategory($category_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching category: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user settings
 */
function getUserSettings($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch();
        
        if (!$settings) {
            // Create default settings
            $stmt = $pdo->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            
            $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log("Error fetching user settings: " . $e->getMessage());
        return null;
    }
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error fetching notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Create notification
 */
function createNotification($user_id, $title, $message, $type = 'reminder', $record_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, record_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $record_id, $title, $message, $type]);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Upload file
 */
function uploadFile($file, $user_id, $record_id = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds limit'];
    }
    
    // Validate file extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Invalid MIME type'];
    }
    
    // Generate safe filename
    $safe_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $upload_path = UPLOAD_DIR . $user_id . '/';
    
    // Create user directory if it doesn't exist
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $destination = $upload_path . $safe_filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    // Store in database
    global $pdo;
    
    try {
        if ($record_id) {
            $stmt = $pdo->prepare("INSERT INTO uploaded_documents (record_id, user_id, original_filename, stored_filename, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$record_id, $user_id, $file['name'], $safe_filename, $destination, $file['size'], $mime_type]);
            
            // Update expiry record with attachment path
            $stmt = $pdo->prepare("UPDATE expiry_records SET attachment_path = ? WHERE id = ?");
            $stmt->execute([$destination, $record_id]);
        }
        
        return ['success' => true, 'path' => $destination, 'filename' => $safe_filename];
    } catch (PDOException $e) {
        error_log("Error storing file info: " . $e->getMessage());
        unlink($destination);
        return ['success' => false, 'error' => 'Database error'];
    }
}

/**
 * Delete file
 */
function deleteFile($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

/**
 * Get pagination HTML
 */
function getPagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="flex items-center justify-between mt-6">';
    $html .= '<div class="text-sm text-gray-600">';
    $html .= 'Page ' . $current_page . ' of ' . $total_pages;
    $html .= '</div>';
    $html .= '<div class="flex space-x-2">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page - 1) . '" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="px-3 py-1 bg-blue-600 text-white rounded">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $base_url . '?page=' . $i . '" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page + 1) . '" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">Next</a>';
    }
    
    $html .= '</div></div>';
    
    return $html;
}
?>
