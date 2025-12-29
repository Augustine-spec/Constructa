# Quick Test Instructions

## Test the Forgot Password System (2 Minutes)

### Prerequisites:
- XAMPP Apache and MySQL are running
- You have at least one user in the database

### If you don't have a test user, create one:

```sql
-- Open phpMyAdmin: http://localhost/phpmyadmin
-- Select the 'constructa' database
-- Run this SQL:

INSERT INTO users (name, email, password, role) 
VALUES ('Test User', 'test@example.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJK', 'homeowner');
```

### Test Steps:

1. **Open your browser and go to:**
   ```
   http://localhost/Constructa/homeowner_login.html
   ```

2. **Click "Forgot Password?"**

3. **Enter the test email:**
   ```
   test@example.com
   ```

4. **Click "Send OTP"**

5. **You should see a GREEN BOX** with the OTP displayed prominently

6. **Copy the OTP** and paste it in the next field

7. **Click "Verify OTP"**

8. **Enter a new password** (at least 8 characters)

9. **Click "Reset Password"**

10. **Success!** You'll be redirected to the login page

### Expected Result:

✅ OTP appears in a big green box on the page
✅ OTP verification works
✅ Password is successfully reset
✅ You can log in with the new password

### If Email is Configured:

✅ OTP will be sent to your email
✅ No green box will appear (OTP only in email)
✅ Check your inbox for the email

---

## Visual Flow:

```
Login Page
    ↓ (Click "Forgot Password?")
Forgot Password Page - Step 1: Enter Email
    ↓ (Click "Send OTP")
[GREEN BOX APPEARS WITH OTP: 123456]
Forgot Password Page - Step 2: Enter OTP
    ↓ (Enter OTP and click "Verify OTP")
Forgot Password Page - Step 3: New Password
    ↓ (Enter new password and click "Reset Password")
Success! → Redirected to Login Page
```

---

## Common Issues:

**"Email not found"**
- The email doesn't exist in the database
- Make sure you're using the correct role (homeowner/engineer)

**"Invalid OTP"**
- You entered the wrong OTP
- OTP might have expired (10 minutes)
- Request a new OTP

**"OTP not showing"**
- Check browser console (F12)
- Check that XAMPP MySQL is running
- Verify the email exists in the database
