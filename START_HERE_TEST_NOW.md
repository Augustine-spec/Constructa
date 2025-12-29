# 🚀 QUICK START - Test Forgot Password NOW

## No Email Setup Needed!

The OTP system is working RIGHT NOW in development mode. The OTP shows on the page instead of email.

## How to Test (2 Minutes):

### Step 1: Make Sure You Have a Test User

Open phpMyAdmin: http://localhost/phpmyadmin

Run this SQL to create a test user:

```sql
USE constructa;

INSERT INTO users (name, email, password, role) 
VALUES ('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'homeowner');
```

(This creates a user with email: `test@example.com` and password: `password`)

### Step 2: Test the Forgot Password Flow

1. **Open your browser:**
   ```
   http://localhost/Constructa/homeowner_login.html
   ```

2. **Click "Forgot Password?"**

3. **Enter the test email:**
   ```
   test@example.com
   ```

4. **Click "Send OTP"**

5. **LOOK FOR THE GREEN BOX!** You'll see:
   ```
   ┌─────────────────────────────────────────┐
   │ 📧 Email Not Configured - Development   │
   │           OTP:                          │
   │                                         │
   │           123456                        │
   │                                         │
   │ See OTP_EMAIL_FIX.md to enable email   │
   └─────────────────────────────────────────┘
   ```

6. **Also check browser console** (Press F12):
   - Click "Console" tab
   - You'll see the OTP logged there too

7. **Copy the OTP** from the green box

8. **Enter it in the OTP field**

9. **Click "Verify OTP"**

10. **Enter a new password** (at least 8 characters)

11. **Click "Reset Password"**

12. **Success!** You'll be redirected to login

13. **Log in with:**
    - Email: `test@example.com`
    - Password: (the new password you just set)

---

## ✅ Expected Result:

- ✅ Green box appears with OTP
- ✅ OTP is also in browser console
- ✅ OTP verification works
- ✅ Password reset works
- ✅ You can log in with new password

---

## 📧 To Get OTP in Email Instead:

See the file: `WHAT_YOU_NEED_TO_DO.md`

It has step-by-step instructions to configure Gmail (takes 5 minutes).

---

## 🆘 Troubleshooting:

**"Green box not appearing"**
- Check if the email exists in database
- Check browser console (F12) - OTP is logged there
- Make sure XAMPP MySQL is running

**"Email not found error"**
- The email doesn't exist in the database
- Create a test user using the SQL above

**"OTP verification fails"**
- Make sure you're entering the exact OTP from the green box
- OTP expires after 10 minutes - request a new one if needed

---

## 💡 Summary:

**Right Now:**
- OTP shows on the page in a green box ✅
- No email configuration needed ✅
- Perfect for testing ✅

**For Production:**
- Configure Gmail to send real emails
- See `WHAT_YOU_NEED_TO_DO.md` for instructions
- Takes about 5 minutes

**Start testing now!** 🚀
