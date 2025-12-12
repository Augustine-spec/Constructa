# OTP-Based Password Reset Implementation

## Overview
The password reset system has been completely updated to use **OTP (One-Time Password)** verification instead of email links.

## Implementation Summary

### What Changed

#### 1. Frontend (`forgot_password.html`)
- **3-Step Process:**
  1. **Step 1:** User enters email → Receives OTP via email
  2. **Step 2:** User enters OTP → System verifies it
  3. **Step 3:** User creates new password → Password is reset

#### 2. Backend PHP Files
Created three new backend scripts:

1. **`send_otp.php`** - Generates and sends 6-digit OTP via email
2. **`verify_otp.php`** - Validates the OTP entered by user
3. **`reset_password.php`** - Updates password after OTP verification

## How It Works

### User Flow

```
1. User clicks "Forgot Password" from login page
   ↓
2. User enters email address
   ↓
3. System sends 6-digit OTP to email (valid for 10 minutes)
   ↓
4. User enters OTP on website
   ↓
5. System verifies OTP
   ↓
   - If INVALID → Show validation error: "Invalid OTP. Please try again."
   - If EXPIRED → Show validation error: "OTP has expired. Please request a new one."
   - If VALID → Proceed to password reset
   ↓
6. User enters new password
   ↓
7. System updates password and redirects to login
```

### Features

✅ **6-digit OTP** - Easy to remember and type
✅ **10-minute expiry** - Security best practice
✅ **Resend OTP** - If user doesn't receive it
✅ **Single-use OTP** - Can't be reused after verification
✅ **Proper validation errors** - Clear feedback on what went wrong
✅ **Beautiful UI** - Matches existing design system
✅ **Progressive disclosure** - Shows only relevant form at each step

## Database Tables

### `password_otp` Table
Created automatically on first use:

```sql
CREATE TABLE password_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('homeowner', 'engineer') NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expiry DATETIME NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(otp)
)
```

## Email Configuration

### Current Status: Development Mode
For development, OTPs are logged to PHP error log. You'll see:
```
Password reset OTP for user@email.com: 123456
```

### For Production
To enable actual email sending:

1. **Option A: PHP mail() function**
   - Uncomment line in `send_otp.php`:
   ```php
   $mailSent = mail($to, $subject, $message, $headers);
   ```
   - Requires server mail configuration

2. **Option B: PHPMailer (Recommended)**
   - Install: `composer require phpmailer/phpmailer`
   - Configure SMTP settings
   - Better deliverability

3. **Option C: Email Services**
   - SendGrid
   - AWS SES
   - Mailgun
   - More reliable for production

## Testing Instructions

### 1. Test Send OTP
1. Navigate to: `http://localhost/Constructa/forgot_password.html`
2. Enter a valid email from your database
3. Click "Send OTP"
4. Check PHP error log for the OTP:
   - XAMPP: `C:\xampp\php\logs\php_error_log`
   - Or check response JSON (development mode includes `dev_otp`)

### 2. Test OTP Verification
1. Enter the 6-digit OTP received
2. Click "Verify OTP"
3. **Test Cases:**
   - ✅ Valid OTP → Should show password form
   - ❌ Invalid OTP → Should show: "Invalid OTP. Please check and try again."
   - ❌ Expired OTP → Should show: "OTP has expired. Please request a new one."
   - ❌ Already used OTP → Should show: "This OTP has already been used."

### 3. Test Resend OTP
1. Click "Resend OTP" button
2. Check for new OTP in logs/email
3. Old OTP should be invalidated

### 4. Test Password Reset
1. After OTP verification, enter new password
2. Confirm password (must match)
3. Click "Reset Password"
4. Should redirect to login page
5. Try logging in with new password

## Validation Errors

The system shows appropriate errors for:

### Email Step
- Empty email
- Invalid email format

### OTP Step
- Empty OTP
- OTP not 6 digits
- Invalid OTP (not in database)
- Expired OTP (> 10 minutes)
- Already used OTP
- Wrong OTP digits

### Password Step
- Password less than 8 characters
- Passwords don't match

## Security Features

1. ✅ **OTP Expiry** - 10 minutes validity
2. ✅ **Single Use** - OTP marked as verified after use
3. ✅ **No Email Enumeration** - Same message for existing/non-existing emails
4. ✅ **Hashed Passwords** - Uses PHP `password_hash()`
5. ✅ **Old OTP Cleanup** - Previous OTPs deleted on new request
6. ✅ **Input Validation** - Server-side validation for all inputs

## Files Modified/Created

### Modified
- `forgot_password.html` - Complete UI redesign for OTP flow

### Created
- `backend/send_otp.php` - OTP generation and email
- `backend/verify_otp.php` - OTP validation
- `backend/reset_password.php` - Password update

## API Endpoints

### POST `/backend/send_otp.php`
**Request:**
```json
{
  "email": "user@example.com",
  "role": "homeowner"
}
```
**Response:**
```json
{
  "success": true,
  "message": "OTP sent successfully.",
  "dev_otp": "123456"  // Development only
}
```

### POST `/backend/verify_otp.php`
**Request:**
```json
{
  "email": "user@example.com",
  "otp": "123456",
  "role": "homeowner"
}
```
**Response:**
```json
{
  "success": true,
  "message": "OTP verified successfully."
}
```

### POST `/backend/reset_password.php`
**Request:**
```json
{
  "email": "user@example.com",
  "password": "newpassword123",
  "role": "homeowner"
}
```
**Response:**
```json
{
  "success": true,
  "message": "Password reset successfully."
}
```

## Troubleshooting

### OTP not received?
1. Check PHP error log for the OTP (development mode)
2. Verify email exists in database
3. Check spam folder (if mail configured)
4. Use "Resend OTP" button

### "Invalid OTP" error?
1. Double-check the 6 digits
2. Make sure OTP hasn't expired (10 min)
3. Request new OTP if expired

### Can't reset password?
1. Ensure OTP was verified first
2. Password must be 8+ characters
3. Both passwords must match

### Database errors?
1. Ensure XAMPP MySQL is running
2. Database should auto-create on first use
3. Check config.php for correct credentials

## Next Steps

1. ✅ Test the complete flow manually
2. ⏳ Configure actual email sending for production
3. ⏳ Add rate limiting (prevent spam)
4. ⏳ Add email templates with company branding
5. ⏳ Consider adding SMS as alternative to email

## Support

If you encounter any issues:
1. Check browser console for errors
2. Check PHP error logs
3. Verify database tables created correctly
4. Test with a valid email from your database
