# ✅ EMAIL SYSTEM FIXED via Manual Installation

## 🔧 Solution implemented
Since the Composer installer was failing with connection errors, I have **manually installed the PHPMailer library** for you!

I downloaded the necessary files directly into: `backend/PHPMailer/`

## 🚀 What you need to do now

**The only thing missing is your Gmail credentials!**

### Step 1: Open the configuration file
Open this file in your editor: `backend/email_config.php`

### Step 2: Update your credentials
Find these lines (around line 17-18) and replace the placeholder text:

```php
define('SMTP_USERNAME', 'your-email@gmail.com');     // <--- Put your REAL Gmail address here
define('SMTP_PASSWORD', 'your-app-password');        // <--- Put your 16-char App Password here
```

### Step 3: Save and Test
1. Save the file.
2. Go to the Forgot Password page.
3. Send an OTP.
4. Check your email inbox!

## ❓ How to get the App Password?
1. Go to **Google Account** -> **Security**.
2. Enable **2-Step Verification**.
3. Search for "**App Passwords**" (or go to https://myaccount.google.com/apppasswords).
4. Create a new one for "Mail".
5. Copy the 16-character code (e.g., `abcd efgh ijkl mnop`).

**Note:** Do NOT use your regular Gmail password. It must be an App Password.
