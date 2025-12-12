# Google OAuth Setup Guide for Constructa

## Why the Error Occurred

The **Google OAuth error (400 - malformed request)** happened because:
1. The `data-client_id` was set to a placeholder: `YOUR_GOOGLE_CLIENT_ID`
2. No proper OAuth redirect URI was configured
3. Google requires a registered application in Google Cloud Console

## How to Properly Set Up Google Sign In

### Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Create Project" or select an existing project
3. Name your project (e.g., "Constructa")

### Step 2: Enable Google Identity Services API

1. In the Google Cloud Console, go to **APIs & Services** > **Library**
2. Search for "Google Identity Services API"
3. Click **Enable**

### Step 3: Create OAuth 2.0 Credentials

1. Go to **APIs & Services** > **Credentials**
2. Click **+ CREATE CREDENTIALS** > **OAuth client ID**
3. Select **Application type**: **Web application**
4. Name it (e.g., "Constructa Web Client")
5. Under **Authorized JavaScript origins**, add:
   ```
   http://localhost
   http://localhost:80
   http://127.0.0.1
   ```
6. Under **Authorized redirect URIs**, add:
   ```
   http://localhost/Constructa/engineer_signup.html
   http://localhost/Constructa/homeowner.html
   ```
7. Click **Create**
8. **IMPORTANT**: Copy the **Client ID** (looks like: `123456789-abc123def456.apps.googleusercontent.com`)

### Step 4: Configure OAuth Consent Screen

1. Go to **APIs & Services** > **OAuth consent screen**
2. Choose **External** (for testing)
3. Fill in:
   - App name: `Constructa`
   - User support email: Your email
   - Developer contact: Your email
4. Click **Save and Continue**
5. Add test users (your own email for testing)
6. Click **Save and Continue**

### Step 5: Update Your Code

#### In `engineer_signup.html` (line 380):

Replace the commented section with:

```html
<div class="divider">
    <span>Or continue with</span>
</div>

<div id="g_id_onload" 
     data-client_id="YOUR_ACTUAL_CLIENT_ID_HERE" 
     data-callback="handleCredentialResponse"
     data-auto_prompt="false">
</div>
<div class="g_id_signin" 
     data-type="standard" 
     data-size="large" 
     data-theme="outline"
     data-text="sign_up_with" 
     data-shape="rectangular" 
     data-logo_alignment="left" 
     data-width="100%">
</div>
```

**Replace `YOUR_ACTUAL_CLIENT_ID_HERE` with the Client ID from Step 3.**

### Step 6: Test Locally

1. Start your XAMPP server
2. Navigate to `http://localhost/Constructa/engineer_signup.html`
3. The Google Sign In button should now work without errors
4. Click it and sign in with a test user account

### Step 7: Handle the Response (Backend Required)

**IMPORTANT**: The current implementation only logs the user in on the frontend. For production, you need:

1. **A backend server** (PHP, Node.js, Python, etc.)
2. **Token verification** - Never trust the token from the frontend alone!

#### Basic PHP Backend Example:

Create `verify_google_token.php`:

```php
<?php
// Include Google API PHP Client Library
require_once 'vendor/autoload.php';

$client = new Google_Client(['client_id' => 'YOUR_CLIENT_ID']);

$id_token = $_POST['credential'];
$payload = $client->verifyIdToken($id_token);

if ($payload) {
    $userid = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];
    
    // TODO: Save user to database
    // TODO: Create session
    
    echo json_encode(['success' => true, 'user' => $name]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
}
?>
```

Then update `handleCredentialResponse()` in your HTML:

```javascript
function handleCredentialResponse(response) {
    // Send to backend for verification
    fetch('verify_google_token.php', {
        method: 'POST',
        body: JSON.stringify({ credential: response.credential }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`Welcome ${data.user}!`);
            window.location.href = 'engineer.html';
        } else {
            alert('Sign in failed. Please try again.');
        }
    });
}
```

## Security Considerations

⚠️ **NEVER**:
- Expose your Client Secret in frontend code
- Trust the JWT token without backend verification
- Store sensitive data in localStorage without encryption

✅ **ALWAYS**:
- Verify tokens on the backend
- Use HTTPS in production (not localhost)
- Implement CSRF protection
- Follow OAuth 2.0 security best practices

## Production Deployment

When deploying to a real domain (e.g., `https://constructa.com`):

1. Update **Authorized JavaScript origins** in Google Cloud Console:
   ```
   https://constructa.com
   ```
2. Update **Authorized redirect URIs**:
   ```
   https://constructa.com/engineer_signup.html
   ```
3. Request app verification from Google (if needed)
4. Switch OAuth consent screen from "Testing" to "Production"

## Alternative: Use a Simple Email/Password System

If Google OAuth is too complex for now, you can:
1. Keep the current form-based signup
2. Store user data in a database (MySQL via XAMPP)
3. Implement session management with PHP
4. Add OAuth later when needed

## Resources

- [Google Identity Services Documentation](https://developers.google.com/identity/gsi/web)
- [OAuth 2.0 for Web Applications](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)

---

**Current Status**: Google Sign In is **DISABLED** to prevent errors. Follow this guide to enable it properly.
