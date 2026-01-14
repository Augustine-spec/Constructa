# Engineer Profile System - Visual Workflow

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      ENGINEER PROFILE SYSTEM                     │
│                  Single Source of Truth Architecture             │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐
│   USER ROLES │
└──────────────┘
      │
      ├─── Homeowner ────────┐
      ├─── Engineer ─────────┤
      └─── Admin ────────────┤
                              │
                              ▼
           ┌──────────────────────────────────┐
           │  view_engineer_profile.php       │
           │  ?engineer_id=123                │
           │                                  │
           │  • Validates authentication      │
           │  • Gets engineer_id from URL     │
           │  • Detects viewer role           │
           │  • Self-view → Redirect to edit  │
           └──────────────────────────────────┘
                              │
                              ▼
           ┌──────────────────────────────────┐
           │  backend/get_engineer_profile.php│
           │                                  │
           │  1. Fetch engineer #123 data     │
           │  2. Calculate project stats      │
           │  3. Check viewer permissions     │
           │  4. Filter data by role          │
           │  5. Return JSON response         │
           └──────────────────────────────────┘
                              │
               ┌──────────────┼──────────────┐
               │              │              │
               ▼              ▼              ▼
        ┌─────────┐    ┌──────────┐   ┌───────────┐
        │Homeowner│    │  Admin   │   │ Engineer  │
        │  View   │    │   View   │   │   View    │
        └─────────┘    └──────────┘   └───────────┘
```

---

## Role-Based Data Flow

### Homeowner Viewing Engineer Profile

```
┌─────────────────────────────────────────────────────────────┐
│ HOMEOWNER JOURNEY                                           │
└─────────────────────────────────────────────────────────────┘

1. Login as Homeowner
   └─> homeowner.php (Dashboard)

2. Navigate to Engineer Directory
   └─> engineer_directory.php
       • Displays grid of approved engineers
       • Each card shows: Name, Specialization, Experience

3. Click Engineer Card
   └─> view_engineer_profile.php?engineer_id=123

4. Backend Processing
   ┌───────────────────────────────────────┐
   │ get_engineer_profile.php              │
   │                                       │
   │ IF viewer_role === 'homeowner':       │
   │   - Load engineer #123 data           │
   │   - Include: name, specialization,    │
   │              bio, experience, stats   │
   │   - EXCLUDE: email, phone, license    │
   │   - permissions.can_request = true    │
   └───────────────────────────────────────┘

5. Frontend Display
   ┌───────────────────────────────────────┐
   │ Profile Card                          │
   │ • Avatar with initial                 │
   │ • Verification badge                  │
   │ • Name and specialization             │
   │ • Stats: Years | Projects | Completed │
   │                                       │
   │ [REQUEST PROJECT] ← Primary Action    │
   │ [CONTACT ENGINEER]← Secondary Action  │
   └───────────────────────────────────────┘
   
   ┌───────────────────────────────────────┐
   │ Bio Section                           │
   │ Professional summary text             │
   └───────────────────────────────────────┘
   
   ┌───────────────────────────────────────┐
   │ Professional Info                     │
   │ • Specialization                      │
   │ • Experience (years)                  │
   │ • Member Since                        │
   │ • Status Badge                        │
   │                                       │
   │ ❌ NO Email/Phone shown               │
   └───────────────────────────────────────┘

6. User Action: Click "Request Project"
   └─> contact_engineer.php?id=123
       OR
       Show modal → POST to backend/request_engineer_service.php
```

---

### Admin Viewing Engineer Profile

```
┌─────────────────────────────────────────────────────────────┐
│ ADMIN JOURNEY                                               │
└─────────────────────────────────────────────────────────────┘

1. Login as Admin
   └─> admin_dashboard.php

2. Navigate to User Management OR directly via URL
   └─> view_engineer_profile.php?engineer_id=123

3. Backend Processing
   ┌───────────────────────────────────────┐
   │ get_engineer_profile.php              │
   │                                       │
   │ IF viewer_role === 'admin':           │
   │   - Load engineer #123 data           │
   │   - Include: ALL fields               │
   │   - Fetch recent activity (10 items)  │
   │   - Calculate admin permissions       │
   │   - permissions.can_admin = true      │
   └───────────────────────────────────────┘

4. Frontend Display
   ┌───────────────────────────────────────┐
   │ Profile Card                          │
   │ • Avatar with badge                   │
   │ • Full stats display                  │
   │                                       │
   │ ADMIN ACTIONS:                        │
   │ [✓ VERIFY ENGINEER]                   │
   │ [🚫 SUSPEND ACCOUNT]                  │
   │ [📋 ASSIGN PROJECT]                   │
   └───────────────────────────────────────┘
   
   ┌───────────────────────────────────────┐
   │ Professional Info                     │
   │ • Specialization                      │
   │ • Experience                          │
   │ • ✅ Email (full access)              │
   │ • ✅ Phone (full access)              │
   │ • ✅ License Number                   │
   │ • ✅ Portfolio Link                   │
   │ • Created At                          │
   │ • Updated At                          │
   └───────────────────────────────────────┘
   
   ┌───────────────────────────────────────┐
   │ Recent Activity (Admin Only)          │
   │ ┌─────────────────────────────────┐   │
   │ │Project │Homeowner│Status│Date  │   │
   │ ├─────────────────────────────────┤   │
   │ │House   │John D.  │Done  │Jan 5 │   │
   │ │Garage  │Sarah M. │Active│Jan 3 │   │
   │ └─────────────────────────────────┘   │
   └───────────────────────────────────────┘

