-- LifeLeft Database Schema
-- MySQL 8+ Compatible

CREATE DATABASE IF NOT EXISTS lifeleft_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lifeleft_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO categories (name, description, icon, color, sort_order) VALUES
('Identity Documents', 'Passports, IDs, licenses, etc.', 'id-card', '#3B82F6', 1),
('Vehicle Documents', 'Vehicle registration, insurance, etc.', 'car', '#10B981', 2),
('Insurance', 'Health, life, property insurance', 'shield', '#F59E0B', 3),
('Subscriptions', 'Streaming, software, services', 'credit-card', '#8B5CF6', 4),
('Bills and Payments', 'Utilities, rent, EMIs', 'file-text', '#EF4444', 5),
('Food and Groceries', 'Food items, groceries', 'shopping-cart', '#EC4899', 6),
('Medicine', 'Medications, prescriptions', 'pill', '#06B6D4', 7),
('Warranties', 'Product warranties, guarantees', 'award', '#84CC16', 8),
('Memberships', 'Gym, clubs, associations', 'users', '#F97316', 9),
('Other', 'Any other expiry items', 'folder', '#6B7280', 10);

-- Expiry records table
CREATE TABLE IF NOT EXISTS expiry_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    issue_date DATE,
    expiry_date DATE NOT NULL,
    renewal_date DATE,
    reminder_days INT DEFAULT 7,
    amount DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT,
    attachment_path VARCHAR(500),
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_status (status),
    INDEX idx_user_expiry (user_id, expiry_date),
    INDEX idx_user_category (user_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Renewal history table
CREATE TABLE IF NOT EXISTS renewal_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    user_id INT NOT NULL,
    previous_expiry_date DATE NOT NULL,
    new_expiry_date DATE NOT NULL,
    renewal_date DATE NOT NULL,
    renewal_amount DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES expiry_records(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_record_id (record_id),
    INDEX idx_user_id (user_id),
    INDEX idx_renewal_date (renewal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uploaded documents table
CREATE TABLE IF NOT EXISTS uploaded_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    user_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES expiry_records(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_record_id (record_id),
    INDEX idx_user_id (user_id),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User settings table
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme VARCHAR(20) DEFAULT 'light',
    date_format VARCHAR(20) DEFAULT 'Y-m-d',
    timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
    default_reminder_days INT DEFAULT 7,
    email_notifications TINYINT(1) DEFAULT 1,
    reminder_30_days TINYINT(1) DEFAULT 1,
    reminder_15_days TINYINT(1) DEFAULT 1,
    reminder_7_days TINYINT(1) DEFAULT 1,
    reminder_3_days TINYINT(1) DEFAULT 1,
    reminder_1_day TINYINT(1) DEFAULT 1,
    smtp_host VARCHAR(255),
    smtp_port INT,
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),
    smtp_encryption VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'reminder',
    is_read TINYINT(1) DEFAULT 0,
    sent_via_email TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES expiry_records(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reminders table (for tracking reminder schedules)
CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_id INT NOT NULL,
    reminder_days INT NOT NULL,
    reminder_date DATE NOT NULL,
    is_sent TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES expiry_records(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_record_id (record_id),
    INDEX idx_reminder_date (reminder_date),
    INDEX idx_is_sent (is_sent),
    UNIQUE KEY idx_unique_reminder (user_id, record_id, reminder_days, reminder_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views for easier querying

-- View for expiry status
CREATE OR REPLACE VIEW expiry_status_view AS
SELECT 
    er.id,
    er.user_id,
    er.title,
    er.expiry_date,
    er.category_id,
    c.name as category_name,
    CASE
        WHEN er.expiry_date < CURDATE() THEN 'expired'
        WHEN DATEDIFF(er.expiry_date, CURDATE()) <= 3 THEN 'critical'
        WHEN DATEDIFF(er.expiry_date, CURDATE()) <= 7 THEN 'upcoming'
        WHEN DATEDIFF(er.expiry_date, CURDATE()) <= 30 THEN 'due_soon'
        ELSE 'active'
    END as status,
    DATEDIFF(er.expiry_date, CURDATE()) as days_remaining,
    er.amount
FROM expiry_records er
JOIN categories c ON er.category_id = c.id;

-- View for dashboard statistics
CREATE OR REPLACE VIEW dashboard_stats_view AS
SELECT 
    user_id,
    COUNT(*) as total_records,
    SUM(CASE WHEN expiry_date >= CURDATE() THEN 1 ELSE 0 END) as active_records,
    SUM(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as expiring_7_days,
    SUM(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_30_days,
    SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired_records,
    SUM(CASE WHEN renewal_date IS NOT NULL AND renewal_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as renewed_recently
FROM expiry_records
GROUP BY user_id;
