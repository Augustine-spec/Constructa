# Engineer Profile System Implementation Plan

## Overview
A comprehensive role-based engineer profile system where the correct engineer profile is always displayed based on a unique engineer ID passed via URL parameter, with role-based views controlling visibility and permissions.

## Core Principle: Single Source of Truth
- **Profile Loading**: Always based on `engineer_id` URL parameter (e.g., `view_profile.php?engineer_id=123`)
- **Viewer Role**: Controls what data is visible and what actions are available
- **Access Control**: Enforced at both frontend (UI) and backend (API) levels

## Files to Create/Modify

### New Files
1. **`view_engineer_profile.php`** - Dynamic profile viewer (replaces current self-view only)
2. **`backend/get_engineer_profile.php`** - Fetch profile data with role-based filtering
3. **`backend/admin_verify_engineer.php`** - Admin controls (verify, suspend, assign)
4. **`backend/request_engineer_service.php`** - Homeowner service request

### Files to Modify
1. **`engineer_profile.php`** - Keep as self-edit page for engineers
2. **`engineer_directory.php`** - Update "View Profile" links
3. **`admin_user_management.php`** - Add "View Profile" links for engineers

## Database Schema (Already Exists)
```
users table:
- id (engineer_id)
- name
- email
- phone
- profile_picture
- role
- status (approved/pending/suspended)
- specialization
- experience
- license_number
- portfolio_url
- bio
- created_at
- updated_at
```

## Role-Based Views

### 1. Engineer (Self-View)
**Access**: `engineer_profile.php` (current page - keep for self-edit)
- Full editable profile
- Upload documents
- Update all fields
- View own statistics

### 2. Engineer (Viewing Other Engineers' Profiles)
**Access**: `view_engineer_profile.php?engineer_id=X`
- Public profile view (same as homeowner)
- Cannot edit
- Professional information only

### 3. Homeowner View
**Access**: `view_engineer_profile.php?engineer_id=X`
**Purpose**: Trust-focused, professional presentation

**Visible Information**:
- ✅ Name and profile photo
- ✅ Specialization
- ✅ Years of experience
- ✅ Bio/Professional summary
- ✅ Verification status badge
- ✅ Key completed projects count
- ✅ Availability status
- ❌ Email (hidden)
- ❌ Phone (hidden initially, shown after request)
- ❌ License number (hidden)

**Actions**:
- Request Project/Service
- Shortlist Engineer
- Contact (initiated via request system)

### 4. Admin View
**Access**: `view_engineer_profile.php?engineer_id=X`
**Purpose**: Management and oversight

**Visible Information**:
- ✅ All profile data (full access)
- ✅ Contact details
- ✅ License information
- ✅ Verification documents
- ✅ Activity history
- ✅ Project statistics
- ✅ Status (approved/pending/suspended)

**Actions**:
- Verify Documents
- Approve/Suspend Account
- Assign Projects
- View Full Activity Log
- Edit Profile (as admin override)

## Security & Access Rules

### Frontend Guard (UI Level)
```php
// At top of view_engineer_profile.php
session_start();
$engineer_id = $_GET['engineer_id'] ?? null;
$viewer_role = $_SESSION['role'] ?? 'guest';
$viewer_id = $_SESSION['user_id'] ?? null;

if (!$engineer_id) {
    header('Location: engineer_directory.php');
    exit();
}

// Determine view mode
$is_self_view = ($viewer_role === 'engineer' && $viewer_id == $engineer_id);
$is_admin = ($viewer_role === 'admin');
$is_homeowner = ($viewer_role === 'homeowner');
$is_other_engineer = ($viewer_role === 'engineer' && $viewer_id != $engineer_id);
```

### Backend Guard (API Level)
```php
// In backend/get_engineer_profile.php
// Returns different data structure based on viewer_role
function getEngineerProfile($engineer_id, $viewer_role, $viewer_id) {
    // Fetch full profile
    // Filter based on role
    // Return appropriate subset
}
```

### Access Matrix

| Viewer Role | Can View Profile | Can Edit | Contact Details | Admin Actions |
|-------------|-----------------|----------|-----------------|---------------|
| Guest (not logged in) | ❌ | ❌ | ❌ | ❌ |
| Homeowner | ✅ Public | ❌ | Via Request | ❌ |
| Same Engineer (self) | ✅ Full | ✅ | ✅ | ❌ |
| Other Engineer | ✅ Public | ❌ | ❌ | ❌ |
| Admin | ✅ Full | ✅ Override | ✅ | ✅ |

