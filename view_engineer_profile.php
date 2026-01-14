<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Get engineer ID from URL
$engineer_id = $_GET['engineer_id'] ?? null;

if (!$engineer_id || !is_numeric($engineer_id)) {
    header('Location: engineer_directory.php');
    exit();
}

// Get viewer information
$viewer_role = $_SESSION['role'] ?? 'guest';
$viewer_id = $_SESSION['user_id'] ?? null;

// If engineer viewing their own profile, redirect to edit page
if ($viewer_role === 'engineer' && $viewer_id == $engineer_id) {
    header('Location: engineer_profile.php');
    exit();
}

require_once 'backend/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Profile - Constructa</title>
    
    <!-- Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --primary: #294033;
            --accent: #10b981;
            --text-main: #1e293b;
            --text-dim: #64748b;
            --soft-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
            --card-radius: 32px;
            --verified-blue: #3b82f6;
            --warning-red: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            background-color: #f8fafc;
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 3D Background */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            pointer-events: none;
        }

        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(41, 64, 51, 0.05) 0%, transparent 40%);
            z-index: -1;
            pointer-events: none;
        }

        /* Navbar */
        nav {
            padding: 1.5rem 4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -1px;
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
        }

        .nav-btn {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.1);
        }

        /* Loading State */
        .loading-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            flex-direction: column;
            gap: 1rem;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(41, 64, 51, 0.1);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Main Layout */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 3rem;
            align-items: start;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .profile-container.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Left Column: Profile Card */
        .profile-card {
            position: sticky;
            top: 120px;
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 3rem 2.5rem;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.06);
            border: 1px solid #f1f5f9;
        }

        .profile-avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
        }

        .profile-avatar-ring {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 6px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            box-shadow: 0 10px 30px rgba(41, 64, 51, 0.15);
        }

        .profile-avatar-text {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .profile-avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .verified-badge {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--verified-blue);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            border: 3px solid #fff;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
            z-index: 2;
        }

        .verified-badge.pending {
            background: #94a3b8;
        }

        .verified-badge.suspended {
            background: var(--warning-red);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .profile-role {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1.5rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
            padding: 2rem 0;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            padding: 1rem 2rem;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .action-btn.primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 8px 20px rgba(41, 64, 51, 0.2);
        }

        .action-btn.primary:hover {
            background: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.25);
        }

        .action-btn.secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .action-btn.secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn.admin-verify {
            background: var(--verified-blue);
            color: white;
        }

        .action-btn.admin-suspend {
            background: var(--warning-red);
            color: white;
        }

        .action-btn.admin-assign {
            background: var(--accent);
            color: white;
        }

        /* Right Column: Details */
        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .details-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--card-radius);
            padding: 2.5rem;
            box-shadow: var(--soft-shadow);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .bio-text {
            color: var(--text-dim);
            line-height: 1.8;
            font-size: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .info-item {
            background: white;
            padding: 1.2rem;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .info-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .info-value a {
            color: var(--accent);
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 2px;
            height: 100%;
            background: var(--glass-border);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.35rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--accent);
        }

        .timeline-date {
            font-weight: 800;
            color: var(--accent);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            display: block;
        }

        .timeline-content {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.suspended {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Admin Activity Table */
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        .activity-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .activity-table td {
            padding: 1rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }

        .activity-table tr:hover {
            background: #f8fafc;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr;
            }

            .profile-card {
                position: relative;
                top: 0;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Hidden class */
        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>
    <!-- 3D Background -->
    <div id="canvas-container"></div>
    <div class="bg-gradient"></div>

    <!-- Navigation -->
    <nav>
        <a href="<?php echo $viewer_role === 'admin' ? 'admin_dashboard.php' : ($viewer_role === 'homeowner' ? 'homeowner.php' : 'engineer.php'); ?>" class="nav-logo">
            CONSTRUCTA
        </a>
        <div class="nav-actions">
            <a href="<?php echo $viewer_role === 'admin' ? 'admin_dashboard.php' : ($viewer_role === 'homeowner' ? 'homeowner.php' : 'engineer.php'); ?>" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </nav>

    <!-- Loading State -->
    <div class="loading-container" id="loadingState">
        <div class="spinner"></div>
        <p style="color: var(--text-dim); font-weight: 600;">Loading profile...</p>
    </div>

    <!-- Profile Container -->
    <div class="profile-container" id="profileContainer">
        <!-- Left: Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar-container">
                <div class="profile-avatar-ring" id="avatarRing">
                    <span class="profile-avatar-text" id="avatarText"></span>
                </div>
                <div class="verified-badge" id="verifiedBadge">
                    <i class="fas fa-check"></i>
                </div>
            </div>

            <h1 class="profile-name" id="profileName">-</h1>
            <p class="profile-role" id="profileRole">-</p>

            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value" id="statExperience">0</span>
                    <span class="stat-label">Years</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="statProjects">0</span>
                    <span class="stat-label">Projects</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="statCompleted">0</span>
                    <span class="stat-label">Completed</span>
                </div>
            </div>

            <div class="profile-actions" id="profileActions">
                <!-- Dynamically populated based on role -->
            </div>
        </div>

        <!-- Right: Details -->
        <div class="profile-details">
            <!-- Bio Section -->
            <div class="details-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h2 class="card-title">Professional Summary</h2>
                </div>
                <p class="bio-text" id="bioText">-</p>
            </div>

            <!-- Professional Information -->
            <div class="details-card" id="professionalInfoCard">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h2 class="card-title">Professional Information</h2>
                </div>
                <div class="info-grid" id="infoGrid">
                    <!-- Dynamically populated -->
                </div>
            </div>

            <!-- Admin-Only: Activity -->
            <div class="details-card hidden" id="adminActivityCard">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h2 class="card-title">Recent Activity</h2>
                </div>
                <div id="activityContent">
                    <!-- Dynamically populated -->
                </div>
            </div>

            <!-- Experience Timeline -->
            <div class="details-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-timeline"></i>
                    </div>
                    <h2 class="card-title">Career Journey</h2>
                </div>
                <div class="timeline" id="timeline">
                    <div class="timeline-item">
                        <span class="timeline-date" id="memberSince">-</span>
                        <div class="timeline-content">
                            Joined Constructa platform
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const engineerId = <?php echo json_encode($engineer_id); ?>;
        const viewerRole = <?php echo json_encode($viewer_role); ?>;

        // Fetch and display profile
        async function loadProfile() {
            try {
                const response = await fetch(`backend/get_engineer_profile.php?engineer_id=${engineerId}`);
                const data = await response.json();

                if (!data.success) {
                    alert(data.message || 'Failed to load profile');
                    window.location.href = 'engineer_directory.php';
                    return;
                }

                displayProfile(data);
            } catch (error) {
                console.error('Error loading profile:', error);
                alert('An error occurred while loading the profile');
            }
        }

        function displayProfile(data) {
            const { engineer, permissions, view_mode, admin_data } = data;

            // Hide loading, show profile
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('profileContainer').classList.add('visible');

            // Avatar
            const avatarText = engineer.name.charAt(0).toUpperCase();
            document.getElementById('avatarText').textContent = avatarText;

            // Verification badge
            const badge = document.getElementById('verifiedBadge');
            if (engineer.is_verified) {
                badge.classList.remove('pending', 'suspended');
                badge.title = 'Verified Engineer';
            } else if (engineer.status === 'suspended') {
                badge.classList.add('suspended');
                badge.innerHTML = '<i class="fas fa-ban"></i>';
                badge.title = 'Suspended Account';
            } else {
                badge.classList.add('pending');
                badge.innerHTML = '<i class="fas fa-clock"></i>';
                badge.title = 'Pending Verification';
            }

            // Name and Role
            document.getElementById('profileName').textContent = engineer.name;
            document.getElementById('profileRole').textContent = engineer.specialization;

            // Stats
            document.getElementById('statExperience').textContent = engineer.experience;
            document.getElementById('statProjects').textContent = engineer.stats.total_projects;
            document.getElementById('statCompleted').textContent = engineer.stats.completed_projects;

            // Bio
            document.getElementById('bioText').textContent = engineer.bio || 'No professional summary provided yet.';

            // Professional Information
            const infoGrid = document.getElementById('infoGrid');
            infoGrid.innerHTML = '';

            // Always show specialization and experience
            infoGrid.innerHTML += createInfoItem('Specialization', engineer.specialization);
            infoGrid.innerHTML += createInfoItem('Experience', `${engineer.experience} years`);
            infoGrid.innerHTML += createInfoItem('Member Since', engineer.member_since);
            infoGrid.innerHTML += createInfoItem('Status', `<span class="status-badge ${engineer.status}">${engineer.status}</span>`);

            // Contact info (only for self or admin)
            if (permissions.can_view_contact) {
                infoGrid.innerHTML += createInfoItem('Email', `<a href="mailto:${engineer.email}">${engineer.email}</a>`);
                infoGrid.innerHTML += createInfoItem('Phone', engineer.phone || 'Not provided');
                if (engineer.license_number) {
                    infoGrid.innerHTML += createInfoItem('License Number', engineer.license_number);
                }
                if (engineer.portfolio_url) {
                    infoGrid.innerHTML += createInfoItem('Portfolio', `<a href="${engineer.portfolio_url}" target="_blank">View Portfolio</a>`);
                }
            }

            // Timeline
            document.getElementById('memberSince').textContent = engineer.member_since;

            // Actions based on permissions
            const actionsContainer = document.getElementById('profileActions');
            actionsContainer.innerHTML = '';

            if (permissions.can_request_service) {
                // Homeowner actions
                actionsContainer.innerHTML = `
                    <button class="action-btn primary" onclick="requestProject()">
                        <i class="fas fa-paper-plane"></i> Request Project
                    </button>
                    <button class="action-btn secondary" onclick="contactEngineer()">
                        <i class="fas fa-envelope"></i> Contact Engineer
                    </button>
                `;
            } else if (permissions.can_admin_actions && admin_data) {
                // Admin actions
                if (admin_data.can_verify) {
                    actionsContainer.innerHTML += `
                        <button class="action-btn admin-verify" onclick="verifyEngineer()">
                            <i class="fas fa-check-circle"></i> Verify Engineer
                        </button>
                    `;
                }
                if (admin_data.can_suspend) {
                    actionsContainer.innerHTML += `
                        <button class="action-btn admin-suspend" onclick="suspendEngineer()">
                            <i class="fas fa-ban"></i> Suspend Account
                        </button>
                    `;
                }
                actionsContainer.innerHTML += `
                    <button class="action-btn admin-assign" onclick="assignProject()">
                        <i class="fas fa-tasks"></i> Assign Project
                    </button>
                `;

                // Show admin activity
                displayAdminActivity(admin_data.recent_activity);
            } else {
                // Other engineers viewing - just contact
                actionsContainer.innerHTML = `
                    <button class="action-btn secondary" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </button>
                `;
            }
        }

        function createInfoItem(label, value) {
            return `
                <div class="info-item">
                    <div class="info-label">${label}</div>
                    <div class="info-value">${value}</div>
                </div>
            `;
        }

        function displayAdminActivity(activities) {
            const card = document.getElementById('adminActivityCard');
            card.classList.remove('hidden');

            if (!activities || activities.length === 0) {
                document.getElementById('activityContent').innerHTML = '<p style="color: var(--text-dim);">No recent activity</p>';
                return;
            }

            let tableHTML = `
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Homeowner</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            activities.forEach(activity => {
                tableHTML += `
                    <tr>
                        <td>${activity.project_title || 'Project Request'}</td>
                        <td>${activity.homeowner_name || 'Unknown'}</td>
                        <td><span class="status-badge ${activity.status}">${activity.status}</span></td>
                        <td>${new Date(activity.created_at).toLocaleDateString()}</td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            document.getElementById('activityContent').innerHTML = tableHTML;
        }

        // Action handlers
        function requestProject() {
            window.location.href = `contact_engineer.php?id=${engineerId}`;
        }

        function contactEngineer() {
            window.location.href = `contact_engineer.php?id=${engineerId}`;
        }

        function verifyEngineer() {
            if (confirm('Verify this engineer? This will approve their account and make them visible to homeowners.')) {
                performAdminAction('verify');
            }
        }

        function suspendEngineer() {
            if (confirm('Suspend this engineer? This will hide them from homeowner directory and block new projects.')) {
                performAdminAction('suspend');
            }
        }

        async function performAdminAction(action) {
            try {
                const formData = new FormData();
                formData.append('engineer_id', engineerId);
                formData.append('action', action);

                const response = await fetch('backend/admin_engineer_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    // Reload profile to show updated status
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error performing admin action:', error);
                alert('An error occurred. Please try again.');
            }
        }

        function assignProject() {
            alert('Project assignment feature coming soon');
        }

        function goBack() {
            window.history.back();
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadProfile();
            init3DBackground();
        });

        // 3D Background
        function init3DBackground() {
            const container = document.getElementById('canvas-container');
            if (!container || typeof THREE === 'undefined') return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f8fafc');
            
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 5, 10);
            
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);
            
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambientLight);
            
            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 20, 10);
            scene.add(mainLight);

            // Create building grid
            const buildingGroup = new THREE.Group();
            scene.add(buildingGroup);
            
            const buildMat = new THREE.MeshPhongMaterial({ 
                color: 0x294033, 
                transparent: true, 
                opacity: 0.08
            });
            const edgeMat = new THREE.LineBasicMaterial({ 
                color: 0x294033, 
                transparent: true, 
                opacity: 0.15
            });

            for (let x = -6; x <= 6; x += 1.5) {
                for (let z = -6; z <= 6; z += 1.5) {
                    const h = Math.random() * 2 + 0.5;
                    const geo = new THREE.BoxGeometry(0.8, h, 0.8);
                    const mesh = new THREE.Mesh(geo, buildMat);
                    mesh.position.set(x, -5 + h / 2, z);
                    
                    const edges = new THREE.EdgesGeometry(geo);
                    const line = new THREE.LineSegments(edges, edgeMat);
                    line.position.copy(mesh.position);
                    
                    buildingGroup.add(mesh);
                    buildingGroup.add(line);
                }
            }

            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) * 0.0003;
                mouseY = (e.clientY - window.innerHeight / 2) * 0.0003;
            });

            function animate() {
                requestAnimationFrame(animate);
                
                buildingGroup.rotation.y += 0.001;
                buildingGroup.rotation.x += 0.03 * (mouseY - buildingGroup.rotation.x);
                buildingGroup.rotation.y += 0.03 * (mouseX - buildingGroup.rotation.y);
                
                renderer.render(scene, camera);
            }
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        }
    </script>
</body>
</html>
