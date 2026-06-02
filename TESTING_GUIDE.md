# SOPHEA - Local Testing Guide

## 🧪 Complete Testing Checklist

This guide will help you test the SOPHEA website locally before deploying to production.

---

## Prerequisites

### Required Software

1. **PHP 7.4+**
   - Download: https://www.php.net/downloads
   - Or use XAMPP/WAMP/MAMP

2. **MySQL/MariaDB**
   - Included in XAMPP/WAMP/MAMP
   - Or standalone: https://www.mysql.com/downloads/

3. **Web Browser**
   - Chrome, Firefox, or Edge (latest version)

4. **Text Editor** (Optional)
   - VS Code, Sublime Text, or similar

---

## Step 1: Setup Local Server

### Option A: PHP Built-in Server (Quick)

```bash
# Navigate to project directory
cd c:\Users\dell\Documents\Sophea\web_admin

# Start PHP server
php -S localhost:8000
```

Access: `http://localhost:8000/index.php`

### Option B: XAMPP (Recommended)

1. **Install XAMPP**
   - Download from: https://www.apachefriends.org/
   - Install to `C:\xampp`

2. **Copy project files**
   ```
   Copy web_admin folder to:
   C:\xampp\htdocs\sophea\
   ```

3. **Start Apache and MySQL**
   - Open XAMPP Control Panel
   - Click "Start" for Apache
   - Click "Start" for MySQL

4. **Access site**
   - URL: `http://localhost/sophea/index.php`

---

## Step 2: Database Setup

### 1. Access phpMyAdmin

- URL: `http://localhost/phpmyadmin`
- Username: `root`
- Password: (leave empty for XAMPP)

### 2. Import Database

1. Click "New" to create database
2. Name it: `sophea_db`
3. Click "Import" tab
4. Choose file: `database/schema.sql`
5. Click "Go"

### 3. Verify Tables Created

Check that these tables exist:
- ✅ `leads`
- ✅ `email_log`
- ✅ `admin_users`
- ✅ `lead_stats` (view)

### 4. Update Database Credentials

Edit `config_db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sophea_db');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP
```

---

## Step 3: Test Static Pages

### Homepage (index.php)

**URL**: `http://localhost:8000/index.php`

**Test Checklist**:
- [ ] Page loads without errors
- [ ] Logo displays correctly
- [ ] Navigation menu works
- [ ] Hero section visible
- [ ] Dashboard mockup displays
- [ ] Método section (dark background)
- [ ] Servicios section (4 cards)
- [ ] Casos de Éxito section (2 cases)
- [ ] Contact form visible
- [ ] Footer displays
- [ ] WhatsApp button (bottom-right)

**Mobile Test**:
- [ ] Hamburger menu appears on mobile
- [ ] Menu opens/closes correctly
- [ ] All sections responsive
- [ ] Text readable on small screens

### Services Page (servicios.php)

**URL**: `http://localhost:8000/servicios.php`

**Test Checklist**:
- [ ] Page loads without errors
- [ ] Hero section displays
- [ ] All 4 services shown in detail
- [ ] Icons display correctly
- [ ] Dual descriptions (Health/General)
- [ ] Technology badges visible
- [ ] Benefits cards display
- [ ] CTA section at bottom
- [ ] Contact form included

---

## Step 4: Test Navigation

### Menu Links

Click each menu item and verify:

| Link | Expected Behavior | ✓ |
|------|-------------------|---|
| Logo | Goes to homepage | [ ] |
| Método | Scrolls to Método section | [ ] |
| Servicios | Opens servicios.php | [ ] |
| Casos de Éxito | Scrolls to Casos section | [ ] |
| Contacto | Scrolls to Contact form | [ ] |
| CTA Button | Scrolls to Contact form | [ ] |

### Smooth Scrolling

- [ ] Anchor links scroll smoothly
- [ ] No page jumps
- [ ] Scroll animation works

---

## Step 5: Test Contact Form

### Form Display