## Professional Enhancements

### Verification Badge System
- **Unverified**: Gray badge, "Pending Verification"
- **Verified**: Blue badge with checkmark, "Verified Engineer"
- **Premium**: Gold badge, "Premium Verified Engineer" (future enhancement)

### Experience Timeline
- Visual timeline showing career progression
- Key milestones and certifications
- Project highlights by year

### Project Highlights Section
- **For Homeowners**: "Successfully completed 12+ projects"
- **For Admins**: Full project list with details and status
- **3D Project Cards**: Visual previews (if available)

### Smart Action Buttons

**Homeowner View**:
```html
<button class="primary-action" onclick="requestProject()">
    <i class="fas fa-paper-plane"></i> Request Project
</button>
<button class="secondary-action" onclick="shortlistEngineer()">
    <i class="fas fa-star"></i> Shortlist
</button>
```

**Admin View**:
```html
<button class="admin-action verify" onclick="verifyEngineer()">
    <i class="fas fa-check-circle"></i> Verify Engineer
</button>
<button class="admin-action suspend" onclick="suspendAccount()">
    <i class="fas fa-ban"></i> Suspend Account
</button>
<button class="admin-action assign" onclick="assignProject()">
    <i class="fas fa-tasks"></i> Assign Project
</button>
```

**Self View** (engineer viewing own profile):
```html
<button class="primary-action" onclick="window.location.href='engineer_profile.php'">
    <i class="fas fa-edit"></i> Edit Profile
</button>
```

## Implementation Steps

### Phase 1: Core Profile Viewer
1. Create `view_engineer_profile.php` with role detection
2. Create `backend/get_engineer_profile.php` API
3. Implement role-based data filtering

### Phase 2: Role-Specific Views
1. Build Homeowner public profile view
2. Build Admin full-access view
3. Build Engineer public view (for viewing others)
4. Redirect self-view to edit page

### Phase 3: Actions & Interactions
1. Implement "Request Project" for homeowners
2. Implement admin controls (verify, suspend, assign)
3. Add shortlist functionality
4. Add activity logging

### Phase 4: Professional UI Enhancements
1. Verification badge system
2. Experience timeline visualization
3. Project highlights carousel
4. Premium design with 3D effects

## Navigation Flow

### From Engineer Directory (Homeowner)
```
engineer_directory.php
  → Click on engineer card
  → view_engineer_profile.php?engineer_id=123
  → Shows public profile with "Request Project" button
```

### From Admin User Management
```
admin_user_management.php
  → Click "View Profile" on engineer
  → view_engineer_profile.php?engineer_id=123
  → Shows full profile with admin controls
```

### Engineer Viewing Own Profile
```
engineer.php (dashboard)
  → Click "My Profile"
  → engineer_profile.php (editable)
  OR
  → view_engineer_profile.php?engineer_id={self}
  → Detects self-view, shows "Edit Profile" button
```

### Engineer Viewing Another Engineer
```
engineer_directory.php (if accessible to engineers)
  → Click on another engineer
  → view_engineer_profile.php?engineer_id=456
  → Shows public profile (like homeowner view)
```

## Testing Checklist

- [ ] Guest cannot access any profile
- [ ] Homeowner sees only public information
- [ ] Homeowner cannot see contact details initially
- [ ] Engineer can view own full profile
- [ ] Engineer viewing other engineers sees public view only
- [ ] Engineer cannot edit other profiles
- [ ] Admin sees all engineer data
- [ ] Admin controls work correctly
- [ ] Verification badge displays correctly
- [ ] Profile always loads correct engineer based on URL parameter
- [ ] No profile data leakage between roles

## Security Considerations

1. **SQL Injection Prevention**: Use prepared statements
2. **XSS Prevention**: Escape all output with `htmlspecialchars()`
3. **CSRF Protection**: Add tokens for state-changing actions
4. **Session Validation**: Always verify session and role
5. **Direct Access Prevention**: Check authentication before loading any page
6. **Data Leakage**: Never send sensitive data to frontend for roles that shouldn't see it

## Future Enhancements

1. **Rating System**: Homeowners can rate engineers after project completion
2. **Endorsements**: Other engineers can endorse skills
3. **Portfolio Gallery**: Rich media uploads
4. **Live Chat**: Direct communication system
5. **Availability Calendar**: Real-time booking
6. **Analytics Dashboard**: Profile views, engagement metrics (for engineers)
7. **Premium Profiles**: Enhanced visibility and features
