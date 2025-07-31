# Jaipur Metro Complaint Portal

A modern, responsive complaint management system for Jaipur Metro built with PHP, MySQL, and Bootstrap.

## 🚀 Features

### User Features
- **User Registration & Login**: Secure authentication system with password hashing
- **Dashboard**: Personal dashboard with complaint statistics and quick actions
- **Complaint Submission**: Easy-to-use form with file upload capability
- **Real-time Tracking**: Track complaint status and admin responses
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

### Admin Features
- **Admin Dashboard**: Comprehensive overview of all complaints
- **Complaint Management**: Update status, add responses, and delete complaints
- **Statistics Overview**: Real-time stats of complaint statuses
- **User Management**: View all users and their complaint history

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- mod_rewrite enabled (for Apache)

## 🛠️ Installation & Setup

### Step 1: Clone/Download the Project
```bash
git clone <repository-url>
cd jaipur-metro-complaints
```

### Step 2: Database Setup
1. Create a MySQL database named `jaipur_metro_complaints`
2. Import the database schema:
```bash
mysql -u your_username -p jaipur_metro_complaints < database_schema.sql
```

### Step 3: Configure Database Connection
Copy the example configuration file and update with your credentials:
```bash
cp config/database.php.example config/database.php
```
Then edit `config/database.php` and update the database credentials:
```php
private $host = 'localhost';
private $dbname = 'jaipur_metro_complaints';
private $username = 'your_db_username';
private $password = 'your_db_password';
```

### Step 4: Set Permissions
```bash
chmod 755 uploads/
chmod 644 config/database.php
```

### Step 5: Web Server Configuration

#### For Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### For Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 🎯 Usage

### For Users
1. Visit the homepage and click "Register" to create an account
2. Login with your credentials
3. Submit complaints using the "Submit Complaint" button
4. Track your complaints from the dashboard
5. View status updates and admin responses

### For Admins
1. Login with admin credentials
   - Default admin: `admin@jaipurmetro.com`
   - Default password: `password` (change immediately)
2. Access the admin panel from the dashboard
3. Manage complaints: update status, add responses
4. View comprehensive statistics and user information

## 📁 Project Structure

```
jaipur-metro-complaints/
│
├── config/
│   └── database.php          # Database configuration
│
├── includes/
│   └── auth.php              # Authentication functions
│
├── admin/
│   └── dashboard.php         # Admin panel
│
├── images/                   # Static images
├── uploads/                  # User uploaded files
│
├── index.php                 # Homepage with login/register
├── dashboard.php             # User dashboard
├── submit_complaint.php      # Complaint submission form
├── logout.php               # Logout functionality
├── database_schema.sql      # Database structure
└── README.md                # This file
```

## 🔒 Security Features

- **Password Hashing**: All passwords are securely hashed using PHP's `password_hash()`
- **SQL Injection Prevention**: All database queries use prepared statements
- **XSS Protection**: All user inputs are sanitized and escaped
- **Session Management**: Secure session handling with proper cleanup
- **File Upload Security**: Restricted file types and secure file naming
- **Admin Access Control**: Role-based access to admin features

## 🎨 Technology Stack

- **Frontend**: Bootstrap 5, Font Awesome, Custom CSS
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Authentication**: PHP Sessions with secure hashing
- **File Handling**: PHP file upload with validation

## 📱 Responsive Design

The application is fully responsive and works across all devices:
- **Desktop**: Full-featured experience with all functionality
- **Tablet**: Optimized layout with touch-friendly interfaces
- **Mobile**: Mobile-first design with collapsible navigation

## 🔧 Configuration Options

### Database Configuration
Edit `config/database.php` to modify:
- Database host, name, username, password
- Connection charset and options

### File Upload Settings
Modify `submit_complaint.php` to change:
- Maximum file size
- Allowed file types
- Upload directory

### Authentication Settings
Edit `includes/auth.php` to modify:
- Session timeout
- Password requirements
- Security tokens

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and user has proper permissions

2. **File Upload Issues**
   - Check `uploads/` directory permissions (755)
   - Verify PHP `upload_max_filesize` and `post_max_size` settings
   - Ensure disk space is available

3. **Session Issues**
   - Check PHP session configuration
   - Verify write permissions on session directory
   - Clear browser cookies and cache

4. **Permission Denied Errors**
   - Set proper file permissions: `chmod 644` for files, `chmod 755` for directories
   - Ensure web server user has read/write access

## 📞 Support

For technical support or questions about the Jaipur Metro Complaint Portal:
- Email: support@jaipurmetro.com
- Phone: +91-141-XXXXXX

## 📄 License

This project is developed for Jaipur Metro Rail Corporation Limited. All rights reserved.

## 🤝 Contributing

This is a proprietary system for Jaipur Metro. For contribution guidelines, please contact the development team.

---

**© 2025 Jaipur Metro Rail Corporation Limited**
