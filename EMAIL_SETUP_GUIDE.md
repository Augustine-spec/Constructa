# Email Setup Guide for Constructa

## Issue: OTP Emails Not Sending

The OTP emails are not sending because XAMPP doesn't have outgoing mail configured by default. This guide will help you set up email functionality using PHPMailer with Gmail SMTP.

## Quick Fix: 3 Options

### Option 1: Install PHPMailer (RECOMMENDED)

1. **Install PHPMailer using Composer:**
   ```bash
   cd c:\xampp\htdocs\Constructa
   composer require phpmailer/phpmailer
   ```

2. **Configure Gmail App Password:**
   - Go to your Google Account settings
   - Enable 2-Factor Authentication if not already enabled
   - Go to Security → App Passwords
   - Generate a new app password for "Mail"
   - Copy the 16-character password

3. **Update `backend/email_config.php` with your credentials** (file will be created)

### Option 2: Use Development Mode (Testing Only)

For testing purposes, the OTP is already logged to:
- **Browser Console** (Press F12 → Console tab)
- **PHP Error Log** (`C:\xampp\php\logs\php_error_log`)

### Option 3: Use SendGrid or Other Email Service

1. Sign up for SendGrid (free tier: 100 emails/day)
2. Get API key
3. Configure in `backend/email_config.php`

## Next Steps

Run this command to install PHPMailer:
```bash
cd c:\xampp\htdocs\Constructa
composer require phpmailer/phpmailer
```

Then update the configuration file that will be created.
