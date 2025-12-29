@echo off
echo ==========================================
echo    Constructa OTP Email System Test
echo ==========================================
echo.

REM Check if Composer is installed
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [X] Composer is NOT installed
    echo     Download from: https://getcomposer.org/download/
    echo.
) else (
    echo [✓] Composer is installed
)

REM Check if PHPMailer is installed
if exist "vendor\autoload.php" (
    echo [✓] PHPMailer is installed
    echo.
    echo Email Status: READY TO CONFIGURE
    echo.
    echo Next Steps:
    echo 1. Get Gmail App Password: https://myaccount.google.com/apppasswords
    echo 2. Update backend\email_config.php with your credentials
    echo 3. Test the forgot password page
) else (
    echo [!] PHPMailer is NOT installed
    echo.
    echo Current Status: DEVELOPMENT MODE
    echo  - OTP will show on screen
    echo  - No emails will be sent
    echo.
    echo To enable email sending:
    echo   composer require phpmailer/phpmailer
    echo.
    echo Or read: OTP_EMAIL_FIX.md
)

echo.
echo ==========================================
echo Files Modified:
echo  - backend\send_otp.php
echo  - backend\email_config.php (NEW)
echo  - forgot_password.html
echo ==========================================
echo.

REM Check PHP error log
if exist "C:\xampp\php\logs\php_error_log" (
    echo Recent PHP Errors ^(Last 10 lines^):
    echo ------------------------------------------
    powershell -Command "Get-Content 'C:\xampp\php\logs\php_error_log' -Tail 10 -ErrorAction SilentlyContinue"
    echo ------------------------------------------
) else (
    echo No PHP error log found
)

echo.
echo For detailed setup instructions, see: OTP_EMAIL_FIX.md
echo.
pause
