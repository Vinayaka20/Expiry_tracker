<?php
/**
 * Reminder Processing Script
 * Run via Windows Task Scheduler to process reminders
 * This script checks for upcoming expirations and creates notifications
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/functions.php';

// This script can be run from command line or cron
// It should be scheduled to run daily

echo "Starting reminder processing...\n";

try {
    // Get all users with active records
    $stmt = $pdo->query("
        SELECT DISTINCT user_id FROM expiry_records 
        WHERE expiry_date >= CURDATE()
    ");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $user_id = $user['user_id'];
        $settings = getUserSettings($user_id);
        
        if (!$settings) continue;
        
        // Get records expiring soon
        $stmt = $pdo->prepare("
            SELECT er.*, c.name as category_name
            FROM expiry_records er
            JOIN categories c ON er.category_id = c.id
            WHERE er.user_id = ?
            AND er.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY er.expiry_date ASC
        ");
        $stmt->execute([$user_id]);
        $records = $stmt->fetchAll();
        
        foreach ($records as $record) {
            $days_remaining = getDaysRemaining($record['expiry_date']);
            
            // Check if reminder should be sent based on user preferences
            $reminder_sent = false;
            $reminder_days = 0;
            
            if ($days_remaining == 30 && $settings['reminder_30_days']) {
                $reminder_days = 30;
                $reminder_sent = true;
            } elseif ($days_remaining == 15 && $settings['reminder_15_days']) {
                $reminder_days = 15;
                $reminder_sent = true;
            } elseif ($days_remaining == 7 && $settings['reminder_7_days']) {
                $reminder_days = 7;
                $reminder_sent = true;
            } elseif ($days_remaining == 3 && $settings['reminder_3_days']) {
                $reminder_days = 3;
                $reminder_sent = true;
            } elseif ($days_remaining == 1 && $settings['reminder_1_day']) {
                $reminder_days = 1;
                $reminder_sent = true;
            }
            
            // Check if reminder was already sent
            if ($reminder_sent) {
                $stmt = $pdo->prepare("
                    SELECT id FROM reminders 
                    WHERE user_id = ? AND record_id = ? AND reminder_days = ? 
                    AND reminder_date = CURDATE()
                ");
                $stmt->execute([$user_id, $record['id'], $reminder_days]);
                
                if (!$stmt->fetch()) {
                    // Create notification
                    $message = "Your '{$record['title']}' ({$record['category_name']}) expires in {$days_remaining} days on " . formatDate($record['expiry_date']);
                    
                    createNotification(
                        $user_id,
                        "Reminder: {$record['title']}",
                        $message,
                        'reminder',
                        $record['id']
                    );
                    
                    // Log reminder
                    $stmt = $pdo->prepare("
                        INSERT INTO reminders (user_id, record_id, reminder_days, reminder_date, is_sent)
                        VALUES (?, ?, ?, CURDATE(), 1)
                    ");
                    $stmt->execute([$user_id, $record['id'], $reminder_days]);
                    
                    echo "Reminder sent for record ID {$record['id']} ({$days_remaining} days)\n";
                    
                    // Send email if configured
                    if ($settings['email_notifications'] && $settings['smtp_host']) {
                        // Email sending would require PHPMailer integration
                        // This is a placeholder for future implementation
                        echo "Email notification would be sent (SMTP configured)\n";
                    }
                }
            }
        }
    }
    
    echo "Reminder processing completed.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Reminder processing error: " . $e->getMessage());
}
?>
