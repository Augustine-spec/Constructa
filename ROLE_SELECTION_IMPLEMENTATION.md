# Login Page Role Selection Implementation

## Overview
The login page now includes a role selection dropdown that allows users to specify their role (Homeowner, Engineer, or Admin) before logging in. The system validates that the provided credentials match the selected role.

## Changes Made

### 1. Frontend Changes (`login.html`)

#### Added Role Selector Dropdown
- **Location**: Between the page subtitle and email field
- **Label**: "I am a"
- **Options**:
  - Select your role (placeholder)
  - Homeowner
  - Engineer
  - Admin

#### Updated JavaScript Login Handler
- Retrieves the selected role from the dropdown
- Validates that a role is selected before submission
- Sends the role to the backend for validation
- Displays appropriate error messages for role mismatches

#### Enhanced CSS Styling
- Custom dropdown arrow using SVG data URI
- Matches existing form input styling
- Hover and focus states for better UX
- Cursor pointer for better interactivity

### 2. Backend Changes (`backend/login.php`)

#### Role Validation Logic
1. **Role Input Validation**: Ensures the selected role is one of the valid options (homeowner, engineer, admin)
2. **Role Matching**: After password verification, checks if the user's actual role matches the selected role
3. **Error Messages**: Provides clear, helpful error messages when roles don't match

#### Example Error Message
```
"Role mismatch: This account is registered as a Homeowner, not an Engineer. Please select the correct role."
```

## User Flow

### Successful Login
1. User selects their role from the dropdown
2. User enters email and password
3. System validates credentials
4. System checks if the role matches
5. User is redirected to the appropriate dashboard:
   - Homeowner → `homeowner.html`
   - Engineer → `engineer.html`
   - Admin → `admin_dashboard.html`

### Failed Login Scenarios

#### Scenario 1: No Role Selected
- **Error**: "Please select your role."
- **Action**: User must select a role before proceeding

#### Scenario 2: Wrong Role Selected
- **Example**: User has an Engineer account but selects "Homeowner"
- **Error**: "Role mismatch: This account is registered as an Engineer, not a Homeowner. Please select the correct role."
- **Action**: User must select the correct role matching their account

#### Scenario 3: Invalid Credentials
- **Error**: "Incorrect password. Please try again." or "No account found with this email address."
- **Action**: User must provide correct credentials

#### Scenario 4: Google Account
- **Error**: "This account was created with Google Sign-In. Please use 'Sign in with Google' instead."
- **Action**: User must use Google Sign-In button

## Security Features

1. **Role Validation**: Prevents users from accessing dashboards they're not authorized for
2. **Server-Side Validation**: All role checks happen on the backend, not just frontend
3. **Clear Error Messages**: Helps legitimate users while not exposing sensitive information
4. **Session Management**: Proper session handling with role information stored

## Testing Recommendations

### Test Case 1: Correct Role Selection
- Create a homeowner account
- Login with "Homeowner" role selected
- Expected: Successful login, redirect to homeowner dashboard

### Test Case 2: Incorrect Role Selection
- Use a homeowner account
- Login with "Engineer" role selected
- Expected: Error message about role mismatch

### Test Case 3: No Role Selection
- Leave role dropdown on "Select your role"
- Attempt to login
- Expected: Error message "Please select your role."

### Test Case 4: Admin Login
- Use an admin account
- Login with "Admin" role selected
- Expected: Successful login, redirect to admin dashboard

## Database Schema Requirements

The `users` table must have a `role` column with values:
- `homeowner`
- `engineer`
- `admin`

## Future Enhancements

1. **Remember Role**: Store the last selected role in localStorage for convenience
2. **Auto-detect Role**: Show a hint based on email domain (e.g., @company.com → Engineer)
3. **Multi-role Accounts**: Support users with multiple roles
4. **Role-based Password Requirements**: Different password complexity for different roles

## Files Modified

1. `login.html` - Added role selector and updated JavaScript
2. `backend/login.php` - Added role validation logic

## Visual Design

The role selector dropdown:
- Matches the existing form aesthetic
- Uses the same green color scheme (#294033)
- Has smooth transitions and hover effects
- Includes a custom dropdown arrow that changes color on focus
- Maintains the clean, modern design of the login page
