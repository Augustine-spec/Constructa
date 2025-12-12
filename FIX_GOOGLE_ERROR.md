# URGENT: Fix Google OAuth "Access Blocked" Error

## Current Error:
```
Access blocked: Authorization Error
no registered origin
Error 401: invalid_client
```

## Why This Happens:
Your Google Cloud Console OAuth credentials are not configured for `http://localhost`.

## EXACT Steps to Fix (Follow Carefully):

### Step 1: Go to Google Cloud Console
1. Open: https://console.cloud.google.com/apis/credentials
2. Sign in with the account that created the client ID
3. Find your OAuth 2.0 Client ID: **665743141019-gq39034aahsgi72o9imvc46gr1dkfpq3.apps.googleusercontent.com**
4. Click the **pencil icon (✏️)** to edit it

### Step 2: Add Authorized JavaScript Origins
In the "Authorized JavaScript origins" section, click **+ ADD URI** and add:

```
http://localhost
```

**Important**: 
- Do NOT add `http://localhost/Constructa`
- Do NOT add a trailing slash
- Just `http://localhost` exactly as shown

### Step 3: Add Authorized Redirect URIs
In the "Authorized redirect URIs" section, click **+ ADD URI** and add these **exact URLs**:

```
http://localhost/Constructa/engineer_signup.html
http://localhost/Constructa/homeowner_signup.html
```

**Copy-paste these exactly** - case matters!

### Step 4: Save Changes
1. Click **SAVE** at the bottom
2. **Wait 5 minutes** for changes to propagate (very important!)

### Step 5: Test Again
1. Make sure XAMPP Apache is running
2. Clear your browser cache (Ctrl+Shift+Delete)
3. Open: `http://localhost/Constructa/engineer_signup.html`
4. Click "Sign up with Google"

## Still Getting Errors?

### If you see "redirect_uri_mismatch":
- The URL in your browser must EXACTLY match what you added in Step 3
- Check for typos, case sensitivity, trailing slashes

### If the popup doesn't open:
- Disable popup blockers for localhost
- Try in an incognito/private window
- Check browser console (F12) for JavaScript errors

### If you see "This app isn't verified":
- Click "Advanced" → "Go to Constructa (unsafe)"
- This is normal for development apps

## Quick Checklist:
- [ ] Added `http://localhost` to Authorized JavaScript origins
- [ ] Added redirect URIs exactly as shown above
- [ ] Clicked SAVE in Google Cloud Console
- [ ] Waited at least 5 minutes
- [ ] Apache is running in XAMPP
- [ ] Accessing via `http://localhost/...` not `file://`

## Screenshot Your Settings:
After saving, your Google Cloud Console should show:

**Authorized JavaScript origins:**
- http://localhost

**Authorized redirect URIs:**
- http://localhost/Constructa/engineer_signup.html
- http://localhost/Constructa/homeowner_signup.html

---

**Current Client ID**: 665743141019-gq39034aahsgi72o9imvc46gr1dkfpq3.apps.googleusercontent.com
**Status**: ✅ Client ID configured in code | ⚠️ Google Cloud Console needs setup
