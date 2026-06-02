# SOPHEA Backend Form System - Setup Guide

## 📋 Overview

Complete backend system for the SOPHEA contact form with:
- ✅ Database storage for leads
- ✅ Email notifications to admin
- ✅ Server-side validation and security
- ✅ AJAX form submission
- ✅ Admin panel for lead management

---

## 🗂️ Files Created

### 1. Database Files

#### `database/schema.sql`
- Creates `sophea_db` database
- **Tables**:
  - `leads` - Stores contact form submissions
  - `email_log` - Tracks sent emails
  - `admin_users` - Admin authentication
  - `lead_stats` view - Statistics

#### `config_db.php`
- Database connection settings
- Email configuration
- Feature toggles

#### `classes/Database.php`
- Singleton database connection class
- CRUD operations for leads
- Email logging
- Statistics queries

### 2. Form Processing

#### `process_form.php`
- Handles form submissions via AJAX
- Server-side validation
- CSRF protection
- Database storage
- Email notifications
- Returns JSON responses

#### `sections/contacto.php` (Updated)
- AJAX form submission
- CSRF token
- Error display
- Loading states
- Success/error messages

### 3. Admin Panel

#### `admin.php`
- Simple password authentication
- Statistics dashboard
- Leads table
- Lead detail modal
- Status management

---

## 🚀 Installation Steps

### Step 1: Create the Database

1. **Access MySQL/phpMyAdmin**

2. **Run the schema**:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

   Or import `database/schema.sql` via phpMyAdmin

3. **Verify tables created**:
   - leads
   - email_log
   - admin_users
   - lead_stats (view)

### Step 2: Configure Database Connection

Edit `config_db.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sophea_db');
define('DB_USER', 'root');           // ← Change this
define('DB_PASS', 'your_password');  // ← Change this

// Email Configuration
define('ADMIN_EMAIL', 'tu@email.com');  // ← Change this
define('FROM_EMAIL', 'noreply@sophea.com.mx');
```

### Step 3: Configure Email

**Option A: PHP mail() function** (default)
- Works if your server has mail configured
- No additional setup needed

**Option B: SMTP (recommended for production)**
- Install PHPMailer: `composer require phpmailer/phpmailer`
- Update `process_form.php` to use SMTP

### Step 4: Set Admin Password

Edit `admin.php` (line 18):

```php
$admin_password = 'your_secure_password'; // Change this!
```

**Default password**: `sophea2025` (CHANGE THIS!)

### Step 5: Test the System

1. **Test form submission**:
   - Go to `index.php#contacto`
   - Fill out the form
   - Submit

2. **Check database**:
   ```sql
   SELECT * FROM leads;
   ```

3. **Check email**:
   - Verify email received at `ADMIN_EMAIL`

4. **Access admin panel**:
   - Go to `admin.php`
   - Login with password
   - View leads

---

## 🔧 Configuration Options

### Enable/Disable Features

Edit `config_db.php`:

```php
// Form Settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);   // Send emails
define('ENABLE_DATABASE_STORAGE', true);      // Save to database
define('ENABLE_WHATSAPP_REDIRECT', true);     // Redirect to WhatsApp
define('ENABLE_CSRF_PROTECTION', true);       // CSRF tokens
```

### Email Template

Customize email in `process_form.php` (function `sendEmailNotification`):
- HTML template starts at line 140
- Modify colors, layout, content

---

## 📊 Database Schema

### `leads` Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| nombre | VARCHAR(255) | Full name |
| especialidad | VARCHAR(255) | Specialty/business type |
| whatsapp | VARCHAR(50) | WhatsApp number |
| mensaje | TEXT | Optional message |
| ip_address | VARCHAR(45) | IP address |
| user_agent | TEXT | Browser info |
| created_at | TIMESTAMP | Submission date |
| status | ENUM | Lead status |
| source | VARCHAR(100) | Lead source |
| notes | TEXT | Admin notes |

### Lead Statuses

- `nuevo` - New lead (default)
- `contactado` - Contacted
- `calificado` - Qualified
- `convertido` - Converted to client
- `descartado` - Discarded

---

## 🔐 Security Features

### 1. CSRF Protection
- Token generated per session
- Validated on form submission
- Prevents cross-site request forgery

### 2. Input Validation
- Server-side validation
- Required fields checked
- Format validation (phone, length)

### 3. Input Sanitization
- HTML special chars escaped
- SQL injection prevention (PDO prepared statements)
- XSS protection

