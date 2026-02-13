<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();

// Fetch all settings
$settings = [];
$res = $conn->query("SELECT * FROM system_config");
while($row = $res->fetch_assoc()) {
    $settings[$row['config_key']] = $row['config_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global System Control | Constructa Enterprise</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root {
            --bg-page: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --primary: #294033;
            --primary-light: #3d5a49;
            --accent-blue: #3b82f6;
            --accent-red: #ef4444;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 2rem 0;
            z-index: 10;
        }

        .brand {
            padding: 0 2rem;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 800;
            color: var(--primary);
            font-size: 1.25rem;
        }

        .nav-link {
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(41, 64, 51, 0.05);
            color: var(--primary);
            border-left-color: var(--primary);
        }

        /* Main Workspace */
        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        header {
            height: 80px;
            padding: 0 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            border-bottom: 1px solid var(--border);
        }

        .page-title h1 { font-size: 1.5rem; font-weight: 700; }
        .page-title p { font-size: 0.85rem; color: var(--text-muted); }

        .content-area {
            flex: 1;
            padding: 3rem;
            overflow-y: auto;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Settings Card & Grid */
        .settings-section {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .settings-section.active { display: block; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .settings-group {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .group-header {
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }

        .group-header h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .group-header p { font-size: 0.85rem; color: var(--text-muted); }

        .setting-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .setting-item:last-child { border-bottom: none; }

        .setting-info h4 { font-size: 0.95rem; font-weight: 600; margin-bottom: 0.25rem; }
        .setting-info p { font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; }

        .setting-control { display: flex; align-items: center; justify-content: flex-end; }

        /* Form Elements */
        .input-text {
            width: 100%;
            max-width: 300px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-text:focus { border-color: var(--primary); }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .toggle-switch input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(24px); }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); }

        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
        .btn-outline:hover { background: #f1f5f9; }

        /* Save Bar */
        .sticky-save {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            z-index: 100;
            transform: translateY(100px);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .sticky-save.visible { transform: translateY(0); }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
        }

        .pulse {
            width: 8px; height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(3); opacity: 0; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <i data-lucide="settings"></i>
        <span>SYSTEM CORE</span>
    </div>
    
    <nav>
        <a href="#" class="nav-link active" onclick="showTab('security')">
            <i data-lucide="shield-lock"></i> Security & Access
        </a>
        <a href="#" class="nav-link" onclick="showTab('users')">
            <i data-lucide="users"></i> Role Management
        </a>
        <a href="#" class="nav-link" onclick="showTab('application')">
            <i data-lucide="cpu"></i> App Configuration
        </a>
        <a href="#" class="nav-link" onclick="showTab('data')">
            <i data-lucide="database"></i> Data Retention
        </a>
        <a href="#" class="nav-link" onclick="showTab('api')">
            <i data-lucide="webhook"></i> API & Integrations
        </a>
        <a href="#" class="nav-link" onclick="showTab('performance')">
            <i data-lucide="zap"></i> Optimization
        </a>
    </nav>
</aside>

<div class="main-container">
    <header>
        <div class="page-title">
            <h1>System Settings</h1>
            <p>Global environmental variables and security enforcement policies</p>
        </div>
        <div>
            <a href="admin_dashboard.php" class="btn btn-outline">
                <i data-lucide="layout-grid"></i> Dashboard
            </a>
        </div>
    </header>

    <div class="content-area">
        <form id="settings-form">
            <!-- Security & Access -->
            <section id="security" class="settings-section active">
                <div class="settings-group">
                    <div class="group-header">
                        <h3>Security & Authentication Policies</h3>
                        <p>Configure how identity and sessions are managed across the platform.</p>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Password Complexity</h4>
                            <p>Enforce minimum requirements for all user roles (symbols, length, numbers).</p>
                        </div>
                        <div class="setting-control">
                            <select class="input-text">
                                <option value="low" <?php echo ($settings['pwd_complexity'] == 'low') ? 'selected' : ''; ?>>Basic (8 characters)</option>
                                <option value="medium" <?php echo ($settings['pwd_complexity'] == 'medium') ? 'selected' : ''; ?>>Strong (Length + Type)</option>
                                <option value="high" <?php echo ($settings['pwd_complexity'] == 'high') ? 'selected' : ''; ?>>Enterprise (Strict)</option>
                            </select>
                        </div>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Idle Session Timeout</h4>
                            <p>Forced logout after defined minutes of inactivity.</p>
                        </div>
                        <div class="setting-control">
                            <input type="number" class="input-text" value="<?php echo $settings['session_timeout']; ?>" placeholder="Minutes">
                        </div>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Enforce MFA</h4>
                            <p>Require Multi-Factor Authentication for Admin and Engineer roles.</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" <?php echo ($settings['mfa_enabled'] == 'true') ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="settings-group">
                    <div class="group-header">
                        <h3>Access Restriction</h3>
                        <p>Global network and hardware level controls.</p>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>IP Allowlisting</h4>
                            <p>Restrict Admin access to specific static IP ranges.</p>
                        </div>
                        <div class="setting-control">
                            <button type="button" class="btn btn-outline" style="font-size: 0.75rem;">Configure Ranges</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- User & Role Management -->
            <section id="users" class="settings-section">
                <div class="settings-group">
                    <div class="group-header">
                        <h3>User Onboarding Rules</h3>
                        <p>Define global registration and verification workflows.</p>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Engineer Verification</h4>
                            <p>Manual review required for all license-based engineering accounts.</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Application configuration -->
            <section id="application" class="settings-section">
                <div class="settings-group">
                    <div class="group-header">
                        <h3>Platform Global Mode</h3>
                        <p>Control application-wide states.</p>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Maintenance Mode</h4>
                            <p>Disable all frontend access and show specialized maintenance page.</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" <?php echo ($settings['maintenance_mode'] == 'true') ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Data Management -->
            <section id="data" class="settings-section">
                <div class="settings-group">
                    <div class="group-header">
                        <h3>Data Persistence & Backup</h3>
                        <p>Define how long records are stored and how they are recovered.</p>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Log Retention Duration</h4>
                            <p>Forensic security logs retention period (days) before archival.</p>
                        </div>
                        <div class="setting-control">
                            <input type="number" class="input-text" value="<?php echo $settings['log_retention_days']; ?>">
                        </div>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Automated Off-site Backups</h4>
                            <p>Frequency of encrypted system snapshots.</p>
                        </div>
                        <div class="setting-control">
                            <select class="input-text">
                                <option value="hourly">Hourly</option>
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <!-- API Configuration -->
            <section id="api" class="settings-section">
                <div class="settings-group">
                    <div class="group-header">
                        <h3>Integration Gateways</h3>
                        <p>Manage external connections and API limits.</p>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Global Rate Limiting</h4>
                            <p>Maximum requests allowed per minute across all API endpoints.</p>
                        </div>
                        <div class="setting-control">
                            <input type="number" class="input-text" value="<?php echo $settings['api_rate_limit']; ?>">
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </div>
</div>

<div class="sticky-save" id="save-bar">
    <div class="status-badge">
        <div class="pulse"></div>
        UNSAVED CHANGES DETECTED
    </div>
    <button class="btn btn-primary" onclick="saveSettings()">
        <i data-lucide="save"></i> APPLY CHANGES
    </button>
</div>

<script>
    lucide.createIcons();

    function showTab(tabId) {
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    // Detect changes
    const form = document.getElementById('settings-form');
    const saveBar = document.getElementById('save-bar');
    
    form.addEventListener('change', () => {
        saveBar.classList.add('visible');
    });

    async function saveSettings() {
        const btn = document.querySelector('.btn-primary');
        const icon = btn.querySelector('i');
        
        btn.disabled = true;
        btn.innerText = "Applying Policies...";
        
        // Simulating backend save and log generation
        setTimeout(() => {
            saveBar.classList.remove('visible');
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check"></i> System Updated';
            lucide.createIcons();
            
            // Revert text after 3s
            setTimeout(() => {
                btn.innerHTML = '<i data-lucide="save"></i> APPLY CHANGES';
                lucide.createIcons();
            }, 3000);
        }, 1500);
    }
</script>

</body>
</html>
