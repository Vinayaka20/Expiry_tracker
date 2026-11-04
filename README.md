
# LifeLeft - Personal Expiry & Renewal Tracker

A complete, professional web application for tracking expiry dates, renewals, subscriptions, bills, insurance, identity documents, warranties, food and medicine expiry dates.

## Features

- **User Authentication**: Secure registration, login, logout with password hashing
- **Dashboard**: Interactive dashboard with summary cards and statistics
- **Record Management**: Full CRUD for expiry records with categories
- **Search & Filters**: Advanced search, category and status filtering, sorting
- **Renewal Tracking**: Complete renewal history with amount tracking
- **Calendar View**: Monthly calendar showing all expiry events
- **Reminder System**: Configurable reminders (30, 15, 7, 3, 1 days before expiry)
- **Notifications**: In-app notifications with read/unread status
- **Document Uploads**: Secure file uploads (PDF, JPG, PNG) with validation
- **Settings**: Profile management, password change, notification preferences

## Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Chart.js
- **Backend**: PHP 8+ with PDO
- **Database**: MySQL 8+
- **Environment**: Windows XAMPP, Apache, MySQL

## Installation

### Prerequisites

- XAMPP installed on Windows
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache web server

### Setup Instructions

1. **Place the Application**
   - Copy the entire `LifeLeft` folder to `C:\xampp\htdocs\`
   - The application should be accessible at `http://localhost/LifeLeft/`

2. **Create the Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click on the "SQL" tab
   - Copy the contents of `database/schema.sql`
   - Paste and execute the SQL script
   - This will create the `lifeleft_db` database with all required tables

3. **Configure Database Connection** (if needed)
   - Open `config/database.php`
   - Update the database credentials if your MySQL setup differs:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'lifeleft_db');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Configure Application URL** (if needed)
   - Open `config/app.php`
   - Update the BASE_URL if your application is not at `/LifeLeft/`:
     ```php
     define('BASE_URL', 'http://localhost/LifeLeft');
     ```

5. **Set File Permissions**
   - Ensure the `uploads/` directory is writable by the web server
   - On Windows XAMPP, this is usually already configured

6. **Access the Application**
   - Open your browser and navigate to: http://localhost/LifeLeft/
   - You will be redirected to the login page
   - Click "Register here" to create your first account

## Database Schema

The application uses the following tables:

- `users` - User accounts and authentication
- `categories` - Pre-defined categories for records
- `expiry_records` - Main expiry record data
- `renewal_history` - Track all renewals
- `uploaded_documents` - Document upload metadata
- `user_settings` - User preferences and settings
- `notifications` - In-app notifications
- `reminders` - Reminder schedule tracking

## Default Categories

The application includes 10 pre-configured categories:

1. Identity Documents
2. Vehicle Documents
3. Insurance
4. Subscriptions
5. Bills and Payments
6. Food and Groceries
7. Medicine
8. Warranties
9. Memberships
10. Other

## Setting Up Automatic Reminders

To enable automatic reminder processing on Windows:

