# User Management System Update

The user management system has been updated with a new flow for Homeowners, Engineers, and Admins.

## New Role and Status Flow

### 1. Homeowner
*   **Registration:** Direct registration via `landingpage.html` -> `homeowner_signup.html`.
*   **Status:** Automatically approved.
*   **Login:** Direct access to Homeowner Dashboard (`homeowner.php`).

### 2. Engineer
*   **Registration:** `landingpage.html` -> `homeowner_signup.html` -> "Apply as Engineer" -> `engineer_application.html`.
*   **Status:** Created as `pending` by default.
*   **Pending:** Unable to access dashboard. Redirected to `engineer_pending.html` which shows application status.
*   **Approval:** Must be approved by an Admin. Once approved, can log in to Engineer Dashboard (`engineer.php`).

### 3. Admin
*   **Registration:** Pre-registered in the system.
*   **Credentials:** `admin@gmail.com` / `admin`.
*   **Login:** Access to Admin Dashboard (`admin_dashboard.php`).
*   **Capabilities:** View pending engineer requests and Approve/Reject them via the new **User Management** section.

## Files Created/Modified

### Backend
*   **`backend/update_schema.php`**: Updates the database to support new roles, status, and engineer fields. **(Run this first!)**
*   **`backend/login.php`**: Updated to check for user status (pending/rejected) and prevent unauthorized access.
*   **`backend/engineer_application.php`**: Handled engineer application submission.
*   **`backend/check_engineer_status.php`**: Checks application status for the pending page.
*   **`backend/get_engineer_requests.php`**: Fetches engineer requests for the admin dashboard.
*   **`backend/update_engineer_status.php`**: Handles approval/rejection logic.

### Frontend
*   **`landingpage.html`**: "Sign Up" button now points directly to Homeowner Signup.
*   **`homeowner_signup.html`**: Added "Apply as Engineer" button.
*   **`engineer_application.html`**: New comprehensive application form for engineers.
*   **`engineer_pending.html`**: Status page for engineers waiting for approval.
*   **`admin_dashboard.php`**: Updated "User Management" card to link to requests page.
*   **`admin_engineer_requests.php`**: New page for admins to manage engineer applications.
*   **`login.html`**: Updated to handle status checks and redirect pending engineers.
*   **`signuprole.html`**: Deleted (no longer needed).

## Action Required

1.  **Update Database:**
    Open your browser and navigate to:
    `http://localhost/Constructa/backend/update_schema.php`
    
    This will add the necessary columns (`status`, `phone`, `specialization`, etc.) to your `users` table and create the default admin account.

2.  **Test the Flow:**
    *   **Homeowner:** Sign up as a new homeowner and login.
    *   **Engineer:** Apply as a new engineer. Try to login (should see pending page).
    *   **Admin:** Login as `admin@gmail.com` (password: `admin`). Go to User Management. Approve the engineer.
    *   **Engineer:** Login again (should now access dashboard).