- [ ] All fields visible
- [ ] Labels clear
- [ ] Placeholders helpful
- [ ] Submit button styled
- [ ] CSRF token present (hidden field)

### Form Validation (Client-side)

Try submitting with:

| Test Case | Expected Result | ✓ |
|-----------|-----------------|---|
| Empty form | Browser validation error | [ ] |
| Only name | Error for other required fields | [ ] |
| Invalid phone | Validation message | [ ] |
| All valid | Form submits | [ ] |

### Form Submission (Server-side)

1. **Fill form with test data**:
   - Nombre: `Test User`
   - Especialidad: `Testing`
   - WhatsApp: `+52 961 123 4567`
   - Mensaje: `This is a test`

2. **Submit form**

3. **Expected Results**:
   - [ ] Loading state shows ("Enviando...")
   - [ ] Success message appears
   - [ ] Form resets
   - [ ] WhatsApp opens (if enabled)

4. **Check Database**:
   ```sql
   SELECT * FROM leads ORDER BY id DESC LIMIT 1;
   ```
   - [ ] New lead record exists
   - [ ] All fields populated correctly
   - [ ] IP address captured
   - [ ] Timestamp correct

5. **Check Email** (if configured):
   - [ ] Email received at `ADMIN_EMAIL`
   - [ ] Email formatted correctly
   - [ ] All lead details present
   - [ ] WhatsApp link works

### Form Error Handling

Test with invalid data:

| Test Case | Expected Result | ✓ |
|-----------|-----------------|---|
| Name < 3 chars | Error message | [ ] |
| Invalid phone format | Error message | [ ] |
| Message > 1000 chars | Error message | [ ] |
| Invalid CSRF token | Security error | [ ] |

---

## Step 6: Test Admin Panel

### Access Admin Panel

**URL**: `http://localhost:8000/admin.php`

### Login Test

1. **Default Credentials**:
   - Password: `sophea2025`

2. **Test Cases**:
   - [ ] Login page displays
   - [ ] Wrong password shows error
   - [ ] Correct password logs in
   - [ ] Redirects to dashboard

### Dashboard Test

- [ ] Statistics cards display
- [ ] Total leads count correct
- [ ] Status counts accurate
- [ ] This month count correct

### Leads Table Test

- [ ] All leads display
- [ ] Columns show correctly
- [ ] WhatsApp links work
- [ ] Status badges colored
- [ ] Dates formatted properly

### Lead Detail Modal

1. **Click "Ver Detalles" on a lead**

2. **Verify**:
   - [ ] Modal opens
   - [ ] All lead info displays
   - [ ] Status dropdown works
   - [ ] Notes field editable
   - [ ] Update button works
   - [ ] Changes save to database

### Logout Test

- [ ] Logout link works
- [ ] Session destroyed
- [ ] Redirects to login

---

## Step 7: Test Responsive Design

### Desktop (1920x1080)

- [ ] Layout looks professional
- [ ] No horizontal scroll
- [ ] Images sized correctly
- [ ] Text readable

### Tablet (768x1024)

- [ ] 2-column layouts work
- [ ] Navigation adapts
- [ ] Forms usable
- [ ] Cards stack properly

### Mobile (375x667)

- [ ] Single column layout
- [ ] Hamburger menu appears
- [ ] Touch targets adequate
- [ ] Text not too small
- [ ] Images responsive
- [ ] Forms easy to fill

### Test in Multiple Browsers

- [ ] Chrome
- [ ] Firefox
- [ ] Edge
- [ ] Safari (if available)

---

## Step 8: Test Performance

### Page Load Speed

Use browser DevTools (F12):

1. **Network Tab**:
   - [ ] Page loads < 3 seconds
   - [ ] No 404 errors
   - [ ] All resources load

2. **Console Tab**:
   - [ ] No JavaScript errors
   - [ ] No warnings

### Lighthouse Audit

1. Open DevTools (F12)
2. Go to "Lighthouse" tab
3. Run audit

