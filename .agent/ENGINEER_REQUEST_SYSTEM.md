# Engineer Project Request System - Implementation Summary

## Overview
Implemented a complete project request workflow where homeowners can contact specific engineers, submit project requests, and engineers can review and manage requests sent only to them.

## System Flow

### 1. Engineer Directory (`engineer_directory.php`)
- **What Changed**: Updated engineer cards to redirect to contact page instead of email
- **How It Works**: When a homeowner clicks on an engineer card, they are redirected to `contact_engineer.php?id={engineer_id}`
- **Key Feature**: Each engineer has a unique ID that gets passed to the contact page

### 2. Contact Engineer Page (`contact_engineer.php`) - NEW FILE
- **Purpose**: Allows homeowners to submit detailed project requests to specific engineers
- **Features**:
  - Displays selected engineer's information (name, specialization, experience)
  - Comprehensive project request form with fields:
    - Project Title
    - Project Type (Residential, Commercial, Industrial, Renovation, Other)
    - Budget
    - Location
    - Timeline
    - Project Size
    - Detailed Description
    - Contact Phone
  - 3D animated background for visual appeal
  - Form validation and AJAX submission
  - Success/error feedback messages

### 3. Backend Request Submission (`backend/submit_project_request.php`) - NEW FILE
- **Purpose**: Handles project request submissions from homeowners
- **Key Features**:
  - Validates all required fields
  - Verifies engineer exists and is approved
  - Creates `project_requests` table if it doesn't exist
  - Stores request with link to specific engineer and homeowner
  - Returns JSON response for AJAX handling

### 4. Project Requests Page (`project_requests.php`) - UPDATED
- **What Changed**: Replaced static dummy data with dynamic database queries
- **How It Works**: 
  - Fetches ONLY requests where `engineer_id` matches logged-in engineer's ID
  - Displays homeowner information for each request
  - Shows request status (pending, accepted, rejected)
  - Provides Accept/Reject buttons for pending requests
  - Shows "Contact Client" button for accepted requests
- **Key Feature**: **Engineer-specific filtering** - Engineer1 only sees requests sent to Engineer1

### 5. Backend Status Update (`backend/update_request_status.php`) - NEW FILE
- **Purpose**: Allows engineers to accept or reject project requests
- **Security**: Verifies the request belongs to the logged-in engineer
- **Features**:
  - Updates request status in database
  - Validates engineer ownership of request
  - Returns success/error messages

## Database Schema

### `project_requests` Table (Auto-created)
```sql
CREATE TABLE project_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    engineer_id INT NOT NULL,              -- Links to specific engineer
    homeowner_id INT NOT NULL,             -- Links to homeowner who submitted
    project_title VARCHAR(255) NOT NULL,
    project_type VARCHAR(100) NOT NULL,
    budget VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    timeline VARCHAR(100) NOT NULL,
    project_size VARCHAR(100),
    description TEXT NOT NULL,
    contact_phone VARCHAR(20),
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (engineer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
)
```

## Request Routing Logic

### How Requests Are Directed to Specific Engineers:

1. **Homeowner selects Engineer1** from directory
   - URL: `contact_engineer.php?id=5` (where 5 is Engineer1's user ID)

2. **Form submission includes engineer_id**
   - Hidden field: `<input type="hidden" name="engineer_id" value="5">`
   - This ensures the request is stored with `engineer_id = 5`

3. **Engineer1 logs in and views Project Requests**
   - Query: `SELECT * FROM project_requests WHERE engineer_id = 5`
   - **Result**: Only shows requests submitted to Engineer1

4. **Engineer2 logs in and views Project Requests**
   - Query: `SELECT * FROM project_requests WHERE engineer_id = 7`
   - **Result**: Only shows requests submitted to Engineer2

## Files Created/Modified

### New Files:
1. `contact_engineer.php` - Contact form for specific engineer
2. `backend/submit_project_request.php` - Handles request submission
3. `backend/update_request_status.php` - Handles accept/reject actions

### Modified Files:
1. `engineer_directory.php` - Updated card onclick to redirect to contact page
2. `project_requests.php` - Replaced static content with dynamic database queries

## User Experience Flow

### For Homeowners:
1. Browse Engineer Directory
2. Click on desired engineer's card
3. Fill out detailed project request form
4. Submit request
5. Receive confirmation message
6. Redirected to dashboard

### For Engineers:
1. Log in to engineer dashboard
2. Navigate to "Project Requests"
3. View ONLY requests sent to them
4. See homeowner details, project information, budget, timeline
5. Accept or Reject requests
6. Contact clients directly for accepted requests

## Security Features

✅ **Session Validation**: All pages check user role and login status
✅ **Engineer-Specific Access**: Engineers can only see/modify their own requests
✅ **SQL Injection Prevention**: All queries use prepared statements
✅ **Input Validation**: Required fields validated on both client and server
✅ **XSS Protection**: All output is HTML-escaped using `htmlspecialchars()`

## Testing Checklist

- [ ] Homeowner can view engineer directory
- [ ] Clicking engineer card redirects to contact page with correct engineer ID
- [ ] Contact form displays correct engineer information
- [ ] Form submission creates database entry with correct engineer_id
- [ ] Engineer1 sees only requests sent to Engineer1
- [ ] Engineer2 sees only requests sent to Engineer2
- [ ] Accept button updates status to 'accepted'
- [ ] Reject button updates status to 'rejected'
- [ ] Contact Client button appears for accepted requests
- [ ] No requests message appears when engineer has no requests

## Next Steps (Optional Enhancements)

1. **Email Notifications**: Send email to engineer when new request is received
2. **Request Analytics**: Dashboard showing request statistics
3. **Proposal System**: Allow engineers to submit detailed proposals
4. **Chat System**: Real-time messaging between homeowner and engineer
5. **File Uploads**: Allow homeowners to attach project documents/images
6. **Rating System**: Homeowners can rate engineers after project completion

## Summary

✅ **Problem Solved**: Requests are now properly routed to specific engineers
✅ **Database-Driven**: All data is stored and retrieved from MySQL database
✅ **Engineer-Specific**: Each engineer sees only their own requests
✅ **Professional UI**: Modern, responsive design with 3D animations
✅ **Complete Workflow**: From directory → contact → submit → review → accept/reject
