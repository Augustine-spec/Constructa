# OTP Email System - Quick Fix Guide

## 🔴 PROBLEM: OTP Emails Not Sending

The OTP system is working, but emails aren't being sent because XAMPP doesn't have email configured by default.

## ✅ SOLUTION: Choose ONE Option Below

### **Option 1: Install PHPMailer (5 minutes - RECOMMENDED)**

This will enable real email sending via Gmail SMTP.

#### Step 1: Install Composer (if not installed)
Download and install from: https://getcomposer.org/download/

#### Step 2: Install PHPMailer
```bash
cd c:\xampp\htdocs\Constructa
composer require phpmailer/phpmailer
```

#### Step 3: Get Gmail App Password
1. Go to your Google Account: https://myaccount.google.com/
2. Go to **Security** → **2-Step Verification** (enable if not already)
3. Go to **Security** → **App passwords**
4. Select **Mail** and **Windows Computer**
5. Click **Generate** and copy the 16-character password

#### Step 4: Configure Email
Open `backend/email_config.php` and update:
```php
define('SMTP_USERNAME', 'your-email@gmail.com');  // Your Gmail address
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');   // Your App Password (16 chars)
```

#### Step 5: Test
Refresh the forgot password page and try sending an OTP!

---

### **Option 2: Use Development Mode (Current - No Setup Required)**

The system already works in development mode! The OTP is displayed in:

1. **Browser Console** (Press F12 → Console tab)
2. **PHP Error Log** (`C:\xampp\php\logs\php_error_log`)

**To use it:**
1. Go to forgot password page
2. Enter your email
3. Open browser console (F12)
4. Look for the green box showing: "🔐 Development OTP: XXXXXX"
5. Use that OTP to continue

---

### **Option 3: Use SendGrid (Free 100 emails/day)**

1. Sign up: https://sendgrid.com/
2. Get API key from Settings → API Keys
3. Update `backend/email_config.php`:
   ```php
   define('EMAIL_PROVIDER', 'sendgrid');
   define('SENDGRID_API_KEY', 'your-api-key-here');
   ```

---

## 🧪 TESTING RIGHT NOW (No Setup)

**The OTP system is ALREADY WORKING in development mode!**

### Test it now:
1. Go to: http://localhost/Constructa/forgot_password.html
2. Enter an email that exists in your database
3. Click "Send OTP"
4. **Open Browser Console (Press F12)**
5. You'll see: `🔐 Development OTP: 123456`
6. Use that OTP on the page

---

## 📋 Database Requirements

Make sure you have a user account in the database:
```sql
-- Check if you have users
SELECT * FROM users;

-- Create a test user if needed
INSERT INTO users (name, email, password, role) 
VALUES ('Test User', 'test@example.com', '$2y$10$...hashed...', 'homeowner');
```

---

## 🔍 Troubleshooting

### "No OTP in console"
- Make sure you entered an email that EXISTS in the database
- Check PHP error log: `C:\xampp\php\logs\php_error_log`
- Look for: "Password reset OTP for email@example.com: 123456"

### "Email not configured" message
- This is NORMAL - it means development mode is active
- The OTP is still generated and shown in console
- To fix: Follow Option 1 above to install PHPMailer

### PHP Errors
- Make sure XAMPP Apache is running
- Make sure MySQL is running
- Database `constructa` should exist

---

## 📚 Files Modified

- ✅ `backend/send_otp.php` - Now uses email_config.php
- ✅ `backend/email_config.php` - Centralized email configuration
- ✅ `backend/verify_otp.php` - Verifies OTP from database
- ✅ `backend/reset_password.php` - Resets password after verification

---

## 🎯 Quick Command Reference

```bash
# Install PHPMailer
cd c:\xampp\htdocs\Constructa
composer require phpmailer/phpmailer

# Check PHP error log (Windows PowerShell)
Get-Content C:\xampp\php\logs\php_error_log -Tail 50

# Or open in Notepad
notepad C:\xampp\php\logs\php_error_log
```

---

**Need more help?** Check the browser console for detailed debug information!
