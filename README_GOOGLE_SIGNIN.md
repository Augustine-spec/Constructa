# How to Test Google Sign-In - IMPORTANT! 🚀

## ⚠️ Common Error: "origin=file:// flowName=GeneralOAuthFlow"

This error means you're opening the HTML file directly instead of through the web server.

## ✅ Correct Setup:

### Step 1: Start XAMPP
1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Wait for Apache to show **green** status

### Step 2: Access Through Web Server
Open your browser and navigate to:
- **Engineer Signup**: `http://localhost/Constructa/engineer_signup.html`
- **Homeowner Signup**: `http://localhost/Constructa/homeowner_signup.html`

### Step 3: Test Google Sign-In
1. Click the **"Sign up with Google"** button
2. Select your Google account
3. Grant permissions

## ❌ DO NOT:
- Double-click the HTML file to open it
- Open via "Open with Browser"
- Use `file:///C:/xampp/...` URLs

## Google Cloud Console Configuration:

Your client ID is already set: `665743141019-gq39034aahsgi72o9imvc46gr1dkfpq3.apps.googleusercontent.com`

### Required Settings:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Navigate to: **APIs & Services** → **Credentials**
3. Click on your OAuth 2.0 Client ID

#### Authorized JavaScript origins:
```
http://localhost
```

#### Authorized redirect URIs:
```
http://localhost/Constructa/engineer_signup.html
http://localhost/Constructa/homeowner_signup.html
http://localhost/Constructa/engineer.html
http://localhost/Constructa/homeowner.html
```

## Testing Checklist:

- [ ] XAMPP Apache is running
- [ ] URL starts with `http://localhost/` (not `file://`)
- [ ] Google Cloud Console has correct authorized URIs
- [ ] Client ID is properly inserted in HTML files

## Still Getting Errors?

### If you see "400 Bad Request" or "redirect_uri_mismatch":
- Check that your Google Cloud Console URIs **exactly match** the URL in your browser
- Make sure there are no trailing slashes differences
- Wait a few minutes after updating Google Cloud Console settings

### If the popup doesn't appear:
- Check browser console for errors (F12)
- Make sure popup blocker is disabled for localhost
- Verify the Google Sign-In library is loading (check Network tab)

### If backend errors occur:
- You'll need to create `backend/google_signup.php` to handle authentication
- See `GOOGLE_OAUTH_SETUP.md` for backend implementation details

## Quick Test URL:
Copy and paste this into your browser (make sure XAMPP is running):
```
http://localhost/Constructa/engineer_signup.html
```

---

**Current Status**: ✅ Client ID configured correctly
**Next Step**: Access via http://localhost instead of file://