1. Open **Task Scheduler** (search for it in Windows)
2. Click **Create Basic Task** in the right panel
3. Name it "LifeLeft Reminders" and click Next
4. Set trigger to **Daily** and click Next
5. Set the time (e.g., 9:00 AM) and click Next
6. Choose **Start a program** and click Next
7. Program/script: `php`
8. Add arguments: `"C:\xampp\htdocs\LifeLeft\reminders\process.php"`
9. Start in: `C:\xampp\htdocs\LifeLeft\reminders\`
10. Click Finish

The reminder script will now run daily and create notifications for upcoming expirations.

## Security Features

- **Password Hashing**: Uses `password_hash()` with bcrypt
- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection Prevention**: All queries use PDO prepared statements
- **XSS Prevention**: Output escaping with `htmlspecialchars()`
- **Session Security**: Secure session management
- **File Upload Security**: MIME type validation, extension checking, safe filenames
- **User Isolation**: Each user can only access their own records

## File Structure

```
LifeLeft/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   ├── js/
│   │   └── app.js             # JavaScript functionality
│   └── images/                # Image assets
├── config/
│   ├── database.php           # Database connection
│   └── app.php                # Application configuration
├── includes/
│   ├── auth.php               # Authentication functions
│   ├── functions.php          # Helper functions
│   ├── header.php             # HTML header and navigation
│   └── footer.php             # HTML footer
├── auth/
│   ├── login.php              # Login page
│   ├── register.php           # Registration page
│   └── logout.php             # Logout handler
├── dashboard/
│   └── index.php              # Dashboard page
├── records/
│   ├── index.php              # Records list with filters
│   ├── add.php                # Add new record
│   ├── edit.php               # Edit record
│   ├── view.php               # View record details
│   └── delete.php             # Delete record
├── renewals/
│   ├── index.php              # Renewal history
│   └── process.php            # Process renewal
├── calendar/
│   └── index.php              # Calendar view
├── reminders/
│   ├── index.php              # Reminder settings
│   └── process.php            # Reminder processing script
├── notifications/
│   └── index.php              # Notifications list
|
│                
├── settings/
│   └── index.php              # User settings
├── api/
│   ├── download.php           # Secure file download
│   └── mark-notification-read.php  # AJAX endpoint
├── uploads/                   # User-uploaded files
├── database/
│   └── schema.sql             # Database schema
├── ai-service/
│   └── README.md              # AI integration documentation
├── index.php                  # Main entry point
└── README.md                  # This file
```

## Usage Guide

### Adding a Record

1. Click "Add Record" in the top navigation
2. Fill in the required fields:
   - Title
   - Category
   - Expiry Date
3. Optional fields:
   - Issue Date
   - Description
   - Amount
   - Notes
   - Attachment
   - Reminder Days
4. Click "Add Record"

### Viewing Records

1. Navigate to "Records" in the sidebar
2. Use the search bar to find specific records
3. Filter by category or status
4. Sort by expiry date, title, created date, or amount
5. Click on a record to view details

### Renewing a Record

1. Open a record details page
2. Click the "Renew" button
3. Enter the new expiry date
4. Optionally add renewal amount and notes
5. Click "Complete Renewal"
6. The old expiry date is saved in renewal history

### Managing Reminders

1. Go to "Reminders" in the sidebar
2. Configure which automatic reminders you want:
   - 30 days before expiry
   - 15 days before expiry
   - 7 days before expiry
   - 3 days before expiry
   - 1 day before expiry
3. Set the default reminder days for new records
4. Save your settings


## Troubleshooting

### Database Connection Error

If you see a "Database connection failed" error:
- Verify MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Ensure the database `lifeleft_db` exists

### File Upload Not Working

If file uploads fail:
- Check that the `uploads/` directory exists
- Verify write permissions on the `uploads/` directory
- Ensure the file is under 5MB and is PDF, JPG, or PNG

### Reminders Not Sending

If automatic reminders aren't working:
- Verify the Task Scheduler task is set up correctly
- Check that PHP is in your system PATH
- Test the reminder script manually: `php reminders/process.php`
- Check the error logs in XAMPP

### Charts Not Displaying

If analytics charts don't appear:
- Verify you have an internet connection (Chart.js loads from CDN)
- Check browser console for JavaScript errors
- Ensure Chart.js CDN is accessible

## Future Enhancements

The application is prepared for future AI integration:

- OCR expiry date extraction from documents
- Automatic document categorization
- Intelligent reminder recommendations
- Monthly AI summaries
- Renewal cost predictions

See `ai-service/README.md` for details on future AI architecture.

## Support

For issues or questions:
1. Check this README for common solutions
2. Review the code comments for implementation details
3. Check browser console and XAMPP error logs

## License

This is a personal project for educational purposes.

## Credits

Built with PHP, MySQL, HTML5, CSS3, JavaScript, and Chart.js.
