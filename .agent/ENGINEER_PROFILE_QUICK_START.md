# Engineer Profile System - Quick Start Guide

## Overview
The Engineer Profile System is now implemented with proper role-based access control. The system ensures that:
- ✅ The correct engineer profile is always displayed based on the `engineer_id` URL parameter
- ✅ Viewer role controls visibility and permissions, not which profile is loaded
- ✅ Security is enforced at both frontend (UI) and backend (API) levels

## System Architecture

### Key Files Created

1. **`view_engineer_profile.php`** - Main profile viewer (dynamic, role-based)
2. **`backend/get_engineer_profile.php`** - Profile data API with role-based filtering
3. **`backend/admin_engineer_actions.php`** - Admin controls (verify, suspend)
4. **`backend/request_engineer_service.php`** - Homeowner service request API
5. **`.agent/ENGINEER_PROFILE_SYSTEM.md`** - Complete implementation plan

### Files Modified

1. **`engineer_directory.php`** - Updated to link to new profile viewer

### Files Kept As-Is

1. **`engineer_profile.php`** - Engineer's own editable profile page (for self-edit)

## How It Works

### Profile Loading Logic
```
User clicks "View Profile" → view_engineer_profile.php?engineer_id=123
   ↓
System loads profile for engineer #123 (NOT based on viewer)
   ↓
Backend filters data based on viewer's role
   ↓
Frontend displays role-appropriate view
```

### Role-Based Views

#### 1. Homeowner View (Public Profile)
**URL**: `view_engineer_profile.php?engineer_id=123`

**Visible Information**:
- ✅ Name and avatar
- ✅ Specialization
- ✅ Years of experience
- ✅ Bio/Professional summary
- ✅ Verification status
- ✅ Project statistics (total, completed)
- ❌ Email (hidden)
- ❌ Phone (hidden)
- ❌ License number (hidden)

**Available Actions**:
- Request Project
- Contact Engineer

#### 2. Admin View (Full Access)
**URL**: `view_engineer_profile.php?engineer_id=123`

**Visible Information**:
- ✅ All public information
- ✅ Email and phone
- ✅ License number
- ✅ Portfolio link
- ✅ Account creation date
- ✅ Last updated timestamp
- ✅ Recent activity log

**Available Actions**:
- Verify Engineer (if not verified)
- Suspend Account (if verified)
- Assign Project (coming soon)

#### 3. Engineer Viewing Own Profile
**URL**: `view_engineer_profile.php?engineer_id={self}`

**Behavior**: Automatically redirects to `engineer_profile.php` (editable version)

#### 4. Engineer Viewing Another Engineer
**URL**: `view_engineer_profile.php?engineer_id=456`

**Behavior**: Shows public profile (same as homeowner view)

## Testing Scenarios

### Test 1: Homeowner Views Engineer Profile ✅
```
1. Log in as homeowner
2. Go to Engineer Directory
3. Click on any engineer card
4. Verify URL is: view_engineer_profile.php?engineer_id=X
5. Verify you see: Name, specialization, bio, stats
6. Verify you DON'T see: Email, phone, license number
7. Verify buttons: "Request Project", "Contact Engineer"
```

### Test 2: Admin Views Engineer Profile ✅
```
1. Log in as admin
2. Navigate to: view_engineer_profile.php?engineer_id=X
3. Verify you see: All profile data including email, phone
4. Verify you see: Recent activity section
5. Test "Verify Engineer" button (if engineer is pending)
6. Test "Suspend Account" button (if engineer is approved)
```

### Test 3: Engineer Views Own Profile ✅
```
1. Log in as engineer
2. Try to access: view_engineer_profile.php?engineer_id={your_id}
3. Verify you are redirected to: engineer_profile.php
4. Verify you can edit your profile
```

### Test 4: Engineer Views Another Engineer ✅
```
1. Log in as engineer (user_id = 5)
2. Navigate to: view_engineer_profile.php?engineer_id=7
3. Verify you see public profile (no email/phone)
4. Verify button: "Go Back"
```

### Test 5: Security - Profile Always Loads Correct Engineer ✅
```
1. As any role, navigate to: view_engineer_profile.php?engineer_id=10
2. Verify the profile shown belongs to engineer #10
3. Navigate to: view_engineer_profile.php?engineer_id=15
4. Verify the profile shown NOW belongs to engineer #15
5. Confirm viewer role doesn't change WHICH profile loads
```

## Navigation Paths

### From Engineer Directory (Homeowner)
```
homeowner.php
  └─> engineer_directory.php
      └─> Click engineer card
          └─> view_engineer_profile.php?engineer_id=123
```

### From Admin User Management
```
admin_dashboard.php
  └─> admin_user_management.php
      └─> Click "View Profile" on engineer row
          └─> view_engineer_profile.php?engineer_id=123
```

### Engineer Editing Own Profile
```
engineer.php (dashboard)
  └─> Click "My Profile"
      └─> engineer_profile.php (editable)
```

## API Endpoints

