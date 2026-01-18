<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();

// Fetch Engineers
$engineers_query = "SELECT id, name, email, phone, status, specialization, created_at FROM users WHERE role = 'engineer' ORDER BY created_at DESC";
$engineers_result = $conn->query($engineers_query);

// Fetch Homeowners
$homeowners_query = "SELECT id, name, email, phone, status, created_at FROM users WHERE role = 'homeowner' ORDER BY created_at DESC";
$homeowners_result = $conn->query($homeowners_query);

function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }
    return $initials;
}

function getRandomColor($name) {
    $hash = md5($name);
    return '#' . substr($hash, 0, 6);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --bg-color: #f0f2f5;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --primary-blue: #3b82f6;
            --primary-green: #10b981;
            --primary-gold: #f59e0b;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(255, 255, 255, 0.5);
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
            overflow-x: hidden;
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
            pointer-events: none;
        }

        /* Navbar */
        header {
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        .logo i { color: var(--primary-gold); }

        .top-nav-btn {
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 8px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--text-dark);
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .top-nav-btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 2rem;
            width: 100%;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .page-header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 3rem;
        }

        .page-title h1 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-gray);
            font-size: 1rem;
        }

        /* Controls Bar */
        .controls-bar {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            padding: 0.8rem;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.6);
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 2rem;
        }

        .search-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 12px;
            background: rgba(255,255,255,0.8);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
        }

        .search-input:focus {
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            border-color: var(--primary-blue);
        }

        .filter-chips {
            display: flex;
            gap: 0.5rem;
        }

        .filter-chip {
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            background: rgba(255,255,255,0.5);
            color: var(--text-gray);
        }

        .filter-chip.active {
            background: #fff;
            color: var(--text-dark);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: scale(1.05);
        }
        
        .filter-chip[data-filter="engineer"].active { color: var(--primary-blue); border-color: rgba(59, 130, 246, 0.2); }
        .filter-chip[data-filter="homeowner"].active { color: var(--primary-green); border-color: rgba(16, 185, 129, 0.2); }

        /* GRID SYSTEM - "Menu Card" Style */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            perspective: 1000px;
        }

        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            cursor: default;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid var(--card-border);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transform-style: preserve-3d;
            animation: fadeInUp 0.5s ease-out backwards;
            min-height: 280px;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.95);
            border-color: #fff;
            z-index: 10;
        }

        .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
            transform: translateZ(20px);
        }

        /* Role Gradients */
        .grad-engineer { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .grad-homeowner { background: linear-gradient(135deg, #10b981, #059669); }

        .card-content h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.2rem;
            transform: translateZ(15px);
        }

        .card-content p {
            color: var(--text-gray);
            font-size: 0.9rem;
            line-height: 1.4;
            transform: translateZ(10px);
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transform: translateZ(12px);
        }

        .status-badge.approved { background: #ecfdf5; color: #059669; }
        .status-badge.pending { background: #fffbeb; color: #d97706; }
        .status-badge.suspended { background: #fef2f2; color: #dc2626; }

        .card-meta {
            margin-top: auto;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transform: translateZ(25px); /* Pop out more */
        }

        /* 3D Decorative Blob */
        .card-bg-3d {
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            transition: all 0.5s ease;
        }

        .feature-card[data-role="homeowner"] .card-bg-3d {
            background: radial-gradient(circle, rgba(16, 185, 129, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .feature-card:hover .card-bg-3d {
            transform: scale(1.5);
        }

        /* Action Buttons in Card */
        .action-btn-group {
            display: flex;
            gap: 0.5rem;
        }

        .icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-view { background: #f1f5f9; color: var(--text-gray); }
        .btn-view:hover { background: #3b82f6; color: white; box-shadow: 0 4px 6px rgba(59,130,246,0.3); }

        .btn-verify { background: #ecfdf5; color: #059669; }
        .btn-verify:hover { background: #10b981; color: white;box-shadow: 0 4px 6px rgba(16,185,129,0.3); }

        .btn-suspend { background: #fef2f2; color: #dc2626; }
        .btn-suspend:hover { background: #ef4444; color: white; box-shadow: 0 4px 6px rgba(239,68,68,0.3); }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

    </style>
</head>

<body>
    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <header>
        <a href="admin_dashboard.php" class="logo">
            <i class="fas fa-shield-alt"></i> Constructa Admin
        </a>
        <nav>
            <a href="admin_dashboard.php" class="top-nav-btn"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="login.html" class="top-nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="container">
        <div class="page-header-section">
             <div class="page-title">
                <h1>User Management</h1>
                <p class="page-subtitle">Manage platform engineers and homeowners.</p>
            </div>
             <div style="text-align: right;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--text-dark);">
                    <?php echo $engineers_result->num_rows + $homeowners_result->num_rows; ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px;">Total Users</div>
            </div>
        </div>

        <div class="controls-bar">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search by name, email, or role...">
            </div>
            <div class="filter-chips">
                <button class="filter-chip active" data-filter="all">All</button>
                <button class="filter-chip" data-filter="engineer">Engineers</button>
                <button class="filter-chip" data-filter="homeowner">Homeowners</button>
            </div>
        </div>

        <!-- MENU CARDS GRID -->
        <div class="features-grid" id="userGrid">
            
            <!-- Loop Engineers -->
            <?php 
            if ($engineers_result && $engineers_result->num_rows > 0) {
                while ($row = $engineers_result->fetch_assoc()) {
                    $statusClass = strtolower($row['status']);
            ?>
            <div class="feature-card tilt-card" data-role="engineer" data-name="<?php echo strtolower($row['name']); ?>" data-email="<?php echo strtolower($row['email']); ?>">
                <div class="icon-wrapper grad-engineer">
                    <i class="fas fa-drafting-compass"></i>
                </div>
                
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; margin-bottom: 0;">
                        <?php echo htmlspecialchars($row['email']); ?>
                    </p>
                    <p style="margin-top: 0.2rem;"><?php echo htmlspecialchars($row['specialization'] ?? 'General Engineer'); ?></p>
                    
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </div>

                <div class="card-meta">
                    <div style="font-size: 0.8rem;">
                        <i class="far fa-calendar-alt"></i> <?php echo date('M Y', strtotime($row['created_at'])); ?>
                    </div>
                    <div class="action-btn-group">
                        <a href="view_engineer_profile.php?engineer_id=<?php echo $row['id']; ?>" class="icon-btn btn-view" title="View Profile">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <?php if ($row['status'] !== 'approved'): ?>
                            <button class="icon-btn btn-verify" title="Verify User" onclick="updateEngineerStatus(<?php echo $row['id']; ?>, 'verify')">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php else: ?>
                            <button class="icon-btn btn-suspend" title="Suspend User" onclick="updateEngineerStatus(<?php echo $row['id']; ?>, 'suspend')">
                                <i class="fas fa-ban"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-bg-3d"></div>
            </div>
            <?php 
                }
            } 
            ?>

            <!-- Loop Homeowners -->
            <?php 
            if ($homeowners_result && $homeowners_result->num_rows > 0) {
                while ($row = $homeowners_result->fetch_assoc()) {
                    $statusClass = strtolower($row['status']);
            ?>
            <div class="feature-card tilt-card" data-role="homeowner" data-name="<?php echo strtolower($row['name']); ?>" data-email="<?php echo strtolower($row['email']); ?>">
                <div class="icon-wrapper grad-homeowner">
                    <i class="fas fa-home"></i>
                </div>
                
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; margin-bottom: 0;">
                        <?php echo htmlspecialchars($row['email']); ?>
                    </p>
                    <p style="margin-top: 0.2rem;">Homeowner</p>
                    
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </div>

                <div class="card-meta">
                     <div style="font-size: 0.8rem;">
                        <i class="far fa-calendar-alt"></i> <?php echo date('M Y', strtotime($row['created_at'])); ?>
                    </div>
                    <div class="action-btn-group">
                         <!-- Homeowner actions -->
                         <button class="icon-btn btn-view" title="View Details">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </div>
                </div>
                
                <div class="card-bg-3d"></div>
            </div>
            <?php 
                }
            } 
            ?>

        </div>
    </div>

    <script>
        // === 3D TILT EFFECT ===
        const initTilt = () => {
            const cards = document.querySelectorAll('.tilt-card');
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * -10;
                    const rotateY = ((x - centerX) / centerX) * 10;
                    
                    // Apply to card
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
                });
            });
        };
        document.addEventListener('DOMContentLoaded', initTilt);

        // Filters & Search
        const searchInput = document.getElementById('searchInput');
        const filters = document.querySelectorAll('.filter-chip');
        const cards = document.querySelectorAll('.feature-card');

        function filterUsers() {
            const query = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('.filter-chip.active').dataset.filter;

            cards.forEach(card => {
                const name = card.dataset.name;
                const email = card.dataset.email;
                const role = card.dataset.role;
                
                const matchesSearch = name.includes(query) || email.includes(query);
                const matchesRole = activeFilter === 'all' || role === activeFilter;

                if (matchesSearch && matchesRole) {
                    card.style.display = 'flex';
                    card.style.animation = 'fadeInUp 0.5s ease backwards';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterUsers);

        filters.forEach(btn => {
            btn.addEventListener('click', () => {
                filters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterUsers();
            });
        });

        // Engineer Actions
        async function updateEngineerStatus(id, action) {
            if (!confirm(`Are you sure you want to ${action} this engineer?`)) return;

            try {
                const formData = new FormData();
                formData.append('engineer_id', id);
                formData.append('action', action);

                const response = await fetch('backend/admin_engineer_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        }

        // 3D Background - Consistent with Saved Favorites
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f8fafc');

            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);
            const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
            blueLight.position.set(-5, 5, 5);
            scene.add(blueLight);

            // Objects
            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            const buildingGeometry = new THREE.BoxGeometry(1, 1, 1);
            const buildingMaterial = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.1, side: THREE.DoubleSide });
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

            // Hero House (Central Floating Object)
            const houseGroup = new THREE.Group();
            const baseGeo = new THREE.BoxGeometry(2, 2, 2);
            const baseEdges = new THREE.EdgesGeometry(baseGeo);
            const baseLine = new THREE.LineSegments(baseEdges, new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
            houseGroup.add(baseLine);
            const roofGeo = new THREE.ConeGeometry(1.5, 1.2, 4);
            const roofEdges = new THREE.EdgesGeometry(roofGeo);
            const roofLine = new THREE.LineSegments(roofEdges, new THREE.LineBasicMaterial({ color: 0x3d5a49, linewidth: 2 }));
            roofLine.position.y = 1.6;
            roofLine.rotation.y = Math.PI / 4;
            houseGroup.add(roofLine);

            const floatGroup = new THREE.Group();
            floatGroup.add(houseGroup);
            floatGroup.position.set(0, 0, 2);
            scene.add(floatGroup);

            // Animation
            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                floatGroup.rotation.y += 0.005;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
                
                // Interactive tilt
                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);

                renderer.render(scene, camera);
            };
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };
        
        document.addEventListener('DOMContentLoaded', initBackground3D);
    </script>
</body>
</html>