### 4. Rate Limiting (Recommended)
Add to `process_form.php`:

```php
// Simple rate limiting
session_start();
$last_submit = $_SESSION['last_submit'] ?? 0;
if (time() - $last_submit < 60) {
    throw new Exception('Por favor espera 1 minuto entre envíos');
}
$_SESSION['last_submit'] = time();
```

---

## 📧 Email Notification

### Email Content

Admins receive:
- Lead name
- Specialty/business type
- WhatsApp number (clickable)
- Message (if provided)
- IP address
- Submission date
- Lead ID

### Troubleshooting Email

**Emails not sending?**

1. Check PHP mail configuration:
   ```php
   <?php
   if (mail('test@example.com', 'Test', 'Test message')) {
       echo 'Mail works!';
   } else {
       echo 'Mail failed!';
   }
   ?>
   ```

2. Check server logs:
   ```bash
   tail -f /var/log/mail.log
   ```

3. Use SMTP instead (recommended):
   - Install PHPMailer
   - Configure SMTP settings

---

## 🎛️ Admin Panel

### Access

URL: `https://yoursite.com/admin.php`

**Default Password**: `sophea2025` (CHANGE THIS!)

### Features

1. **Statistics Dashboard**:
   - Total leads
   - New leads
   - Converted leads
   - This month's leads

2. **Leads Table**:
   - View all leads
   - Sort by date
   - Status badges
   - WhatsApp links

3. **Lead Details**:
   - Full lead information
   - Update status
   - Add notes
   - Contact via WhatsApp

### Improving Admin Security

**Production Recommendations**:

1. **Use database authentication**:
   ```php
   // Check against admin_users table
   $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? AND password_hash = ?");
   ```

2. **Add IP whitelist**:
   ```php
   $allowed_ips = ['123.456.789.0'];
   if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
       die('Access denied');
   }
   ```

3. **Use HTTPS only**:
   ```php
   if (!isset($_SERVER['HTTPS'])) {
       header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
       exit;
   }
   ```

---

## 🧪 Testing

### Test Form Submission

1. Fill out form with test data
2. Submit
3. Check for success message
4. Verify WhatsApp redirect
5. Check database for new lead
6. Check email inbox

### Test Validation

Try submitting:
- Empty fields
- Invalid phone format
- Very long text
- Special characters

Should show appropriate error messages.

### Test Admin Panel

1. Login with password
2. View statistics
3. Click on a lead
4. Update status
5. Add notes
6. Verify changes saved

---

## 📝 API Response Format

### Success Response

```json
{
    "success": true,
    "message": "¡Gracias por tu interés! Te contactaremos pronto.",
    "whatsapp_url": "https://wa.me/52961XXXXXXX?text=..."
}
```

### Error Response

```json
{
    "success": false,
    "message": "Por favor, corrija los errores en el formulario",
    "errors": {
        "nombre": "El nombre es requerido",
        "whatsapp": "Formato de WhatsApp inválido"
    }
}
```

---

## 🔄 Workflow

1. **User fills form** → `sections/contacto.php`
2. **AJAX submission** → `process_form.php`
3. **Validation** → Server-side checks
4. **Save to database** → `leads` table
5. **Send email** → Admin notification
6. **Return response** → JSON to frontend
7. **Redirect to WhatsApp** → Optional
8. **Admin views** → `admin.php`

---

## 🚨 Important Notes

### Before Going Live

1. ✅ Change database password
2. ✅ Change admin panel password
3. ✅ Update email addresses in `config_db.php`
4. ✅ Test email delivery
5. ✅ Set `DEBUG_MODE = false` in `config.php`
6. ✅ Backup database regularly
7. ✅ Use HTTPS
8. ✅ Implement rate limiting

### Maintenance

- **Backup database** weekly
- **Review leads** daily
- **Update statuses** regularly
- **Clean old leads** monthly (optional)

---

## 📞 Support

If you encounter issues:

1. Check PHP error logs
2. Check database connection
3. Verify file permissions
4. Test email configuration
5. Review browser console for AJAX errors

---

## ✅ Checklist

- [ ] Database created and configured
- [ ] `config_db.php` updated with credentials
- [ ] Admin password changed
- [ ] Email tested and working
- [ ] Form submission tested
- [ ] Admin panel accessible
- [ ] HTTPS enabled (production)
- [ ] Backups configured

---

**Your backend system is ready!** 🎉

The form now saves leads to the database, sends email notifications, and provides an admin panel for management.