**Target Scores**:
- Performance: > 80
- Accessibility: > 90
- Best Practices: > 90
- SEO: > 90

---

## Step 9: Test Security

### CSRF Protection

1. **Inspect form**:
   - [ ] CSRF token present
   - [ ] Token changes per session

2. **Test without token**:
   - [ ] Submission fails
   - [ ] Error message shown

### SQL Injection Test

Try submitting:
```
Nombre: ' OR '1'='1
```

- [ ] Input sanitized
- [ ] No SQL error
- [ ] Data stored safely

### XSS Test

Try submitting:
```
Mensaje: <script>alert('XSS')</script>
```

- [ ] Script not executed
- [ ] HTML escaped
- [ ] Displays as text

---

## Step 10: Test Email System

### Configure Email

1. **Update `config_db.php`**:
   ```php
   define('ADMIN_EMAIL', 'your_real_email@example.com');
   ```

2. **Test PHP mail**:
   ```php
   <?php
   mail('test@example.com', 'Test', 'Test message');
   ?>
   ```

### Submit Test Lead

1. Fill and submit form
2. Check email inbox
3. Verify:
   - [ ] Email received
   - [ ] Subject correct
   - [ ] HTML formatted
   - [ ] All details present
   - [ ] Links work

### Check Email Log

```sql
SELECT * FROM email_log ORDER BY id DESC LIMIT 1;
```

- [ ] Email logged
- [ ] Status = 'sent'
- [ ] No error message

---

## Common Issues & Solutions

### Issue: Page shows PHP code

**Solution**: Make sure PHP server is running and accessing via `http://localhost`, not `file://`

### Issue: Database connection error

**Solutions**:
1. Check MySQL is running
2. Verify credentials in `config_db.php`
3. Ensure database `sophea_db` exists

### Issue: Form doesn't submit

**Solutions**:
1. Check browser console for errors
2. Verify `process_form.php` exists
3. Check file permissions

### Issue: Emails not sending

**Solutions**:
1. Check PHP mail configuration
2. Use SMTP instead (PHPMailer)
3. Check spam folder

### Issue: Admin panel won't login

**Solutions**:
1. Verify password in `admin.php`
2. Check session is starting
3. Clear browser cookies

---

## Testing Checklist Summary

### Critical Tests (Must Pass)

- [ ] Homepage loads without errors
- [ ] Navigation works correctly
- [ ] Contact form submits successfully
- [ ] Data saves to database
- [ ] Admin panel accessible
- [ ] Responsive on mobile
- [ ] No console errors

### Important Tests (Should Pass)

- [ ] Email notifications work
- [ ] CSRF protection active
- [ ] Input sanitization working
- [ ] All pages responsive
- [ ] Performance acceptable
- [ ] Services page displays

### Nice to Have

- [ ] Lighthouse scores > 80
- [ ] Works in all browsers
- [ ] Email logs correctly
- [ ] Smooth animations

---

## Next Steps After Testing

Once all tests pass:

1. **Update Placeholders**:
   - Change phone numbers
   - Update email addresses
   - Set real WhatsApp number
   - Change admin password

2. **Optimize**:
   - Compress images
   - Minify CSS/JS
   - Enable caching

3. **Deploy**:
   - Choose hosting provider
   - Upload files
   - Configure domain
   - Enable HTTPS

4. **Monitor**:
   - Set up analytics
   - Monitor errors
   - Track conversions
   - Backup regularly

---

## Testing Log Template

Use this to track your testing:

```
Date: ___________
Tester: ___________

✓ = Pass | ✗ = Fail | - = Not Tested

[ ] Homepage loads
[ ] Navigation works
[ ] Form submits
[ ] Database saves
[ ] Email sends
[ ] Admin panel works
[ ] Mobile responsive
[ ] No errors

Issues Found:
1. ___________
2. ___________

Notes:
___________
```

---

**Happy Testing!** 🧪

If you encounter any issues not covered here, check the error logs or contact support.