5. Admin Action: Verify Engineer
   ┌───────────────────────────────────────┐
   │ JavaScript: verifyEngineer()          │
   │ 1. Confirm action                     │
   │ 2. POST to admin_engineer_actions.php │
   │    - engineer_id: 123                 │
   │    - action: 'verify'                 │
   │ 3. Backend validates admin role       │
   │ 4. UPDATE users SET status='approved' │
   │ 5. Return success message             │
   │ 6. Page reload → badge updated        │
   └───────────────────────────────────────┘
```

---

### Engineer Viewing Own Profile

```
┌─────────────────────────────────────────────────────────────┐
│ ENGINEER (SELF-VIEW) JOURNEY                                │
└─────────────────────────────────────────────────────────────┘

1. Login as Engineer (user_id = 10)
   └─> engineer.php (Dashboard)

2. Click "My Profile"
   └─> view_engineer_profile.php?engineer_id=10

3. PHP Detection
   ┌───────────────────────────────────────┐
   │ view_engineer_profile.php             │
   │                                       │
   │ IF $viewer_role === 'engineer'        │
   │    AND $viewer_id == $engineer_id:    │
   │                                       │
   │    header('Location:                  │
   │            engineer_profile.php');    │
   │    exit();                            │
   └───────────────────────────────────────┘

4. Redirect to Edit Page
   └─> engineer_profile.php
       • Full editable form
       • All fields can be updated
       • Save button → backend/update_engineer_profile.php
```

---

### Engineer Viewing Another Engineer

```
┌─────────────────────────────────────────────────────────────┐
│ ENGINEER (VIEWING OTHERS) JOURNEY                           │
└─────────────────────────────────────────────────────────────┘

1. Login as Engineer (user_id = 10)
   └─> engineer.php

2. Navigate to another engineer's profile
   └─> view_engineer_profile.php?engineer_id=15

3. Backend Processing
   ┌───────────────────────────────────────┐
   │ get_engineer_profile.php              │
   │                                       │
   │ IF viewer_role === 'engineer'         │
   │    AND viewer_id != engineer_id:      │
   │                                       │
   │   - Show PUBLIC profile               │
   │   - Same data as homeowner view       │
   │   - NO contact details                │
   │   - NO admin actions                  │
   │   - NO service request button         │
   └───────────────────────────────────────┘

4. Frontend Display
   ┌───────────────────────────────────────┐
   │ Profile Card                          │
   │ • Public information only             │
   │ • No special actions                  │
   │                                       │
   │ [← GO BACK]                           │
   └───────────────────────────────────────┘
```

---

## Security Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                          │
└─────────────────────────────────────────────────────────────┘

LAYER 1: Authentication Check (PHP)
┌────────────────────────────────────┐
│ session_start();                   │
│ if (!isset($_SESSION['user_id'])) │
│     → Redirect to login.html       │
└────────────────────────────────────┘
                ↓
LAYER 2: Engineer ID Validation
┌────────────────────────────────────┐
│ $engineer_id = $_GET['engineer_id']│
│ if (!$engineer_id ||               │
│     !is_numeric($engineer_id))     │
│     → Redirect to directory        │
└────────────────────────────────────┘
                ↓
LAYER 3: Database Query (Prepared Statement)
┌────────────────────────────────────┐
│ $stmt = $conn->prepare(            │
│   "SELECT * FROM users             │
│    WHERE id = ? AND role='engineer'│
│ ");                                │
│ $stmt->bind_param("i", $eng_id);   │
└────────────────────────────────────┘
                ↓
LAYER 4: Role-Based Filtering
┌────────────────────────────────────┐
│ if ($viewer_role !== 'admin' &&    │
│     $viewer_id != $engineer_id) {  │
│   // Remove sensitive fields       │
│   unset($data['email']);           │
│   unset($data['phone']);           │
│   unset($data['license_number']);  │
│ }                                  │
└────────────────────────────────────┘
                ↓
LAYER 5: Frontend Rendering (XSS Prevention)
┌────────────────────────────────────┐
│ // Use textContent, not innerHTML  │
│ element.textContent = engineer.name│
│                                    │
│ // Escape in template literals     │
│ createInfoItem(label, htmlspecialch│
│                ars(value))         │
└────────────────────────────────────┘
```

---

## Data Privacy Matrix

```
┌───────────────┬──────────┬──────────┬─────────┬──────────┐
│ Data Field    │Homeowner │  Admin   │Self View│Other Eng │
├───────────────┼──────────┼──────────┼─────────┼──────────┤
│ Name          │    ✅    │    ✅    │   ✅    │    ✅    │
│ Avatar        │    ✅    │    ✅    │   ✅    │    ✅    │
│ Specialization│    ✅    │    ✅    │   ✅    │    ✅    │
│ Experience    │    ✅    │    ✅    │   ✅    │    ✅    │
│ Bio           │    ✅    │    ✅    │   ✅    │    ✅    │
│ Status Badge  │    ✅    │    ✅    │   ✅    │    ✅    │
│ Project Stats │    ✅    │    ✅    │   ✅    │    ✅    │
│ Member Since  │    ✅    │    ✅    │   ✅    │    ✅    │
├───────────────┼──────────┼──────────┼─────────┼──────────┤
│ Email         │    ❌    │    ✅    │   ✅    │    ❌    │
│ Phone         │    ❌    │    ✅    │   ✅    │    ❌    │
│ License #     │    ❌    │    ✅    │   ✅    │    ❌    │
│ Portfolio URL │    ❌    │    ✅    │   ✅    │    ❌    │
│ Created At    │    ❌    │    ✅    │   ✅    │    ❌    │
│ Updated At    │    ❌    │    ✅    │   ✅    │    ❌    │
├───────────────┼──────────┼──────────┼─────────┼──────────┤
│ Recent Activity│   ❌    │    ✅    │   ❌    │    ❌    │
│ Admin Controls│    ❌    │    ✅    │   ❌    │    ❌    │
└───────────────┴──────────┴──────────┴─────────┴──────────┘

Legend:
✅ = Field is visible
❌ = Field is hidden
```

---

## Permission Matrix

```
┌────────────────────┬──────────┬────────┬─────────┬──────────┐
│ Action             │Homeowner │ Admin  │Self View│Other Eng │
├────────────────────┼──────────┼────────┼─────────┼──────────┤
│ View Profile       │    ✅    │   ✅   │   ✅    │    ✅    │
│ View Contact Info  │    ❌    │   ✅   │   ✅    │    ❌    │
│ Edit Profile       │    ❌    │   ✅*  │   ✅    │    ❌    │
│ Request Project    │    ✅    │   ❌   │   ❌    │    ❌    │
│ Verify Engineer    │    ❌    │   ✅   │   ❌    │    ❌    │
│ Suspend Account    │    ❌    │   ✅   │   ❌    │    ❌    │
│ Assign Project     │    ❌    │   ✅   │   ❌    │    ❌    │
│ View Activity Log  │    ❌    │   ✅   │   ❌    │    ❌    │
└────────────────────┴──────────┴────────┴─────────┴──────────┘

* Admin edit is override capability (not implemented yet)
```

---

## Success Criteria Checklist

### Core Requirements ✅

- [x] Profile always loaded based on `engineer_id` URL parameter
- [x] Viewer role controls visibility, not which profile loads
- [x] Homeowner sees public profile only
- [x] Admin sees full profile with controls
- [x] Engineer self-view redirects to edit page
- [x] Engineer viewing others sees public profile
- [x] Contact details hidden from public view
- [x] Verification badge system implemented
- [x] Role-based action buttons
- [x] Security enforced at frontend and backend

### Technical Requirements ✅

- [x] Session-based authentication
- [x] Prepared SQL statements (no injection)
- [x] XSS prevention in output
- [x] Role validation in backend APIs
- [x] Error handling and user feedback
- [x] Responsive design
- [x] 3D animated background
- [x] Professional UI/UX

### Professional Features ✅

- [x] Verification badge system
- [x] Experience timeline
- [x] Project statistics
- [x] Admin activity log
- [x] Smart action buttons
- [x] Loading states
- [x] Smooth animations

---

## File Structure

```
Constructa/
├── view_engineer_profile.php          ← Main profile viewer
├── engineer_profile.php               ← Self-edit page (existing)
├── engineer_directory.php             ← Updated with new links
├── backend/
│   ├── get_engineer_profile.php       ← Profile data API
│   ├── admin_engineer_actions.php     ← Admin controls API
│   └── request_engineer_service.php   ← Service request API
└── .agent/
    ├── ENGINEER_PROFILE_SYSTEM.md     ← Implementation plan
    ├── ENGINEER_PROFILE_QUICK_START.md← Testing guide
    ├── ENGINEER_PROFILE_SUMMARY.md    ← System summary
    └── ENGINEER_PROFILE_WORKFLOW.md   ← This file
```

---

## Quick Test Commands

### Test as Homeowner
```
1. Login as homeowner
2. Navigate to: /engineer_directory.php
3. Click any engineer card
4. Verify: Public info shown, no email/phone
5. Verify: "Request Project" button visible
```

### Test as Admin
```
1. Login as admin
2. Navigate to: /view_engineer_profile.php?engineer_id=1
3. Verify: All info shown including email/phone
4. Verify: Admin action buttons visible
5. Click "Verify Engineer" → Status should update
```

### Test Security
```
1. Logout
2. Try: /view_engineer_profile.php?engineer_id=1
3. Verify: Redirected to login.html
```

---

**Status**: ✅ COMPLETE
**Ready for**: Production Testing
**Next Step**: User Acceptance Testing
