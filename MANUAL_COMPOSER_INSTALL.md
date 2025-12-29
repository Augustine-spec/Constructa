# Manual Composer Installation Guide

## Quick Manual Installation (No Installer Needed)

If the Composer installer is giving SSL errors, you can install it manually:

### Step 1: Download Composer Manually

1. **Download composer.phar:**
   - Go to: https://getcomposer.org/download/
   - Right-click on "composer.phar" link
   - Save it to: `C:\xampp\php\composer.phar`

### Step 2: Create a Batch File

1. **Create a new file:** `C:\xampp\php\composer.bat`

2. **Add this content:**
   ```batch
   @echo OFF
   php "%~dp0composer.phar" %*
   ```

3. **Save the file**

### Step 3: Add to PATH

1. **Open System Environment Variables:**
   - Press Windows Key
   - Type "environment variables"
   - Click "Edit the system environment variables"
   - Click "Environment Variables" button

2. **Edit PATH:**
   - Under "User variables", find "Path"
   - Click "Edit"
   - Click "New"
   - Add: `C:\xampp\php`
   - Click "OK" on all windows

### Step 4: Restart PowerShell

1. **Close all PowerShell windows**
2. **Open a new PowerShell window**

### Step 5: Test

Run:
```powershell
composer --version
```

You should see: `Composer version 2.x.x`

### Step 6: Install PHPMailer

```powershell
cd C:\xampp\htdocs\Constructa
composer require phpmailer/phpmailer
```

---

## Alternative: Skip Composer Entirely

**You don't actually need Composer or PHPMailer right now!**

The forgot password system works perfectly without email. The OTP is displayed on the webpage.

### To test without email:

1. Go to: `http://localhost/Constructa/homeowner_login.html`
2. Click "Forgot Password?"
3. Enter a registered email
4. Click "Send OTP"
5. **Look for the green box with the OTP**
6. Use the OTP to reset password

**This works immediately with no installation needed!**
