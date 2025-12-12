# 🚀 Google Sign In Setup Instructions

## ✅ What I've Already Done For You:

1. ✅ **Re-enabled Google Sign In button** on `engineer_signup.html`
2. ✅ **Created PHP backend** for secure token verification (`backend/google_signup.php`)
3. ✅ **Set up database configuration** (`backend/config.php`)
4. ✅ **Added session management** (`backend/auth.php`)
5. ✅ **Auto-creates database and tables** on first run

## 🔧 What You Need To Do Now:

### **STEP 1: Get Your Google Client ID** ⭐ (REQUIRED)

#### 1.1 Go to Google Cloud Console
- Open: https://console.cloud.google.com/
- Sign in with your Google account

#### 1.2 Create a New Project
- Click **"Select a project"** → **"NEW PROJECT"**
- Project name: `Constructa`
- Click **"CREATE"**
- Wait for the project to be created (30 seconds)

#### 1.3 Enable Google Identity Services API
- In the left menu, click **"APIs & Services"** → **"Library"**
- Search for: `Google Identity Services API`
- Click on it → Click **"ENABLE"**

#### 1.4 Configure OAuth Consent Screen
- Go to **"APIs & Services"** → **"OAuth consent screen"**
- Select **"External"** → Click **"CREATE"**
- Fill in the required fields:
  - **App name**: `Constructa`
  - **User support email**: Your email
  - **Developer contact information**: Your email
- Click **"SAVE AND CONTINUE"**
- Click **"SAVE AND CONTINUE"** again (skip Scopes)
- Add **Test users**:
  - Click **"+ ADD USERS"**
  - Enter your email address
  - Click **"ADD"**
- Click **"SAVE AND CONTINUE"**
- Click **"BACK TO DASHBOARD"**

#### 1.5 Create OAuth 2.0 Client ID
- Go to **"APIs & Services"** → **"Credentials"**
- Click **"+ CREATE CREDENTIALS"** → **"OAuth client ID"**
- Application type: **"Web application"**
- Name: `Constructa Web Client`
- Under **"Authorized JavaScript origins"**, click **"+ ADD URI"**:
  ```
  http://localhost
  ```
- Under **"Authorized redirect URIs"**, click **"+ ADD URI"**:
  ```
  http://localhost/Constructa/engineer_signup.html
  ```
- Click **"CREATE"**

#### 1.6 Copy Your Client ID
- A popup will show your **Client ID**
- It looks like: `123456789-abcdefghijk.apps.googleusercontent.com`
- **COPY THIS!** You'll need it in the next step

---

### **STEP 2: Update Your Code with Client ID**

#### 2.1 Update `engineer_signup.html`
- Open: `c:\xampp\htdocs\Constructa\engineer_signup.html`
- Find line ~407 (search for `PASTE_YOUR_GOOGLE_CLIENT_ID_HERE`)
- Replace `PASTE_YOUR_GOOGLE_CLIENT_ID_HERE` with your actual Client ID
- Example:
  ```html
  data-client_id="123456789-abcdefghijk.apps.googleusercontent.com"
  ```
- **Save the file**

#### 2.2 Update `backend/config.php`
- Open: `c:\xampp\htdocs\Constructa\backend\config.php`
- Find line ~15 (search for `PASTE_YOUR_GOOGLE_CLIENT_ID_HERE`)
- Replace with the **SAME** Client ID
- Example:
  ```php
  $GOOGLE_CLIENT_ID = '123456789-abcdefghijk.apps.googleusercontent.com';
  ```
- **Save the file**

---

### **STEP 3: Start XAMPP**

1. Open **XAMPP Control Panel**
2. Start **Apache** (click "Start")
3. Start **MySQL** (click "Start")
4. Wait until both show **green "Running"** status

---

### **STEP 4: Test Google Sign In**

1. Open your browser (Chrome recommended)
2. Navigate to: `http://localhost/Constructa/engineer_signup.html`
3. Click the **"Sign up with Google"** button
4. Sign in with your Google account (use the test user you added in Step 1.4)
5. Allow permissions
6. You should be redirected to `engineer.html`

**🎉 If it works, you're done! The database was created automatically.**

---

## 🔍 Troubleshooting

### ❌ Error: "Access blocked: Constructa has not completed verification"
**Solution**: This is normal for testing!
- Go to Google Cloud Console → OAuth consent screen
- Under "Test users", add your email
- Make sure you're signing in with that email

### ❌ Error: "redirect_uri_mismatch"
**Solution**: 
- Check that you added `http://localhost/Constructa/engineer_signup.html` in Google Cloud Console
- Make sure there are no extra spaces or typos
- The URI must match EXACTLY

### ❌ Error: "Database connection failed"
**Solution**:
- Make sure MySQL is running in XAMPP
- Check `backend/config.php` - default username is `root`, password is empty
- If you changed MySQL password, update it in `config.php`

### ❌ Error: "Failed to fetch"
**Solution**:
- Make sure Apache is running in XAMPP
- Check that `backend/google_signup.php` exists
- Try opening: `http://localhost/Constructa/backend/google_signup.php` directly to see PHP errors

### ❌ Google button doesn't appear
**Solution**:
- Check browser console (F12) for JavaScript errors
- Make sure you replaced `PASTE_YOUR_GOOGLE_CLIENT_ID_HERE` with actual Client ID
- Clear browser cache (Ctrl+Shift+Delete)

---

## 📊 Database Information

The database is **automatically created** when you first run the backend.

**Database Name**: `constructa`
**Table Name**: `users`

**Columns:**
- `id` - Auto-incrementing user ID
- `name` - User's full name from Google
- `email` - User's email (unique)
- `google_id` - Google's unique user ID
- `profile_picture` - URL to Google profile picture
- `role` - 'engineer' or 'homeowner'
- `created_at` - When account was created
- `updated_at` - Last update timestamp

**To view the database:**
1. Open browser: `http://localhost/phpmyadmin`
2. Click on `constructa` database
3. Click on `users` table
4. You'll see all registered users

---

## 🔐 Security Notes

✅ **What's Secure:**
- Token is verified on the backend (PHP)
- User password is not stored (Google handles authentication)
- Email uniqueness is enforced
- SQL injection protection with prepared statements

⚠️ **For Production (later):**
- Use HTTPS instead of HTTP
- Verify Google token signature with Google's public keys
- Add CSRF protection
- Move `config.php` outside web root
- Never commit `config.php` to Git

---

## 📝 Next Steps

Once Google Sign In works:

1. **Add Google Sign In to Homeowner signup** (similar process)
2. **Create login pages** that also use Google Sign In
3. **Add logout functionality** (button that calls `backend/auth.php?logout=1`)
4. **Protect pages** that require login using `backend/auth.php`

---

## 🆘 Still Having Issues?

Check these files have the correct Client ID:
- ✅ `engineer_signup.html` (line ~407)
- ✅ `backend/config.php` (line ~15)

Make sure XAMPP services are running:
- ✅ Apache: **Green "Running"**
- ✅ MySQL: **Green "Running"**

If problems persist, check:
- Browser console (F12 → Console tab)
- Apache error log (XAMPP Control Panel → Apache → Logs → Error Log)
- PHP error log (check `backend/google_signup.php` directly in browser)

---

**👉 START WITH STEP 1 NOW!**

Go to: https://console.cloud.google.com/
