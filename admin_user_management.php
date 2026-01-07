<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root {
            --bg-color: #f6f7f2;
            --text-dark: #121212;
            --text-gray: #555555;
            --primary-green: #294033;
            --accent-green: #3d5a49;
            --card-bg: #ffffff;
            --warning-yellow: #f59e0b;
            --success-green: #16a34a;
            --error-red: #dc2626;
            --info-blue: #3b82f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: transparent;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: #f6f7f2;
            pointer-events: none;
        }

        /* Navbar */
        header {
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-green);
            text-decoration: none;
        }

        .logo i {
            font-size: 1.5rem;
        }

        nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary-green);
        }

        /* Main Content */
        main {
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 3rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .page-subtitle {
            color: var(--text-gray);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-value.homeowners { color: var(--info-blue); }
        .stat-value.engineers { color: var(--primary-green); }
        .stat-value.pending { color: var(--warning-yellow); }
        .stat-value.total { color: var(--text-dark); }

        .requests-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .requests-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .requests-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border: none;
            background: #f3f4f6;
            color: var(--text-gray);
            font-weight: 500;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .filter-tab.active {
            color: white;
            background-color: var(--primary-green);
        }

        .user-card {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }

        .user-card:hover {
            background-color: #f9fafb;
        }

        .user-card:last-child {
            border-bottom: none;
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .user-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-subinfo {
            color: var(--text-gray);
            font-size: 0.9rem;
            display: flex;
            gap: 1rem;
        }

        .role-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .role-badge.homeowner {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--info-blue);
        }

        .role-badge.engineer {
            background-color: rgba(41, 64, 51, 0.1);
            color: var(--primary-green);
        }

        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-yellow);
        }

        .status-badge.approved {
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--success-green);
        }

        .status-badge.rejected {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--error-red);
        }

        .user-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-gray);
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .user-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-approve { background-color: var(--success-green); color: white; }
        .btn-reject { background-color: var(--error-red); color: white; }
        .btn-delete { background-color: #f3f4f6; color: var(--error-red); }
        
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        #loading {
            padding: 2rem;
            text-align: center;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-green);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>
    <!-- Navigation -->
    <header>
        <a href="admin_dashboard.php" class="logo">
            <i class="far fa-building"></i>
            Constructa Admin
        </a>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Manage homeowners and professional engineers</p>
        </div>

        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value total" id="totalCount">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Homeowners</div>
                <div class="stat-value homeowners" id="homeownerCount">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Engineers</div>
                <div class="stat-value engineers" id="engineerCount">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Apps</div>
                <div class="stat-value pending" id="pendingCount">-</div>
            </div>
        </div>

        <div class="requests-container">
            <div class="requests-header">
                <h2 class="requests-title">All Users</h2>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all" onclick="filterUsers('all')">All</button>
                    <button class="filter-tab" data-filter="homeowner" onclick="filterUsers('homeowner')">Homeowners</button>
                    <button class="filter-tab" data-filter="engineer" onclick="filterUsers('engineer')">Engineers</button>
                    <button class="filter-tab" data-filter="pending" onclick="filterUsers('pending')">Pending Engineers</button>
                </div>
            </div>

            <div id="loading">
                <div class="spinner"></div>
                <p>Loading user data...</p>
            </div>

            <div id="usersList" style="display: none;"></div>
        </div>
    </main>

    <script>
        let allUsers = [];
        let currentFilter = 'all';

        async function loadUsers() {
            try {
                const response = await fetch('backend/get_all_users.php');
                const data = await response.json();

                if (data.success) {
                    allUsers = data.users;
                    updateStats(data.stats);
                    displayUsers();
                } else {
                    document.getElementById('loading').innerHTML = '<p style="color: var(--error-red);">' + (data.message || 'Failed to load users') + '</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loading').innerHTML = '<p style="color: var(--error-red);">An error occurred while loading users.</p>';
            }
        }

        function updateStats(stats) {
            document.getElementById('totalCount').textContent = stats.total || 0;
            document.getElementById('homeownerCount').textContent = stats.homeowners || 0;
            document.getElementById('engineerCount').textContent = stats.engineers || 0;
            document.getElementById('pendingCount').textContent = stats.pending_engineers || 0;
        }

        function displayUsers() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('usersList').style.display = 'block';

            let filteredUsers = allUsers;
            if (currentFilter === 'homeowner') {
                filteredUsers = allUsers.filter(u => u.role === 'homeowner');
            } else if (currentFilter === 'engineer') {
                filteredUsers = allUsers.filter(u => u.role === 'engineer');
            } else if (currentFilter === 'pending') {
                filteredUsers = allUsers.filter(u => u.role === 'engineer' && u.status === 'pending');
            }

            const usersList = document.getElementById('usersList');

            if (filteredUsers.length === 0) {
                usersList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <p>No users found matching this filter</p>
                    </div>
                `;
                return;
            }

            usersList.innerHTML = filteredUsers.map(user => `
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-info">
                            <h3>${user.name}</h3>
                            <div class="user-subinfo">
                                <span>${user.email}</span>
                                <span class="role-badge ${user.role}">${user.role}</span>
                                ${user.role === 'engineer' ? `<span class="status-badge ${user.status}">${user.status}</span>` : ''}
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Joined</span>
                            <span class="detail-value">${new Date(user.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>

                    <div class="user-details">
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value">${user.phone || 'N/A'}</span>
                        </div>
                        ${user.role === 'engineer' ? `
                            <div class="detail-item">
                                <span class="detail-label">Specialization</span>
                                <span class="detail-value">${user.specialization || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Experience</span>
                                <span class="detail-value">${user.experience || 0} years</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">License</span>
                                <span class="detail-value">${user.license_number || 'N/A'}</span>
                            </div>
                        ` : `
                            <div class="detail-item">
                                <span class="detail-label">Account Type</span>
                                <span class="detail-value">Homeowner</span>
                            </div>
                        `}
                    </div>

                    <div class="user-actions">
                        ${user.role === 'engineer' && user.status === 'pending' ? `
                            <button class="btn btn-approve" onclick="updateEngineerStatus(${user.id}, 'approved')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-reject" onclick="updateEngineerStatus(${user.id}, 'rejected')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        ` : ''}
                        
                        ${user.role === 'engineer' && user.status === 'approved' ? `
                            <button class="btn btn-reject" onclick="updateEngineerStatus(${user.id}, 'rejected')">
                                <i class="fas fa-user-slash"></i> Suspend
                            </button>
                        ` : ''}

                        ${user.role === 'engineer' && user.status === 'rejected' ? `
                            <button class="btn btn-approve" onclick="updateEngineerStatus(${user.id}, 'approved')">
                                <i class="fas fa-user-check"></i> Re-approve
                            </button>
                        ` : ''}

                        <button class="btn btn-delete" onclick="deleteUser(${user.id})">
                            <i class="fas fa-trash"></i> Delete User
                        </button>
                    </div>
                </div>
            `).join('');
        }

        async function updateEngineerStatus(userId, newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus} this application?`)) return;

            try {
                const response = await fetch('backend/update_engineer_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, status: newStatus })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Status updated successfully!');
                    loadUsers();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred.');
            }
        }

        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to PERMANENTLY delete this user? This action cannot be undone.')) return;

            try {
                const response = await fetch('backend/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();
                if (data.success) {
                    alert('User deleted successfully!');
                    loadUsers();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred.');
            }
        }

        function filterUsers(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.filter === filter) tab.classList.add('active');
            });
            displayUsers();
        }

        loadUsers();

        document.addEventListener('DOMContentLoaded', () => {
            // === 3D BACKGROUND ANIMATION ===
            const initBackground3D = () => {
                const container = document.getElementById('canvas-container');
                if (!container) return;

                const scene = new THREE.Scene();
                scene.background = new THREE.Color('#f6f7f2');

                const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
                camera.position.z = 8;
                camera.position.y = 2;

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                container.appendChild(renderer.domElement);

                const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
                scene.add(ambientLight);

                const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
                mainLight.position.set(10, 10, 10);
                scene.add(mainLight);

                const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
                blueLight.position.set(-5, 5, 5);
                scene.add(blueLight);

                const cityGroup = new THREE.Group();
                scene.add(cityGroup);

                const buildingGeometry = new THREE.BoxGeometry(1, 1, 1);
                const buildingMaterial = new THREE.MeshPhongMaterial({
                    color: 0x294033, transparent: true, opacity: 0.1, side: THREE.DoubleSide
                });
                const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.3 });

                const gridSize = 10;
                const spacing = 3;

                for (let x = -gridSize; x < gridSize; x++) {
                    for (let z = -gridSize; z < gridSize; z++) {
                        const height = Math.random() * 2 + 0.5;
                        const building = new THREE.Group();
                        const geometry = new THREE.BoxGeometry(1, height, 1);
                        const mesh = new THREE.Mesh(geometry, buildingMaterial);
                        mesh.position.y = height / 2;
                        const edges = new THREE.EdgesGeometry(geometry);
                        const line = new THREE.LineSegments(edges, edgeMaterial);
                        line.position.y = height / 2;
                        building.add(mesh);
                        building.add(line);
                        building.position.set(x * spacing, -2, z * spacing);
                        cityGroup.add(building);
                    }
                }

                const houseGroup = new THREE.Group();
                const baseGeo = new THREE.BoxGeometry(2, 2, 2);
                const baseLine = new THREE.LineSegments(new THREE.EdgesGeometry(baseGeo), new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
                houseGroup.add(baseLine);
                const roofGeo = new THREE.ConeGeometry(1.5, 1.2, 4);
                const roofLine = new THREE.LineSegments(new THREE.EdgesGeometry(roofGeo), new THREE.LineBasicMaterial({ color: 0x3d5a49, linewidth: 2 }));
                roofLine.position.y = 1.6;
                roofLine.rotation.y = Math.PI / 4;
                houseGroup.add(roofLine);

                const floatGroup = new THREE.Group();
                floatGroup.add(houseGroup);
                floatGroup.position.set(0, 0, 2);
                scene.add(floatGroup);

                let mouseX = 0; let mouseY = 0;
                let targetRotationX = 0; let targetRotationY = 0;
                document.addEventListener('mousemove', (event) => {
                    mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                    mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
                });
                let scrollY = 0;
                window.addEventListener('scroll', () => { scrollY = window.scrollY * 0.001; });

                const animate = () => {
                    requestAnimationFrame(animate);
                    cityGroup.rotation.y += 0.001;
                    floatGroup.rotation.y += 0.005;
                    floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
                    targetRotationX = mouseX; targetRotationY = mouseY;
                    cityGroup.rotation.x += 0.05 * (targetRotationY - cityGroup.rotation.x);
                    cityGroup.rotation.y += 0.05 * (targetRotationX - cityGroup.rotation.y);
                    camera.position.y = 2 - scrollY * 2;
                    camera.position.z = 8 + scrollY * 5;
                    renderer.render(scene, camera);
                };
                animate();
                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                });
            };
            if (typeof THREE !== 'undefined') { initBackground3D(); }
        });
    </script>
</body>

</html>
