# Role Validation Fix - Troubleshooting Guide

## Issue Reported

When selecting "Engineer" role and logging in with an engineer account, a validation error appears saying "This account is registered as an Engineer" - even though the roles match.

## Root Cause Analysis

The issue could be caused by:

1. **Case Sensitivity**: Database has "Engineer" but dropdown sends "engineer"
2. **Whitespace**: Extra spaces in database role values
3. **Encoding Issues**: Special characters or different character encoding
4. **Logic Error**: Validation condition might be inverted

## Fix Applied

### Updated `backend/login.php`

**Before (Strict Comparison):**
```php
if ($selectedRole && $user['role'] !== $selectedRole) {
    throw new Exception("Role mismatch...");
}
```

**After (Normalized Comparison):**
```php
if ($selectedRole) {
    // Normalize both roles to lowercase for comparison
    $userRoleLower = strtolower(trim($user['role']));
    $selectedRoleLower = strtolower(trim($selectedRole));
    
    // Debug logging
    error_log("Role Validation - User Role: '{$userRoleLower}', Selected Role: '{$selectedRoleLower}'");
    
    if ($userRoleLower !== $selectedRoleLower) {
        throw new Exception("Role mismatch...");
    }
}
```

### Key Improvements:

1. **`trim()`** - Removes leading/trailing whitespace
2. **`strtolower()`** - Converts to lowercase for case-insensitive comparison
3. **Debug logging** - Logs actual values being compared
4. **Clearer logic** - Only throws error when roles DON'T match

## Testing Tools Created

### 1. debug_roles.php
**Purpose**: Check all user roles in the database
**URL**: `http://localhost/Constructa/debug_roles.php`

**What it shows:**
- All users with their roles
- Role length (to detect whitespace)
- Role hex values (to detect special characters)

**How to use:**
1. Open in browser
2. Check if any roles have unexpected length or characters
3. Compare with expected values shown at bottom

### 2. test_role_validation.php
**Purpose**: Test the validation logic with different scenarios
**URL**: `http://localhost/Constructa/test_role_validation.php`

**What it tests:**
- Exact match: `engineer` vs `engineer` ✅
- Case difference: `Engineer` vs `engineer` ✅
- Whitespace: `engineer ` vs `engineer` ✅
- Real mismatch: `homeowner` vs `engineer` ❌

**How to use:**
1. Open in browser
2. All rows should show green in "New Logic" column
3. If any show red, the logic needs further adjustment

## Step-by-Step Testing

### Step 1: Check Database Roles
```
1. Open: http://localhost/Constructa/debug_roles.php
2. Look at the "Role" and "Role Length" columns
3. Expected lengths:
   - homeowner: 9 characters
   - engineer: 8 characters
   - admin: 5 characters
4. If lengths don't match, there's whitespace in the database
```

### Step 2: Test Validation Logic
```
1. Open: http://localhost/Constructa/test_role_validation.php
2. Verify all tests pass (green) in "New Logic" column
3. This confirms the normalization works correctly
```

### Step 3: Test Actual Login
```
1. Open: http://localhost/Constructa/login.html
2. Select "Engineer" from role dropdown
3. Enter engineer account credentials
4. Click "Log In"
5. Expected: Successful login → redirect to engineer.html
6. If error occurs, check Apache error logs for debug output
```

### Step 4: Check Debug Logs
```
1. Open: C:\xampp\apache\logs\error.log
2. Look for lines like: "Role Validation - User Role: 'engineer', Selected Role: 'engineer'"
3. This shows exactly what's being compared
4. If values don't match, investigate why
```

## Common Issues & Solutions

### Issue 1: Roles Still Don't Match

**Symptom**: Error persists even after fix

**Solution**:
```sql
-- Clean up database roles (remove whitespace)
UPDATE users SET role = TRIM(role);

-- Ensure all roles are lowercase
UPDATE users SET role = LOWER(role);
```

### Issue 2: Google Sign-In Users Can't Login

**Symptom**: Google users get role validation error

**Solution**: Google Sign-In doesn't send a role, so validation should be skipped. The current code already handles this with `if ($selectedRole)`.

### Issue 3: Admin Can't Login

**Symptom**: Admin role not recognized

**Solution**: Ensure admin accounts exist in database:
```sql
SELECT * FROM users WHERE role = 'admin';
```

If none exist, create one:
```sql
INSERT INTO users (name, email, password, role, created_at) 
VALUES ('Admin', 'admin@constructa.com', '$2y$10$...', 'admin', NOW());
```

## Verification Checklist

After applying the fix, verify:

- [ ] `debug_roles.php` shows clean role values (no extra spaces)
- [ ] `test_role_validation.php` shows all green results
- [ ] Login with homeowner account + "Homeowner" role = Success
- [ ] Login with engineer account + "Engineer" role = Success
- [ ] Login with admin account + "Admin" role = Success
- [ ] Login with homeowner account + "Engineer" role = Error (correct behavior)
- [ ] Google Sign-In works without role selection
- [ ] Error messages are clear and helpful

## Expected Behavior

### ✅ Correct Role Selected
```
Role: Engineer
Email: engineer@example.com
Password: correct_password
Result: ✅ Login successful → Redirect to engineer.html
```

### ❌ Wrong Role Selected
```
Role: Homeowner
Email: engineer@example.com
Password: correct_password
Result: ❌ Error: "Role mismatch: This account is registered as an Engineer, not a Homeowner. Please select the correct role."
```

### ❌ No Role Selected
```
Role: (not selected)
Email: engineer@example.com
Password: correct_password
Result: ❌ Error: "Please select your role."
```

## Files Modified

1. **backend/login.php** - Updated role validation logic
2. **debug_roles.php** - New debugging tool
3. **test_role_validation.php** - New testing tool

## Next Steps

1. **Run debug_roles.php** to check current database state
2. **Run test_role_validation.php** to verify logic works
3. **Test actual login** with different role combinations
4. **Check error logs** if issues persist
5. **Clean database** if whitespace/case issues found

## Production Cleanup

Before going to production, remove debug logging:

```php
// Remove this line from backend/login.php:
error_log("Role Validation - User Role: '{$userRoleLower}', Selected Role: '{$selectedRoleLower}'");
```

Also consider deleting debug files:
- `debug_roles.php`
- `test_role_validation.php`

## Contact

If the issue persists after following this guide, please provide:
1. Screenshot of `debug_roles.php` output
2. Screenshot of `test_role_validation.php` output
3. Exact error message from login attempt
4. Relevant lines from Apache error.log
