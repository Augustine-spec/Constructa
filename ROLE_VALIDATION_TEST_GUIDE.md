# Role Validation Testing Guide

## Overview
This guide helps you test the new role selection and validation feature on the login page.

## Test Scenarios

### ✅ Scenario 1: Successful Login with Correct Role

**Steps:**
1. Navigate to `http://localhost/Constructa/login.html`
2. Select your actual role from the "I am a" dropdown (e.g., "Homeowner")
3. Enter your email address
4. Enter your password
5. Click "Log In"

**Expected Result:**
- ✅ Login successful
- ✅ Redirected to the appropriate dashboard:
  - Homeowner → `homeowner.html`
  - Engineer → `engineer.html`
  - Admin → `admin_dashboard.html`

---

### ❌ Scenario 2: Login with Wrong Role Selected

**Steps:**
1. Navigate to `http://localhost/Constructa/login.html`
2. Select a DIFFERENT role than your actual account (e.g., select "Engineer" when you have a Homeowner account)
3. Enter your correct email address
4. Enter your correct password
5. Click "Log In"

**Expected Result:**
- ❌ Login fails
- Error message displayed: **"Role mismatch: This account is registered as a [Actual Role], not a [Selected Role]. Please select the correct role."**
- Example: "Role mismatch: This account is registered as a Homeowner, not an Engineer. Please select the correct role."
- User remains on login page
- Can correct the role selection and try again

---

### ❌ Scenario 3: Login Without Selecting Role

**Steps:**
1. Navigate to `http://localhost/Constructa/login.html`
2. Leave the role dropdown on "Select your role" (default)
3. Enter your email address
4. Enter your password
5. Click "Log In"

**Expected Result:**
- ❌ Login fails immediately (client-side validation)
- Error message displayed: **"Please select your role."**
- Form does not submit to server
- User must select a role before proceeding

---

### ❌ Scenario 4: Invalid Credentials

**Steps:**
1. Navigate to `http://localhost/Constructa/login.html`
2. Select any role from the dropdown
3. Enter an incorrect email or password
4. Click "Log In"

**Expected Result:**
- ❌ Login fails
- Error message displayed:
  - **"No account found with this email address. Please sign up first."** (if email doesn't exist)
  - **"Incorrect password. Please try again."** (if password is wrong)

---

### ℹ️ Scenario 5: Google Account User

**Steps:**
1. Navigate to `http://localhost/Constructa/login.html`
2. Select any role
3. Enter email of an account created with Google Sign-In
4. Enter any password
5. Click "Log In"

**Expected Result:**
- ❌ Login fails
- Error message displayed: **"This account was created with Google Sign-In. Please use 'Sign in with Google' instead."**
- User should use the Google Sign-In button instead

---

## Creating Test Accounts

To properly test role validation, you need accounts with different roles:

### Method 1: Using Signup Pages
1. **Homeowner Account**: Sign up at `homeowner_signup.html`
2. **Engineer Account**: Sign up at `engineer_signup.html`
3. **Admin Account**: Manually create in database (see below)

### Method 2: Direct Database Insert (Admin Account)

```sql
-- Connect to your database
USE constructa;

-- Create an admin account
INSERT INTO users (name, email, password, role, created_at) 
VALUES (
    'Admin User',
    'admin@constructa.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
    'admin',
    NOW()
);
```

**Note:** The hashed password above is for "password". For security, use a strong password in production.

---

## Visual Indicators

### Role Dropdown States

1. **Default State**: Light gray background (#f9f9f9)
2. **Focused State**: White background with green border (#294033)
3. **Dropdown Arrow**: Gray arrow that turns green on focus

### Error Messages

- **Color**: Red (#dc2626)
- **Location**: Below the password field, above the "Log In" button
- **Font Size**: 0.85rem
- **Display**: Only shown when there's an error

---

## Testing Checklist

- [ ] Role dropdown displays all three options (Homeowner, Engineer, Admin)
- [ ] Role dropdown is required (cannot submit without selection)
- [ ] Successful login with correct role redirects to correct dashboard
- [ ] Wrong role selection shows appropriate error message
- [ ] Error message clearly states actual role vs selected role
- [ ] No role selection shows validation error
- [ ] Invalid credentials show appropriate error messages
- [ ] Google account users are directed to use Google Sign-In
- [ ] Form is disabled during login attempt (button shows "Logging in...")
- [ ] Form re-enables after failed login attempt

---

## Common Issues & Solutions

### Issue: "Invalid server response"
**Cause:** Backend PHP error or JSON parsing issue
**Solution:** 
1. Check browser console for actual server response
2. Check PHP error logs in XAMPP
3. Verify `backend/login.php` has no syntax errors

### Issue: Role dropdown not showing
**Cause:** JavaScript or CSS not loading
**Solution:**
1. Clear browser cache
2. Check browser console for errors
3. Verify `login.html` was saved correctly

### Issue: Always redirects to homeowner dashboard
**Cause:** Backend not sending correct role in response
**Solution:**
1. Check `backend/login.php` returns `role` in JSON response
2. Verify database has correct role values
3. Check session is storing role correctly

---

## Security Notes

1. **Server-Side Validation**: All role checks happen on the backend, not just frontend
2. **Session Management**: Role is stored in session after successful login
3. **No Role Bypass**: Cannot access dashboards without proper role authentication
4. **Clear Error Messages**: Helps legitimate users without exposing security details

---

## Next Steps After Testing

Once all tests pass:

1. ✅ Test with real user accounts
2. ✅ Verify dashboard access controls
3. ✅ Test Google Sign-In flow
4. ✅ Test "Forgot Password" flow
5. ✅ Implement role-based features in dashboards
6. ✅ Add role-based permissions for API endpoints

---

## Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Check XAMPP error logs for PHP errors
3. Verify database connection in `backend/config.php`
4. Review `ROLE_SELECTION_IMPLEMENTATION.md` for implementation details
