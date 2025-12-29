# Session Variable Fix - Login Redirect Issue

## Problem
After logging in, users remained on the login page instead of being redirected to their dashboard.

## Root Cause
There was a **mismatch in session variable names** between:
- **Backend files** (login.php, signup.php, etc.) - using `$_SESSION['user_role']`, `$_SESSION['user_name']`
- **Frontend dashboard files** (homeowner.php, engineer.php, etc.) - checking for `$_SESSION['role']`, `$_SESSION['full_name']`

This caused the dashboard pages to think the user wasn't logged in and redirect them back to the login page.

## Solution
Updated ALL backend files to use consistent session variable names that match what the dashboard expects:

### Session Variables - BEFORE ❌
```php
$_SESSION['user_id']     // ✅ OK
$_SESSION['user_name']   // ❌ Wrong - dashboards expect 'full_name'
$_SESSION['user_email']  // ❌ Wrong - dashboards expect 'email'
$_SESSION['user_role']   // ❌ Wrong - dashboards expect 'role'
```

### Session Variables - AFTER ✅
```php
$_SESSION['user_id']     // ✅ User ID
$_SESSION['full_name']   // ✅ User's full name
$_SESSION['email']       // ✅ User's email
$_SESSION['role']        // ✅ User's role (homeowner/engineer/admin)
```

## Files Fixed

### ✅ 1. backend/login.php
- Traditional email/password login
- Updated session variables

### ✅ 2. backend/google_login.php
- Google Sign-In login
- Updated session variables

### ✅ 3. backend/signup.php
- Traditional signup
- Updated session variables

### ✅ 4. backend/google_signup.php
- Google Sign-In signup (both new users and existing users)
- Updated session variables in 2 places

## What Now Works ✅

1. **Login with Email/Password**: 
   - Sets correct session variables
   - Redirects to proper dashboard (homeowner.php, engineer.php, or admin_dashboard.php)
   - Dashboard recognizes the session and shows user's name

2. **Google Sign-In Login**:
   - Sets correct session variables
   - Redirects to proper dashboard
   - Dashboard recognizes the session

3. **Traditional Signup**:
   - Creates account with correct session
   - Auto-logs in user
   - Redirects to homeowner.php

4. **Google Sign-In Signup**:
   - Creates account or logs in existing user
   - Sets correct session variables
   - Works properly

## Testing Steps

1. **Clear your browser cache and cookies** (important!)
2. Go to `http://localhost/Constructa/login.html`
3. Log in with your credentials
4. You should be redirected to your dashboard (homeowner.php, engineer.php, or admin_dashboard.php)
5. The dashboard should show "Welcome back, [Your Name]" instead of redirecting back to login

## Technical Details

The dashboard pages check session like this:
```php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}
```

If `$_SESSION['role']` doesn't exist (because backend set `$_SESSION['user_role']`), the user gets redirected back to login even though they successfully logged in.

---

**Status**: ✅ FIXED  
**Date**: 2025-12-29  
**Files Modified**: 4 backend files  
**Issue**: Session variable naming mismatch  
**Solution**: Standardized all session variables