### 1. Get Engineer Profile
**Endpoint**: `backend/get_engineer_profile.php`
**Method**: GET
**Parameters**:
- `engineer_id` (required): The engineer's user ID

**Response** (Homeowner):
```json
{
  "success": true,
  "view_mode": "public",
  "engineer": {
    "id": 123,
    "name": "John Engineer",
    "specialization": "Structural Engineer",
    "experience": 10,
    "bio": "...",
    "status": "approved",
    "is_verified": true,
    "member_since": "January 2024",
    "stats": {
      "total_projects": 15,
      "completed_projects": 12,
      "active_projects": 3
    }
  },
  "permissions": {
    "can_edit": false,
    "can_view_contact": false,
    "can_request_service": true,
    "can_admin_actions": false,
    "show_public_view": true
  }
}
```

**Response** (Admin):
```json
{
  "success": true,
  "view_mode": "admin",
  "engineer": {
    // ... all public fields PLUS:
    "email": "john@example.com",
    "phone": "1234567890",
    "license_number": "ENG-12345",
    "portfolio_url": "https://...",
    "created_at": "2024-01-15 10:30:00"
  },
  "admin_data": {
    "recent_activity": [...],
    "can_verify": false,
    "can_suspend": true
  },
  "permissions": {
    "can_edit": true,
    "can_view_contact": true,
    "can_request_service": false,
    "can_admin_actions": true
  }
}
```

### 2. Admin Actions
**Endpoint**: `backend/admin_engineer_actions.php`
**Method**: POST
**Parameters**:
- `engineer_id` (required): The engineer's user ID
- `action` (required): 'verify', 'suspend', or 'activate'

**Response**:
```json
{
  "success": true,
  "message": "Engineer verified successfully",
  "new_status": "approved",
  "engineer_name": "John Engineer"
}
```

### 3. Request Service (Homeowner)
**Endpoint**: `backend/request_engineer_service.php`
**Method**: POST
**Parameters**:
- `engineer_id` (required)
- `project_title` (required)
- `project_description` (required)
- `location` (optional)
- `budget_range` (optional)

**Response**:
```json
{
  "success": true,
  "message": "Project request sent successfully",
  "request_id": 456,
  "engineer_name": "John Engineer"
}
```

## Security Features Implemented

### 1. Authentication Check
```php
// At top of view_engineer_profile.php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
```

### 2. Role-Based Data Filtering (Backend)
```php
// In get_engineer_profile.php
if ($is_self_view || $is_admin) {
    // Include email, phone, license
} else {
    // Public data only
}
```

### 3. Admin-Only Actions (Backend)
```php
// In admin_engineer_actions.php
if ($_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}
```

### 4. SQL Injection Prevention
```php
// All queries use prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $engineer_id);
```

### 5. XSS Prevention
```javascript
// All data is properly escaped in JavaScript
document.getElementById('profileName').textContent = engineer.name;
```

## Common Issues & Solutions

### Issue: Profile doesn't load
**Solution**: Check browser console for errors. Verify engineer_id is valid and engineer exists.

### Issue: Wrong profile shows
**Solution**: This shouldn't happen! The profile is ALWAYS loaded based on URL parameter. If this occurs, there's a bug - report it.

### Issue: Homeowner sees contact details
**Solution**: Clear browser cache. Check backend API response - should NOT include email/phone for homeowners.

### Issue: Admin can't verify engineer
**Solution**: Check if engineer is already verified (status = 'approved'). Verify button only shows for pending engineers.

## Next Steps / Future Enhancements

1. **Modal for Project Request** - Instead of redirecting, show a modal form
2. **Shortlist Feature** - Allow homeowners to save favorite engineers
3. **Rating System** - Post-project ratings and reviews
4. **Portfolio Gallery** - Rich media uploads for engineers
5. **Live Chat** - Real-time communication
6. **Email Notifications** - Notify engineer when profile is viewed
7. **Analytics** - Profile view tracking for engineers

## Quick Commands

### Create a test engineer (MySQL)
```sql
INSERT INTO users (name, email, role, status, specialization, experience, bio) 
VALUES ('Test Engineer', 'test@example.com', 'engineer', 'approved', 
        'Structural Engineering', 5, 'Experienced structural engineer with 5+ years.');
```

### View profile as homeowner
```
http://localhost/Constructa/view_engineer_profile.php?engineer_id=1
```

### View profile as admin
```
# Log in as admin first, then:
http://localhost/Constructa/view_engineer_profile.php?engineer_id=1
```

## Summary

✅ **Profile Loading**: Always based on `engineer_id` URL parameter
✅ **Role-Based Views**: Homeowner, Admin, Engineer (self/other)
✅ **Security**: Multi-layer authentication and authorization
✅ **Privacy**: Contact details hidden from public view
✅ **Admin Controls**: Verify, suspend, activate engineers
✅ **Professional UI**: 3D backgrounds, premium design
✅ **Data Integrity**: Single source of truth principle

The system is now ready for testing and production use!
