// TEST SCRIPT FOR OTP PASSWORD RESET
// Open this in browser console to quickly test the OTP flow

// ========================================
// STEP 1: Send OTP
// ========================================
async function testSendOTP() {
    const email = prompt("Enter test email (must exist in database):");
    const role = prompt("Enter role (homeowner/engineer):", "homeowner");

    const response = await fetch('backend/send_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, role })
    });

    const data = await response.json();
    console.log("Send OTP Response:", data);

    if (data.success && data.dev_otp) {
        console.log("✅ OTP SENT!");
        console.log("📧 OTP Code:", data.dev_otp);
        console.log("Copy this OTP:", data.dev_otp);
        return { email, role, otp: data.dev_otp };
    } else {
        console.error("❌ Failed to send OTP:", data.message);
        return null;
    }
}

// ========================================
// STEP 2: Verify OTP
// ========================================
async function testVerifyOTP(email, role, otp) {
    if (!otp) {
        otp = prompt("Enter the OTP:");
    }

    const response = await fetch('backend/verify_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, role, otp })
    });

    const data = await response.json();
    console.log("Verify OTP Response:", data);

    if (data.success) {
        console.log("✅ OTP VERIFIED!");
        return true;
    } else {
        console.error("❌ OTP Verification Failed:", data.message);
        return false;
    }
}

// ========================================
// STEP 3: Reset Password
// ========================================
async function testResetPassword(email, role, password) {
    if (!password) {
        password = prompt("Enter new password (min 8 chars):");
    }

    const response = await fetch('backend/reset_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, role, password })
    });

    const data = await response.json();
    console.log("Reset Password Response:", data);

    if (data.success) {
        console.log("✅ PASSWORD RESET SUCCESSFUL!");
        console.log("You can now login with your new password");
        return true;
    } else {
        console.error("❌ Password Reset Failed:", data.message);
        return false;
    }
}

// ========================================
// COMPLETE FLOW TEST
// ========================================
async function testCompleteFlow() {
    console.log("🚀 Starting Complete OTP Flow Test...\n");

    // Step 1: Send OTP
    console.log("📧 STEP 1: Sending OTP...");
    const otpData = await testSendOTP();
    if (!otpData) return;

    console.log("\n⏳ Waiting 2 seconds...\n");
    await new Promise(resolve => setTimeout(resolve, 2000));

    // Step 2: Verify OTP
    console.log("🔍 STEP 2: Verifying OTP...");
    const verified = await testVerifyOTP(otpData.email, otpData.role, otpData.otp);
    if (!verified) return;

    console.log("\n⏳ Waiting 2 seconds...\n");
    await new Promise(resolve => setTimeout(resolve, 2000));

    // Step 3: Reset Password
    console.log("🔐 STEP 3: Resetting Password...");
    await testResetPassword(otpData.email, otpData.role, "newpassword123");

    console.log("\n✨ Test Complete!");
}

// ========================================
// QUICK TEST COMMANDS
// ========================================
console.log(`
╔═══════════════════════════════════════════════════════════╗
║         OTP PASSWORD RESET - TESTING CONSOLE              ║
╠═══════════════════════════════════════════════════════════╣
║                                                           ║
║  Quick Commands:                                          ║
║  ───────────────                                          ║
║  testCompleteFlow()    → Test entire flow automatically   ║
║  testSendOTP()         → Test sending OTP only           ║
║  testVerifyOTP(email, role, otp) → Test OTP verification ║
║  testResetPassword(email, role, pwd) → Test reset        ║
║                                                           ║
║  Example Usage:                                           ║
║  ──────────────                                           ║
║  1. testCompleteFlow()                                    ║
║     Follow the prompts!                                   ║
║                                                           ║
║  2. Manual Testing:                                       ║
║     let data = await testSendOTP();                       ║
║     await testVerifyOTP(data.email, data.role, data.otp); ║
║     await testResetPassword(data.email, data.role, "pwd");║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝

Type: testCompleteFlow() to start!
`);
